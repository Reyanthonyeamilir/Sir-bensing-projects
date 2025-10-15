<?php

namespace App\Controller;

use App\Repository\PetproductsRepository;
use App\Repository\InventoryRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        PetproductsRepository $petproductsRepository,
        InventoryRepository $inventoryRepository,
        UserRepository $userRepository
    ): Response {
        // ✅ Get all products
        $products = $petproductsRepository->findAll();

        // ✅ Total products count
        $totalProducts = count($products);

        // ✅ Total price of all products
        $totalPrice = array_sum(array_map(fn($p) => $p->getPrice(), $products));

        // ✅ Total inventory quantity (sum of all quantities)
        $totalInventory = $inventoryRepository->createQueryBuilder('i')
            ->select('SUM(i.quantity)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // ✅ Total users from UserRepository
        $totalUsers = $userRepository->count([]);

        // Example placeholders (until bookings are added)
        $bookingsCount = 5;
        $priceGrowth = 8.5; // example growth %

        return $this->render('dashboard/index.html.twig', [
            'totalProducts'   => $totalProducts,
            'totalPrice'      => $totalPrice,
            'priceGrowth'     => $priceGrowth,
            'totalUsers'      => $totalUsers,
            'bookingsCount'   => $bookingsCount,
            'totalInventory'  => $totalInventory,
        ]);
    }
}
