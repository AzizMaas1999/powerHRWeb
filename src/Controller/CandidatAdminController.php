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
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[IsGranted('ROLE_ADMIN')]
#[Route('/candidatadmin')]
final class CandidatAdminController extends AbstractController
{
    #[Route('/', name: 'app_candidatadmin_index', methods: ['GET'])]
    public function index(CandidatRepository $candidatRepository): Response
    {
        return $this->render('candidatadmin/index.html.twig', [
            'candidats' => $candidatRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_candidatadmin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $candidat = new Candidat();
        $form = $this->createForm(CandidatType::class, $candidat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle CV upload
            $cvFile = $form->get('cv')->getData();
            
            if ($cvFile) {
                $originalFilename = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$cvFile->guessExtension();
                
                try {
                    $cvFile->move(
                        $this->getParameter('cv_directory'),
                        $newFilename
                    );
                    $candidat->setCvPdfUrl($newFilename);
                } catch (FileException $e) {
                    // Handle exception
                    $this->addFlash('error', 'Un problème est survenu lors du téléchargement du CV');
                }
            }
            
            $entityManager->persist($candidat);
            $entityManager->flush();

            $this->addFlash('success', 'Candidat ajouté avec succès.');
            return $this->redirectToRoute('app_candidatadmin_index');
        }

        return $this->render('candidatadmin/new.html.twig', [
            'candidat' => $candidat,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_candidatadmin_show', methods: ['GET'])]
    public function show(Candidat $candidat): Response
    {
        return $this->render('candidatadmin/show.html.twig', [
            'candidat' => $candidat,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_candidatadmin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Candidat $candidat, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(CandidatType::class, $candidat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle CV upload
            $cvFile = $form->get('cv')->getData();
            
            if ($cvFile) {
                $originalFilename = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$cvFile->guessExtension();
                
                try {
                    $cvFile->move(
                        $this->getParameter('cv_directory'),
                        $newFilename
                    );
                    
                    // Delete old CV if exists
                    if ($candidat->getCvPdfUrl()) {
                        $oldCvPath = $this->getParameter('cv_directory').'/'.$candidat->getCvPdfUrl();
                        if (file_exists($oldCvPath)) {
                            unlink($oldCvPath);
                        }
                    }
                    
                    $candidat->setCvPdfUrl($newFilename);
                } catch (FileException $e) {
                    // Handle exception
                    $this->addFlash('error', 'Un problème est survenu lors du téléchargement du CV');
                }
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Candidat mis à jour avec succès.');
            return $this->redirectToRoute('app_candidatadmin_index');
        }

        return $this->render('candidatadmin/edit.html.twig', [
            'candidat' => $candidat,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_candidatadmin_delete', methods: ['POST'])]
    public function delete(Request $request, Candidat $candidat, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$candidat->getId(), $request->getPayload()->getString('_token'))) {
            // Delete CV file if exists
            if ($candidat->getCvPdfUrl()) {
                $cvPath = $this->getParameter('cv_directory').'/'.$candidat->getCvPdfUrl();
                if (file_exists($cvPath)) {
                    unlink($cvPath);
                }
            }
            
            $entityManager->remove($candidat);
            $entityManager->flush();
            
            $this->addFlash('success', 'Candidat supprimé avec succès.');
        }

        return $this->redirectToRoute('app_candidatadmin_index');
    }
}