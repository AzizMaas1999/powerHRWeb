<?php

namespace App\Controller;

use App\Entity\FicheEmploye;
use App\Form\FicheEmployeType;
use App\Repository\FicheEmployeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/fiche/employeadmin')]
final class FicheEmployeAdminController extends AbstractController
{
    #[Route('/',name: 'app_fiche_employeadmin_index', methods: ['GET'])]
    public function index(FicheEmployeRepository $ficheEmployeRepository): Response
    {
        return $this->render('fiche_employeadmin/index.html.twig', [
            'ficheemployes' => $ficheEmployeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_fiche_employeadmin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ficheEmploye = new FicheEmploye();
        $form = $this->createForm(FicheEmployeType::class, $ficheEmploye);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ficheEmploye);
            $entityManager->flush();

            $this->addFlash('success', 'Fiche employé ajoutée avec succès.');
            return $this->redirectToRoute('app_fiche_employeadmin_index');
        }

        return $this->render('fiche_employeadmin/new.html.twig', [
            'fiche_employe' => $ficheEmploye,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_fiche_employeadmin_show', methods: ['GET'])]
    public function show(FicheEmploye $ficheEmploye): Response
    {
        return $this->render('fiche_employeadmin/show.html.twig', [
            'fiche_employe' => $ficheEmploye,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_fiche_employeadmin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FicheEmploye $ficheEmploye, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FicheEmployeType::class, $ficheEmploye);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Fiche employé mise à jour avec succès.');
            return $this->redirectToRoute('app_fiche_employeadmin_index');
        }

        return $this->render('fiche_employeadmin/edit.html.twig', [
            'fiche_employe' => $ficheEmploye,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_fiche_employeadmin_delete', methods: ['POST'])]
    public function delete(Request $request, FicheEmploye $ficheEmploye, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ficheEmploye->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($ficheEmploye);
            $entityManager->flush();
            
            $this->addFlash('success', 'Fiche employé supprimée avec succès.');
        }

        return $this->redirectToRoute('app_fiche_employeadmin_index');
    }
}