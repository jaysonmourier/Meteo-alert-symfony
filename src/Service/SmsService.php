<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;

class SmsService
{
    public function __construct(private LoggerInterface $logger) {}

    /**
     * Simule l'envoi d'un SMS.
     * 
     * Actuellement, cette méthode ne fait que de logger le SMS sans réellement l'envoyer.
     * Dans un scénario réel, on pourrait utiliser un service tel que Twilio.
     * 
     * @param string $to
     * @param string $message
     * @return void
     */
    public function send(string $to, string $message)
    {
        $this->logger->info("Send SMS to " . $to . " with the following message: " . $message);
    }
}