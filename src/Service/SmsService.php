<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;

class SmsService
{
    public function __construct(private LoggerInterface $logger) {}
    public function send(string $to, string $message)
    {
        $this->logger->info("Send SMS to " . $to . " with the following message: " . $message);
    }
}