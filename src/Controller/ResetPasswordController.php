<?php

namespace App\Controller;

use App\Entity\Employe;
use App\Entity\FicheEmploye;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager,
        private \App\Service\SendBulkEmailService $sendBulkEmailService
    ){}

    /**
     * Display & process form to request a password reset.
     */
    #[Route('', name: 'app_forgot_password_request')]
    public function request(Request $request, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $email */
            $email = $form->get('ficheEmploye')->getData();

            return $this->processSendingPasswordResetEmail($email, $translator);
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form,
        ]);
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator, ?string $token = null): Response
    {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();

        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            /** @var Employe $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Encode(hash) the plain password, and set it.
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $this->entityManager->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form,
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData, TranslatorInterface $translator): Response
    {
        // First find the FicheEmploye by email
        $ficheEmploye = $this->entityManager->getRepository(FicheEmploye::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        // If no FicheEmploye found or no associated Employe, redirect silently
        if (!$ficheEmploye || !$ficheEmploye->getEmploye()) {
            return $this->redirectToRoute('app_check_email');
        }

        $user = $ficheEmploye->getEmploye();

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->redirectToRoute('app_check_email');
        }

        $body = $this->renderView('reset_password/email.html.twig', [
            'resetToken' => $resetToken,
        ]);

        try {
            $response = $this->sendBulkEmailService->sendOtpEmail(
                $ficheEmploye->getEmail(),
                'Your password reset request',
                $body
            );

            if (!isset($response['success']) || !$response['success']) {
                throw new \Exception('Failed to send email: ' . ($response['message'] ?? 'Unknown error'));
            }

            // Store the token object in session for retrieval in check-email route.
            $this->setTokenObjectInSession($resetToken);

            return $this->redirectToRoute('app_check_email');
        } catch (\Exception $e) {
            $this->addFlash('reset_password_error', sprintf(
                'There was a problem sending your password reset email - %s',
                $e->getMessage()
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }
    }
}
