<?php

namespace App\Controller;

use App\Entity\PetSale;
use App\Form\PetSaleType;
use App\Repository\PetSaleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pet/sale')]
final class PetSaleController extends AbstractController
{
    #[Route(name: 'app_pet_sale_index', methods: ['GET'])]
    public function index(PetSaleRepository $petSaleRepository): Response
    {
        return $this->render('pet_sale/index.html.twig', [
            'pet_sales' => $petSaleRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_pet_sale_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $petSale = new PetSale();
        $form = $this->createForm(PetSaleType::class, $petSale);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($petSale);
            $entityManager->flush();

            return $this->redirectToRoute('app_pet_sale_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pet_sale/new.html.twig', [
            'pet_sale' => $petSale,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pet_sale_show', methods: ['GET'])]
    public function show(PetSale $petSale): Response
    {
        return $this->render('pet_sale/show.html.twig', [
            'pet_sale' => $petSale,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pet_sale_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PetSale $petSale, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PetSaleType::class, $petSale);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_pet_sale_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pet_sale/edit.html.twig', [
            'pet_sale' => $petSale,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pet_sale_delete', methods: ['POST'])]
    public function delete(Request $request, PetSale $petSale, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$petSale->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($petSale);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_pet_sale_index', [], Response::HTTP_SEE_OTHER);
    }
}
