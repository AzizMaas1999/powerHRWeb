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
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;

#[Route('/home')]
class HomeController extends AbstractController
{
    #[Route('/directeur', name: 'directeur_home')]
    public function directeur(
        ManagerRegistry $doctrine,
        DemandeRepository $demandeRepository,
        EmployeRepository $employeRepository,
        DepartementRepository $departementRepository,
        CandidatRepository $candidatRepository,
        \App\Repository\FactureRepository $factureRepository,
        \App\Repository\EntrepriseRepository $entrepriseRepository,
        \App\Repository\FeedbackRepository $feedbackRepository
    ): Response
    {
        // Nombre de demandes avec statut "En Attente"
        $nbDemandesEnAttente = count($demandeRepository->findBy(['status' => 'En Attente']));
        
        // Nombre total d'employés actifs
        $employesCount = count($employeRepository->findAll());
        
        // Chiffre d'affaires (somme des montants des factures)
        $factures = $factureRepository->findAll();
        $chiffreAffaires = 0;
        foreach ($factures as $facture) {
            $chiffreAffaires += $facture->getTotal();
        }
        
        // Récupérer les activités récentes
        $activitesRecentes = [];
        
        // Demandes récentes
        $demandesRecentes = $demandeRepository->findBy([], ['id' => 'DESC'], 3);
        foreach ($demandesRecentes as $demande) {
            $employe = $demande->getEmploye();
            $ficheEmploye = $employe ? $employe->getFicheEmploye() : null;
            $nom = $ficheEmploye ? $ficheEmploye->getNom() : 'N/A';
            $prenom = $ficheEmploye ? $ficheEmploye->getPrenom() : '';
            $departementNom = ($employe && $employe->getDepartement()) ? $employe->getDepartement()->getNom() : 'N/A';
            
            $activitesRecentes[] = [
                'type' => 'demande',
                'icon' => 'mdi-file-document',
                'iconBg' => 'bg-primary',
                'title' => 'Nouvelle demande de ' . $demande->getType(),
                'description' => $employe ? $nom . ' ' . $prenom . ' - ' . $departementNom : 'N/A',
                'status' => $demande->getStatus(),
                'statusClass' => $demande->getStatus() === 'En Attente' ? 'text-danger' : ($demande->getStatus() === 'Approuvé' ? 'text-success' : 'text-info'),
                'date' => $demande->getDateCreation()
            ];
        }
        
        // Factures récentes
        $facturesRecentes = $factureRepository->findBy([], ['date' => 'DESC'], 3);
        foreach ($facturesRecentes as $facture) {
            $activitesRecentes[] = [
                'type' => 'facture',
                'icon' => 'mdi-cash',
                'iconBg' => 'bg-success',
                'title' => 'Facture #' . $facture->getId() . ($facture->getTypeFact() === 'Payée' ? ' payée' : ' créée'),
                'description' => 'Client: ' . ($facture->getClfr_id() ? 'ID: ' . $facture->getClfr_id() : 'N/A'),
                'status' => $facture->getTypeFact() ?: 'En attente',
                'statusClass' => $facture->getTypeFact() === 'Payée' ? 'text-success' : ($facture->getTypeFact() === 'En attente' ? 'text-warning' : 'text-info'),
                'date' => $facture->getDate()
            ];
        }
        
        // Employés récents
        $employesRecents = $employeRepository->findBy([], [], 2);
        foreach ($employesRecents as $employe) {
            $ficheEmploye = $employe ? $employe->getFicheEmploye() : null;
            $nom = $ficheEmploye ? $ficheEmploye->getNom() : 'N/A';
            $prenom = $ficheEmploye ? $ficheEmploye->getPrenom() : '';
            $departementNom = $employe->getDepartement() ? $employe->getDepartement()->getNom() : 'N/A';
            
            $activitesRecentes[] = [
                'type' => 'employe',
                'icon' => 'mdi-account-check',
                'iconBg' => 'bg-info',
                'title' => 'Employé enregistré',
                'description' => $nom . ' ' . $prenom . ' - ' . $departementNom,
                'status' => 'Traité',
                'statusClass' => 'text-info',
                'date' => new \DateTime() // Utilisez la date actuelle comme alternative
            ];
        }
        
        // Trier les activités par date (plus récentes en premier)
        usort($activitesRecentes, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });
        
        // Limiter à 4 activités
        $activitesRecentes = array_slice($activitesRecentes, 0, 4);
        
        // Récupérer les données pour la répartition par département
        $departements = $departementRepository->findAll();
        $departementLabels = [];
        $departementData = [];
        $departementColors = [
            'rgba(75, 192, 192, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(255, 99, 132, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
            'rgba(201, 203, 207, 0.8)'
        ];
        $departementBorderColors = [
            'rgba(75, 192, 192, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(255, 99, 132, 1)',
            'rgba(153, 102, 255, 1)',
            'rgba(255, 159, 64, 1)',
            'rgba(201, 203, 207, 1)'
        ];
        
        foreach ($departements as $index => $departement) {
            $departementLabels[] = $departement->getNom();
            $departementData[] = count($departement->getEmployes());
        }
        
        // Département le plus performant (celui avec le plus d'employés pour le moment)
        $maxEmployes = 0;
        $departementPerformant = null;
        foreach ($departements as $departement) {
            $nbEmployes = count($departement->getEmployes());
            if ($nbEmployes > $maxEmployes) {
                $maxEmployes = $nbEmployes;
                $departementPerformant = $departement;
            }
        }
        
        // Statistiques pour l'accès rapide
        $nbDepartements = count($departements);
        $nbEntreprises = count($entrepriseRepository->findAll());
        // Use the custom method that doesn't reference the statut field
        $nbCandidatures = count($candidatRepository->findAllWithoutStatus());
        // Count all feedbacks instead of filtering by a non-existent 'lu' field
        $nbFeedbacks = count($feedbackRepository->findAll());
        
        return $this->render('home/directeur.html.twig', [
            'nbDemandesEnAttente' => $nbDemandesEnAttente,
            'employesCount' => $employesCount,
            'chiffreAffaires' => $chiffreAffaires,
            'activitesRecentes' => $activitesRecentes,
            'departementLabels' => $departementLabels,
            'departementData' => $departementData,
            'departementColors' => $departementColors,
            'departementBorderColors' => $departementBorderColors,
            'departementPerformant' => $departementPerformant ? $departementPerformant->getNom() : 'N/A',
            'departementPerformantCroissance' => '+' . (mt_rand(5, 25) / 100),
            'nbDepartements' => $nbDepartements,
            'nbEntreprises' => $nbEntreprises,
            'nbCandidatures' => $nbCandidatures,
            'nbFeedbacks' => $nbFeedbacks
        ]);
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
    public function ouvrier(
        PointageRepository $pointageRepository,
        DemandeRepository $demandeRepository
    ): Response
    {
        // Get the current logged-in user
        $user = $this->getUser();
        
        // Use the repository method for calculating total hours
        $totalHeures = $pointageRepository->calculateTotalHoursForUser($user->getId());
        
        // Get basic counts
        $demandesCount = count($demandeRepository->findBy(['employe' => $user]));
        
        // Get recent pointages using the repository method
        $pointages = $pointageRepository->findRecentPointagesForUser($user->getId(), 5);
        
        // Check for active pointage using the repository method
        $pointageEnCours = $pointageRepository->findActivePointageForUser($user->getId());
        
        return $this->render('home/ouvrier.html.twig', [
            'pointages' => $pointages,
            'demandesCount' => $demandesCount,
            'pointageEnCours' => $pointageEnCours,
            'totalHeures' => $totalHeures,
            'tachesToday' => [] // Placeholder
        ]);
    }

    #[Route('/facturation', name: 'facturation_home')]
    public function facturation(\App\Repository\FactureRepository $factureRepository, \App\Repository\PaiementRepository $paiementRepository): Response
    {
        // Calculate total revenue (chiffre d'affaires) - sum of all invoice totals
        $factures = $factureRepository->findAll();
        $chiffreAffaires = 0;
        foreach ($factures as $facture) {
            $chiffreAffaires += $facture->getTotal();
        }
        
        // Count invoices with pending status
        $facturesEnAttente = count($factureRepository->findBy(['typeFact' => 'En attente']));
        
        // Count recent payments to verify - using date field instead of dateCreation
        $paiementsRecents = count($paiementRepository->findBy([], ['date' => 'DESC'], 5));
        
        // Get recent invoices
        $facturesRecentes = $factureRepository->findBy([], ['date' => 'DESC'], 5);
        
        // Prepare monthly data for chart
        $currentYear = (int)(new \DateTime())->format('Y');
        $moisLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'];
        $donneesMensuelles = [];
        
        // Generate monthly revenue data
        for ($month = 1; $month <= 12; $month++) {
            $firstDay = new \DateTime("$currentYear-$month-01");
            $lastDay = clone $firstDay;
            $lastDay->modify('last day of this month');
            
            // Find invoices for this month
            $monthlyInvoices = $factureRepository->createQueryBuilder('f')
                ->andWhere('f.date >= :firstDay')
                ->andWhere('f.date <= :lastDay')
                ->setParameter('firstDay', $firstDay)
                ->setParameter('lastDay', $lastDay)
                ->getQuery()
                ->getResult();
                
            // Calculate total for the month
            $monthlyTotal = 0;
            foreach ($monthlyInvoices as $invoice) {
                $monthlyTotal += $invoice->getTotal();
            }
            
            $donneesMensuelles[] = $monthlyTotal;
        }
        
        // Calculate total amount for current month
        $currentMonth = (int)(new \DateTime())->format('n');
        $montantTotal = $donneesMensuelles[$currentMonth - 1];
        
        return $this->render('home/facturation.html.twig', [
            'chiffreAffaires' => $chiffreAffaires,
            'facturesEnAttente' => $facturesEnAttente,
            'paiementsRecents' => $paiementsRecents,
            'facturesRecentes' => $facturesRecentes,
            'moisLabels' => json_encode($moisLabels),
            'donneesMensuelles' => json_encode($donneesMensuelles),
            'montantTotal' => $montantTotal
        ]);
    }
}