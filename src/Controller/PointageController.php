<?php

namespace App\Controller;

use App\Entity\Pointage;
use App\Form\PointageType;
use App\Repository\PointageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pointage')]
final class PointageController extends AbstractController
{
    #[Route(name: 'app_pointage_index', methods: ['GET'])]
    public function index(PointageRepository $pointageRepository): Response
    {
        return $this->render('pointage/index.html.twig', [
            'pointages' => $pointageRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_pointage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $pointage = new Pointage();
        $form = $this->createForm(PointageType::class, $pointage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pointage);
            $entityManager->flush();

            return $this->redirectToRoute('app_pointage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pointage/new.html.twig', [
            'pointage' => $pointage,
            'form' => $form,
        ]);
    }

    #[Route('/csv', name: 'app_pointage_csv', methods: ['GET'])]
    public function csv(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->render('pointage/csv.html.twig');
    }

    #[Route('/{id}', name: 'app_pointage_show', methods: ['GET'])]
    public function show(Pointage $pointage): Response
    {
        return $this->render('pointage/show.html.twig', [
            'pointage' => $pointage,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pointage_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pointage $pointage, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PointageType::class, $pointage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_pointage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pointage/edit.html.twig', [
            'pointage' => $pointage,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pointage_delete', methods: ['POST'])]
    public function delete(Request $request, Pointage $pointage, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pointage->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($pointage);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_pointage_index', [], Response::HTTP_SEE_OTHER);
    }
}
