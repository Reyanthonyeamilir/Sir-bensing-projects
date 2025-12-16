<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Form\OrderType;
use App\Form\EditOrderType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order')]
final class OrderController extends AbstractController
{
    #[Route(name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        // Admin and Staff see ALL orders
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_staff')) {
            $orders = $orderRepository->findAllSorted();
        } else {
            // Regular users see only their own orders
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new \Exception('User not found.');
            }
            $orders = $orderRepository->findBy(['customer' => $user], ['createdAt' => 'DESC']);
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
        
        // Auto-set the current user as customer
        $user = $this->getUser();
        if ($user instanceof User) {
            $order->setCustomer($user);
        }
        
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check stock availability
            $product = $order->getProduct();
            $quantity = $order->getQuantity();
            
            if ($product->getStock() < $quantity) {
                $this->addFlash('error', 'Insufficient stock. Only ' . $product->getStock() . ' items available.');
                return $this->render('order/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }
            
            // Calculate amount
            $amount = $product->getPrice() * $quantity;
            $order->setAmount((string) $amount);

            // Update product stock
            $product->setStock($product->getStock() - $quantity);
            
            // Set status to 'pending' if not set
            if (!$order->getStatus()) {
                $order->setStatus('pending');
            }

            $entityManager->persist($order);
            $entityManager->flush();

            $this->addFlash('success', 'Order created successfully!');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        // Admin and Staff can view ALL orders
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_staff')) {
            return $this->render('order/show.html.twig', [
                'order' => $order,
            ]);
        }
        
        // Regular users can only view their own orders
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new \Exception('You must be logged in.');
        }
        
        $customer = $order->getCustomer();
        if (!$customer instanceof User) {
            throw new \Exception('Order customer not found.');
        }
        
        if ($customer->getId() !== $user->getId()) {
            $this->addFlash('error', 'You can only view orders that you created.');
            return $this->redirectToRoute('app_order_index');
        }
        
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        // IMPORTANT: Admin AND Staff can edit ALL orders - NO RESTRICTIONS!
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_staff')) {
            $this->addFlash('error', 'You do not have permission to edit orders.');
            return $this->redirectToRoute('app_order_index');
        }

        // Store original quantity for stock adjustment
        $originalQuantity = $order->getQuantity();
        $originalProduct = $order->getProduct();
        
        $form = $this->createForm(EditOrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle stock adjustments
            $newProduct = $order->getProduct();
            $newQuantity = $order->getQuantity();
            
            // If product changed, restore stock from old product and deduct from new product
            if ($originalProduct && $originalProduct->getId() !== $newProduct->getId()) {
                // Restore stock to original product
                $originalProduct->setStock($originalProduct->getStock() + $originalQuantity);
                
                // Deduct stock from new product
                if ($newProduct->getStock() < $newQuantity) {
                    $this->addFlash('error', 'Insufficient stock for the selected product.');
                    return $this->redirectToRoute('app_order_edit', ['id' => $order->getId()]);
                }
                $newProduct->setStock($newProduct->getStock() - $newQuantity);
            } else {
                // Same product, adjust stock based on quantity change
                $quantityDifference = $newQuantity - $originalQuantity;
                if ($quantityDifference > 0) {
                    // Increasing quantity, check stock
                    if ($newProduct->getStock() < $quantityDifference) {
                        $this->addFlash('error', 'Insufficient stock. Only ' . $newProduct->getStock() . ' items available.');
                        return $this->redirectToRoute('app_order_edit', ['id' => $order->getId()]);
                    }
                    $newProduct->setStock($newProduct->getStock() - $quantityDifference);
                } else {
                    // Decreasing quantity, restore stock
                    $newProduct->setStock($newProduct->getStock() + abs($quantityDifference));
                }
            }
            
            // Update amount
            $amount = $newProduct->getPrice() * $newQuantity;
            $order->setAmount((string) $amount);
            
            // Update updatedAt
            $order->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Asia/Manila')));
            
            $entityManager->flush();

            $this->addFlash('success', 'Order updated successfully!');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        // IMPORTANT: Admin AND Staff can delete ALL orders - NO RESTRICTIONS!
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_staff')) {
            $this->addFlash('error', 'You do not have permission to delete orders.');
            return $this->redirectToRoute('app_order_index');
        }

        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            // Restore product stock before deleting order
            $product = $order->getProduct();
            if ($product) {
                $product->setStock($product->getStock() + $order->getQuantity());
            }
            
            $entityManager->remove($order);
            $entityManager->flush();
            
            $this->addFlash('success', 'Order deleted successfully!');
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }
}