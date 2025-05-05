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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\Persistence\ManagerRegistry; // <<< ajoute cette ligne en haut avec les autres "use"
use DateTime;

#[Route('/home')]
class HomeController extends AbstractController
{
    #[Route('/directeur', name: 'directeur_home')]
    public function directeur(ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
    
        // Requête pour compter les demandes avec statut "En Attente"
        /*$query = $entityManager->createQuery(
            'SELECT COUNT(d.id) FROM App\Entity\Demande d WHERE d.status = :status'
        )->setParameter('status', 'En Attente');
    
        $nbDemandesEnAttente = $query->getSingleScalarResult();*/
        return $this->render('home/directeur.html.twig'/*,[
            'nbDemandesEnAttente' => $nbDemandesEnAttente,
        ]*/);
    }

    #[Route('/charges', name: 'charges_home')]
    public function charges(
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
            
            $presenceCount = count($pointageRepository->createQueryBuilder('p')
                ->andWhere('p.date >= :firstDay')
                ->andWhere('p.date <= :lastDay')
                ->setParameter('firstDay', $firstDayOfMonth)
                ->setParameter('lastDay', $lastDayOfMonth)
                ->getQuery()
                ->getResult());
                
            // Calculate average presence per employee
            $avgPresencePerEmployee = $employesCount > 0 ? $presenceCount / $employesCount : 0;
            $presenceData[] = round($avgPresencePerEmployee, 1); // Round to 1 decimal place
            
            // Calculate average absence per employee
            $workingDays = 20; // Moyenne de jours ouvrables par mois
            $expectedAttendance = $workingDays; // Per employee
            $avgAbsencePerEmployee = max(0, min($expectedAttendance - $avgPresencePerEmployee, $expectedAttendance * 0.3)); // Plafonner à 30%
            $absenceData[] = round($avgAbsencePerEmployee, 1); // Round to 1 decimal place
        }
        
        // Calculate average values across all 4 months
        $avgPresence = count($presenceData) > 0 ? array_sum($presenceData) / count($presenceData) : 0;
        $avgAbsence = count($absenceData) > 0 ? array_sum($absenceData) / count($absenceData) : 0;
        $totalAvg = $avgPresence + $avgAbsence;
        $presenceRate = $totalAvg > 0 ? round(($avgPresence / $totalAvg) * 100) : 0;
        $absenceRate = 100 - $presenceRate;
        
        // Récupérer les pointages récents
        $recentPointages = $pointageRepository->findBy([], ['date' => 'DESC'], 4);
        
        // Récupérer les données pour la répartition par département
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
        $today->setTime(0, 0, 0); // Set to beginning of day
        
        // Get all employees except admins
        $allEmployes = $employeRepository->findAll();
        $employeStatusToday = [];
        
        // Check status for each employee today
        foreach ($allEmployes as $employe) {
            // Skip admin users
            if ($employe->getPoste() === 'Admin') {
                continue;
            }
            
            $todayPointage = $pointageRepository->findOneBy([
                'employe' => $employe,
                'date' => $today
            ]);
            
            $status = 'Absent';
            if ($todayPointage) {
                if ($todayPointage->getHeureSortie()) {
                    // If both entry and exit times are recorded
                    $status = 'Présent';
                } elseif ($todayPointage->getHeureEntree()) {
                    // If only entry time is recorded
                    $entryTime = $todayPointage->getHeureEntree();
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
        
        return $this->render('home/charges.html.twig', [
            'paies' => $paieRepository->findAll(),
            'employesCount' => $employesCount,
            'fichesCount' => $fichesCount,
            'questionnairesCount' => $questionnairesCount,
            'months' => $months,
            'presenceData' => $presenceData,
            'absenceData' => $absenceData,
            'presenceRate' => $presenceRate,
            'absenceRate' => $absenceRate,
            'recentPointages' => $recentPointages,
            'departementLabels' => $departementLabels,
            'departementData' => $departementData,
            'demandesEnAttente' => $demandesCount,
            'demandesEnAttenteListe' => $demandesEnAttenteListe,
            'candidatsCount' => $candidatsCount,
            'paiesTraitees' => $paiesTraitees,
            'employeStatusToday' => $employeStatusToday
        ]);
    }

    #[Route('/ouvrier', name: 'ouvrier_home')]
    public function ouvrier(): Response
    {
        return $this->render('home/ouvrier.html.twig');
    }

    #[Route('/facturation', name: 'facturation_home')]
    public function facturation(): Response
    {
        return $this->render('home/facturation.html.twig');
    }
}