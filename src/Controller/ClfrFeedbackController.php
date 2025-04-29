<?php 
// src/Controller/ClfrFeedbackController.php

namespace App\Controller;

use App\Repository\ClfrRepository;
use App\Service\RatingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClfrFeedbackController extends AbstractController
{
    private $ratingService;

    public function __construct(RatingService $ratingService)
    {
        $this->ratingService = $ratingService;
    }

    #[Route('/clfrs', name: 'app_clfr_feedback')]
    public function index(ClfrRepository $clFrRepository): Response
    {
        $clfrs = $clFrRepository->findAll();
        
        // Get average ratings for each CLFR
        $ratings = [];
        foreach ($clfrs as $clfr) {
            $ratings[$clfr->getId()] = [
                'average' => $this->ratingService->getAverageRatingForClfr($clfr->getId()),
                'count' => $this->ratingService->getRatingCountForClfr($clfr->getId())
            ];
        }

        return $this->render('feedback/liste_clfr_feedback.html.twig', [
            'clfrs' => $clfrs,
            'ratings' => $ratings,
        ]);
    }
}
