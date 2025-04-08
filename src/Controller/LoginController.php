<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        $user = $this->getUser();

        if ($user) {
            dump('User authenticated!'); // Debugging: user is authenticated
            exit;  // Stop execution here to prevent page refresh

            // Get the user's roles
            $roles = $user->getRoles(); 
            dump('User roles: ', $roles);  // Debugging: print user roles
            exit;

            // Redirect based on user roles
            if (in_array('ROLE_ADMIN', $roles)) {
                dump('Redirecting to admin dashboard');
                exit;
                return $this->redirectToRoute('app_dashboard_admin');
            }

            if (in_array('ROLE_DIRECTEUR', $roles)) {
                dump('Redirecting to directeur home');
                exit;
                return $this->redirectToRoute('app_directeur_home');
            }

            if (in_array('ROLE_OUVRIER', $roles)) {
                dump('Redirecting to ouvrier home');
                exit;
                return $this->redirectToRoute('app_ouvrier_home');
            }

            if (in_array('ROLE_CHARGES', $roles)) {
                dump('Redirecting to charge home');
                exit;
                return $this->redirectToRoute('app_charge_home');
            }

            if (in_array('ROLE_FACTURATION', $roles)) {
                dump('Redirecting to facturation home');
                exit;
                return $this->redirectToRoute('app_facturation_home');
            }

            // Fallback if no role matched
            dump('No role matched, redirecting to login');
            exit;
            return $this->redirectToRoute('app_login');
        }

        // If not authenticated, show login form
        dump('User not authenticated, showing login form');
        exit;
        return $this->render('home/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }
}
