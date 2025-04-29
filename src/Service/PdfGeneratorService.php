<?php

namespace App\Service;

use App\Entity\Entreprise;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class PdfGeneratorService
{
    private string $apiKey;
    private string $templateId;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->apiKey = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJhNTE3ZjZjMmE3MDRmNmRlODBlOTZhN2RkMzYzMzVkMmNjYmUxYjc3ZDVlOTRiOGNiNzMwN2I5OTA3ZTIwYmZkIiwic3ViIjoibWFoYW5lamkyMDUwQGdtYWlsLmNvbSIsImV4cCI6MTc0NTg5NjIyNH0.1ixGRTqysk7mpoRvJ--18QEX6J1KJNfrptEe7ao5lDs';
        $this->templateId = '1388129';
        $this->logger = $logger;
    }

    public function generatePdf(Entreprise $entreprise): array
    {
        dump('Starting PDF generation for: ' . $entreprise->getNom());
        
        $curl = curl_init();
        
        // Get current date in the required format
        $currentDate = new \DateTime();
        $formattedDate = $currentDate->format('Y-m-d');

        $data = [
            'template' => [
                'id' => $this->templateId,
                'data' => [
                    'Name' => $entreprise->getNom(),
                    'DueDate' => $formattedDate
                ]
            ],
            'format' => 'pdf',
            'output' => 'url',
            'name' => 'Certificate Example'
        ];

        dump('Request data:', $data);

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://us1.pdfgeneratorapi.com/api/v4/documents/generate",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $this->apiKey,
                "Content-Type: application/json"
            ],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_VERBOSE => true
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        dump('HTTP Code:', $httpCode);
        dump('Response:', $response);
        dump('cURL Error:', $err ?: 'None');

        curl_close($curl);

        if ($err) {
            dump('Failed with cURL error:', $err);
            return ['success' => false, 'error' => 'Erreur réseau: ' . $err];
        }

        // Handle specific HTTP status codes
        switch ($httpCode) {
            case 401:
                dump('Authentication failed - API key may be invalid or expired');
                return ['success' => false, 'error' => 'Erreur d\'authentification: La clé API est invalide ou a expiré. Veuillez vérifier vos paramètres d\'API.'];
            case 403:
                dump('Access forbidden - insufficient permissions');
                return ['success' => false, 'error' => 'Accès refusé: Permissions insuffisantes pour générer le PDF.'];
            case 404:
                dump('Template not found');
                return ['success' => false, 'error' => 'Modèle de PDF introuvable. Veuillez vérifier l\'ID du modèle.'];
            case 429:
                dump('Rate limit exceeded');
                return ['success' => false, 'error' => 'Limite de requêtes dépassée. Veuillez réessayer plus tard.'];
            case 500:
                dump('Server error');
                return ['success' => false, 'error' => 'Erreur serveur: Le service de génération de PDF est temporairement indisponible.'];
        }

        // Accept both 200 and 201 as success codes
        if ($httpCode !== 200 && $httpCode !== 201) {
            dump('Failed with HTTP code:', $httpCode);
            return ['success' => false, 'error' => 'Erreur API: HTTP ' . $httpCode];
        }

        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            dump('Failed to decode JSON:', json_last_error_msg());
            return ['success' => false, 'error' => 'Format de réponse invalide'];
        }

        dump('Decoded response:', $result);

        // Check for both possible response formats
        if (isset($result['response']) && is_string($result['response'])) {
            dump('Success - returning response');
            return ['success' => true, 'url' => $result['response']];
        }

        // Check for alternative response format
        if (isset($result['url'])) {
            dump('Success - returning URL from alternative format');
            return ['success' => true, 'url' => $result['url']];
        }

        dump('Failed - invalid response structure');
        return ['success' => false, 'error' => 'Structure de réponse invalide du service PDF'];
    }
} 