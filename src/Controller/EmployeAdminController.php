<?php

namespace App\Controller;

use App\Entity\Employe;
use App\Form\EmployeType;
use App\Repository\EmployeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route('/employeadmin')]
final class EmployeAdminController extends AbstractController
{
    #[Route('/', name: 'app_employeadmin_index', methods: ['GET'])]
    public function index(EmployeRepository $employeRepository): Response
    {
        return $this->render('employeadmin/index.html.twig', [
            'employes' => $employeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_employeadmin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $employe = new Employe();
        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $hashedPassword = $userPasswordHasher->hashPassword(
                $employe,
                $form->get('password')->getData()
            );
            $employe->setPassword($hashedPassword);
            
            // Set default role
            $employe->setRoles(['ROLE_USER']);
            
            $entityManager->persist($employe);
            $entityManager->flush();

            $this->addFlash('success', 'Employé ajouté avec succès.');
            return $this->redirectToRoute('app_employeadmin_index');
        }

        return $this->render('employeadmin/new.html.twig', [
            'employe' => $employe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_employeadmin_show', methods: ['GET'])]
    public function show(Employe $employe): Response
    {
        return $this->render('employeadmin/show.html.twig', [
            'employe' => $employe,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_employeadmin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Employe $employe, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if password field is filled
            if ($password = $form->get('password')->getData()) {
                $hashedPassword = $userPasswordHasher->hashPassword(
                    $employe,
                    $password
                );
                $employe->setPassword($hashedPassword);
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Employé mis à jour avec succès.');
            return $this->redirectToRoute('app_employeadmin_index');
        }

        return $this->render('employeadmin/edit.html.twig', [
            'employe' => $employe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_employeadmin_delete', methods: ['POST'])]
    public function delete(Request $request, Employe $employe, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$employe->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($employe);
            $entityManager->flush();
            
            $this->addFlash('success', 'Employé supprimé avec succès.');
        }

        return $this->redirectToRoute('app_employeadmin_index');
    }
}