<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Form\PaiementType;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use \Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Repository\FactureRepository;


#[Route('/paiement')]
final class PaiementController extends AbstractController
{
    #[Route(name: 'app_paiement_index', methods: ['GET'])]
    public function index(PaiementRepository $paiementRepository): Response
    {
        return $this->render('paiement/index.html.twig', [
            'paiements' => $paiementRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_paiement_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, FactureRepository $factureRepository): Response
{
    $paiement = new Paiement();

    $facturesSansPaiement = $factureRepository->findBy(['paiement_id' => null]);

    $form = $this->createForm(PaiementType::class, $paiement, [
        'factures' => $facturesSansPaiement,
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // ✅ Récupérer les factures sélectionnées
        $selectedFactures = $form->get('factures')->getData();

        // ✅ Calculer le total des factures sélectionnées
        $totalFactures = 0;
        foreach ($selectedFactures as $facture) {
            $totalFactures += $facture->getTotal();
        }

        // ✅ Vérifier que le montant du paiement correspond
        if ($paiement->getMontant() != $totalFactures) {
            $this->addFlash('error', 'Le montant du paiement doit correspondre au total des factures sélectionnées.');

            return $this->render('paiement/new.html.twig', [
                'paiement' => $paiement,
                'form' => $form,
            ]);
        }

        // ✅ Enregistrer le paiement
        $entityManager->persist($paiement);
        $entityManager->flush(); // pour avoir l'ID

        // ✅ Lier les factures au paiement
        foreach ($selectedFactures as $facture) {
            $facture->setPaiement_id($paiement->getId());
            $entityManager->persist($facture);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_paiement_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('paiement/new.html.twig', [
        'paiement' => $paiement,
        'form' => $form,
    ]);
}


    #[Route('/{id}', name: 'app_paiement_show', methods: ['GET'])]
    public function show(Paiement $paiement): Response
    {
        return $this->render('paiement/show.html.twig', [
            'paiement' => $paiement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_paiement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Paiement $paiement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PaiementType::class, $paiement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_paiement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('paiement/edit.html.twig', [
            'paiement' => $paiement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_paiement_delete', methods: ['POST'])]
    public function delete(Request $request, Paiement $paiement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$paiement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($paiement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_paiement_index', [], Response::HTTP_SEE_OTHER);
    }
} 