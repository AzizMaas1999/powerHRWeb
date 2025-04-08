<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DirecteurHomeController extends AbstractController
{
    #[Route('/directeur/home', name: 'app_directeur_home')]
    public function index(): Response
    {
        return $this->render('directeur_home/index.html.twig', [
            'controller_name' => 'DirecteurHomeController',
        ]);
    }
    #[Route('/demande', name: 'app_demande_index')]
    public function demande(): Response
    {
        return $this->render('demande/index.html.twig', [
            'controller_name' => 'DemandeController',
        ]);
    }
    #[Route('/employe', name: 'app_employe_index')]
    public function employe(): Response
    {
        return $this->render('employe/index.html.twig', [
            'controller_name' => 'EmployeController',
        ]);
    }
    #[Route('/questionnaire', name: 'app_questionnaire_index')]
    public function questionnaire(): Response
    {
        return $this->render('questionnaire/index.html.twig', [
            'controller_name' => 'QuestionnaireController',
        ]);
    }

}
