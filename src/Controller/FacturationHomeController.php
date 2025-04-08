<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FacturationHomeController extends AbstractController
{
    #[Route('/facturation/home', name: 'app_facturation_home')]
    public function index(): Response
    {
        return $this->render('facturation_home/index.html.twig', [
            'controller_name' => 'FacturationHomeController',
        ]);
    }
}
