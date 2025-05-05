<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Form\CandidatType;
use App\Repository\CandidatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/candidat')]
final class CandidatController extends AbstractController
{
    #[Route(name: 'app_candidat_index', methods: ['GET'])]
    #[IsGranted('ROLE_CHARGES')]
    public function index(Request $request, CandidatRepository $candidatRepository): Response
    {
        $search = $request->query->get('search');
        $candidats = $search 
            ? $candidatRepository->search($search)
            : $candidatRepository->findAll();

        return $this->render('candidat/index.html.twig', [
            'candidats' => $candidats,
            'search' => $search
        ]);
    }

    #[Route('/new', name: 'app_candidat_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $candidat = new Candidat();
        $form = $this->createForm(CandidatType::class, $candidat);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($candidat);
            $entityManager->flush();
     
            $this->addFlash('success', 'Votre candidature a été enregistrée avec succès!');
            return $this->redirectToRoute('app_candidat_new');
        }
    
        return $this->render('candidat/new.html.twig', [
            'candidat' => $candidat,
            'form' => $form,
        ]);
    }
    

    #[Route('/{id}', name: 'app_candidat_show', methods: ['GET'])]
    #[IsGranted('ROLE_CHARGES')]
    public function show(Candidat $candidat): Response
    {
        return $this->render('candidat/show.html.twig', [
            'candidat' => $candidat,
        ]);
    }

    #[Route('/{id}', name: 'app_candidat_delete', methods: ['POST'])]
    #[IsGranted('ROLE_CHARGES')]
    public function delete(Request $request, Candidat $candidat, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$candidat->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($candidat);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_candidat_index', [], Response::HTTP_SEE_OTHER);
    }
}
