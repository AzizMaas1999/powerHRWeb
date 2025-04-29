<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SendBulkEmailService
{
    private $client;
    private $apiKey;
    private $apiHost;
    private $from;
    private $sendersName;

    public function __construct(
        HttpClientInterface $client,
        string $apiKey,
        string $apiHost,
        string $from,
        string $sendersName
    ) {
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->apiHost = $apiHost;
        $this->from = $from;
        $this->sendersName = $sendersName;
    }

    public function sendOtpEmail(string $to, string $subject, string $body): array
    {
        $response = $this->client->request('POST', 'https://send-bulk-emails.p.rapidapi.com/api/send/otp/mail', [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-rapidapi-host' => $this->apiHost,
                'x-rapidapi-key' => $this->apiKey,
            ],
            'json' => [
                'subject' => $subject,
                'from' => $this->from,
                'to' => $to,
                'senders_name' => $this->sendersName,
                'body' => $body,
            ],
        ]);

        return $response->toArray(false); // false: don't throw on non-2xx
    }
}