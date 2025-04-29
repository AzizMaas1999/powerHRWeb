<?php

namespace App\Controller;

use App\Service\ClfrChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClfrChatbotController extends AbstractController
{
    private $chatbotService;

    public function __construct(ClfrChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    #[Route('/clfr/chat/{clfrId?}', name: 'app_clfr_chat', methods: ['GET'])]
    public function index(?int $clfrId = null): Response
    {
        return $this->render('clfr_chatbot/index.html.twig', [
            'clfrId' => $clfrId
        ]);
    }

    #[Route('/clfr/chat/send', name: 'app_clfr_chat_send', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';
        $clfrId = $data['clfrId'] ?? null;

        if (empty($message)) {
            return new JsonResponse(['error' => 'Message vide'], 400);
        }

        $response = $this->chatbotService->processMessage($message, $clfrId);
        return new JsonResponse(['response' => $response]);
    }
} 