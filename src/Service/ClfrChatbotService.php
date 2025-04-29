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

    public function __construct(
        HttpClientInterface $httpClient, 
        ClfrRepository $clfrRepository
    ) {
        $this->httpClient = $httpClient;
        $this->clfrRepository = $clfrRepository;
    }

    public function processMessage(string $userMessage, ?int $clfrId = null): string
    {
        // Vérifier si l'utilisateur a spécifié un CLFR par son nom ou son ID
        $foundClfrId = $clfrId;
        if ($foundClfrId === null) {
            $foundClfrId = $this->findClfrIdFromMessage($userMessage);
        }
        
        // Traitement de la demande d'informations CLFR
        $userMessageLower = strtolower($userMessage);
        if (strpos($userMessageLower, 'afficher les informations') !== false || 
            strpos($userMessageLower, 'informations clfr') !== false ||
            strpos($userMessageLower, 'voir les informations') !== false ||
            strpos($userMessageLower, 'détails du clfr') !== false ||
            strpos($userMessageLower, 'afficher son information') !== false ||
            strpos($userMessageLower, 'afficher ses informations') !== false ||
            strpos($userMessageLower, 'voir son information') !== false ||
            strpos($userMessageLower, 'voir ses informations') !== false ||
            strpos($userMessageLower, 'détails') !== false ||
            strpos($userMessageLower, 'information') !== false) {
            
            // Si un CLFR est spécifié, afficher ses informations
            if ($foundClfrId !== null) {
                return $this->getClfrInfo($foundClfrId);
            } else {
                // Sinon, afficher la liste des CLFR
                return $this->getClfrList();
            }
        }

        // Traitement de la demande d'aide
        if (strpos($userMessageLower, 'help') !== false || 
            strpos($userMessageLower, 'aide') !== false ||
            strpos($userMessageLower, 'comment') !== false) {
            return $this->getHelpMessage();
        }
        
        // Si un CLFR est spécifié mais que la demande n'est pas claire
        if ($foundClfrId !== null) {
            $clfr = $this->clfrRepository->find($foundClfrId);
            if ($clfr) {
                return "Vous avez sélectionné le CLFR : {$clfr->getNom()}. Que souhaitez-vous faire ?\n" .
                       "- Afficher les informations du CLFR\n" .
                       "- Obtenir de l'aide";
            }
        }
        
        $context = $this->getContext($foundClfrId);
        
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

    private function getHelpMessage(): string
    {
        return "Voici comment utiliser le système CLFR :\n\n" .
               "1. Pour ajouter un nouveau CLFR :\n" .
               "   - Allez dans la section 'CLFR'\n" .
               "   - Cliquez sur 'Ajouter un nouveau CLFR'\n" .
               "   - Remplissez le formulaire avec les informations du client/fournisseur\n" .
               "   - Cliquez sur 'Enregistrer'\n\n" .
               "2. Pour modifier un CLFR :\n" .
               "   - Trouvez le CLFR dans la liste\n" .
               "   - Cliquez sur le bouton 'Modifier'\n" .
               "   - Modifiez les informations nécessaires\n" .
               "   - Cliquez sur 'Enregistrer'\n\n" .
               "3. Pour voir les informations d'un CLFR :\n" .
               "   - Sélectionnez le CLFR dans la liste\n" .
               "   - Demandez 'Afficher les informations du CLFR'";
    }

    private function findClfrIdFromMessage(string $message): ?int
    {
        // Vérifier si le message contient un ID entre parenthèses
        if (preg_match('/\(ID: (\d+)\)/', $message, $matches)) {
            return (int)$matches[1];
        }
        
        // Vérifier si le message contient juste un ID
        if (preg_match('/^(\d+)$/', $message, $matches)) {
            return (int)$matches[1];
        }
        
        // Vérifier si le message contient un nom de CLFR
        $clfrs = $this->clfrRepository->findAll();
        foreach ($clfrs as $clfr) {
            if (stripos($message, $clfr->getNom()) !== false) {
                return $clfr->getId();
            }
        }
        
        return null;
    }

    private function getSystemPrompt(): string
    {
        return "Vous êtes un assistant spécialisé dans la gestion des clients et fournisseurs. 
                Vous pouvez aider avec :
                - Les informations sur les clients et fournisseurs
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

    private function getClfrList(): string
    {
        $clfrs = $this->clfrRepository->findAll();
        if (empty($clfrs)) {
            return "Aucun CLFR n'est disponible dans la base de données.";
        }

        $response = "Veuillez spécifier un CLFR parmi la liste suivante :\n\n";
        foreach ($clfrs as $clfr) {
            $response .= "- {$clfr->getNom()} (ID: {$clfr->getId()})\n";
        }
        
        return $response;
    }

    private function getClfrInfo(?int $clfrId): string
    {
        if (!$clfrId) {
            return $this->getClfrList();
        }

        $clfr = $this->clfrRepository->find($clfrId);
        if (!$clfr) {
            return "CLFR non trouvé.";
        }

        return "Informations sur {$clfr->getNom()}:\n" .
               "- Type: " . ucfirst($clfr->getType()) . "\n" .
               "- Matricule fiscal: {$clfr->getMatriculeFiscale()}\n" .
               "- Adresse: {$clfr->getAdresse()}\n" .
               "- Téléphone: {$clfr->getNumTel()}\n" .
               "- Employé responsable: " . ($clfr->getEmploye() ? $clfr->getEmploye()->getNom() : "Non assigné");
    }
} 