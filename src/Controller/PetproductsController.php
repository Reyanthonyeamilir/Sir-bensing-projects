<?php

namespace App\Controller;

use App\Entity\Petproducts;
use App\Form\PetproductsType;
use App\Repository\PetproductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

#[Route('/petproducts')]
final class PetproductsController extends AbstractController
{
    #[Route(name: 'app_petproducts_index', methods: ['GET'])]
    public function index(PetproductsRepository $repo): Response
    {
        $products = $repo->findAll();
        return $this->render('petproducts/index.html.twig', [
            'petproducts' => $products,
        ]);
    }

    #[Route('/new', name: 'app_petproducts_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $petproduct = new Petproducts();
        $form = $this->createForm(PetproductsType::class, $petproduct);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ADD THIS LINE: Set the current user as creator
            if ($this->getUser()) {
                $petproduct->setCreatedBy($this->getUser()->getUserIdentifier());
            }

            // optional image upload
            if ($form->has('imageFile')) {
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    $newFilename = uniqid().'.'.$imageFile->guessExtension();
                    try {
                        $imageFile->move(
                            $this->getParameter('uploads_directory'),
                            $newFilename
                        );
                        $petproduct->setImageUrl($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'File upload failed!');
                    }
                }
            }

            // ensure createdAt is set
            if (!$petproduct->getCreatedAt()) {
                $petproduct->setCreatedAt(new \DateTime());
            }

            // Ensure isActive has a default value
            if ($petproduct->isActive() === null) {
                $petproduct->setIsActive(true);
            }

            $em->persist($petproduct);
            $em->flush();

            $this->addFlash('success', 'Product added successfully!');
            return $this->redirectToRoute('app_petproducts_index');
        }

        return $this->render('petproducts/new.html.twig', [
            'form' => $form->createView(),
            'petproduct' => $petproduct,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_petproducts_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Petproducts $petproduct, EntityManagerInterface $em): Response
    {
        // SECURITY: allow admins and staff to edit ANY product (full access for both)
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_staff')) {
            // allowed - both admin and staff can edit ALL products
        } else {
            $this->addFlash('error', 'You do not have permission to edit products.');
            return $this->redirectToRoute('app_petproducts_index');
        }

        $form = $this->createForm(PetproductsType::class, $petproduct);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->has('imageFile')) {
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    $newFilename = uniqid().'.'.$imageFile->guessExtension();
                    try {
                        $imageFile->move(
                            $this->getParameter('uploads_directory'),
                            $newFilename
                        );
                        $petproduct->setImageUrl($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'File upload failed!');
                    }
                }
            }

            $em->flush();
            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('app_petproducts_index');
        }

        return $this->render('petproducts/edit.html.twig', [
            'form' => $form->createView(),
            'petproduct' => $petproduct,
        ]);
    }

    #[Route('/{id}', name: 'app_petproducts_show', methods: ['GET'])]
    public function show(Petproducts $petproduct, EntityManagerInterface $em): Response
    {
        // Check if product has orders before showing the page
        $orderCount = 0;
        try {
            // Check if Order entity exists
            $hasOrders = $em->createQuery('
                SELECT COUNT(o.id) 
                FROM App\Entity\Order o 
                WHERE o.product = :productId
            ')
            ->setParameter('productId', $petproduct->getId())
            ->getSingleScalarResult();
            
            $orderCount = $hasOrders;
        } catch (\Exception $e) {
            // Also check for OrderItem if it exists
            try {
                $hasOrderItems = $em->createQuery('
                    SELECT COUNT(oi.id) 
                    FROM App\Entity\OrderItem oi 
                    WHERE oi.product = :productId
                ')
                ->setParameter('productId', $petproduct->getId())
                ->getSingleScalarResult();
                
                $orderCount = $hasOrderItems;
            } catch (\Exception $e2) {
                // If neither entity exists, order count remains 0
            }
        }
        
        return $this->render('petproducts/show.html.twig', [
            'petproduct' => $petproduct,
            'orderCount' => $orderCount,
        ]);
    }

    #[Route('/{id}', name: 'app_petproducts_delete', methods: ['POST'])]
    public function delete(Request $request, Petproducts $petproduct, EntityManagerInterface $em): Response
    {
        // SECURITY: allow admins and staff to delete ANY product (full access for both)
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_staff')) {
            // allowed - both admin and staff can delete ALL products
        } else {
            $this->addFlash('error', 'You do not have permission to delete products.');
            return $this->redirectToRoute('app_petproducts_index');
        }

        if ($this->isCsrfTokenValid('delete'.$petproduct->getId(), $request->getPayload()->getString('_token'))) {
            // First, proactively check if product has any orders
            $hasOrders = false;
            $orderCount = 0;
            
            try {
                // Check Orders entity - based on your error, this is where the foreign key is
                $orderCount = $em->createQuery('
                    SELECT COUNT(o.id) 
                    FROM App\Entity\Order o 
                    WHERE o.product = :productId
                ')
                ->setParameter('productId', $petproduct->getId())
                ->getSingleScalarResult();
                
                if ($orderCount > 0) {
                    $hasOrders = true;
                }
            } catch (\Exception $e) {
                // If Order entity doesn't exist, try OrderItem
                try {
                    $orderCount = $em->createQuery('
                        SELECT COUNT(oi.id) 
                        FROM App\Entity\OrderItem oi 
                        WHERE oi.product = :productId
                    ')
                    ->setParameter('productId', $petproduct->getId())
                    ->getSingleScalarResult();
                    
                    if ($orderCount > 0) {
                        $hasOrders = true;
                    }
                } catch (\Exception $e2) {
                    // Neither entity exists, continue
                }
            }
            
            if ($hasOrders) {
                $this->addFlash('error', sprintf(
                    'Cannot delete product. This product has %d order(s) associated with it. ' .
                    'You can deactivate it instead by editing the product and setting status to "Inactive".',
                    $orderCount
                ));
                return $this->redirectToRoute('app_petproducts_show', ['id' => $petproduct->getId()]);
            }
            
            // Try to delete with proper exception handling
            try {
                $em->remove($petproduct);
                $em->flush();
                
                $this->addFlash('success', 'Product deleted successfully!');
                
            } catch (ForeignKeyConstraintViolationException $e) {
                // Handle foreign key constraint violation gracefully
                $this->addFlash('error', 
                    'Cannot delete product because it is being used in existing orders. ' .
                    'Please deactivate the product instead by editing it and setting status to "Inactive". ' .
                    'Deactivating will keep the product in the system for historical records but hide it from new orders.'
                );
                
                return $this->redirectToRoute('app_petproducts_show', ['id' => $petproduct->getId()]);
                
            } catch (\Exception $e) {
                // Check if it's a general database constraint violation
                if (strpos($e->getMessage(), 'foreign key constraint') !== false || 
                    strpos($e->getMessage(), '1451') !== false ||
                    strpos($e->getMessage(), '23000') !== false) {
                    
                    $this->addFlash('error', 
                        'Cannot delete product. It is referenced in existing orders or transactions. ' .
                        'Please deactivate the product instead.'
                    );
                } else {
                    $this->addFlash('error', 
                        'An unexpected error occurred while deleting the product: ' . 
                        $e->getMessage()
                    );
                }
                
                return $this->redirectToRoute('app_petproducts_show', ['id' => $petproduct->getId()]);
            }
            
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_petproducts_index');
    }

    #[Route('/{id}/toggle-status', name: 'app_petproducts_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, Petproducts $petproduct, EntityManagerInterface $em): Response
    {
        // Security check - BOTH Admin and Staff can toggle status of ANY product
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_staff')) {
            // allowed - both can toggle any product
        } else {
            $this->addFlash('error', 'You do not have permission to modify products.');
            return $this->redirectToRoute('app_petproducts_show', ['id' => $petproduct->getId()]);
        }
        
        if ($this->isCsrfTokenValid('toggle-status'.$petproduct->getId(), $request->getPayload()->getString('_token'))) {
            $newStatus = !$petproduct->isActive();
            $petproduct->setIsActive($newStatus);
            
            $em->flush();
            
            $statusText = $newStatus ? 'activated' : 'deactivated';
            $this->addFlash('success', "Product has been {$statusText} successfully!");
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }
        
        return $this->redirectToRoute('app_petproducts_show', ['id' => $petproduct->getId()]);
    }
}