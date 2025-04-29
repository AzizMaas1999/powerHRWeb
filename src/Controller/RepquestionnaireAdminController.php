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
use App\Repository\QuestionnaireRepository;

#[Route('/repquestionnaireadmin')]
final class RepquestionnaireAdminController extends AbstractController
{
    #[Route('/', name: 'app_repquestionnaireadmin_index', methods: ['GET'])]
    public function index(RepquestionnaireRepository $repquestionnaireRepository): Response
    {
        return $this->render('repquestionnaireadmin/index.html.twig', [
            'repquestionnaires' => $repquestionnaireRepository->findAll(),
        ]);
    }

    #[Route('/repquestionnaireadmin/new/{questionnaireId}', name: 'app_repquestionnaireadmin_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, QuestionnaireRepository $questionnaireRepository, int $questionnaireId): Response
    {
        $repQuestionnaire = new RepQuestionnaire();
        $repQuestionnaire->setDateCreation(new \DateTime());
    
        $user = $this->getUser();
        $repQuestionnaire->setEmploye($user);
    
        $questionnaire = $questionnaireRepository->find($questionnaireId);
        if (!$questionnaire) {
            throw $this->createNotFoundException('Questionnaire non trouvé');
        }
    
        $repQuestionnaire->setQuestionnaireId($questionnaire->getId());
    
        $form = $this->createForm(RepQuestionnaireType::class, $repQuestionnaire);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($repQuestionnaire);
            $entityManager->flush();
    
            return $this->redirectToRoute('app_repquestionnaireadmin_index');
        }
    
        return $this->render('repquestionnaireadmin/new.html.twig', [
            'rep_questionnaire' => $repQuestionnaire,
            'form' => $form->createView(),
            'questionnaire' => $questionnaire,
        ]);
    }
    
    #[Route('/{id}', name: 'app_repquestionnaireadmin_show', methods: ['GET'])]
    public function show(Repquestionnaire $repquestionnaire): Response
    {
        return $this->render('repquestionnaireadmin/show.html.twig', [
            'repquestionnaire' => $repquestionnaire,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_repquestionnaireadmin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Repquestionnaire $repquestionnaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RepquestionnaireType::class, $repquestionnaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('info', 'Réponse modifiée avec succès.');

            return $this->redirectToRoute('app_repquestionnaireadmin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('repquestionnaireadmin/edit.html.twig', [
            'repquestionnaire' => $repquestionnaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_repquestionnaireadmin_delete', methods: ['POST'])]
    public function delete(Request $request, Repquestionnaire $repquestionnaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $repquestionnaire->getId(), $request->get('_token'))) {
            $entityManager->remove($repquestionnaire);
            $entityManager->flush();

            $this->addFlash('danger', 'Réponse supprimée avec succès.');
        }

        return $this->redirectToRoute('app_repquestionnaireadmin_index', [], Response::HTTP_SEE_OTHER);
    }
}
