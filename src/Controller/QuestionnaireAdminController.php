<?php

namespace App\Controller;

use App\Entity\Questionnaire;
use App\Form\QuestionnaireType;
use App\Repository\QuestionnaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/questionnaireadmin')]
final class QuestionnaireAdminController extends AbstractController
{
    #[Route(name: 'app_questionnaireadmin_index', methods: ['GET'])]
    public function index(QuestionnaireRepository $questionnaireRepository): Response
    {
        return $this->render('questionnaireadmin/index.html.twig', [
            'questionnaires' => $questionnaireRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_questionnaireadmin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $questionnaire = new Questionnaire();
        $questionnaire->setDateCreation(new \DateTime());

        $form = $this->createForm(QuestionnaireType::class, $questionnaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($questionnaire);
            $entityManager->flush();

            $this->addFlash('success', 'Questionnaire ajouté avec succès.');

            return $this->redirectToRoute('app_questionnaireadmin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('questionnaireadmin/new.html.twig', [
            'questionnaire' => $questionnaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_questionnaireadmin_show', methods: ['GET'])]
    public function show(Questionnaire $questionnaire): Response
    {
        return $this->render('questionnaireadmin/show.html.twig', [
            'questionnaire' => $questionnaire,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_questionnaireadmin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Questionnaire $questionnaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuestionnaireType::class, $questionnaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Questionnaire modifié avec succès.');

            return $this->redirectToRoute('app_questionnaireadmin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('questionnaireadmin/edit.html.twig', [
            'questionnaire' => $questionnaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_questionnaireadmin_delete', methods: ['POST'])]
    public function delete(Request $request, Questionnaire $questionnaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $questionnaire->getId(), $request->get('_token'))) {
            $entityManager->remove($questionnaire);
            $entityManager->flush();

            $this->addFlash('danger', 'Questionnaire supprimé avec succès.');
        }

        return $this->redirectToRoute('app_questionnaireadmin_index', [], Response::HTTP_SEE_OTHER);
    }
}
