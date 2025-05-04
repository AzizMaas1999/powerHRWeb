<?php

namespace App\Controller;

use App\Entity\Departement;
use App\Form\DepartementType;
use App\Repository\DepartementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/departementadmin')]
final class DepartementAdminController extends AbstractController
{
    #[Route('/', name: 'app_departementadmin_index', methods: ['GET'])]
    public function index(DepartementRepository $departementRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $departementRepository->searchDepartements($request->query->get('q'));
        
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            4 // Items per page
        );

        if ($request->query->get('ajax')) {
            return $this->render('departementadmin/_list.html.twig', [
                'pagination' => $pagination,
            ]);
        }

        return $this->render('departementadmin/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_departementadmin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $departement = new Departement();
        $form = $this->createForm(DepartementType::class, $departement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($departement);
            $entityManager->flush();

            $this->addFlash('success', 'Département ajouté avec succès.');
            return $this->redirectToRoute('app_departementadmin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('departementadmin/new.html.twig', [
            'departement' => $departement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_departementadmin_show', methods: ['GET'])]
    public function show(Departement $departement): Response
    {
        return $this->render('departementadmin/show.html.twig', [
            'departement' => $departement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_departementadmin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Departement $departement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DepartementType::class, $departement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Département mis à jour avec succès.');
            return $this->redirectToRoute('app_departementadmin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('departementadmin/edit.html.twig', [
            'departement' => $departement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_departementadmin_delete', methods: ['POST'])]
    public function delete(Request $request, Departement $departement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$departement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($departement);
            $entityManager->flush();
            
            $this->addFlash('success', 'Département supprimé avec succès.');
        }

        return $this->redirectToRoute('app_departementadmin_index', [], Response::HTTP_SEE_OTHER);
    }
}