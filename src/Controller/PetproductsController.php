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
    public function show(Petproducts $petproduct): Response
    {
        return $this->render('petproducts/show.html.twig', [
            'petproduct' => $petproduct,
        ]);
    }

    #[Route('/{id}', name: 'app_petproducts_delete', methods: ['POST'])]
    public function delete(Request $request, Petproducts $petproduct, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$petproduct->getId(), $request->getPayload()->getString('_token'))) {
            // Just remove the product - no inventory records to worry about
            $em->remove($petproduct);
            $em->flush();
            
            $this->addFlash('success', 'Product deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_petproducts_index');
    }
}