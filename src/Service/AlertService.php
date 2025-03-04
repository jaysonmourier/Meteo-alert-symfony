<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;
use Psr\Log\LoggerInterface;
use App\Message\SmsNotification;
use App\Repository\DestinataireRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

class AlertService
{
    private const INSEE_REGEX = '/^\d{5}$/';
    public function __construct(
        private MessageBusInterface $messageBusInterface,
        private DestinataireRepository $destinataireRepository, 
        private LoggerInterface $logger
    ) {}

    public function getInseeFromRequest(array $data): int
    {

        if (!isset($data['insee'])) {
            $this->logger->error("Missing INSEE code in the request.");
            throw new RuntimeException("Missing INSEE code.");
        }

        $insee = $data['insee'];

        if (empty($insee) || !preg_match(self::INSEE_REGEX, $insee)) {
            $this->logger->error("Invalid INSEE code.", ['insee' => $insee]);
            throw new RuntimeException("Invalid INSEE code.");
        }

        return (int) $insee;
    }

    public function getNumbersFromInsee(int $insee): array
    {
        $this->logger->debug("get numbers for insee => " . $insee);
        return $this->destinataireRepository->getNumbersByInsee($insee);
    }

    public function getMessageFromRequest(array $data): string
    {
        if (!isset($data['message'])) {
            $this->logger->error("Missing message in the request.");
            throw new RuntimeException("Missing message.");
        }

        return $data['message'];
    }

    public function dispatch(array $numbers, string $message): void
    {
        foreach ($numbers as $number) {
            $this->messageBusInterface->dispatch(new SmsNotification($number, $message));
        }
    }
}