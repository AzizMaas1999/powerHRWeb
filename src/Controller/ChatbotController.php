<?php

namespace App\Controller;

use OpenAI\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    #[Route('/chatbot', name: 'app_chatbot', methods: ['GET', 'POST'])]
    public function index(Request $request, Client $client): Response
    {
        $responseText = null;

        if ($request->isMethod('POST')) {
            $userMessage = $request->request->get('message');

            $result = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'Tu es un assistant pour la gestion des clients et fournisseurs.'],
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

            $responseText = $result->choices[0]->message->content ?? 'Aucune réponse générée.';
        }

        return $this->render('chatbot/index.html.twig', [
            'response' => $responseText,
        ]);
    }
}
