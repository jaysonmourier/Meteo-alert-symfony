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
    
    /**
     * Cette fonction est exécutée lors du traitement des messages en attente
     * via la commande 'php bin/console messenger:consume async'.
     * 
     * Elle fait appel au service App\Service\SmsService.
     * 
     * @param \App\Message\SmsNotification $smsNotification
     * @return void
     */
    public function __invoke(SmsNotification $smsNotification)
    {
        $this->smsService->send($smsNotification->getTo(), $smsNotification->getContent());
    }
}
