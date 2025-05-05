<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SendBulkEmailService
{
    private $client;
    private $params;

    public function __construct(
        HttpClientInterface $client,
        ParameterBagInterface $params
    ) {
        $this->client = $client;
        $this->params = $params;
    }

    public function sendOtpEmail(string $to, string $subject, string $body): array
    {
        $response = $this->client->request(
            'POST',
            'https://send-bulk-emails.p.rapidapi.com/api/v1/send-email',
            [
                'headers' => [
                    'content-type' => 'application/json',
                    'X-RapidAPI-Host' => $this->params->get('app.rapidapi.host'),
                    'X-RapidAPI-Key' => $this->params->get('app.rapidapi.key'),
                ],
                'json' => [
                    'to' => [$to],
                    'from' => $this->params->get('app.rapidapi.from'),
                    'subject' => $subject,
                    'body' => $body,
                    'sendersName' => $this->params->get('app.rapidapi.senders_name'),
                ],
            ]
        );

        return $response->toArray();
    }
}