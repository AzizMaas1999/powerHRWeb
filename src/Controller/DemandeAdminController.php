<?php

namespace App\Controller;

use App\Entity\Demande;
use App\Form\DemandeType;
use App\Repository\DemandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/demandeadmin')]
final class DemandeAdminController extends AbstractController
{
    #[Route(name: 'app_demandeadmin_index', methods: ['GET'])]
    public function indexadmin(DemandeRepository $demandeRepository): Response
    {
        $demandesEnAttente = $demandeRepository->findBy(['status' => 'En Attente']);

        return $this->render('demandeadmin/index.html.twig', [
            'demandes' => $demandesEnAttente,
        ]);
    }

    #[Route('/new', name: 'app_demandeadmin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $demande = new Demande();
        $demande->setDateCreation(new \DateTime());
        $demande->setStatus('En Attente');

        $form = $this->createForm(DemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($demande);
            $entityManager->flush();

            $this->addFlash('success', 'Demande ajoutée avec succès.');

            return $this->redirectToRoute('app_demandeadmin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('demandeadmin/new.html.twig', [
            'demande' => $demande,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_demandeadmin_show', methods: ['GET'])]
    public function show(Demande $demande): Response
    {
        return $this->render('demandeadmin/show.html.twig', [
            'demande' => $demande,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_demandeadmin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('info', 'Demande modifiée avec succès.');

            return $this->redirectToRoute('app_demandeadmin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('demandeadmin/edit.html.twig', [
            'demande' => $demande,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_demandeadmin_delete', methods: ['POST'])]
    public function delete(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $demande->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($demande);
            $entityManager->flush();

            $this->addFlash('danger', 'Demande supprimée avec succès.');
        }

        return $this->redirectToRoute('app_demandeadmin_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/liste/attente', name: 'app_demandeadmin_liste_attente', methods: ['GET'])]
    public function listeAttente(DemandeRepository $demandeRepository): Response
    {
        $demandesEnAttente = $demandeRepository->findBy(['status' => 'En Attente']);

        return $this->render('demandeadmin/listeDR.html.twig', [
            'demandes' => $demandesEnAttente,
        ]);
    }

    #[Route('/{id}/valider', name: 'app_demandeadmin_valider', methods: ['POST'])]
    public function valider(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('Valider' . $demande->getId(), $request->getPayload()->getString('_token'))) {
            $demande->setStatus('Valider');
            $entityManager->flush();

            $this->addFlash('success', 'Demande validée avec succès.');
        }

        return $this->redirectToRoute('app_demandeadmin_liste_attente');
    }

    #[Route('/{id}/refuser', name: 'app_demandeadmin_refuser', methods: ['POST'])]
    public function refuser(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('Refuser' . $demande->getId(), $request->getPayload()->getString('_token'))) {
            $demande->setStatus('Refuser');
            $entityManager->flush();

            $this->addFlash('danger', 'Demande refusée.');
        }

        return $this->redirectToRoute('app_demandeadmin_liste_attente');
    }
}
