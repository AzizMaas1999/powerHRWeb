<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class TestMailController extends AbstractController
{
    #[Route('/test-mail', name: 'app_test_mail')]
    public function index(MailerInterface $mailer): Response
    {
        try {
            $email = (new Email())
                ->from('no-reply@powerhr.com')
                ->to('yassine.nawara007@email.com')
                ->subject('Test Email')
                ->text('This is a test email')
                ->html('<p>This is a test email</p>');

            $mailer->send($email);

            return new Response('Email sent successfully!');
        } catch (\Exception $e) {
            return new Response('Error sending email: ' . $e->getMessage());
        }
    }
} 