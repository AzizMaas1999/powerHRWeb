<?php
namespace App\ApiBundle\Service;

use Twilio\Rest\Client;

/**
 * Service for sending and verifying SMS codes using Twilio Verify API.
 */
class TwilioSmsVerificationService
{
    private string $sid;
    private string $token;
    private string $from;
    private string $verifySid;
    private Client $client;

    public function __construct(string $sid, string $token, string $from, string $verifySid)
    {
        $this->sid = $sid;
        $this->token = $token;
        $this->from = $from;
        $this->verifySid = $verifySid;
        $this->client = new Client($sid, $token);
    }

    /**
     * Start verification: send a code to the phone number using Twilio Verify.
     *
     * @param string $to Recipient phone number (E.164 format)
     * @return array     Twilio API response
     * @throws \Exception
     */
    public function startVerification(string $to): array
    {
        try {
            $verification = $this->client->verify->v2->services($this->verifySid)
                ->verifications
                ->create($to, 'sms');
            return [
                'sid' => $verification->sid,
                'status' => $verification->status,
                'to' => $verification->to,
                'channel' => $verification->channel,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Twilio Verify start failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check verification: verify the code for the phone number using Twilio Verify.
     *
     * @param string $to   Recipient phone number (E.164 format)
     * @param string $code Verification code
     * @return array       Twilio API response
     * @throws \Exception
     */
    public function checkVerification(string $to, string $code): array
    {
        try {
            $verificationCheck = $this->client->verify->v2->services($this->verifySid)
                ->verificationChecks
                ->create([
                    'to' => $to,
                    'code' => $code
                ]);
            return [
                'sid' => $verificationCheck->sid,
                'status' => $verificationCheck->status,
                'to' => $verificationCheck->to,
                'valid' => $verificationCheck->valid,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Twilio Verify check failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
