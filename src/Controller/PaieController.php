<?php

namespace App\Controller;

use App\Entity\Paie;
use App\Form\PaieType;
use App\Repository\PaieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/paie')]
final class PaieController extends AbstractController
{
    #[Route(name: 'app_paie_index', methods: ['GET'])]
    public function index(PaieRepository $paieRepository): Response
    {
        return $this->render('paie/index.html.twig', [
            'paies' => $paieRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_paie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $paie = new Paie();
        $form = $this->createForm(PaieType::class, $paie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($paie);
            $entityManager->flush();

            return $this->redirectToRoute('app_paie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('paie/new.html.twig', [
            'paie' => $paie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_paie_show', methods: ['GET'])]
    public function show(Paie $paie): Response
    {
        return $this->render('paie/show.html.twig', [
            'paie' => $paie,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_paie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Paie $paie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PaieType::class, $paie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_paie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('paie/edit.html.twig', [
            'paie' => $paie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_paie_delete', methods: ['POST'])]
    public function delete(Request $request, Paie $paie, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$paie->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($paie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_paie_index', [], Response::HTTP_SEE_OTHER);
    }
}
