<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\Persistence\ManagerRegistry; // <<< ajoute cette ligne en haut avec les autres "use"

#[Route('/home')]
class HomeController extends AbstractController
{
    #[Route('/directeur', name: 'directeur_home')]
    public function directeur(ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
    
        // Requête pour compter les demandes avec statut "En Attente"
        $query = $entityManager->createQuery(
            'SELECT COUNT(d.id) FROM App\Entity\Demande d WHERE d.status = :status'
        )->setParameter('status', 'En Attente');
    
        $nbDemandesEnAttente = $query->getSingleScalarResult();
    
        return $this->render('home/directeur.html.twig', [
            'nbDemandesEnAttente' => $nbDemandesEnAttente,
        ]);
    }

    #[Route('/charges', name: 'charges_home')]
    public function charges(): Response
    {
        return $this->render('home/charges.html.twig');
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