<?php

namespace App\Controller;

use App\Repository\ClfrRepository;
use App\Service\RatingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/rating')]
class RatingController extends AbstractController
{
    private $ratingService;

    public function __construct(RatingService $ratingService)
    {
        $this->ratingService = $ratingService;
    }

    #[Route('/new/{clfrId}', name: 'app_rating_new', methods: ['GET', 'POST'])]
    public function new(Request $request, int $clfrId): Response
    {
        if ($request->isMethod('POST')) {
            $rating = $request->request->getInt('rating');
            $comment = $request->request->get('comment');
            
            if ($rating < 1 || $rating > 5) {
                $this->addFlash('error', 'La note doit être comprise entre 1 et 5.');
                return $this->redirectToRoute('app_rating_new', ['clfrId' => $clfrId]);
            }
            
            if ($this->ratingService->saveRating($clfrId, $rating, $comment)) {
                $this->addFlash('success', 'Évaluation enregistrée avec succès.');
                return $this->redirectToRoute('app_clfr_feedback');
            } else {
                $this->addFlash('error', 'Une erreur est survenue l\'enregistrement de votre évaluation.');
            }
        }
        
        return $this->render('rating/new.html.twig', [
            'clfrId' => $clfrId
        ]);
    }

    #[Route('/{clfrId}', name: 'app_rating_show', methods: ['GET'])]
    public function show(int $clfrId): Response
    {
        $ratings = $this->ratingService->getRatingsForClfr($clfrId);
        $averageRating = $this->ratingService->getAverageRatingForClfr($clfrId);
        $ratingCount = $this->ratingService->getRatingCountForClfr($clfrId);
        
        return $this->render('rating/show.html.twig', [
            'clfrId' => $clfrId,
            'ratings' => $ratings,
            'averageRating' => $averageRating,
            'ratingCount' => $ratingCount
        ]);
    }

    #[Route('/{clfrId}/stats', name: 'app_rating_stats', methods: ['GET'])]
    public function stats(int $clfrId): JsonResponse
    {
        $averageRating = $this->ratingService->getAverageRatingForClfr($clfrId);
        $ratingCount = $this->ratingService->getRatingCountForClfr($clfrId);
        
        return new JsonResponse([
            'averageRating' => $averageRating,
            'ratingCount' => $ratingCount
        ]);
    }
} 