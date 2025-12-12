<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/order')]
final class OrderController extends AbstractController
{
    #[Route(name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        // For staff users, show only orders where they are the customer
        if ($this->isGranted('ROLE_staff') && !$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedException('User not found.');
            }
            $orders = $orderRepository->findBy(['customer' => $user]);
        } else {
            // Admin sees all orders, users see all orders (but can only view)
            $orders = $orderRepository->findAll();
        }

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Only ADMIN and STAFF can create orders
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_staff')) {
            $this->addFlash('error', 'You do not have permission to create orders.');
            return $this->redirectToRoute('app_order_index');
        }

        $order = new Order();
        
        // Auto-set the current user as customer for staff
        if ($this->isGranted('ROLE_staff') && !$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if ($user instanceof User) {
                $order->setCustomer($user);
            }
        }
        
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // For staff users, ensure they can only create orders for themselves
            if ($this->isGranted('ROLE_staff') && !$this->isGranted('ROLE_ADMIN')) {
                $user = $this->getUser();
                if ($user instanceof User) {
                    $order->setCustomer($user);
                }
            }
            
            // Calculate amount
            $amount = $order->getProduct()->getPrice() * $order->getQuantity();
            $order->setAmount($amount);

            // Update product stock
            $product = $order->getProduct();
            $product->setStock($product->getStock() - $order->getQuantity());

            $entityManager->persist($order);
            $entityManager->flush();

            $this->addFlash('success', 'Order created successfully!');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        // Check permissions for viewing
        $user = $this->getUser();
        
        // Staff can only view orders where they are the customer
        if ($this->isGranted('ROLE_staff') && !$this->isGranted('ROLE_ADMIN')) {
            if (!$user instanceof User) {
                throw new AccessDeniedException('You must be logged in.');
            }
            
            $customer = $order->getCustomer();
            if (!$customer instanceof User) {
                throw new AccessDeniedException('Order customer not found.');
            }
            
            if ($customer->getId() !== $user->getId()) {
                $this->addFlash('error', 'You can only view orders that you created.');
                return $this->redirectToRoute('app_order_index');
            }
        }
        
        // Regular users can view all orders (no restriction)
        
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        // Check permissions
        $this->checkOrderPermissions($order, 'edit');
        
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // For staff users, ensure they can't change the customer
            if ($this->isGranted('ROLE_staff') && !$this->isGranted('ROLE_ADMIN')) {
                $user = $this->getUser();
                if ($user instanceof User) {
                    // Prevent staff from changing customer
                    $formCustomer = $form->get('customer')->getData();
                    if ($formCustomer && $formCustomer->getId() !== $user->getId()) {
                        $this->addFlash('error', 'You cannot change the customer of this order.');
                        return $this->redirectToRoute('app_order_edit', ['id' => $order->getId()]);
                    }
                    $order->setCustomer($user);
                }
            }
            
            // Update amount if quantity or product changed
            $amount = $order->getProduct()->getPrice() * $order->getQuantity();
            $order->setAmount($amount);

            // Update updatedAt
            $order->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Asia/Manila')));

            $entityManager->flush();

            $this->addFlash('success', 'Order updated successfully!');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        // Check permissions
        $this->checkOrderPermissions($order, 'delete');
        
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            // For staff users, additional validation
            if ($this->isGranted('ROLE_staff') && !$this->isGranted('ROLE_ADMIN')) {
                $user = $this->getUser();
                $customer = $order->getCustomer();
                
                if ($user instanceof User && $customer instanceof User) {
                    if ($customer->getId() !== $user->getId()) {
                        $this->addFlash('error', 'You can only delete orders that you created.');
                        throw new AccessDeniedException('You can only delete orders that you created.');
                    }
                }
            }
            
            $entityManager->remove($order);
            $entityManager->flush();
            
            $this->addFlash('success', 'Order deleted successfully!');
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }
    
    /**
     * Check if the current user has permission for the action on this order
     * STAFF CANNOT EDIT/DELETE ADMIN RECORDS
     * ADMIN HAS FULL ACCESS
     */
    private function checkOrderPermissions(Order $order, string $action): void
    {
        $user = $this->getUser();
        
        // Admin has full access to everything
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }
        
        // Staff can only CRUD orders where they are the customer
        if ($this->isGranted('ROLE_staff')) {
            if (!$user instanceof User) {
                $this->addFlash('error', 'You must be logged in.');
                throw new AccessDeniedException('You must be logged in.');
            }
            
            $customer = $order->getCustomer();
            if (!$customer instanceof User) {
                $this->addFlash('error', 'Order customer not found.');
                throw new AccessDeniedException('Order customer not found.');
            }
            
            // IMPORTANT: Check if the customer (order creator) has ADMIN role
            // Staff cannot edit/delete orders created by admin users
            if (in_array('ROLE_ADMIN', $customer->getRoles())) {
                $this->addFlash('error', 'You cannot ' . $action . ' orders created by administrators.');
                throw new AccessDeniedException('You cannot ' . $action . ' orders created by administrators.');
            }
            
            // Check if staff owns this order
            if ($customer->getId() === $user->getId()) {
                return; // Staff owns this order
            } else {
                $this->addFlash('error', 'You can only ' . $action . ' orders that you created.');
                throw new AccessDeniedException('You can only ' . $action . ' orders that you created.');
            }
        }
        
        // Regular users cannot edit or delete (only view)
        if ($this->isGranted('ROLE_USER')) {
            $this->addFlash('error', 'You do not have permission to ' . $action . ' orders.');
            throw new AccessDeniedException('You do not have permission to ' . $action . ' orders.');
        }
        
        // No role matched
        $this->addFlash('error', 'Access denied.');
        throw new AccessDeniedException('Access denied.');
    }
}