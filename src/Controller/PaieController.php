<?php

namespace App\Controller;

use App\Entity\Paie;
use App\Entity\Pointage;
use App\Form\PaieType;
use App\Repository\PaieRepository;
use App\Repository\PointageRepository;
use App\Repository\EmployeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/paie')]
final class PaieController extends AbstractController
{
    #[Route(name: 'app_paie_index', methods: ['GET'])]
    public function index(PaieRepository $paieRepository, PointageRepository $pointageRepository, EmployeRepository $employeRepository): Response
    {
        return $this->render('paie/index.html.twig', [
            'paies' => $paieRepository->findAll(
                ['mois' => 'ASC'],
                ['annee' => 'ASC']
            ),
            'pointages' => $pointageRepository->findAll(
                ['paie' => 'NOT NULL']
            ),
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

    #[Route('/employe', name: 'app_showEmployePaie_index', methods: ['GET'])]
    public function showEmp(EmployeRepository $employeRepository, PointageRepository $pointageRepository, PaieRepository $paieRepository): Response
    {
        return $this->render('paie/showEmp.html.twig', [
            'employes' => $employeRepository->findBy([], ['username' => 'ASC']),
            'pointages' => $pointageRepository->findAll(),
            'paies' => $pointageRepository->findAll(),
        ]);
    }

    #[Route('/newPaie/{id}/{nb}', name: 'app_newEmployePaie', methods: ['GET', 'POST'])]
    public function newPaie(int $id, int $nb, Request $request, EntityManagerInterface $entityManager, EmployeRepository $employeRepository, PointageRepository $pointageRepository): Response
    {
        $paie = new Paie();
        $employe = $employeRepository->find($id);
        $paie->setNbJour($nb);
        $paie->setMontant($nb * ($employe->getSalaire() / 30));
        $paie->setMois(date('M'));
        $paie->setAnnee(date('Y'));
        $entityManager->persist($paie);
        $pointage = $pointageRepository->findBy(
            ['employe' => $id],
            ['date' => date('Y-m')]
        );
        foreach ($pointage as $p) {
            $p->setPaie($paie);        
        }
        $entityManager->flush();
        return $this->redirectToRoute('app_paie_index', [], Response::HTTP_SEE_OTHER);
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
