<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Form\FeedbackType;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/feedbackadmin')]
final class FeedbackAdminController extends AbstractController
{
    #[Route('/', name: 'app_feedbackadmin_index', methods: ['GET'])]
    public function index(FeedbackRepository $feedbackRepository): Response
    {
        return $this->render('feedbackadmin/index.html.twig', [
            'feedbacks' => $feedbackRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_feedbackadmin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $feedback = new Feedback();
        $feedback->setDateCreation(new \DateTime());
        
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($feedback);
            $entityManager->flush();

            $this->addFlash('success', 'Feedback ajouté avec succès.');
            return $this->redirectToRoute('app_feedbackadmin_index');
        }

        return $this->render('feedbackadmin/new.html.twig', [
            'feedback' => $feedback,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_feedbackadmin_show', methods: ['GET'])]
    public function show(Feedback $feedback): Response
    {
        return $this->render('feedbackadmin/show.html.twig', [
            'feedback' => $feedback,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_feedbackadmin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Feedback mis à jour avec succès.');
            return $this->redirectToRoute('app_feedbackadmin_index');
        }

        return $this->render('feedbackadmin/edit.html.twig', [
            'feedback' => $feedback,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_feedbackadmin_delete', methods: ['POST'])]
    public function delete(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$feedback->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($feedback);
            $entityManager->flush();
            
            $this->addFlash('success', 'Feedback supprimé avec succès.');
        }

        return $this->redirectToRoute('app_feedbackadmin_index');
    }
    
    #[Route('/stats', name: 'app_feedbackadmin_stats', methods: ['GET'])]
    public function stats(FeedbackRepository $feedbackRepository): Response
    {
        $allFeedbacks = $feedbackRepository->findAll();
        $totalCount = count($allFeedbacks);
        
        $positiveCount = count($feedbackRepository->findBy(['type' => 'positif']));
        $negativeCount = count($feedbackRepository->findBy(['type' => 'negatif']));
        $neutralCount = count($feedbackRepository->findBy(['type' => 'neutre']));
        
        $positivePercentage = $totalCount > 0 ? round(($positiveCount / $totalCount) * 100) : 0;
        $negativePercentage = $totalCount > 0 ? round(($negativeCount / $totalCount) * 100) : 0;
        $neutralPercentage = $totalCount > 0 ? round(($neutralCount / $totalCount) * 100) : 0;
        
        return $this->render('feedbackadmin/stats.html.twig', [
            'totalCount' => $totalCount,
            'positiveCount' => $positiveCount,
            'negativeCount' => $negativeCount,
            'neutralCount' => $neutralCount,
            'positivePercentage' => $positivePercentage,
            'negativePercentage' => $negativePercentage,
            'neutralPercentage' => $neutralPercentage,
        ]);
    }
}