<?php

namespace App\Controller;

use App\Entity\Employe;
use App\Entity\FicheEmploye;
use App\Entity\Pointage;
use App\Entity\Questionnaire;
use App\Repository\EmployeRepository;
use App\Repository\FicheEmployeRepository;
use App\Repository\PointageRepository;
use App\Repository\QuestionnaireRepository;
use App\Repository\DepartementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/home')]
class HomeController extends AbstractController
{
    #[Route('/directeur', name: 'directeur_home')]
    public function directeur(): Response
    {
        return $this->render('home/directeur.html.twig');
    }

    #[Route('/charges', name: 'charges_home')]
    public function charges(
        EmployeRepository $employeRepository,
        FicheEmployeRepository $ficheEmployeRepository,
        PointageRepository $pointageRepository,
        QuestionnaireRepository $questionnaireRepository,
        DepartementRepository $departementRepository
    ): Response {
        // Get active employees count
        $activeEmployees = $employeRepository->count(['poste' => ['Ouvrier', 'Charges', 'Directeur']]);
        
        // Get pending forms count
        $pendingForms = $ficheEmployeRepository->count([]);
        
        // Get questionnaires count
        $questionnaires = $questionnaireRepository->count([]);
        
        // Get recent check-ins
        $recentCheckIns = $pointageRepository->findBy(
            [],
            ['date' => 'DESC', 'heureEntree' => 'DESC'],
            4
        );
        
        // Get department distribution
        $departments = $departementRepository->findAll();
        $departmentData = [];
        foreach ($departments as $department) {
            $employeeCount = $employeRepository->count(['departement' => $department]);
            if ($employeeCount > 0) {
                $departmentData[] = [
                    'name' => $department->getNom(),
                    'count' => $employeeCount
                ];
            }
        }
        
        // Get employee presence data for the last 6 months
        $presenceData = [];
        $absenceData = [];
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime("-$i months");
            $months[] = $date->format('M');
            
            $startDate = new \DateTime($date->format('Y-m-01'));
            $endDate = new \DateTime($date->format('Y-m-t'));
            
            $presenceCount = $pointageRepository->countPresences($startDate, $endDate);
            $totalDays = $endDate->format('t');
            $absenceCount = $totalDays - $presenceCount;
            
            $presenceData[] = $presenceCount;
            $absenceData[] = $absenceCount;
        }

        return $this->render('home/charges.html.twig', [
            'activeEmployees' => $activeEmployees,
            'pendingForms' => $pendingForms,
            'questionnaires' => $questionnaires,
            'recentCheckIns' => $recentCheckIns,
            'departmentData' => $departmentData,
            'presenceData' => $presenceData,
            'absenceData' => $absenceData,
            'months' => $months
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