<?php
namespace App\ApiBundle\Controller;

use App\ApiBundle\Service\TwilioSmsVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SmsVerificationController extends AbstractController
{
    #[Route('/sms/send', name: 'api_sms_send', methods: ['POST'])]
    public function send(Request $request, TwilioSmsVerificationService $twilioService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $to = $data['to'] ?? null;
        if (!$to) {
            return $this->json(['error' => 'Missing "to" parameter'], 400);
        }
        try {
            $result = $twilioService->startVerification($to);
            return $this->json(['success' => true, 'result' => $result]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/sms/verify', name: 'api_sms_verify', methods: ['POST'])]
    public function verify(Request $request, TwilioSmsVerificationService $twilioService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $to = $data['to'] ?? null;
        $code = $data['code'] ?? null;
        if (!$to || !$code) {
            return $this->json([
                'error' => 'Missing "to" or "code" parameter',
                'debug' => $data
            ], 400);
        }
        try {
            $result = $twilioService->checkVerification($to, $code);
            return $this->json([
                'success' => $result['valid'] ?? false,
                'result' => $result,
                'debug' => [
                    'to' => $to,
                    'code' => $code
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'debug' => [
                    'to' => $to,
                    'code' => $code
                ]
            ], 500);
        }
    }
}
