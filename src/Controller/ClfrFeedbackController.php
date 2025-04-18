<?php 
// src/Controller/ClfrFeedbackController.php

namespace App\Controller;

use App\Repository\ClfrRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClfrFeedbackController extends AbstractController
{
    #[Route('/clfrs', name: 'app_clfr_feedback')]
    public function index(ClfrRepository $clFrRepository): Response
    {
        $clfrs = $clFrRepository->findAll();

        return $this->render('feedback/liste_clfr_feedback.html.twig', [
            'clfrs' => $clfrs,
        ]);
    }
}
