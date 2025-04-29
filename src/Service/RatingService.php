<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RatingService
{
    private $ratingsFile;
    private $filesystem;

    public function __construct(ParameterBagInterface $params, Filesystem $filesystem)
    {
        $this->ratingsFile = $params->get('kernel.project_dir') . '/data/ratings.json';
        $this->filesystem = $filesystem;
        
        // Créer le répertoire data s'il n'existe pas
        $dataDir = dirname($this->ratingsFile);
        if (!$this->filesystem->exists($dataDir)) {
            $this->filesystem->mkdir($dataDir);
        }
        
        // Créer le fichier ratings.json s'il n'existe pas
        if (!$this->filesystem->exists($this->ratingsFile)) {
            $this->filesystem->dumpFile($this->ratingsFile, json_encode([]));
        }
    }

    /**
     * Sauvegarder une évaluation
     */
    public function saveRating(int $clfrId, int $rating, ?string $comment = null): bool
    {
        try {
            $ratings = $this->getAllRatings();
            
            // Ajouter la nouvelle évaluation
            $ratings[] = [
                'clfrId' => $clfrId,
                'rating' => $rating,
                'comment' => $comment,
                'date' => (new \DateTime())->format('Y-m-d H:i:s')
            ];
            
            // Sauvegarder dans le fichier
            $this->filesystem->dumpFile($this->ratingsFile, json_encode($ratings, JSON_PRETTY_PRINT));
            
            return true;
        } catch (\Exception $e) {
            // Log l'erreur
            error_log('Erreur lors de la sauvegarde de l\'évaluation: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer toutes les évaluations
     */
    public function getAllRatings(): array
    {
        if (!$this->filesystem->exists($this->ratingsFile)) {
            return [];
        }
        
        try {
            $content = file_get_contents($this->ratingsFile);
            $ratings = json_decode($content, true);
            return is_array($ratings) ? $ratings : [];
        } catch (\Exception $e) {
            error_log('Erreur lors de la lecture des évaluations: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les évaluations pour un CLFR
     */
    public function getRatingsForClfr(int $clfrId): array
    {
        $allRatings = $this->getAllRatings();
        return array_filter($allRatings, function($rating) use ($clfrId) {
            return $rating['clfrId'] === $clfrId;
        });
    }

    /**
     * Calculer la moyenne des évaluations pour un CLFR
     */
    public function getAverageRatingForClfr(int $clfrId): float
    {
        $ratings = $this->getRatingsForClfr($clfrId);
        
        if (empty($ratings)) {
            return 0;
        }
        
        $sum = array_sum(array_column($ratings, 'rating'));
        return round($sum / count($ratings), 1);
    }

    /**
     * Compter les évaluations pour un CLFR
     */
    public function getRatingCountForClfr(int $clfrId): int
    {
        return count($this->getRatingsForClfr($clfrId));
    }
} 