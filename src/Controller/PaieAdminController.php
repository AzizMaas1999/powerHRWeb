<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use DateTime;
use App\Entity\Paie;
use App\Entity\Employe;
use App\Form\PaieType;
use App\Repository\PaieRepository;
use App\Repository\PointageRepository;
use App\Repository\EmployeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

#[IsGranted('ROLE_ADMIN')]
#[Route('/paieadmin')]
final class PaieAdminController extends AbstractController
{
    #[Route('/', name: 'app_paieadmin_index', methods: ['GET'])]
    public function index(Request $request, PaieRepository $paieRepository, PointageRepository $pointageRepository): Response
    {
        // Handle search
        $searchUsername = $request->query->get('search_username');
        
        if ($searchUsername) {
            $paies = $paieRepository->searchByUsername($searchUsername);
        } else {
            // Get all paies ordered by year and month DESC
            $paies = $paieRepository->findAllOrderedByYearAndMonthDesc();
        }
        
        // Handle AJAX requests
        if ($request->isXmlHttpRequest()) {
            return $this->render('paieadmin/_table.html.twig', [
                'paies' => $paies,
                'pointages' => $pointageRepository->findAll(
                    ['paie' => 'NOT NULL']
                ),
                'search_username' => $searchUsername,
            ]);
        }
        
        return $this->render('paieadmin/index.html.twig', [
            'paies' => $paies,
            'pointages' => $pointageRepository->findAll(
                ['paie' => 'NOT NULL']
            ),
            'search_username' => $searchUsername,
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

            $this->addFlash('success', 'Paie ajoutée avec succès.');
            return $this->redirectToRoute('app_paieadmin_index');
        }

        return $this->render('paieadmin/new.html.twig', [
            'paie' => $paie,
            'form' => $form,
        ]);
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
            'employe' => $employe,
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
        
        // Calculate gross salary (assuming hourly rate is 15)
        $hourlyRate = $employe->getSalaire() ?: 15;
        $grossSalary = $totalHours * $hourlyRate;
        
        // Apply deductions (example: 20% total deductions)
        $deductions = $grossSalary * 0.20;
        $netSalary = $grossSalary - $deductions;
        
        // Create new Paie record
        $paie = new Paie();
        $paie->setEmploye($employe)
             ->setMontantBrut($grossSalary)
             ->setMontantNet($netSalary)
             ->setDatePaiement(new DateTime())
             ->setMois($month)
             ->setAnnee($year)
             ->setHeuresTravaillees($totalHours)
             ->setMethodePaiement('Virement bancaire');
        
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
                'employe' => $employe,
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
                
                // Appliquer des déductions (exemple: 20% de déductions totales)
                $deductions = $grossSalary * 0.20;
                $netSalary = $grossSalary - $deductions;
                
                // Créer une nouvelle fiche de paie
                $paie = new Paie();
                $paie->setEmploye($employe)
                     ->setMontantBrut($grossSalary)
                     ->setMontantNet($netSalary)
                     ->setDatePaiement(new DateTime())
                     ->setMois($month)
                     ->setAnnee($year)
                     ->setHeuresTravaillees($totalHours)
                     ->setMethodePaiement('Virement bancaire');
                
                $entityManager->persist($paie);
                $totalPaieCount++;
            }
        }
        
        if ($totalPaieCount > 0) {
            $this->addFlash('success', "Total: $totalPaieCount paies ont été générées pour le mois en cours");
        } else {
            $this->addFlash('info', "Aucune nouvelle paie à générer pour ce mois.");
        }
        
        $entityManager->flush();
        
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
                    'employe' => $employe,
                    'mois' => $month,
                    'annee' => $year
                ]);
                
                if (!$existingPaie) {
                    // Créer une paie avec des données aléatoires pour les mois précédents
                    $totalHours = rand(130, 180);
                    $hourlyRate = $employe->getSalaire() ?: 15;
                    $grossSalary = $totalHours * $hourlyRate;
                    $deductions = $grossSalary * 0.20;
                    $netSalary = $grossSalary - $deductions;
                    
                    $paie = new Paie();
                    $paie->setEmploye($employe)
                         ->setMontantBrut($grossSalary)
                         ->setMontantNet($netSalary)
                         ->setDatePaiement(new DateTime($previousMonth->format('Y-m-d')))
                         ->setMois($month)
                         ->setAnnee($year)
                         ->setHeuresTravaillees($totalHours)
                         ->setMethodePaiement('Virement bancaire');
                    
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

    #[Route('/fiche/{id}/generer', name: 'app_paieadmin_fiche_generer', methods: ['GET'])]
    public function genererFichePaie(
        Employe $employe, 
        PaieRepository $paieRepository,
        Request $request
    ): Response
    {
        // Par défaut, utiliser le mois courant
        $selectedMonth = $request->query->get('month', (new \DateTime())->format('F'));
        $selectedYear = $request->query->getInt('year', (new \DateTime())->format('Y'));
        
        // Récupérer les paies pour cet employé pour le mois et année sélectionnés
        $paie = $paieRepository->findOneBy([
            'employe' => $employe,
            'mois' => $selectedMonth,
            'annee' => $selectedYear
        ]);
        
        if (!$paie) {
            $this->addFlash('warning', "Pas de fiche de paie disponible pour {$employe->getUsername()} - {$selectedMonth} {$selectedYear}");
            return $this->redirectToRoute('app_paieadmin_fiche_employe');
        }

        // Generate PDF
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        $html = $this->renderView('paieadmin/fiche_paie_pdf.html.twig', [
            'paie' => $paie,
            'employe' => $employe
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();
        
        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set(
            'Content-Disposition',
            "attachment; filename=\"fiche-paie-{$employe->getUsername()}-{$selectedMonth}-{$selectedYear}.pdf\""
        );
        
        return $response;
    }
    
    #[Route('/generer-toutes', name: 'app_paieadmin_generer_toutes', methods: ['GET'])]
    public function genererToutesLesFiches(
        PaieRepository $paieRepository,
        Request $request,
        MailerInterface $mailer
    ): Response
    {
        $selectedMonth = $request->query->get('month', (new \DateTime())->format('F'));
        $selectedYear = $request->query->getInt('year', (new \DateTime())->format('Y'));
        
        // Récupérer toutes les paies pour le mois et année sélectionnés
        $paies = $paieRepository->findBy([
            'mois' => $selectedMonth,
            'annee' => $selectedYear
        ]);
        
        if (empty($paies)) {
            $this->addFlash('warning', "Aucune fiche de paie disponible pour {$selectedMonth} {$selectedYear}");
            return $this->redirectToRoute('app_paieadmin_index');
        }
        
        $this->addFlash('success', count($paies) . " fiches de paie générées pour {$selectedMonth} {$selectedYear}");
        
        return $this->render('paieadmin/fiches_generees.html.twig', [
            'paies' => $paies,
            'month' => $selectedMonth,
            'year' => $selectedYear
        ]);
    }

    #[Route('/{id}', name: 'app_paieadmin_show', methods: ['GET'])]
    public function show(Paie $paie): Response
    {
        return $this->render('paieadmin/show.html.twig', [
            'paie' => $paie,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_paieadmin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Paie $paie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PaieType::class, $paie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Paie mise à jour avec succès.');
            return $this->redirectToRoute('app_paieadmin_index');
        }

        return $this->render('paieadmin/edit.html.twig', [
            'paie' => $paie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_paieadmin_delete', methods: ['POST'])]

    public function delete(Request $request, Paie $paie, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$paie->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($paie);
            $entityManager->flush();
            
            $this->addFlash('success', 'Paie supprimée avec succès.');
        }

        return $this->redirectToRoute('app_paieadmin_index');
    }
}