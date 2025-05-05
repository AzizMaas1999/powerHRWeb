<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Form\FeedbackType;
use App\Entity\Clfr;
use App\Repository\ClfrRepository;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_FACTURATION')]

#[Route('/feedback')]
final class FeedbackController extends AbstractController
{
    #[Route('/{clfr_id?}', name: 'app_feedback_index', methods: ['GET'])]
    public function index(FeedbackRepository $feedbackRepository, ?int $clfr_id = null): Response
    {
        $feedbacks = $clfr_id 
            ? $feedbackRepository->findBy(['clfr' => $clfr_id])
            : $feedbackRepository->findAll();

        return $this->render('feedback/index.html.twig', [
            'feedbacks' => $feedbacks,
            'clfr_id' => $clfr_id
        ]);
    }

    #[Route('/new/{clfr_id}', name: 'app_feedback_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ClfrRepository $clfrRepository, int $clfr_id): Response
    {
        $feedback = new Feedback();
        $feedback->setDateCreation(new \DateTime());
        
        $clfr = $clfrRepository->find($clfr_id);
        if ($clfr) {
            $feedback->setClfr($clfr);
        }
        
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($feedback);
            $entityManager->flush();

            return $this->redirectToRoute('app_feedback_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('feedback/new.html.twig', [
            'feedback' => $feedback,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_feedback_show', methods: ['GET'])]
    public function show(Feedback $feedback): Response
    {
        return $this->render('feedback/show.html.twig', [
            'feedback' => $feedback,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_feedback_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_feedback_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('feedback/edit.html.twig', [
            'feedback' => $feedback,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_feedback_delete', methods: ['POST'])]
    public function delete(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$feedback->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($feedback);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_feedback_index', [], Response::HTTP_SEE_OTHER);
    }
}
