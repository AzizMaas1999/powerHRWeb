<?php // src/Security/LoginSuccessHandler.php
namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Routing\RouterInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {   
        $roles = $token->getRoleNames();
        
        if (in_array('ROLE_ADMIN', $roles)) {
            
            return new RedirectResponse($this->router->generate('app_dashboard_admin'));
        } elseif (in_array('ROLE_DIRECTEUR', $roles)) {
            return new RedirectResponse($this->router->generate('app_directeur_home'));
        } elseif (in_array('ROLE_FACTURATION', $roles)) {
            return new RedirectResponse($this->router->generate('app_facturation_home'));
        } elseif (in_array('ROLE_CHARGES', $roles)) {
            return new RedirectResponse($this->router->generate('app_charges_home'));
        } elseif (in_array('ROLE_OUVRIER', $roles)) {
            
            return new RedirectResponse($this->router->generate('app_ouvrier_home'));
        }

        return new RedirectResponse($this->router->generate('app_login'));
    }
}
