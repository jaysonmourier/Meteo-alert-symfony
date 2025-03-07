<?php

declare(strict_types=1);

namespace App\Service;

interface NotificationServiceInterface
{
    public function send(string $to, string $message): void;
}
