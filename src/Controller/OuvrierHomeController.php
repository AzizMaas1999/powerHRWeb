<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_OUVRIER')]

final class OuvrierHomeController extends AbstractController
{
    #[Route('/ouvrier/home', name: 'app_ouvrier_home')]
    public function index(): Response
    {   dump($this->getUser());
        return $this->render('ouvrier_home/index.html.twig', [
            'controller_name' => 'OuvrierHomeController',
        ]);
    }
}
