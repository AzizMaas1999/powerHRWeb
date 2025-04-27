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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\QuestionnaireRepository;


#[IsGranted('ROLE_OUVIER')]
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

    #[Route('/repquestionnaire/new/{questionnaireId}', name: 'app_repquestionnaire_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, QuestionnaireRepository $questionnaireRepository, int $questionnaireId): Response
    {
        $repQuestionnaire = new RepQuestionnaire();
        $repQuestionnaire->setDateCreation(new \DateTime());

        $questionnaire = $questionnaireRepository->find($questionnaireId);
        if (!$questionnaire) {
            throw $this->createNotFoundException('Questionnaire not found');
        }
    
        $repQuestionnaire->setQuestionnaireId($questionnaire->getId());
    
        $form = $this->createForm(RepQuestionnaireType::class, $repQuestionnaire);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($repQuestionnaire);
            $entityManager->flush();

            $this->addFlash('success', 'Reponse Questionnaire ajouté avec succès.');

    
            return $this->redirectToRoute('app_repquestionnaire_index');
        }
    
        return $this->render('repquestionnaire/new.html.twig', [
            'rep_questionnaire' => $repQuestionnaire,
            'form' => $form->createView(),
            'questionnaire' => $questionnaire, // 👈 On envoie l'objet à la vue
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
