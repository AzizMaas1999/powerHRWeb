<?php

namespace App\Controller;

use App\Entity\Paie;
use App\Entity\Pointage;
use App\Entity\Employe;
use App\Entity\Departement;
use App\Repository\PaieRepository;
use App\Repository\PointageRepository;
use App\Repository\EmployeRepository;
use App\Repository\DepartementRepository;
use App\Repository\FicheEmployeRepository;
use App\Repository\DemandeRepository;
use App\Repository\QuestionnaireRepository;
use App\Repository\CandidatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use DateTime;

#[IsGranted('ROLE_ADMIN')]
#[Route('/homeadmin')]
final class HomeAdminController extends AbstractController
{
    #[Route('/charges', name: 'charges_admin_home')]
    public function chargesAdmin(
        PaieRepository $paieRepository, 
        EmployeRepository $employeRepository, 
        PointageRepository $pointageRepository,
        DepartementRepository $departementRepository,
        DemandeRepository $demandeRepository,
        FicheEmployeRepository $ficheEmployeRepository,
        QuestionnaireRepository $questionnaireRepository,
        CandidatRepository $candidatRepository
    ): Response
    {
        // Nombre total d'employés actifs
        $employesCount = count($employeRepository->findAll());
        
        // Nombre de fiches à traiter
        $fichesCount = count($ficheEmployeRepository->findAll());
        
        // Nombre de questionnaires
        $questionnairesCount = count($questionnaireRepository->findAll());
        
        // Données pour le graphique de présence/absence
        $months = ['Jan', 'Fév', 'Mar', 'Avr']; // Limit to first 4 months
        $presenceData = [];
        $absenceData = [];
        
        $currentYear = (int)(new DateTime())->format('Y');
        
        // Récupérer les données pour les 4 premiers mois
        for ($i = 1; $i <= 4; $i++) { // Only loop through first 4 months
            // Pour simplifier, compter tous les pointages du mois comme présences
            $firstDayOfMonth = new \DateTime("$currentYear-$i-01");
            $lastDayOfMonth = clone $firstDayOfMonth;
            $lastDayOfMonth->modify('last day of this month');
            
            $presenceCount = count($pointageRepository->findByDateRange($firstDayOfMonth, $lastDayOfMonth));
            $presenceData[] = $presenceCount;
            
            // Pour simplifier, utiliser un nombre fixe d'absences
            // Dans un scénario réel, vous pourriez calculer les absences en fonction du nombre total d'employés
            // moins les présences par jour
            $absenceData[] = max(0, $employesCount * 3 - $presenceCount); // 3 jours de travail en moyenne par mois
        }
        
        // Calculer les taux de présence et d'absence
        $totalPresence = array_sum($presenceData);
        $totalAbsence = array_sum($absenceData);
        $total = $totalPresence + $totalAbsence;
        
        $presenceRate = $total > 0 ? round(($totalPresence / $total) * 100) : 0;
        $absenceRate = $total > 0 ? round(($totalAbsence / $total) * 100) : 0;
        
        // Récupérer tous les départements et leurs effectifs
        $departements = $departementRepository->findAll();
        $departementLabels = [];
        $departementData = [];
        
        foreach ($departements as $departement) {
            $departementLabels[] = $departement->getNom();
            $departementData[] = count($departement->getEmployes());
        }
        
        // Demandes de congés en attente
        $demandesCount = count($demandeRepository->findBy(['status' => 'En Attente']));
        $demandesEnAttenteListe = $demandeRepository->findBy(['status' => 'En Attente'], ['dateDebut' => 'DESC'], 5);
        
        // Get actual count of candidates from the database
        $candidatsCount = count($candidatRepository->findAll());
        
        // Paies traitées ce mois
        $currentMonthName = (new DateTime())->format('F');
        $paiesTraitees = count($paieRepository->findBy(['mois' => $currentMonthName]));
        
        // Récupérer les pointages récents
        $today = new \DateTime('now', new \DateTimeZone('Africa/Tunis'));
        $today->setTime(0, 0, 0);
        
        $tomorrow = clone $today;
        $tomorrow->add(new \DateInterval('P1D'));
        
        // Préparer les données de statut pour les employés
        $employes = $employeRepository->findBy([], ['username' => 'ASC'], 5);
        $employeStatusToday = [];
        
        foreach ($employes as $employe) {
            $status = 'Absent'; // Par défaut, considérer comme absent
            
            // Rechercher le pointage de l'employé pour aujourd'hui
            $pointage = $pointageRepository->findOneBy([
                'employe' => $employe,
                'date' => $today
            ]);
            
            if ($pointage) {
                if ($pointage->getHeureEntree()) {
                    $entryTime = $pointage->getHeureEntree();
                    $expectedEntryTime = new \DateTime('08:00:00'); // 8 AM as expected start time
                    
                    if ($entryTime > $expectedEntryTime) {
                        // If entry time is after expected time
                        $status = 'Retard';
                    } else {
                        $status = 'Présent';
                    }
                }
            }
            
            $employeStatusToday[] = [
                'employe' => $employe,
                'status' => $status
            ];
        }
        
        return $this->render('home/chargesadmin.html.twig', [
            'paies' => $paieRepository->findAll(),
            'employesCount' => $employesCount,
            'fichesCount' => $fichesCount,
            'questionnairesCount' => $questionnairesCount,
            'months' => $months,
            'presenceData' => $presenceData,
            'absenceData' => $absenceData,
            'presenceRate' => $presenceRate,
            'absenceRate' => $absenceRate,
            'recentPointages' => $pointageRepository->findBy([], ['date' => 'DESC'], 5),
            'departementLabels' => $departementLabels,
            'departementData' => $departementData,
            'demandesEnAttente' => $demandesCount,
            'demandesEnAttenteListe' => $demandesEnAttenteListe,
            'candidatsCount' => $candidatsCount,
            'paiesTraitees' => $paiesTraitees,
            'employeStatusToday' => $employeStatusToday
        ]);
    }
}