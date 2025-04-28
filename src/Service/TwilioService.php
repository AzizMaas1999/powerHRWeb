<?php

namespace App\Service;

use Twilio\Rest\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TwilioService
{
    private $client;
    private $verifySid;

    public function __construct(ParameterBagInterface $params)
    {
        $accountSid = $params->get('twilio.account_sid');
        $authToken = $params->get('twilio.auth_token');
        $this->verifySid = $params->get('twilio.verify_sid');
        
        $this->client = new Client($accountSid, $authToken);
    }

    public function sendVerificationCode(string $phoneNumber): bool
    {
        try {
            $verification = $this->client->verify->v2->services($this->verifySid)
                ->verifications
                ->create($phoneNumber, "sms");
            
            return $verification->status === "pending";
        } catch (\Exception $e) {
            // Log the error
            return false;
        }
    }

    public function verifyCode(string $phoneNumber, string $code): bool
    {
        try {
            $verification = $this->client->verify->v2->services($this->verifySid)
                ->verificationChecks
                ->create([
                    "to" => $phoneNumber,
                    "code" => $code
                ]);
            
            return $verification->status === "approved";
        } catch (\Exception $e) {
            // Log the error
            return false;
        }
    }
} 