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
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/questionnaire')]
final class QuestionnaireController extends AbstractController
{
    #[Route(name: 'app_questionnaire_index', methods: ['GET'])]
    public function index(QuestionnaireRepository $questionnaireRepository): Response
    {
        $user = $this->getUser(); // employé connecté
    
        $questionnaires = $questionnaireRepository->findBy([
            'employe' => $user,
        ]);
    
        return $this->render('questionnaire/index.html.twig', [
            'questionnaires' => $questionnaires,
        ]);
    }
    

    #[Route('/new', name: 'app_questionnaire_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    $user = $this->getUser(); // Récupère l'employé connecté

    $questionnaire = new Questionnaire();
    $questionnaire->setDateCreation(new \DateTime());
    $questionnaire->setEmploye($user); // Associe l'employé connecté

    $form = $this->createForm(QuestionnaireType::class, $questionnaire);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($questionnaire);
        $entityManager->flush();

        $this->addFlash('success', 'Questionnaire ajouté avec succès.');

        return $this->redirectToRoute('app_questionnaire_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('questionnaire/new.html.twig', [
        'questionnaire' => $questionnaire,
        'form' => $form,
    ]);
}

    #[Route('/{id}', name: 'app_questionnaire_show', methods: ['GET'])]
    public function show(Questionnaire $questionnaire): Response
    {
        return $this->render('questionnaire/show.html.twig', [
            'questionnaire' => $questionnaire,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_questionnaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Questionnaire $questionnaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuestionnaireType::class, $questionnaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Questionnaire modifié avec succès.');

            return $this->redirectToRoute('app_questionnaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('questionnaire/edit.html.twig', [
            'questionnaire' => $questionnaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_questionnaire_delete', methods: ['POST'])]
    public function delete(Request $request, Questionnaire $questionnaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $questionnaire->getId(), $request->get('_token'))) {
            $entityManager->remove($questionnaire);
            $entityManager->flush();

            $this->addFlash('danger', 'Questionnaire supprimé avec succès.');
        }

        return $this->redirectToRoute('app_questionnaire_index', [], Response::HTTP_SEE_OTHER);
    }
}
