<?php

namespace App\Controller;

use App\Repository\PetproductsRepository;
use App\Repository\InventoryRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        PetproductsRepository $petproductsRepository,
        InventoryRepository $inventoryRepository,
        UserRepository $userRepository
    ): Response {
        // Check if user is authenticated
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // Get current user and roles
        $user = $this->getUser();
        $userRoles = $user->getRoles();

        // Initialize data array
        $data = [];

        // ADMIN DASHBOARD DATA
        if (in_array('ROLE_ADMIN', $userRoles)) {
            $products = $petproductsRepository->findAll();
            $totalProducts = count($products);
            $totalPrice = array_sum(array_map(fn($p) => $p->getPrice(), $products));
            $priceGrowth = 8.5; // Example - you might calculate this dynamically
            
            $data = [
                'totalProducts'   => $totalProducts,
                'totalPrice'      => $totalPrice,
                'priceGrowth'     => $priceGrowth,
                'totalUsers'      => $userRepository->count([]),
                'bookingsCount'   => 5, // Replace with actual bookings count
                'totalInventory'  => $inventoryRepository->createQueryBuilder('i')
                    ->select('SUM(i.quantity)')
                    ->getQuery()
                    ->getSingleScalarResult() ?? 0,
            ];
        }
        // STAFF DASHBOARD DATA - FIXED: Check both uppercase and lowercase
        elseif (in_array('ROLE_STAFF', $userRoles) || in_array('ROLE_staff', $userRoles)) {
            $products = $petproductsRepository->findAll();
            $totalProducts = count($products);
            
            $data = [
                'totalProducts'   => $totalProducts,
                'totalInventory'  => $inventoryRepository->createQueryBuilder('i')
                    ->select('SUM(i.quantity)')
                    ->getQuery()
                    ->getSingleScalarResult() ?? 0,
                'bookingsCount'   => 5, // Replace with actual bookings count
            ];
        }
        // USER DASHBOARD DATA
        elseif (in_array('ROLE_USER', $userRoles)) {
            // Get user-specific data
            // You'll need to implement these methods based on your entities
            
            // Example: Get user's orders count
            // $userOrders = count($user->getOrders()); // Assuming User entity has getOrders()
            
            // Example: Get user's bookings count
            // $userBookings = count($user->getBookings()); // Assuming User entity has getBookings()
            
            $data = [
                'userOrders'    => 0, // Replace with actual user orders count
                'userBookings'  => 0, // Replace with actual user bookings count
            ];
        }
        // NO VALID ROLE - Redirect to login
        else {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('dashboard/index.html.twig', $data);
    }
    
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function adminDashboard(
        PetproductsRepository $petproductsRepository,
        InventoryRepository $inventoryRepository,
        UserRepository $userRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Admin-only data
        $products = $petproductsRepository->findAll();
        $totalProducts = count($products);
        $totalPrice = array_sum(array_map(fn($p) => $p->getPrice(), $products));
        
        return $this->render('dashboard/index.html.twig', [
            'totalProducts'   => $totalProducts,
            'totalPrice'      => $totalPrice,
            'priceGrowth'     => 8.5,
            'totalUsers'      => $userRepository->count([]),
            'bookingsCount'   => 5,
            'totalInventory'  => $inventoryRepository->createQueryBuilder('i')
                ->select('SUM(i.quantity)')
                ->getQuery()
                ->getSingleScalarResult() ?? 0,
        ]);
    }
    
    #[Route('/staff/dashboard', name: 'app_staff_dashboard')]
    public function staffDashboard(
        PetproductsRepository $petproductsRepository,
        InventoryRepository $inventoryRepository
    ): Response {
        // FIXED: Check both uppercase and lowercase for staff role
        if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_staff')) {
            return $this->redirectToRoute('app_login');
        }
        
        $products = $petproductsRepository->findAll();
        $totalProducts = count($products);
        
        return $this->render('dashboard/index.html.twig', [
            'totalProducts'   => $totalProducts,
            'totalInventory'  => $inventoryRepository->createQueryBuilder('i')
                ->select('SUM(i.quantity)')
                ->getQuery()
                ->getSingleScalarResult() ?? 0,
            'bookingsCount'   => 5,
        ]);
    }
    
    #[Route('/user/dashboard', name: 'app_user_dashboard')]
    public function userDashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Get current user
        $user = $this->getUser();
        
        return $this->render('dashboard/index.html.twig', [
            'userOrders'    => 0, // Replace with: count($user->getOrders())
            'userBookings'  => 0, // Replace with: count($user->getBookings())
        ]);
    }
}