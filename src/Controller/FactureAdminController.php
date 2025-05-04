<?php

namespace App\Controller;

use App\Entity\Facture;
use App\Form\FactureType;
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/factureadmin')]
final class FactureAdminController extends AbstractController
{
    #[Route('/', name: 'app_factureadmin_index', methods: ['GET'])]
    public function index(FactureRepository $factureRepository): Response
    {
        return $this->render('factureadmin/index.html.twig', [
            'factures' => $factureRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_factureadmin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $facture = new Facture();
        $facture->setDateCreation(new \DateTime());
        $facture->setStatus('Non payée');
        
        $form = $this->createForm(FactureType::class, $facture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($facture);
            $entityManager->flush();

            $this->addFlash('success', 'Facture créée avec succès.');
            return $this->redirectToRoute('app_factureadmin_index');
        }

        return $this->render('factureadmin/new.html.twig', [
            'facture' => $facture,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_factureadmin_show', methods: ['GET'])]
    public function show(Facture $facture): Response
    {
        return $this->render('factureadmin/show.html.twig', [
            'facture' => $facture,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_factureadmin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Facture $facture, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FactureType::class, $facture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Facture mise à jour avec succès.');
            return $this->redirectToRoute('app_factureadmin_index');
        }

        return $this->render('factureadmin/edit.html.twig', [
            'facture' => $facture,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_factureadmin_delete', methods: ['POST'])]
    public function delete(Request $request, Facture $facture, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$facture->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($facture);
            $entityManager->flush();
            
            $this->addFlash('success', 'Facture supprimée avec succès.');
        }

        return $this->redirectToRoute('app_factureadmin_index');
    }
}