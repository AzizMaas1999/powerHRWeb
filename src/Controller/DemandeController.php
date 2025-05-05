<?php

namespace App\Controller;
use App\Entity\Employe;
use App\Entity\Demande;
use App\Form\DemandeType;
use App\Repository\DemandeRepository;
use App\Repository\EmployeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_OUVRIER')]
#[IsGranted('ROLE_CHARGE')]

#[Route('/demande')]
final class DemandeController extends AbstractController
{ #[Route(name: 'app_demande_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $typeRecherche = $request->query->get('type');

        $qb = $entityManager->createQueryBuilder();
        $qb->select('d')
           ->from(Demande::class, 'd')
           ->where('d.employe = :employe')
           ->setParameter('employe', $user);

        if ($typeRecherche) {
            $qb->andWhere('LOWER(d.type) LIKE :type')
               ->setParameter('type', '%' . strtolower($typeRecherche) . '%');
        }

        $demandes = $qb->getQuery()->getResult();

        // Calcul seulement pour les demandes VALIDÉES
        $totalJoursConges = 0;
        foreach ($demandes as $demande) {
            if ($demande->getStatus() === 'Valider' && $demande->getDateDebut() && $demande->getDateFin()) {
                $totalJoursConges += $demande->getDateDebut()->diff($demande->getDateFin())->days;
            }
        }

        return $this->render('demande/index.html.twig', [
            'demandes' => $demandes,
            'totalJoursConges' => $totalJoursConges,
            'typeRecherche' => $typeRecherche,
        ]);
    }
    
    
    
    
    // src/Repository/DemandeRepository.php

    public function findByTypeInsensitive(?string $type): array
    {
        $qb = $this->createQueryBuilder('d');
    
        if ($type) {
            $qb->where('LOWER(d.type) LIKE :type')
               ->setParameter('type', '%' . strtolower($type) . '%');
        }
    
        return $qb->getQuery()->getResult();
    }
    

    #[Route('/new', name: 'app_demande_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
    
        $demande = new Demande();
        $demande->setDateCreation(new \DateTime());
        $demande->setStatus('En Attente');
        $demande->setEmploye($user);
    
        $form = $this->createForm(DemandeType::class, $demande);
        $form->handleRequest($request);
    
        // On récupère seulement les demandes VALIDÉES
        $demandesValidees = $entityManager->getRepository(Demande::class)->findBy([
            'employe' => $user,
            'status' => 'Valider'
        ]);
    
        $totalJoursConges = 0;
        foreach ($demandesValidees as $d) {
            if ($d->getDateDebut() && $d->getDateFin()) {
                $totalJoursConges += $d->getDateDebut()->diff($d->getDateFin())->days;
            }
        }
    
        if ($form->isSubmitted() && $form->isValid()) {
            $dateDebut = $demande->getDateDebut();
            $dateFin = $demande->getDateFin();
    
            if ($dateDebut && $dateFin) {
                $daysDiff = $dateDebut->diff($dateFin)->days;
    
                if (($totalJoursConges + $daysDiff) > 21) {
                    $this->addFlash('danger', 'Erreur : Vous dépassez la limite de 21 jours de congé autorisés.');
                    return $this->redirectToRoute('app_demande_new', [], Response::HTTP_SEE_OTHER);
                }
            }
    
            $entityManager->persist($demande);
            $entityManager->flush();
    
            $this->addFlash('success', 'Demande créée avec succès.');
            return $this->redirectToRoute('app_demande_index');
        }
    
        return $this->render('demande/new.html.twig', [
            'form' => $form->createView(),
            'demande' => $demande,
            'totalJoursConges' => $totalJoursConges,
        ]);
    }
    


    #[Route('/liste/attente', name: 'app_demande_liste_attente', methods: ['GET'])]
    public function listeAttente(DemandeRepository $demandeRepository): Response
    {
        $demandesEnAttente = $demandeRepository->findBy(['status' => 'En Attente']);
    
        return $this->render('demande/listeDR.html.twig', [
            'demandes' => $demandesEnAttente,
        ]);
    }

    #[Route('/show/{id}', name: 'app_demande_show', methods: ['GET'])]
    public function show(Demande $demande): Response
    {
        return $this->render('demande/show.html.twig', [
            'demande' => $demande,
        ]);
    }
    

    #[Route('/{id}/edit', name: 'app_demande_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('info', 'Demande modifiée avec succès.');

            return $this->redirectToRoute('app_demande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('demande/edit.html.twig', [
            'demande' => $demande,
            'form' => $form,
        ]);
    }

    #[Route('/del/{id}', name: 'app_demande_delete', methods: ['POST'])]
    public function delete(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$demande->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($demande);
            $entityManager->flush();

            $this->addFlash('danger', 'Demande supprimée avec succès.');
        }

        return $this->redirectToRoute('app_demande_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/{id}/valider', name: 'app_demande_valider', methods: ['POST'])]
    public function valider(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('Valider' . $demande->getId(), $request->getPayload()->getString('_token'))) {
            $employe = $demande->getEmploye();
    
            // Calculer le total des jours déjà validés
            $demandesValidees = $entityManager->getRepository(Demande::class)->findBy([
                'employe' => $employe,
                'status' => 'Valider'
            ]);
    
            $totalJoursConges = 0;
            foreach ($demandesValidees as $demandeValidee) {
                if ($demandeValidee->getDateDebut() && $demandeValidee->getDateFin()) {
                    $totalJoursConges += $demandeValidee->getDateDebut()->diff($demandeValidee->getDateFin())->days;
                }
            }
    
            // Calculer la durée de la demande qu'on veut valider
            $nouvelleDuree = 0;
            if ($demande->getDateDebut() && $demande->getDateFin()) {
                $nouvelleDuree = $demande->getDateDebut()->diff($demande->getDateFin())->days;
            }
    
            // Vérifier si on dépasse 21 jours
            if (($totalJoursConges + $nouvelleDuree) > 21) {
                $this->addFlash('danger', 'Erreur : Vous ne pouvez pas dépasser 21 jours de congés validés.');
                return $this->redirectToRoute('app_demande_liste_attente');
            }
    
            // Si tout est OK : valider la demande
            $demande->setStatus('Valider');
            $entityManager->flush();
    
            $this->addFlash('success', 'Demande validée avec succès.');
        }
    
        return $this->redirectToRoute('app_demande_liste_attente');
    }
    
    
    #[Route('/{id}/refuser', name: 'app_demande_refuser', methods: ['POST'])]
    public function refuser(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('Refuser' . $demande->getId(), $request->getPayload()->getString('_token'))) {
            $demande->setStatus('Refuser');
            $entityManager->flush();
    
            $this->addFlash('danger', 'Demande refusée.');
        }
    
        return $this->redirectToRoute('app_demande_liste_attente');
    }
        
    
    #[Route('/holidays', name: 'app_demande_holidays', methods: ['GET'])]
public function holidays(): Response
{
    $apiKey = 'N7UBNmURg6UslVb8yYS2uw==o8Wub6wy1lBttEVO';
    $client = HttpClient::create();

    $response = $client->request('GET', 'https://api.api-ninjas.com/v1/holidays', [
        'query' => [
            'country' => 'tn', 
           
        ],
        'headers' => [
            'X-Api-Key' => $apiKey,
        ],
    ]);

    if ($response->getStatusCode() === 200) {
        $holidays = $response->toArray();
        return $this->render('demande/holidays.html.twig', [
            'holidays' => $holidays,
        ]);
    } else {
        return new Response('Erreur lors de la récupération des jours fériés.', $response->getStatusCode());
    }
}
#[Route('/generate-qr', name: 'generate_qr_code')]
    public function generateQrCode(): Response
    {
        $url = 'http://192.168.137.104:8000/demande/holidays'; // Changez l'URL selon vos besoins

        // Créer un QR Code
        $qrCode = new QrCode($url);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        // Retourner l'image PNG dans la réponse HTTP
        return new Response($result->getString(), 200, [
            'Content-Type' => 'image/png',
        ]);
    }


}
