<?php

namespace App\Controller;

use App\Entity\Employe;
use App\Entity\FicheEmploye;
use App\Repository\FicheEmployeRepository;
use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot_password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        FicheEmployeRepository $ficheEmployeRepo,
        MailerInterface $mailer,
        PasswordResetService $resetService
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $ficheEmploye = $ficheEmployeRepo->findOneBy(['email' => $email]);

            if ($ficheEmploye && $ficheEmploye->getEmploye()) {
                $token = $resetService->generateToken($ficheEmploye->getEmploye());
                
                $email = (new Email())
                    ->from('noreply@yourdomain.com')
                    ->to($ficheEmploye->getEmail())
                    ->subject('Password Reset Request')
                    ->html($this->renderView(
                        'emails/password_reset.html.twig',
                        ['token' => $token]
                    ));

                $mailer->send($email);
                $this->addFlash('success', 'Password reset link sent to your email');
                return $this->redirectToRoute('app_login');
            }

            $this->addFlash('error', 'Email not found or no associated account');
        }

        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        PasswordResetService $resetService
    ): Response {
        $employe = $resetService->getEmployeByToken($token);

        if (!$employe) {
            $this->addFlash('error', 'Invalid or expired token');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Passwords do not match');
            } else {
                $resetService->updatePassword($employe, $newPassword);
                $this->addFlash('success', 'Password updated successfully');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
            'email' => $employe->getFicheEmploye()?->getEmail() ?? ''
        ]);
    }
}