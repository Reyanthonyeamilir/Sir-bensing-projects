<?php

namespace App\Controller;

use App\Repository\PetproductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MainController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('home.html.twig');
    }

    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('about.html.twig');
    }

    #[Route('/prices', name: 'prices')]
    public function prices(): Response
    {
        return $this->render('prices.html.twig');
    }

    #[Route('/contact', name: 'contact')]
    public function contact(): Response
    {
        return $this->render('contact.html.twig');
    }

    #[Route('/product', name: 'product')]
    public function product(PetproductsRepository $petproductsRepository, Request $request): Response
    {
        $category = $request->query->get('category');
        $subCategory = $request->query->get('subCategory');

        $allProducts = $petproductsRepository->findAll();
        $petproducts = $allProducts;

        if ($category) {
            $criteria = ['category' => $category];
            if ($subCategory) {
                $criteria['sub_category'] = $subCategory; // ✅ matches entity
            }
            $petproducts = $petproductsRepository->findBy($criteria);
        }

        return $this->render('product.html.twig', [
            'petproducts' => $petproducts,
            'allProducts' => $allProducts,
            'currentCategory' => $category,
            'currentSubCategory' => $subCategory,
        ]);
    }

    #[Route('/product/view/{id}', name: 'product_view')]
    public function productView(int $id, PetproductsRepository $petproductsRepository): Response
    {
        $petproduct = $petproductsRepository->find($id);

        if (!$petproduct) {
            throw new NotFoundHttpException('Product not found.');
        }

        return $this->render('product_view.html.twig', [
            'petproduct' => $petproduct,
        ]);
    }

    #[Route('/login', name: 'login')]
    public function login(): Response
    {
        return $this->render('login.html.twig');
    }

    #[Route('/register', name: 'register')]
    public function register(): Response
    {
        return $this->render('register.html.twig');
    }
}
