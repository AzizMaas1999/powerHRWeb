<?php

namespace App\Controller;

use App\Entity\Clfr;
use App\Form\ClfrType;
use App\Repository\ClfrRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\FormError;

#[IsGranted('ROLE_CHARGE')]
#[Route('/clfr')]
final class ClfrController extends AbstractController
{
    #[Route(name: 'app_clfr_index', methods: ['GET'])]
    public function index(ClfrRepository $clfrRepository): Response
    {
        return $this->render('clfr/index.html.twig', [
            'clfrs' => $clfrRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_clfr_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $clfr = new Clfr();
        $form = $this->createForm(ClfrType::class, $clfr);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photoPath')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gérer l'erreur si besoin
                }

                $clfr->setPhotoPath('uploads/photos/' . $newFilename);
            }

            $entityManager->persist($clfr);
            $entityManager->flush();

            return $this->redirectToRoute('app_clfr_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('clfr/new.html.twig', [
            'clfr' => $clfr,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_clfr_show', methods: ['GET'])]
    public function show(Clfr $clfr): Response
    {
        return $this->render('clfr/show.html.twig', [
            'clfr' => $clfr,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_clfr_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Clfr $clfr, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ClfrType::class, $clfr);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photoPath')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gérer l'erreur
                }

                $clfr->setPhotoPath('uploads/photos/' . $newFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_clfr_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('clfr/edit.html.twig', [
            'clfr' => $clfr,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_clfr_delete', methods: ['POST'])]
    public function delete(Request $request, Clfr $clfr, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $clfr->getId(), $request->request->get('_token'))) {
            $entityManager->remove($clfr);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_clfr_index');
    }
}
