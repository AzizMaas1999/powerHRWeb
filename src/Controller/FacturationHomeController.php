<?php

namespace App\Controller;

use DateTime;
use App\Repository\FactureRepository;
use App\Repository\PaiementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_FACTURATION')]
#[Route('/facturation')]
final class FacturationHomeController extends AbstractController
{
    #[Route('/', name: 'app_facturation_home', methods: ['GET'])]
    public function index(FactureRepository $factureRepository, PaiementRepository $paiementRepository): Response
    {
        // Calculer le chiffre d'affaires total (somme des montants des factures payées)
        $factures = $factureRepository->findBy(['status' => 'Payée']);
        $chiffreAffaires = 0;
        foreach ($factures as $facture) {
            $chiffreAffaires += $facture->getTotal();
        }
        
        // Nombre de factures en attente de paiement
        $facturesEnAttente = count($factureRepository->findBy(['status' => 'Non payée']));
        
        // Récupérer les paiements récents (nombre de paiements récents à vérifier)
        $dateLimit = new DateTime('-7 days');
        $paiements = $paiementRepository->createQueryBuilder('p')
            ->where('p.date >= :date')
            ->setParameter('date', $dateLimit)
            ->getQuery()
            ->getResult();
        $paiementsRecents = count($paiements);
        
        // Récupérer les factures les plus récentes (5 dernières)
        $facturesRecentes = $factureRepository->findBy([], ['date' => 'DESC'], 5);
        
        // Préparer les données pour le graphique par mois
        $currentYear = (new DateTime())->format('Y');
        $donneesMensuelles = [];
        $moisLabels = [];
        
        // Initialiser les données pour tous les mois de l'année en cours
        for ($i = 1; $i <= 12; $i++) {
            $moisLabels[] = $this->getMoisFrancais($i);
            $donneesMensuelles[$i] = 0;
        }
        
        // Calculer le chiffre d'affaires par mois pour l'année en cours
        foreach ($factures as $facture) {
            if ($facture->getDate()->format('Y') == $currentYear) {
                $mois = (int)$facture->getDate()->format('n');
                $donneesMensuelles[$mois] += $facture->getTotal();
            }
        }
        
        // Calculer le montant total pour le mois en cours
        $moisActuel = (int)(new DateTime())->format('n');
        $montantTotal = $donneesMensuelles[$moisActuel];
        
        // Convertir les tableaux pour la sortie JSON
        $donneesMensuellesJSON = array_values($donneesMensuelles);
        
        return $this->render('home/facturation.html.twig', [
            'chiffreAffaires' => $chiffreAffaires,
            'facturesEnAttente' => $facturesEnAttente,
            'paiementsRecents' => $paiementsRecents,
            'facturesRecentes' => $facturesRecentes,
            'donneesMensuelles' => json_encode($donneesMensuellesJSON),
            'moisLabels' => json_encode($moisLabels),
            'montantTotal' => $montantTotal,
        ]);
    }
    
    /**
     * Récupère le nom du mois en français à partir de son numéro
     */
    private function getMoisFrancais(int $numeroMois): string
    {
        $moisFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        return $moisFr[$numeroMois] ?? '';
    }
}