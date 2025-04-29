<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class RapidApiMailService
{
    private $apiKey;
    private $senderEmail;
    private $sendersName;
    private $logger;
    
    public function __construct(ParameterBagInterface $parameterBag, LoggerInterface $logger = null)
    {
        // These values should be stored in your .env file
        $this->apiKey = $parameterBag->get('rapidapi.key') ?? '63ce630824msh737b54099b38971p129c30jsn41e9312fe9bd';
        $this->senderEmail = $parameterBag->get('rapidapi.sender_email') ?? 'gateway.smtp587@gmail.com';
        $this->sendersName = $parameterBag->get('rapidapi.senders_name') ?? 'PowerHR';
        $this->logger = $logger;
    }
    
    /**
     * Send an email using RapidAPI Send Bulk Emails service
     * 
     * @param string $receiverEmail The recipient's email address
     * @param string $subject Email subject
     * @param string $body HTML content of the email
     * 
     * @return array The API response
     */
    public function sendEmail(string $receiverEmail, string $subject, string $body): array
    {
        $curl = curl_init();
        
        $postData = [
            'subject' => $subject,
            'from' => $this->senderEmail,
            'to' => $receiverEmail,
            'senders_name' => $this->sendersName,
            'body' => $body
        ];
        
        if ($this->logger) {
            $this->logger->info('Sending email via RapidAPI Send Bulk Emails', [
                'to' => $receiverEmail,
                'subject' => $subject,
                'from' => $this->senderEmail,
            ]);
            $this->logger->debug('RapidAPI Send Bulk Emails request data', [
                'endpoint' => "https://send-bulk-emails.p.rapidapi.com/api/send/otp/mail",
                'api_key' => substr($this->apiKey, 0, 10) . '...',
                'request_data' => $postData,
            ]);
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://send-bulk-emails.p.rapidapi.com/api/send/otp/mail",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "x-rapidapi-host: send-bulk-emails.p.rapidapi.com",
                "x-rapidapi-key: {$this->apiKey}"
            ],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $infoText = print_r(curl_getinfo($curl), true);

        curl_close($curl);
        
        if ($error) {
            if ($this->logger) {
                $this->logger->error('RapidAPI Send Bulk Emails error', [
                    'error' => $error,
                    'status_code' => $statusCode,
                    'curl_info' => $infoText
                ]);
            }
            
            return [
                'success' => false,
                'message' => "cURL Error: " . $error,
                'status_code' => $statusCode,
                'curl_info' => $infoText
            ];
        }
        
        // Log API response
        if ($this->logger) {
            $this->logger->info('RapidAPI Send Bulk Emails response', [
                'status_code' => $statusCode,
                'response' => $response,
                'curl_info' => $infoText
            ]);
        }
        
        return [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'response' => json_decode($response, true),
            'status_code' => $statusCode,
            'raw_response' => $response,
            'curl_info' => $infoText
        ];
    }
    
    /**
     * Send a payment notification email to an employee
     * 
     * @param string $employeeEmail Recipient email address
     * @param string $employeeName Employee name
     * @param string $month Payment month
     * @param string $year Payment year
     * @param float $amount Payment amount
     * @param int $workDays Number of work days
     * 
     * @return array The API response
     */
    public function sendPaymentNotification(
        string $employeeEmail, 
        string $employeeName, 
        string $month, 
        string $year, 
        float $amount, 
        int $workDays
    ): array {
        $subject = "Votre fiche de paie - {$month} {$year}";
        
        // Create HTML content for the email
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset=\"UTF-8\">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    text-align: center;
                    padding-bottom: 20px;
                    border-bottom: 2px solid #ddd;
                }
                .content {
                    padding: 20px 0;
                }
                .summary {
                    background-color: #f9f9f9;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    padding: 15px;
                    margin: 20px 0;
                }
                .summary-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 8px 0;
                    border-bottom: 1px solid #eee;
                }
                .summary-row:last-child {
                    border-bottom: none;
                }
                .footer {
                    border-top: 1px solid #ddd;
                    padding-top: 20px;
                    text-align: center;
                    font-size: 12px;
                    color: #777;
                }
            </style>
        </head>
        <body>
            <div class=\"container\">
                <div class=\"header\">
                    <h2>PowerHR</h2>
                    <h3>Notification de Paiement - {$month} {$year}</h3>
                </div>
                
                <div class=\"content\">
                    <p>Bonjour {$employeeName},</p>
                    
                    <p>Nous vous informons que votre salaire pour le mois de <strong>{$month} {$year}</strong> a été traité.</p>
                    
                    <div class=\"summary\">
                        <div class=\"summary-row\">
                            <span>Nombre de jours travaillés:</span>
                            <span><strong>{$workDays} jours</strong></span>
                        </div>
                        <div class=\"summary-row\">
                            <span>Montant:</span>
                            <span><strong>" . number_format($amount, 2, ',', ' ') . " TND</strong></span>
                        </div>
                    </div>
                    
                    <p>Pour plus de détails, vous pouvez consulter votre fiche de paie complète dans votre espace personnel.</p>
                    
                    <p>Cordialement,<br>
                    L'équipe des Ressources Humaines<br>
                    PowerHR</p>
                </div>
                
                <div class=\"footer\">
                    <p>Ce message est généré automatiquement, merci de ne pas y répondre.</p>
                    <p>&copy; " . date('Y') . " PowerHR. Tous droits réservés.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($employeeEmail, $subject, $body);
    }
}