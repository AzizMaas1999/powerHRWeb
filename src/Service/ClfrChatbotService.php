<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Repository\ClfrRepository;

class ClfrChatbotService
{
    private const API_KEY = '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';
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';
    
    private $httpClient;
    private $clfrRepository;

    public function __construct(HttpClientInterface $httpClient, ClfrRepository $clfrRepository)
    {
        $this->httpClient = $httpClient;
        $this->clfrRepository = $clfrRepository;
    }

    public function processMessage(string $userMessage, ?int $clfrId = null): string
    {
        $context = $this->getContext($clfrId);
        
        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . self::API_KEY,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'openai/gpt-3.5-turbo-16k',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->getSystemPrompt()
                        ],
                        [
                            'role' => 'user',
                            'content' => $context . "\n\nQuestion: " . $userMessage
                        ]
                    ]
                ]
            ]);

            $data = json_decode($response->getContent(), true);
            return $data['choices'][0]['message']['content'] ?? 'Désolé, je n\'ai pas pu obtenir une réponse.';
        } catch (\Exception $e) {
            return 'Erreur de communication avec l\'API: ' . $e->getMessage();
        }
    }

    private function getSystemPrompt(): string
    {
        return "Vous êtes un assistant spécialisé dans la gestion des clients et fournisseurs. 
                Vous pouvez aider avec :
                - Les informations sur les clients et fournisseurs
                - Les évaluations et feedbacks
                - Les demandes de rendez-vous
                - Les questions générales sur les services
                - L'assistance pour les problèmes courants
                
                Répondez de manière professionnelle et concise.";
    }

    private function getContext(?int $clfrId): string
    {
        if ($clfrId) {
            $clfr = $this->clfrRepository->find($clfrId);
            if ($clfr) {
                return "Contexte: Client/Fournisseur: {$clfr->getNom()}, 
                        Type: {$clfr->getType()}, 
                        Contact: {$clfr->getEmail()}";
            }
        }
        return "Contexte: Consultation générale";
    }
} 