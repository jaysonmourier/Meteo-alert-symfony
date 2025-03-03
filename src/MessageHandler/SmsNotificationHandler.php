<?php

namespace App\MessageHandler;

use App\Message\SmsNotification;
use App\Service\SmsService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SmsNotificationHandler
{
    public function __construct(
        private SmsService $smsService
    ) {}
    public function __invoke(SmsNotification $smsNotification)
    {
        $this->smsService->send($smsNotification->getTo(), $smsNotification->getContent());
    }
}
