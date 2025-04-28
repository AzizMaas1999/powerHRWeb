<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Twilio\Rest\Client;

class PhoneVerificationController extends AbstractController
{
    #[Route('/api/send-verification', name: 'api_send_verification', methods: ['POST'])]
    public function sendVerification(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $phoneNumber = $data['phone_number'] ?? null;

        if (!$phoneNumber) {
            return $this->json(['success' => false, 'message' => 'Phone number is required'], 400);
        }

        $sid = $_ENV['TWILIO_ACCOUNT_SID'];
        $token = $_ENV['TWILIO_AUTH_TOKEN'];
        $verifySid = $_ENV['TWILIO_VERIFY_SID'];
        $client = new Client($sid, $token);

        try {
            $verification = $client->verify->v2->services($verifySid)
                ->verifications
                ->create($phoneNumber, "sms");

            return $this->json(['success' => $verification->status === "pending"]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/verify-code', name: 'api_verify_code', methods: ['POST'])]
    public function verifyCode(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $phoneNumber = $data['phone_number'] ?? null;
        $code = $data['code'] ?? null;

        if (!$phoneNumber || !$code) {
            return $this->json(['success' => false, 'message' => 'Phone number and code are required'], 400);
        }

        $sid = $_ENV['TWILIO_ACCOUNT_SID'];
        $token = $_ENV['TWILIO_AUTH_TOKEN'];
        $verifySid = $_ENV['TWILIO_VERIFY_SID'];
        $client = new Client($sid, $token);

        try {
            $verification = $client->verify->v2->services($verifySid)
                ->verificationChecks
                ->create([
                    "to" => $phoneNumber,
                    "code" => $code
                ]);

            return $this->json(['success' => $verification->status === "approved"]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
} 