<?php

namespace App\Controller;

use App\Repository\PetproductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(PetproductsRepository $petproductsRepository): Response
    {
        // Get all products
        $products = $petproductsRepository->findAll();

        // Calculate total products
        $totalProducts = count($products);

        // Calculate total price of all products
        $totalPrice = array_sum(array_map(fn($p) => $p->getPrice(), $products));

        // Assume placeholder values for other stats (until bookings/users exist)
        $totalUsers = 12;
        $bookingsCount = 5;
        $priceGrowth = 8.5; // example growth %

        return $this->render('dashboard/index.html.twig', [
            'totalProducts' => $totalProducts,
            'totalPrice' => $totalPrice,
            'priceGrowth' => $priceGrowth,
            'totalUsers' => $totalUsers,
            'bookingsCount' => $bookingsCount,
        ]);
    }
}
