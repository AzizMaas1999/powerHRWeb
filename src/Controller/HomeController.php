<?php

namespace App\Controller;

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