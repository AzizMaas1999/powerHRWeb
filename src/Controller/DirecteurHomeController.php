<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/directeur')]
final class DirecteurHomeController extends AbstractController
{
    #[Route('/home', name: 'app_directeur_home')]
    public function index(): Response
    {
        return $this->render('directeur_home/index.html.twig', [
            'controller_name' => 'DirecteurHomeController',
        ]);
    }

}
