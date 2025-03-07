<?php

namespace App\MessageHandler;

use App\Message\Message;
use App\Service\NotificationServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SmsNotificationHandler
{
    public function __construct(
        private NotificationServiceInterface $notificationServiceInterface
    ) {
    }

    /**
     * Cette méthode est exécutée lors du traitement des messages en attente
     * via la commande 'php bin/console messenger:consume async'.
     *
     * Elle fait appel au service App\Service\SmsService.
     *
     * @param \App\Message\Message $smsNotification
     * @return void
     */
    public function __invoke(Message $message)
    {
        $this->notificationServiceInterface->send($message->getTo(), $message->getContent());
    }
}
