<?php

namespace App\Controller;

use App\Entity\Paie;
use App\Entity\Employe;
use App\Form\PaieType;
use App\Repository\PaieRepository;
use App\Repository\EmployeRepository;
use App\Repository\PointageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use DateTime;

#[IsGranted('ROLE_ADMIN')]
#[Route('/paieadmin')]
final class PaieAdminController extends AbstractController
{
    #[Route('/', name: 'app_paieadmin_index', methods: ['GET'])]
    public function index(PaieRepository $paieRepository): Response
    {
        return $this->render('paieadmin/index.html.twig', [
            'paies' => $paieRepository->findBy([], ['annee' => 'DESC', 'mois' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_paieadmin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $paie = new Paie();
        $form = $this->createForm(PaieType::class, $paie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($paie);
            $entityManager->flush();

            $this->addFlash('success', 'La fiche de paie a été créée avec succès.');
            return $this->redirectToRoute('app_paieadmin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('paieadmin/new.html.twig', [
            'paie' => $paie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_paieadmin_show', methods: ['GET'])]
    public function show(Paie $paie): Response
    {
        // Récupération des pointages liés à cette paie pour calculer les heures travaillées
        $pointages = $paie->getPointages();
        
        // Récupérer l'employé associé à travers le premier pointage (si disponible)
        $employe = null;
        if (!$pointages->isEmpty()) {
            $employe = $pointages->first()->getEmploye();
        }
        
        return $this->render('paieadmin/show.html.twig', [
            'paie' => $paie,
            'employe' => $employe,
            'pointages' => $pointages,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_paieadmin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Paie $paie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PaieType::class, $paie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La fiche de paie a été modifiée avec succès.');
            return $this->redirectToRoute('app_paieadmin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('paieadmin/edit.html.twig', [
            'paie' => $paie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_paieadmin_delete', methods: ['POST'])]
    public function delete(Request $request, Paie $paie, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$paie->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($paie);
            $entityManager->flush();
            
            $this->addFlash('success', 'La fiche de paie a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_paieadmin_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/employe', name: 'app_showEmployePaieadmin_index', methods: ['GET'])]
    public function showEmp(EmployeRepository $employeRepository, PointageRepository $pointageRepository, PaieRepository $paieRepository): Response
    {
        return $this->render('paieadmin/showEmp.html.twig', [
            'employes' => $employeRepository->findAll(),
            'pointages' => $pointageRepository->findAll(),
            'paies' => $paieRepository->findAll(),
        ]);
    }

    #[Route('/generate/{id}', name: 'app_paieadmin_generate', methods: ['GET', 'POST'])]
    public function generatePaie(
        Employe $employe,
        Request $request,
        EntityManagerInterface $entityManager,
        PointageRepository $pointageRepository
    ): Response
    {
        // Get selected month & year from request, or use current month
        $month = $request->query->get('month', (new DateTime())->format('F'));
        $year = $request->query->getInt('year', (new DateTime())->format('Y'));
        
        // Check if a paie already exists for this employee/month/year
        $existingPaie = $entityManager->getRepository(Paie::class)->findOneBy([
            'mois' => $month,
            'annee' => $year
        ]);
        
        if ($existingPaie) {
            $this->addFlash('warning', "Une fiche de paie existe déjà pour {$employe->getUsername()} pour {$month} {$year}");
            return $this->redirectToRoute('app_showEmployePaieadmin_index');
        }
        
        // Calculate salary based on hours worked
        $firstDayOfMonth = new DateTime("{$year}-" . date('m', strtotime($month)) . "-01");
        $lastDayOfMonth = clone $firstDayOfMonth;
        $lastDayOfMonth->modify('last day of this month');
        
        $pointages = $pointageRepository->findByEmployeAndDateRange(
            $employe,
            $firstDayOfMonth,
            $lastDayOfMonth
        );
        
        $totalHours = 0;
        foreach ($pointages as $pointage) {
            if ($pointage->getHeureEntree() && $pointage->getHeureSortie()) {
                $entry = $pointage->getHeureEntree();
                $exit = $pointage->getHeureSortie();
                
                // Calculate hours worked
                $interval = $entry->diff($exit);
                $hours = $interval->h + ($interval->i / 60);
                
                $totalHours += $hours;
            }
        }
        
        // Calculate salary based on hours (assuming 160 hours per month as standard)
        $baseSalary = $employe->getSalaire();
        $hourlyRate = $baseSalary / 160;
        $calculatedSalary = $totalHours * $hourlyRate;
        
        // Create new paie
        $paie = new Paie();
        $paie->setMontant($calculatedSalary)
             ->setNbJour(count($pointages))
             ->setMois($month)
             ->setAnnee($year);
        
        $entityManager->persist($paie);
        $entityManager->flush();
        
        $this->addFlash('success', "La fiche de paie de {$employe->getUsername()} pour {$month} {$year} a été générée avec succès");
        
        return $this->redirectToRoute('app_paieadmin_show', [
            'id' => $paie->getId()
        ]);
    }

    #[Route('/genererAutomatique', name: 'app_paieadmin_generate_auto', methods: ['GET'])]
    public function genererAutomatique(
        EntityManagerInterface $entityManager,
        EmployeRepository $employeRepository,
        PointageRepository $pointageRepository
    ): Response
    {
        // Définir mois et année pour la génération (par défaut, mois en cours)
        $currentDate = new DateTime();
        $month = $currentDate->format('F');
        $year = $currentDate->format('Y');
        
        // Récupérer tous les employés
        $employes = $employeRepository->findAll();
        $paieRepository = $entityManager->getRepository(Paie::class);
        
        $totalPaieCount = 0;
        
        // Pour chaque employé
        foreach ($employes as $employe) {
            // Vérifier si une fiche de paie existe déjà pour ce mois
            $existingPaie = $paieRepository->findOneBy([
                'mois' => $month,
                'annee' => $year
            ]);
            
            if (!$existingPaie) {
                // Calculer les heures travaillées pour le mois
                $firstDayOfMonth = new DateTime("{$year}-" . date('m', strtotime($month)) . "-01");
                $lastDayOfMonth = clone $firstDayOfMonth;
                $lastDayOfMonth->modify('last day of this month');
                
                $pointages = $pointageRepository->findByEmployeAndDateRange(
                    $employe,
                    $firstDayOfMonth,
                    $lastDayOfMonth
                );
                
                $totalHours = 0;
                foreach ($pointages as $pointage) {
                    if ($pointage->getHeureEntree() && $pointage->getHeureSortie()) {
                        $entry = $pointage->getHeureEntree();
                        $exit = $pointage->getHeureSortie();
                        
                        // Calculate hours worked
                        $interval = $entry->diff($exit);
                        $hours = $interval->h + ($interval->i / 60);
                        
                        $totalHours += $hours;
                    }
                }
                
                // Calculer le salaire brut (supposons un taux horaire de 15)
                $hourlyRate = $employe->getSalaire() ?: 15;
                $grossSalary = $totalHours * $hourlyRate;
                
                // Créer la paie
                $paie = new Paie();
                $paie->setMontant($grossSalary)
                     ->setNbJour(count($pointages))
                     ->setMois($month)
                     ->setAnnee($year);
                
                $entityManager->persist($paie);
                $totalPaieCount++;
                
                // Associate pointages with this paie
                foreach ($pointages as $pointage) {
                    $pointage->setPaie($paie);
                }
            }
        }
        
        $entityManager->flush();
        
        if ($totalPaieCount > 0) {
            $this->addFlash('success', "$totalPaieCount fiches de paie ont été générées avec succès");
        } else {
            $this->addFlash('info', "Toutes les fiches de paie pour le mois en cours ont déjà été générées");
        }
        
        return $this->redirectToRoute('app_paieadmin_index');
    }
    
    #[Route('/genererMoisPrecedents', name: 'app_paieadmin_generate_previous_months', methods: ['GET'])]
    public function genererMoisPrecedents(
        EntityManagerInterface $entityManager,
        EmployeRepository $employeRepository,
        PointageRepository $pointageRepository
    ): Response
    {
        // Générer pour les 3 derniers mois
        $totalPaieCount = 0;
        $currentDate = new DateTime();
        
        for ($i = 1; $i <= 3; $i++) {
            $previousMonth = clone $currentDate;
            $previousMonth->modify("-{$i} month");
            
            $month = $previousMonth->format('F');
            $year = $previousMonth->format('Y');
            
            // Récupérer tous les employés
            $employes = $employeRepository->findAll();
            $paieRepository = $entityManager->getRepository(Paie::class);
            
            // Pour chaque employé
            foreach ($employes as $employe) {
                // Vérifier si une fiche de paie existe déjà pour ce mois
                $existingPaie = $paieRepository->findOneBy([
                    'mois' => $month,
                    'annee' => $year
                ]);
                
                if (!$existingPaie) {
                    // Calculer les heures travaillées pour le mois
                    $firstDayOfMonth = new DateTime("{$year}-" . date('m', strtotime($month)) . "-01");
                    $lastDayOfMonth = clone $firstDayOfMonth;
                    $lastDayOfMonth->modify('last day of this month');
                    
                    $pointages = $pointageRepository->findByEmployeAndDateRange(
                        $employe,
                        $firstDayOfMonth,
                        $lastDayOfMonth
                    );
                    
                    if (count($pointages) == 0) {
                        continue; // Skip if no pointages for this month
                    }
                    
                    $totalHours = 0;
                    foreach ($pointages as $pointage) {
                        if ($pointage->getHeureEntree() && $pointage->getHeureSortie()) {
                            $entry = $pointage->getHeureEntree();
                            $exit = $pointage->getHeureSortie();
                            
                            // Calculate hours worked
                            $interval = $entry->diff($exit);
                            $hours = $interval->h + ($interval->i / 60);
                            
                            $totalHours += $hours;
                        }
                    }
                    
                    // Create paie
                    $paie = new Paie();
                    $paie->setMontant($employe->getSalaire() * ($totalHours / 160))
                         ->setNbJour(count($pointages))
                         ->setMois($month)
                         ->setAnnee($year);
                    
                    $entityManager->persist($paie);
                    $totalPaieCount++;
                }
            }
            
            $this->addFlash('success', "Total: $totalPaieCount paies ont été générées pour les mois précédents");
        }
        
        $entityManager->flush();
        
        return $this->redirectToRoute('app_paieadmin_index');
    }
    
    #[Route('/fiche', name: 'app_paieadmin_fiche_employe', methods: ['GET'])]
    public function ficheEmploye(EmployeRepository $employeRepository): Response
    {
        return $this->render('paieadmin/fiche_employe.html.twig', [
            'employes' => $employeRepository->findBy([], ['username' => 'ASC']),
        ]);
    }
}