<?php

namespace App\Controller;

use App\Entity\Repquestionnaire;
use App\Form\RepquestionnaireType;
use App\Repository\RepquestionnaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/repquestionnaire')]
final class RepquestionnaireController extends AbstractController
{
    #[Route(name: 'app_repquestionnaire_index', methods: ['GET'])]
    public function index(RepquestionnaireRepository $repquestionnaireRepository): Response
    {
        return $this->render('repquestionnaire/index.html.twig', [
            'repquestionnaires' => $repquestionnaireRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_repquestionnaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $repquestionnaire = new Repquestionnaire();
        $repquestionnaire->setDateCreation(new \DateTime());
        $form = $this->createForm(RepquestionnaireType::class, $repquestionnaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($repquestionnaire);
            $entityManager->flush();

            $this->addFlash('success', 'Réponse ajoutée avec succès.');

            return $this->redirectToRoute('app_repquestionnaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('repquestionnaire/new.html.twig', [
            'repquestionnaire' => $repquestionnaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_repquestionnaire_show', methods: ['GET'])]
    public function show(Repquestionnaire $repquestionnaire): Response
    {
        return $this->render('repquestionnaire/show.html.twig', [
            'repquestionnaire' => $repquestionnaire,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_repquestionnaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Repquestionnaire $repquestionnaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RepquestionnaireType::class, $repquestionnaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('info', 'Réponse modifiée avec succès.');

            return $this->redirectToRoute('app_repquestionnaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('repquestionnaire/edit.html.twig', [
            'repquestionnaire' => $repquestionnaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_repquestionnaire_delete', methods: ['POST'])]
    public function delete(Request $request, Repquestionnaire $repquestionnaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$repquestionnaire->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($repquestionnaire);
            $entityManager->flush();

            $this->addFlash('danger', 'Réponse supprimée avec succès.');
        }

        return $this->redirectToRoute('app_repquestionnaire_index', [], Response::HTTP_SEE_OTHER);
    }
}
