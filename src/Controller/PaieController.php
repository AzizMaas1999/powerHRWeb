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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/paie')]
final class PaieController extends AbstractController
{
    #[Route(name: 'app_paie_index', methods: ['GET'])]
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
            return $this->render('paie/_table.html.twig', [
                'paies' => $paies,
                'pointages' => $pointageRepository->findAll(
                    ['paie' => 'NOT NULL']
                ),
                'search_username' => $searchUsername,
            ]);
        }
        
        return $this->render('paie/index.html.twig', [
            'paies' => $paies,
            'pointages' => $pointageRepository->findAll(
                ['paie' => 'NOT NULL']
            ),
            'search_username' => $searchUsername,
        ]);
    }

    #[Route('/new', name: 'app_paie_new', methods: ['GET', 'POST'])]

    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $paie = new Paie();
        $form = $this->createForm(PaieType::class, $paie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($paie);
            $entityManager->flush();

            return $this->redirectToRoute('app_paie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('paie/new.html.twig', [
            'paie' => $paie,
            'form' => $form,
        ]);
    }

    #[Route('/employe', name: 'app_showEmployePaie_index', methods: ['GET'])]
    public function showEmp(EmployeRepository $employeRepository, PointageRepository $pointageRepository, PaieRepository $paieRepository): Response
    {
        return $this->render('paie/showEmp.html.twig', [
            'employes' => $employeRepository->findBy([], ['username' => 'ASC']),
            'pointages' => $pointageRepository->findAll(),
            'paies' => $paieRepository->findAll(),
        ]);
    }

    #[Route('/confirm/{id}/{nb}', name: 'app_confirmEmployePaie', methods: ['GET', 'POST'])]
    public function confirmPaie(int $id, int $nb, Request $request, EntityManagerInterface $entityManager, EmployeRepository $employeRepository): Response
    {
        $employe = $employeRepository->find($id);
        if (!$employe) {
            $this->addFlash('danger', 'Employé non trouvé');
            return $this->redirectToRoute('app_showEmployePaie_index');
        }
        
        // Calculate the payment amount
        $montant = $nb * ($employe->getSalaire() / 30);
        
        // Get current month name and year
        $currentDate = new DateTime();
        $currentYear = $currentDate->format('Y');
        
        // Get month name in French
        $moisFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        $currentMonthNum = (int)$currentDate->format('n');
        $currentMonth = $moisFr[$currentMonthNum];
        $currentDay = (int)$currentDate->format('d');
        
        // Process form submission
        if ($request->isMethod('POST')) {
            return $this->redirectToRoute('app_newEmployePaie', [
                'id' => $id,
                'nb' => $nb
            ]);
        }

        return $this->render('paie/confirm.html.twig', [
            'employe' => $employe,
            'nb_jours' => $nb,
            'absences' => $currentDay - $nb,
            'montant' => $montant,
            'mois' => $currentMonth,
            'annee' => $currentYear,
            'isCurrentMonth' => true
        ]);
    }

    #[Route('/newPaie/{id}/{nb}', name: 'app_newEmployePaie', methods: ['GET', 'POST'])]
    public function newPaie(
        int $id, 
        int $nb, 
        EntityManagerInterface $entityManager, 
        EmployeRepository $employeRepository, 
        PointageRepository $pointageRepository,
        \App\Service\RapidApiMailService $mailService
    ): Response
    {
        $paie = new Paie();
        $employe = $employeRepository->find($id);
        $paie->setNbJour($nb);
        $paie->setMontant($nb * ($employe->getSalaire() / 30));
        
        // Get month name in French
        $moisFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        $currentDate = new DateTime();
        $currentMonthNum = (int)$currentDate->format('n');
        $currentMonth = $moisFr[$currentMonthNum];
        $currentYear = $currentDate->format('Y');
        
        $paie->setMois($currentMonth);
        $paie->setAnnee($currentYear);
        $entityManager->persist($paie);
        
        $parameters = [
            'employe' => $employe,
        ];
        
        // Find pointages for current month
        $currentMonthStart = new DateTime(date('Y-m-01'));
        $currentMonthEnd = new DateTime(date('Y-m-t'));
        
        $pointages = $pointageRepository->findPointagesByEmployeAndDateRange(
            $employe, 
            $currentMonthStart, 
            $currentMonthEnd
        );
        
        if (!$pointages) {
            // Fallback to basic query if custom method not available
            $parameters['date'] = $currentDate->format('Y-m');
            $pointages = $pointageRepository->findBy($parameters);
        }
     
        foreach ($pointages as $p) {
            $p->setPaie($paie);       
        }
        
        $entityManager->flush();
        
        // Send email notification to employee if they have an email address
        $ficheEmploye = $employe->getFicheEmploye();
        if ($ficheEmploye && $ficheEmploye->getEmail()) {
            try {
                $result = $mailService->sendPaymentNotification(
                    $ficheEmploye->getEmail(),
                    $ficheEmploye->getPrenom() . ' ' . $ficheEmploye->getNom(),
                    $currentMonth,
                    $currentYear,
                    $paie->getMontant(),
                    $nb
                );
                
                if ($result['success']) {
                    $this->addFlash('success', 'Paiement créé avec succès pour ' . $employe->getUsername() . ' et notification envoyée par email.');
                } else {
                    $this->addFlash('success', 'Paiement créé avec succès pour ' . $employe->getUsername() . '.');
                    $this->addFlash('warning', 'L\'email de notification n\'a pas pu être envoyé.');
                }
            } catch (\Exception $e) {
                $this->addFlash('success', 'Paiement créé avec succès pour ' . $employe->getUsername() . '.');
                $this->addFlash('warning', 'L\'email de notification n\'a pas pu être envoyé: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('success', 'Paiement créé avec succès pour ' . $employe->getUsername() . '.');
            $this->addFlash('info', 'Aucun email de notification n\'a été envoyé car l\'employé n\'a pas d\'adresse email définie.');
        }
        
        return $this->redirectToRoute('app_paie_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/generate/current-month', name: 'app_paie_generate_current_month', methods: ['GET'])]

    public function generateCurrentMonthPaie(EntityManagerInterface $entityManager, EmployeRepository $employeRepository, PointageRepository $pointageRepository): Response
    {
        // Obtenir le mois et l'année actuels
        $currentDate = new DateTime();
        $currentMonthNum = (int)$currentDate->format('n');
        $currentYear = (int)$currentDate->format('Y');
        
        // Tableau de correspondance des noms de mois en français
        $moisFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        $currentMonth = $moisFr[$currentMonthNum];
        
        // Récupérer tous les employés
        $employes = $employeRepository->findAll();
        $paieCount = 0;
        
        foreach ($employes as $employe) {
            // Récupérer les pointages de l'employé pour le mois en cours en utilisant la nouvelle méthode
            $pointages = $pointageRepository->findByEmployeAndMonthYear($employe, $currentMonthNum, $currentYear);
            
            if (count($pointages) > 0) {
                // Vérifier si un des pointages a déjà une paie associée pour ce mois
                $existingPaie = false;
                foreach ($pointages as $pointage) {
                    if ($pointage->getPaie() && 
                        $pointage->getPaie()->getMois() === $currentMonth && 
                        $pointage->getPaie()->getAnnee() === (string)$currentYear) {
                        $existingPaie = true;
                        break;
                    }
                }
                
                if ($existingPaie) {
                    continue; // Passer à l'employé suivant s'il a déjà une paie
                }
                
                // Créer une nouvelle paie
                $paie = new Paie();
                $nbJours = count($pointages);
                
                $paie->setNbJour($nbJours);
                $paie->setMontant($nbJours * ($employe->getSalaire() / 30));
                $paie->setMois($currentMonth);
                $paie->setAnnee((string)$currentYear);
                
                $entityManager->persist($paie);
                
                // Associer les pointages à cette paie
                foreach ($pointages as $p) {
                    $p->setPaie($paie);
                }
                
                $paieCount++;
            }
        }
        
        $entityManager->flush();
        
        $this->addFlash('success', "$paieCount paies ont été générées pour $currentMonth $currentYear");
        return $this->redirectToRoute('app_paie_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/generate/all-previous', name: 'app_paie_generate_all_previous', methods: ['GET'])]
    public function generateAllPreviousPaies(EntityManagerInterface $entityManager, EmployeRepository $employeRepository, PointageRepository $pointageRepository): Response
    {
        // Tableau de correspondance des noms de mois en français
        $moisFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        // Date actuelle
        $currentDate = new DateTime();
        $currentMonthNum = (int)$currentDate->format('n');
        $currentYear = (int)$currentDate->format('Y');
        
        // Récupérer tous les employés
        $employes = $employeRepository->findAll();
        $totalPaieCount = 0;
        
        // Récupérer tous les pointages sans paie
        $pointagesSansPaie = $entityManager->getRepository(\App\Entity\Pointage::class)->findBy(['paie' => null]);
        
        // Grouper par mois/année
        $pointagesGroupes = [];
        foreach ($pointagesSansPaie as $pointage) {
            $date = $pointage->getDate();
            if (!$date) continue;
            
            $annee = (int)$date->format('Y');
            $mois = (int)$date->format('n');
            
            // Ignorer les mois futurs
            if (($annee > $currentYear) || ($annee == $currentYear && $mois >= $currentMonthNum)) {
                continue;
            }
            
            // Créer une clé unique pour ce mois/année
            $key = "$annee-$mois";
            if (!isset($pointagesGroupes[$key])) {
                $pointagesGroupes[$key] = [
                    'annee' => $annee,
                    'mois' => $mois
                ];
            }
        }
        
        // Trier les groupes par date (du plus ancien au plus récent)
        ksort($pointagesGroupes);
        
        foreach ($pointagesGroupes as $groupe) {
            $monthNum = $groupe['mois'];
            $year = $groupe['annee'];
            $monthName = $moisFr[$monthNum];
            $paieCount = 0;
            
            foreach ($employes as $employe) {
                // Récupérer les pointages de l'employé pour ce mois/année
                $pointages = $pointageRepository->findByEmployeAndMonthYear($employe, $monthNum, $year);
                
                if (count($pointages) > 0) {
                    // Vérifier si tous les pointages n'ont pas de paie associée
                    $allWithoutPaie = true;
                    foreach ($pointages as $pointage) {
                        if ($pointage->getPaie()) {
                            $allWithoutPaie = false;
                            break;
                        }
                    }
                    
                    if (!$allWithoutPaie) {
                        continue; // Passer à l'employé suivant si un des pointages a déjà une paie
                    }
                    
                    // Créer une nouvelle paie
                    $paie = new Paie();
                    $nbJours = count($pointages);
                    
                    $paie->setNbJour($nbJours);
                    $paie->setMontant($nbJours * ($employe->getSalaire() / 30));
                    $paie->setMois($monthName);
                    $paie->setAnnee((string)$year);
                    
                    $entityManager->persist($paie);
                    
                    // Associer les pointages à cette paie
                    foreach ($pointages as $p) {
                        $p->setPaie($paie);
                    }
                    
                    $paieCount++;
                }
            }
            
            $totalPaieCount += $paieCount;
            if ($paieCount > 0) {
                $this->addFlash('success', "$paieCount paies ont été générées pour $monthName $year");
            }
        }
        
        if ($totalPaieCount == 0) {
            $this->addFlash('info', "Aucune nouvelle paie n'a été générée. Toutes les paies des mois précédents ont déjà été traitées.");
        } else {
            $this->addFlash('success', "Total: $totalPaieCount paies ont été générées pour les mois précédents");
        }
        
        $entityManager->flush();
        
        return $this->redirectToRoute('app_paie_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/fiche', name: 'app_paie_fiche_employe', methods: ['GET'])]
    public function ficheEmploye(EmployeRepository $employeRepository): Response
    {
        return $this->render('paie/fiche_employe.html.twig', [
            'employes' => $employeRepository->findBy([], ['username' => 'ASC']),
        ]);
    }

    #[Route('/fiche/{id}/generer', name: 'app_paie_fiche_generer', methods: ['GET'])]
    public function genererFichePaie(
        Employe $employe, 
        PaieRepository $paieRepository,
        Request $request
    ): Response
    {
        // Par défaut, utiliser le mois courant
        $currentDate = new DateTime();
        $currentYear = $currentDate->format('Y');
        $currentMonth = (int)$currentDate->format('n');

        // Si un mois et une année spécifiques sont demandés
        $year = $request->query->get('annee', $currentYear);
        $month = $request->query->get('mois', $currentMonth);

        // Tableau de correspondance des noms de mois en français
        $moisFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        // Convertir le numéro du mois en nom si nécessaire
        if (is_numeric($month)) {
            $monthName = $moisFr[(int)$month];
        } else {
            $monthName = $month;
        }

        // Rechercher la paie correspondant à l'employé, au mois et à l'année
        $paie = $paieRepository->findOneBy([
            'mois' => $monthName,
            'annee' => $year
        ]);

        if (!$paie) {
            $this->addFlash('danger', 'Aucune fiche de paie trouvée pour ' . $employe->getUsername() . ' en ' . $monthName . ' ' . $year);
            return $this->redirectToRoute('app_paie_fiche_employe');
        }

        // Calculer les détails de la paie
        $salaireBase = $employe->getSalaire();
        $nbJours = $paie->getNbJour();
        $montantPaie = $paie->getMontant();
        $dateCreation = new DateTime();

        return $this->render('paie/fiche_detail.html.twig', [
            'employe' => $employe,
            'paie' => $paie,
            'salaireBase' => $salaireBase,
            'mois' => $monthName,
            'annee' => $year, 
            'dateCreation' => $dateCreation,
        ]);
    }

    #[Route('/fiche/{id}/pdf', name: 'app_paie_fiche_pdf', methods: ['GET'])]
    public function genererFichePaiePdf(
        Employe $employe, 
        PaieRepository $paieRepository,
        Request $request
    ): Response
    {
        // Par défaut, utiliser le mois courant
        $currentDate = new DateTime();
        $currentYear = $currentDate->format('Y');
        $currentMonth = (int)$currentDate->format('n');

        // Si un mois et une année spécifiques sont demandés
        $year = $request->query->get('annee', $currentYear);
        $month = $request->query->get('mois', $currentMonth);

        // Tableau de correspondance des noms de mois en français
        $moisFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        // Convertir le numéro du mois en nom si nécessaire
        if (is_numeric($month)) {
            $monthName = $moisFr[(int)$month];
        } else {
            $monthName = $month;
        }

        // Rechercher la paie correspondant à l'employé, au mois et à l'année
        $paie = $paieRepository->findOneBy([
            'mois' => $monthName,
            'annee' => $year
        ]);

        if (!$paie) {
            $this->addFlash('danger', 'Aucune fiche de paie trouvée pour ' . $employe->getUsername() . ' en ' . $monthName . ' ' . $year);
            return $this->redirectToRoute('app_paie_fiche_employe');
        }

        // Calculer les détails de la paie
        $salaireBase = $employe->getSalaire();
        $nbJours = $paie->getNbJour();
        $montantPaie = $paie->getMontant();
        $dateCreation = new DateTime();

        // Configure Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->setIsRemoteEnabled(true);
        
        // Instancier Dompdf
        $dompdf = new Dompdf($options);
        
        // Générer le HTML
        $html = $this->renderView('paie/fiche_pdf.html.twig', [
            'employe' => $employe,
            'paie' => $paie,
            'salaireBase' => $salaireBase,
            'mois' => $monthName,
            'annee' => $year, 
            'dateCreation' => $dateCreation,
        ]);

        // Charger le HTML dans Dompdf
        $dompdf->loadHtml($html);
        
        // Définir le format du papier
        $dompdf->setPaper('A4', 'portrait');
        
        // Rendu du PDF
        $dompdf->render();
        
        // Nom du fichier
        $filename = sprintf('fiche-paie-%s-%s-%s.pdf', 
            $employe->getUsername(), 
            $monthName, 
            $year
        );
        
        // Envoyer le PDF au navigateur
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }
    
    #[Route('/fiche/{id}/email', name: 'app_paie_fiche_email', methods: ['GET'])]
    public function envoyerFichePaieEmail(
        Employe $employe, 
        PaieRepository $paieRepository,
        MailerInterface $mailer,
        Request $request
    ): Response
    {
        // Par défaut, utiliser le mois courant
        $currentDate = new DateTime();
        $currentYear = $currentDate->format('Y');
        $currentMonth = (int)$currentDate->format('n');

        // Si un mois et une année spécifiques sont demandés
        $year = $request->query->get('annee', $currentYear);
        $month = $request->query->get('mois', $currentMonth);

        // Tableau de correspondance des noms de mois en français
        $moisFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        // Convertir le numéro du mois en nom si nécessaire
        if (is_numeric($month)) {
            $monthName = $moisFr[(int)$month];
        } else {
            $monthName = $month;
        }

        // Rechercher la paie correspondant à l'employé, au mois et à l'année
        $paie = $paieRepository->findOneBy([
            'mois' => $monthName,
            'annee' => $year
        ]);

        if (!$paie) {
            $this->addFlash('danger', 'Aucune fiche de paie trouvée pour ' . $employe->getUsername() . ' en ' . $monthName . ' ' . $year);
            return $this->redirectToRoute('app_paie_fiche_employe');
        }
        
        // Vérifier si l'employé a une fiche employé avec une adresse email
        $ficheEmploye = $employe->getFicheEmploye();
        if (!$ficheEmploye || !$ficheEmploye->getEmail()) {
            $this->addFlash('danger', 'L\'employé ' . $employe->getUsername() . ' n\'a pas d\'adresse email définie dans sa fiche.');
            return $this->redirectToRoute('app_paie_fiche_employe');
        }

        // Calculer les détails de la paie
        $salaireBase = $employe->getSalaire();
        $nbJours = $paie->getNbJour();
        $montantPaie = $paie->getMontant();
        $dateCreation = new DateTime();

        // Configure Dompdf pour générer le PDF
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->setIsRemoteEnabled(true);
        
        // Instancier Dompdf
        $dompdf = new Dompdf($options);
        
        // Générer le HTML
        $html = $this->renderView('paie/fiche_pdf.html.twig', [
            'employe' => $employe,
            'paie' => $paie,
            'salaireBase' => $salaireBase,
            'mois' => $monthName,
            'annee' => $year, 
            'dateCreation' => $dateCreation,
        ]);

        // Charger le HTML dans Dompdf
        $dompdf->loadHtml($html);
        
        // Définir le format du papier
        $dompdf->setPaper('A4', 'portrait');
        
        // Rendu du PDF
        $dompdf->render();
        
        // Nom du fichier
        $filename = sprintf('fiche-paie-%s-%s-%s.pdf', 
            $employe->getUsername(), 
            $monthName, 
            $year
        );
        
        // Stocker temporairement le PDF
        $pdfContent = $dompdf->output();
        $tempFilePath = sys_get_temp_dir() . '/' . $filename;
        file_put_contents($tempFilePath, $pdfContent);
        
        try {
            // Créer un email avec template
            $email = (new TemplatedEmail())
                ->from('rh@powerhr.com') // Generic address for development with Mailtrap
                ->to($ficheEmploye->getEmail())
                ->subject('Votre fiche de paie - ' . $monthName . ' ' . $year)
                ->htmlTemplate('paie/email/fiche_paie_email.html.twig')
                ->context([
                    'employe' => $employe,
                    'mois' => $monthName,
                    'annee' => $year,
                ])
                ->addPart(new DataPart(new File($tempFilePath), $filename, 'application/pdf'));
                
            // Envoyer l'email
            $mailer->send($email);
            
            // Supprimer le fichier temporaire
            unlink($tempFilePath);
            
            $this->addFlash('success', 'La fiche de paie a été envoyée à ' . $ficheEmploye->getEmail());
            
        } catch (\Exception $e) {
            // Supprimer le fichier temporaire en cas d'erreur
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
            
            $this->addFlash('danger', 'Impossible d\'envoyer l\'email: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_paie_fiche_employe');
    }
    
    #[Route('/fiche/{id}/email-annee', name: 'app_paie_fiche_email_annee', methods: ['GET'])]
    public function envoyerFichePaieEmailAnnee(
        Employe $employe, 
        PaieRepository $paieRepository,
        MailerInterface $mailer,
        Request $request
    ): Response
    {
        // Récupérer l'année demandée
        $currentYear = (new DateTime())->format('Y');
        $year = $request->query->get('annee', $currentYear);
        
        // Vérifier si l'employé a une fiche employé avec une adresse email
        $ficheEmploye = $employe->getFicheEmploye();
        if (!$ficheEmploye || !$ficheEmploye->getEmail()) {
            $this->addFlash('danger', 'L\'employé ' . $employe->getUsername() . ' n\'a pas d\'adresse email définie dans sa fiche.');
            return $this->redirectToRoute('app_paie_fiche_employe');
        }

        // Tableau de correspondance des noms de mois en français
        $moisFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        // Récupérer toutes les paies de l'année pour cet employé
        $paies = [];
        $fichesTrouvees = 0;
        
        foreach ($moisFr as $num => $nom) {
            $paie = $paieRepository->findOneBy([
                'mois' => $nom,
                'annee' => $year
            ]);
            
            if ($paie) {
                $fichesTrouvees++;
                $paies[$num] = [
                    'mois_nom' => $nom,
                    'mois_num' => $num,
                    'paie' => $paie
                ];
            }
        }
        
        if ($fichesTrouvees === 0) {
            $this->addFlash('danger', 'Aucune fiche de paie trouvée pour ' . $employe->getUsername() . ' en ' . $year);
            return $this->redirectToRoute('app_paie_fiche_employe');
        }
        
        // Calculer les détails pour toutes les paies
        $salaireBase = $employe->getSalaire();
        $totalMontant = 0;
        $totalJours = 0;
        $totalDeductions = 0;
        $totalNet = 0;
        
        foreach ($paies as &$item) {
            $paie = $item['paie'];
            $montant = $paie->getMontant();
            $totalMontant += $montant;
            $totalJours += $paie->getNbJour();
            
            $deductions = ($montant * 0.0918) + ($montant * 0.035); // CNSS + Assurance maladie
            $net = $montant - $deductions;
            
            $totalDeductions += $deductions;
            $totalNet += $net;
            
            $item['deductions'] = $deductions;
            $item['net'] = $net;
        }
        
        // Configure Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->setIsRemoteEnabled(true);
        
        // Instancier Dompdf
        $dompdf = new Dompdf($options);
        
        // Générer le HTML
        $html = $this->renderView('paie/fiche_pdf_annee.html.twig', [
            'employe' => $employe,
            'paies' => $paies,
            'salaireBase' => $salaireBase,
            'annee' => $year,
            'dateCreation' => new DateTime(),
            'totalMontant' => $totalMontant,
            'totalJours' => $totalJours,
            'totalDeductions' => $totalDeductions,
            'totalNet' => $totalNet,
            'fichesTrouvees' => $fichesTrouvees
        ]);
        
        // Charger le HTML dans Dompdf
        $dompdf->loadHtml($html);
        
        // Définir le format du papier
        $dompdf->setPaper('A4', 'portrait');
        
        // Rendu du PDF
        $dompdf->render();
        
        // Nom du fichier
        $filename = sprintf('fiche-paie-annuelle-%s-%s.pdf', $employe->getUsername(), $year);
        
        // Stocker temporairement le PDF
        $pdfContent = $dompdf->output();
        $tempFilePath = sys_get_temp_dir() . '/' . $filename;
        file_put_contents($tempFilePath, $pdfContent);
        
        try {
            // Créer un email avec template
            $email = (new TemplatedEmail())
                ->from('rh@powerhr.com') // Generic address for development with Mailtrap
                ->to($ficheEmploye->getEmail())
                ->subject('Votre récapitulatif annuel de paie - ' . $year)
                ->htmlTemplate('paie/email/fiche_paie_annee_email.html.twig')
                ->context([
                    'employe' => $employe,
                    'annee' => $year,
                    'totalMontant' => $totalMontant,
                    'totalNet' => $totalNet
                ])
                ->addPart(new DataPart(new File($tempFilePath), $filename, 'application/pdf'));
                
            // Envoyer l'email
            $mailer->send($email);
            
            // Supprimer le fichier temporaire
            unlink($tempFilePath);
            
            $this->addFlash('success', 'Le récapitulatif annuel de paie a été envoyé à ' . $ficheEmploye->getEmail());
            
        } catch (\Exception $e) {
            // Supprimer le fichier temporaire en cas d'erreur
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
            
            $this->addFlash('danger', 'Impossible d\'envoyer l\'email: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_paie_fiche_employe');
    }

    #[Route('/fiche/{id}/generer-annee', name: 'app_paie_fiche_generer_annee', methods: ['GET'])]
    public function genererFichePaieAnnee(
        Employe $employe, 
        PaieRepository $paieRepository,
        Request $request
    ): Response
    {
        // Récupérer l'année demandée
        $currentYear = (new DateTime())->format('Y');
        $year = $request->query->get('annee', $currentYear);
        
        // Tableau de correspondance des noms de mois en français
        $moisFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        // Récupérer toutes les paies de l'année pour cet employé
        $paies = [];
        $fichesTrouvees = 0;
        
        foreach ($moisFr as $num => $nom) {
            $paie = $paieRepository->findOneBy([
                'mois' => $nom,
                'annee' => $year
            ]);
            
            if ($paie) {
                $fichesTrouvees++;
                $paies[$num] = [
                    'mois_nom' => $nom,
                    'mois_num' => $num,
                    'paie' => $paie
                ];
            }
        }
        
        if ($fichesTrouvees === 0) {
            $this->addFlash('danger', 'Aucune fiche de paie trouvée pour ' . $employe->getUsername() . ' en ' . $year);
            return $this->redirectToRoute('app_paie_fiche_employe');
        }
        
        // Calculer les détails pour toutes les paies
        $salaireBase = $employe->getSalaire();
        $totalMontant = 0;
        $totalJours = 0;
        $totalDeductions = 0;
        $totalNet = 0;
        
        foreach ($paies as &$item) {
            $paie = $item['paie'];
            $montant = $paie->getMontant();
            $totalMontant += $montant;
            $totalJours += $paie->getNbJour();
            
            $deductions = ($montant * 0.0918) + ($montant * 0.035); // CNSS + Assurance maladie
            $net = $montant - $deductions;
            
            $totalDeductions += $deductions;
            $totalNet += $net;
            
            $item['deductions'] = $deductions;
            $item['net'] = $net;
        }
        
        return $this->render('paie/fiche_annee.html.twig', [
            'employe' => $employe,
            'paies' => $paies,
            'salaireBase' => $salaireBase,
            'annee' => $year,
            'dateCreation' => new DateTime(),
            'totalMontant' => $totalMontant,
            'totalJours' => $totalJours,
            'totalDeductions' => $totalDeductions,
            'totalNet' => $totalNet,
            'fichesTrouvees' => $fichesTrouvees
        ]);
    }
    
    #[Route('/fiche/{id}/pdf-annee', name: 'app_paie_fiche_pdf_annee', methods: ['GET'])]
    public function genererFichePaiePdfAnnee(
        Employe $employe, 
        PaieRepository $paieRepository,
        Request $request
    ): Response
    {
        // Récupérer l'année demandée
        $currentYear = (new DateTime())->format('Y');
        $year = $request->query->get('annee', $currentYear);
        
        // Tableau de correspondance des noms de mois en français
        $moisFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        // Récupérer toutes les paies de l'année pour cet employé
        $paies = [];
        $fichesTrouvees = 0;
        
        foreach ($moisFr as $num => $nom) {
            $paie = $paieRepository->findOneBy([
                'mois' => $nom,
                'annee' => $year
            ]);
            
            if ($paie) {
                $fichesTrouvees++;
                $paies[$num] = [
                    'mois_nom' => $nom,
                    'mois_num' => $num,
                    'paie' => $paie
                ];
            }
        }
        
        if ($fichesTrouvees === 0) {
            $this->addFlash('danger', 'Aucune fiche de paie trouvée pour ' . $employe->getUsername() . ' en ' . $year);
            return $this->redirectToRoute('app_paie_fiche_employe');
        }
        
        // Calculer les détails pour toutes les paies
        $salaireBase = $employe->getSalaire();
        $totalMontant = 0;
        $totalJours = 0;
        $totalDeductions = 0;
        $totalNet = 0;
        
        foreach ($paies as &$item) {
            $paie = $item['paie'];
            $montant = $paie->getMontant();
            $totalMontant += $montant;
            $totalJours += $paie->getNbJour();
            
            $deductions = ($montant * 0.0918) + ($montant * 0.035); // CNSS + Assurance maladie
            $net = $montant - $deductions;
            
            $totalDeductions += $deductions;
            $totalNet += $net;
            
            $item['deductions'] = $deductions;
            $item['net'] = $net;
        }
        
        // Configure Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->setIsRemoteEnabled(true);
        
        // Instancier Dompdf
        $dompdf = new Dompdf($options);
        
        // Générer le HTML
        $html = $this->renderView('paie/fiche_pdf_annee.html.twig', [
            'employe' => $employe,
            'paies' => $paies,
            'salaireBase' => $salaireBase,
            'annee' => $year,
            'dateCreation' => new DateTime(),
            'totalMontant' => $totalMontant,
            'totalJours' => $totalJours,
            'totalDeductions' => $totalDeductions,
            'totalNet' => $totalNet,
            'fichesTrouvees' => $fichesTrouvees
        ]);
        
        // Charger le HTML dans Dompdf
        $dompdf->loadHtml($html);
        
        // Définir le format du papier
        $dompdf->setPaper('A4', 'portrait');
        
        // Rendu du PDF
        $dompdf->render();
        
        // Nom du fichier
        $filename = sprintf('fiche-paie-annuelle-%s-%s.pdf', $employe->getUsername(), $year);
        
        // Envoyer le PDF au navigateur
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }

    #[Route('/{id}', name: 'app_paie_show', methods: ['GET'])]
    public function show(Paie $paie): Response
    {
        return $this->render('paie/show.html.twig', [
            'paie' => $paie,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_paie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Paie $paie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PaieType::class, $paie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_paie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('paie/edit.html.twig', [
            'paie' => $paie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_paie_delete', methods: ['POST'])]

    public function delete(Request $request, Paie $paie, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$paie->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($paie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_paie_index', [], Response::HTTP_SEE_OTHER);
    }
}
