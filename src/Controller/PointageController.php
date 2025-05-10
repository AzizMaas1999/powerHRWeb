<?php

namespace App\Controller;

use App\Entity\Pointage;
use App\Entity\Employe;
use App\Form\PointageType;
use App\Repository\PointageRepository;
use App\Repository\EmployeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


#[Route('/pointage')]
final class PointageController extends AbstractController
{
    #[Route(name: 'app_pointage_index', methods: ['GET'])]
    public function index(PointageRepository $pointageRepository, Request $request): Response
    {
        // Récupérer le paramètre de recherche de date
        $searchDate = $request->query->get('search_date');
        
        if ($searchDate) {
            // Recherche par date spécifique
            try {
                $date = new \DateTime($searchDate);
                $pointages = $pointageRepository->findBy(['date' => $date], ['date' => 'DESC']);
            } catch (\Exception $e) {
                // En cas d'erreur de format de date, ne rien filtrer
                $this->addFlash('warning', 'Format de date invalide. Utilisez le format YYYY-MM-DD.');
                $pointages = $pointageRepository->findBy([], ['date' => 'DESC']);
            }
        } else {
            // Sans filtre, afficher tous les pointages triés par date
            $pointages = $pointageRepository->findBy([], ['date' => 'DESC']);
        }
        
        return $this->render('pointage/index.html.twig', [
            'pointages' => $pointages,
            'search_date' => $searchDate,
        ]);
    }

    #[Route('/pointageentree', name: 'app_pointageEntree', methods: ['GET', 'POST'])]
    public function pointageEntree(EntityManagerInterface $entityManager, PointageRepository $pointageRepository, Request $request): Response
    {
        $pointage = new Pointage();
        $pointageexiste = $pointageRepository->findOneBy([
            'employe' => $this->getUser(),
            'date' => new \DateTime('now', new \DateTimeZone('Africa/Tunis')),
            'heureSortie' => null,
        ]);
        if ($pointageexiste) {
            $this->addFlash('danger', 'Vous avez déjà pointé votre entrée aujourd\'hui!');
            return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
        }
        else {
            $pointage->setDate(new \DateTime('now', new \DateTimeZone('Africa/Tunis')));
            $pointage->setHeureEntree(new \DateTime('now', new \DateTimeZone('Africa/Tunis')));
            $pointage->setHeureSortie(null);
            $pointage->setPaie(null);
            $employe = $this->getUser(); 
            $pointage->setEmploye($employe);

            $entityManager->persist($pointage);
            $entityManager->flush();
            $this->addFlash('success', 'Pointage d\'entrée enregistré avec succès!');
            return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
        }
    }

    #[Route('/pointagesortie', name: 'app_pointageSortie', methods: ['GET', 'POST'])]
    public function pointageSortie(EntityManagerInterface $entityManager, PointageRepository $pointageRepository, Request $request): Response
    {
        $pointageexiste = $pointageRepository->findOneBy([
            'employe' => $this->getUser(),
            'date' => new \DateTime('now', new \DateTimeZone('Africa/Tunis')),
        ]);
        if ($pointageexiste) {
            if ($pointageexiste->getHeureSortie() === null) {
                $pointageexiste->setHeureSortie(new \DateTime('now', new \DateTimeZone('Africa/Tunis')));
                $entityManager->flush();
                $this->addFlash('success', 'Pointage de sortie enregistré avec succès!');
                return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
            }
            else {
                $this->addFlash('danger', 'Vous avez déjà pointé votre sortie aujourd\'hui!');
                return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
            }
            
        }
        else {
            $this->addFlash('danger', 'Vous n\'avez pas encore pointé votre entrée aujourd\'hui!');
            return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
        }
    }

    #[Route('/new/{id}', name: 'app_pointage_new', methods: ['GET', 'POST'])]
    public function new(int $id, EmployeRepository $employeRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        
        $pointage = new Pointage();
        $employe = $employeRepository->find($id);
        if ($employe) {
            $pointage->setEmploye($employe);
        }
        $pointage->setPaie(null);
        $form = $this->createForm(PointageType::class, $pointage);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pointage);
            $entityManager->flush();

            return $this->redirectToRoute('app_pointage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pointage/new.html.twig', [
            'pointage' => $pointage,
            'form' => $form,
        ]);
    }

    #[Route('/csv', name: 'csv_pointage_all')]
    public function csv(Request $request, EntityManagerInterface $em): Response
    {
        $preview = [];
        $errors = [];
        
        // Handle import confirmation
        if ($request->isMethod('POST') && $request->request->has('import')) {
            // Save to DB
            $rows = json_decode($request->request->get('data'), true);
            $importCount = 0;
            
            foreach ($rows as $row) {
                $employe = $em->getRepository(Employe::class)->find($row['employe_id']);
                if (!$employe) {
                    $this->addFlash('warning', 'L\'employé avec ID ' . $row['employe_id'] . ' n\'existe pas.');
                    continue;
                }
                
                $pointage = new Pointage();
                
                try {
                    $pointage->setDate(new \DateTime($row['date']));
                    $pointage->setHeureEntree(new \DateTime($row['date'] . ' ' . $row['heureEntree']));
                    $pointage->setHeureSortie(new \DateTime($row['date'] . ' ' . $row['heureSortie']));
                    $pointage->setEmploye($employe);
                    
                    $em->persist($pointage);
                    $importCount++;
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Erreur lors de l\'importation d\'une ligne: ' . $e->getMessage());
                }
            }
            
            $em->flush();
            
            $this->addFlash('success', $importCount . ' pointages importés avec succès.');
            return $this->redirectToRoute('csv_pointage_all');
        }
        
        // Handle file upload for preview
        if ($request->isMethod('POST') && $request->files->get('csv_file')) {
            /** @var UploadedFile $file */
            $file = $request->files->get('csv_file');
            
            // Validate file type
            $validMimeTypes = ['text/csv', 'text/plain', 'application/vnd.ms-excel'];
            $ext = strtolower($file->getClientOriginalExtension());
            $mime = $file->getMimeType();
            
            if ($ext !== 'csv' || !in_array($mime, $validMimeTypes)) {
                $this->addFlash('danger', 'Seuls les fichiers CSV valides sont autorisés.');
                return $this->redirectToRoute('csv_pointage_all');
            }
            
            // Validate and process the CSV
            if (($handle = fopen($file->getPathname(), 'r')) !== false) {
                // Define expected headers
                $expectedHeaders = ['date', 'heureEntree', 'heureSortie', 'employe_id'];
                
                // Read header row
                $headers = fgetcsv($handle);
                
                // Validate headers
                if (count($headers) < 4) {
                    $this->addFlash('danger', 'Fichier invalide!!!');
                    fclose($handle);
                    return $this->redirectToRoute('csv_pointage_all');
                }
                
                // Initialize data validation patterns
                $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
                $timePattern = '/^\d{2}:\d{2}:\d{2}$/';
                $idPattern = '/^\d+$/';
                
                // Process data rows
                $lineNumber = 1; // Start after header
                while (($data = fgetcsv($handle)) !== false) {
                    $lineNumber++;
                    
                    // Skip empty rows
                    if (empty($data[0])) continue;
                    
                    // Check if row has all required fields
                    if (count($data) < 4) {
                        $errors[] = [
                            'line' => $lineNumber,
                            'message' => 'Fichier invalide!!!'
                        ];
                        continue;
                    }
                    
                    // Validate data
                    $date = trim($data[0]);
                    $heureEntree = trim($data[1]);
                    $heureSortie = trim($data[2]);
                    $employeId = trim($data[3]);
                    
                    $isValid = true;
                    $errorMessage = '';
                    
                    // Validate date format
                    if (!preg_match($datePattern, $date)) {
                        $isValid = false;
                        $errorMessage = 'Fichier invalide!!!';
                    }
                    // Validate time formats (now expecting HH:MM:SS)
                    elseif (!preg_match($timePattern, $heureEntree)) {
                        $isValid = false;
                        $errorMessage = 'Fichier invalide!!!';
                    }
                    elseif (!preg_match($timePattern, $heureSortie)) {
                        $isValid = false;
                        $errorMessage = 'Fichier invalide!!!';
                    }
                    // Validate employee ID
                    elseif (!preg_match($idPattern, $employeId)) {
                        $isValid = false;
                        $errorMessage = 'Fichier invalide!!!';
                    }
                    
                    // Optional: Validate employee existence
                    if ($isValid) {
                        $employe = $em->getRepository(Employe::class)->find($employeId);
                        if (!$employe) {
                            $isValid = false;
                            $errorMessage = 'Fichier invalide!!!';
                        }
                    }
                    
                    // Add valid rows to preview
                    if ($isValid) {
                        $preview[] = [
                            'date' => $date,
                            'heureEntree' => $heureEntree,
                            'heureSortie' => $heureSortie,
                            'employe_id' => $employeId
                        ];
                    } else {
                        $errors[] = [
                            'line' => $lineNumber,
                            'message' => $errorMessage
                        ];
                    }
                }
                
                fclose($handle);
                
                if (empty($preview)) {
                    $this->addFlash('danger', 'Le fichier ne contient aucune donnée valide.');
                    return $this->redirectToRoute('csv_pointage_all');
                }
            } else {
                $this->addFlash('danger', 'Impossible de lire le fichier CSV.');
                return $this->redirectToRoute('csv_pointage_all');
            }
        }
        
        return $this->render('pointage/newCsv.html.twig', [
            'preview' => $preview,
            'errors' => $errors
        ]);
    }

    #[Route('/employe', name: 'app_showEmploye_index', methods: ['GET'])]
    public function showEmp(EmployeRepository $employeRepository, PointageRepository $pointageRepository): Response
    {
        return $this->render('pointage/showEmp.html.twig', [
            'employes' => $employeRepository->findAll(),
            'pointages' => $pointageRepository->findAll(),
        ]);
    }
    
    #[Route('/show/{id}', name: 'app_pointage_show', methods: ['GET'])]
    public function show(Pointage $pointage): Response
    {
        return $this->render('pointage/show.html.twig', [
            'pointage' => $pointage,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pointage_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pointage $pointage, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PointageType::class, $pointage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_pointage_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pointage/edit.html.twig', [
            'pointage' => $pointage,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pointage_delete', methods: ['POST'])]
    public function delete(Request $request, Pointage $pointage, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pointage->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($pointage);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_pointage_index', [], Response::HTTP_SEE_OTHER);
    }
}
