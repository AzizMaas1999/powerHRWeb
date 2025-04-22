<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CHARGE')]

final class ChargeHomeController extends AbstractController
{
    #[Route('/charges/home', name: 'app_charges_home')]
    public function index(): Response
    {
        return $this->render('charge_home/index.html.twig', [
            'controller_name' => 'ChargeHomeController',
        ]);
    }
    #[Route('/paie', name: 'app_paie_index')]
    public function paie(): Response
    {
        return $this->render('paie/index.html.twig', [
            'controller_name' => 'PaieController',
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
    #[Route('/fiche/employe', name: 'app_fiche_employe_index')]
    public function ficheEmploye(): Response
    {
        return $this->render('fiche_employe/index.html.twig', [
            'controller_name' => 'FicheEmployeController',
        ]);
    }
    #[Route('/charge/home', name: 'app_charge_home')]
    public function logout(): Response
    {
        return $this->render('charge_home/index.html.twig', [
            'controller_name' => 'ChargeHomeController',
        ]);
    }
    

}
