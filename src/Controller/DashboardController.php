<?php

namespace App\Controller;

use App\Repository\PetproductsRepository;
use App\Repository\UserRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        PetproductsRepository $petproductsRepository,
        UserRepository $userRepository,
        OrderRepository $orderRepository
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

        // 1. GET ACTUAL CURRENT INVENTORY VALUE FROM DATABASE
        $totalProducts = $petproductsRepository->count(['isActive' => true]);
        
        $totalStock = $petproductsRepository->createQueryBuilder('p')
            ->select('SUM(p.stock)')
            ->where('p.isActive = true')
            ->getQuery()
            ->getSingleScalarResult();
        $totalStock = $totalStock ?? 0;
        
        $totalCategories = $petproductsRepository->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.category)')
            ->where('p.isActive = true')
            ->getQuery()
            ->getSingleScalarResult();
        $totalCategories = (int)$totalCategories;
        
        // CURRENT inventory value from database (actual stock * price)
        $currentInventoryValue = $petproductsRepository->createQueryBuilder('p')
            ->select('SUM(p.price * p.stock)')
            ->where('p.isActive = true')
            ->getQuery()
            ->getSingleScalarResult();
        $currentInventoryValue = $currentInventoryValue ?? 0;
        
        // 2. GET COMPLETED ORDERS VALUE (money earned from sales)
        $completedOrdersRevenue = $orderRepository->createQueryBuilder('o')
            ->select('SUM(o.amount)')
            ->where('o.status = :status')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();
        $completedOrdersRevenue = $completedOrdersRevenue ?? 0;
        
        // 3. GET PENDING ORDERS VALUE (potential sales)
        $pendingOrdersValue = $orderRepository->createQueryBuilder('o')
            ->select('SUM(o.amount)')
            ->where('o.status = :status')
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();
        $pendingOrdersValue = $pendingOrdersValue ?? 0;
        
        // 4. GET LOW STOCK PRODUCTS
        $lowStockProducts = $petproductsRepository->createQueryBuilder('p')
            ->where('p.stock < 10')
            ->andWhere('p.isActive = true')
            ->orderBy('p.stock', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // BASE DATA FOR ALL USERS
        $data = [
            'totalProducts' => $totalProducts,
            'totalStock' => $totalStock,
            'totalCategories' => $totalCategories,
            'totalValue' => $currentInventoryValue, // Current actual inventory value
            'completedRevenue' => $completedOrdersRevenue, // Money earned
            'pendingValue' => $pendingOrdersValue, // Potential sales
            'lowStockProducts' => $lowStockProducts,
        ];

        // ADMIN DASHBOARD DATA
        if (in_array('ROLE_ADMIN', $userRoles)) {
            $totalUsers = $userRepository->count([]);
            $totalOrders = $orderRepository->count([]);
            $completedOrders = $orderRepository->count(['status' => 'completed']);
            $pendingOrders = $orderRepository->count(['status' => 'pending']);
            
            $recentOrders = $orderRepository->findBy([], ['createdAt' => 'DESC'], 10);

            $data = array_merge($data, [
                'totalUsers' => $totalUsers,
                'totalOrders' => $totalOrders,
                'completedOrders' => $completedOrders,
                'pendingOrders' => $pendingOrders,
                'recentOrders' => $recentOrders,
                'isAdmin' => true,
            ]);
        }
        // STAFF DASHBOARD DATA
        elseif (in_array('ROLE_STAFF', $userRoles) || in_array('ROLE_staff', $userRoles)) {
            $totalOrders = $orderRepository->count([]);
            $completedOrders = $orderRepository->count(['status' => 'completed']);
            $pendingOrders = $orderRepository->count(['status' => 'pending']);
            
            $recentOrders = $orderRepository->findBy([], ['createdAt' => 'DESC'], 10);

            $data = array_merge($data, [
                'totalOrders' => $totalOrders,
                'completedOrders' => $completedOrders,
                'pendingOrders' => $pendingOrders,
                'recentOrders' => $recentOrders,
                'isStaff' => true,
            ]);
        }
        // USER DASHBOARD DATA
        elseif (in_array('ROLE_USER', $userRoles)) {
            $userOrders = $orderRepository->findBy(
                ['customer' => $user],
                ['createdAt' => 'DESC'],
                5
            );
            
            $totalUserOrders = $orderRepository->count(['customer' => $user]);
            $pendingUserOrders = $orderRepository->count([
                'customer' => $user,
                'status' => 'pending'
            ]);
            $completedUserOrders = $orderRepository->count([
                'customer' => $user,
                'status' => 'completed'
            ]);
            
            $userTotalSpent = $orderRepository->createQueryBuilder('o')
                ->select('SUM(o.amount)')
                ->where('o.customer = :user')
                ->andWhere('o.status = :status')
                ->setParameter('user', $user)
                ->setParameter('status', 'completed')
                ->getQuery()
                ->getSingleScalarResult();
            $userTotalSpent = $userTotalSpent ?? 0;

            $data = array_merge($data, [
                'userOrders' => $userOrders,
                'totalUserOrders' => $totalUserOrders,
                'pendingUserOrders' => $pendingUserOrders,
                'completedUserOrders' => $completedUserOrders,
                'userTotalSpent' => $userTotalSpent,
                'isUser' => true,
            ]);
        }
        // NO VALID ROLE - Redirect to login
        else {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('Dashboard/index.html.twig', $data);
    }
    
    /**
     * Mark order as completed (NO DATABASE STOCK DEDUCTION)
     */
    #[Route('/order/{id}/complete', name: 'order_complete', methods: ['POST'])]
    #[IsGranted('ROLE_staff')]
    public function completeOrder(
        int $id, 
        OrderRepository $orderRepository, 
        EntityManagerInterface $entityManager
    ): Response {
        $order = $orderRepository->find($id);
        
        if (!$order) {
            $this->addFlash('error', 'Order not found.');
            return $this->redirectToRoute('app_dashboard');
        }
        
        if ($order->getStatus() === 'completed') {
            $this->addFlash('warning', 'Order is already completed.');
            return $this->redirectToRoute('app_dashboard');
        }
        
        // SIMPLY MARK AS COMPLETED - NO STOCK DEDUCTION
        $order->setStatus('completed');
        $order->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Asia/Manila')));
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Order marked as completed successfully.');
        
        return $this->redirectToRoute('app_dashboard');
    }
    
    /**
     * Mark order as cancelled
     */
    #[Route('/order/{id}/cancel', name: 'order_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_staff')]
    public function cancelOrder(
        int $id, 
        OrderRepository $orderRepository, 
        EntityManagerInterface $entityManager
    ): Response {
        $order = $orderRepository->find($id);
        
        if (!$order) {
            $this->addFlash('error', 'Order not found.');
            return $this->redirectToRoute('app_dashboard');
        }
        
        if ($order->getStatus() === 'cancelled') {
            $this->addFlash('warning', 'Order is already cancelled.');
            return $this->redirectToRoute('app_dashboard');
        }
        
        // SIMPLY MARK AS CANCELLED - NO STOCK RESTORATION
        $order->setStatus('cancelled');
        $order->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Asia/Manila')));
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Order cancelled successfully.');
        
        return $this->redirectToRoute('app_dashboard');
    }
}