<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use App\Message\SmsNotification;
use App\Exceptions\InvalidInseeException;
use App\Exceptions\MissingInseeException;
use App\Exceptions\MissingMessageException;
use App\Repository\DestinataireRepository;
use Symfony\Component\Messenger\MessageBusInterface;

class AlertService
{
    private const INSEE_REGEX = '/^\d{5}$/';
    public function __construct(
        private MessageBusInterface $messageBusInterface,
        private DestinataireRepository $destinataireRepository, 
        private LoggerInterface $logger
    ) {}

    /**
     * Permet de récuperer le code INSEE dans le tableau passé en paramètre.
     * 
     * Si le code INSEE n'est pas présent ou invalide, une exception est levée.
     * 
     * @param array $data
     * @throws \App\Exceptions\MissingInseeException
     * @throws \App\Exceptions\InvalidInseeException
     * @return int
     */
    public function getInsee(array $data): int
    {

        if (!isset($data['insee'])) {
            $this->logger->error("Missing INSEE code in the request.");
            throw new MissingInseeException("Missing INSEE code.");
        }

        $insee = $data['insee'];

        if (empty($insee) || !preg_match(self::INSEE_REGEX, $insee)) {
            $this->logger->error("Invalid INSEE code.", ['insee' => $insee]);
            throw new InvalidInseeException("Invalid INSEE code.");
        }

        return (int) $insee;
    }

    /**
     * Permet de récuperer le champ 'message' du tableau passé en paramètre.
     * 
     * Si la clé 'message' ne se trouve pas dans le tableau, une exception est levée.
     * 
     * @param array $data
     * @throws \App\Exceptions\MissingMessageException
     * @return string
     */
    public function getMessage(array $data): string
    {
        if (!isset($data['message'])) {
            $this->logger->error("Missing message in the request.");
            throw new MissingMessageException("Missing message.");
        }

        return $data['message'];
    }

    /**
     * Permet de récuperer les numéros de téléphones associés au code INSEE passé en paramètre.
     * 
     * @param int $insee
     * @return array
     */
    public function getNumbersFromInsee(int $insee): array
    {
        $this->logger->debug("get numbers for insee => " . $insee);
        return $this->destinataireRepository->getNumbersByInsee($insee);
    }

    /**
     * Permet de dispatcher des notifications SMS via 'Symfony\Component\Messenger\MessageBusInterface'. 
     * 
     * @param array $numbers
     * @param string $message
     * @return void
     */
    public function dispatchSmsNotification(array $numbers, string $message): void
    {
        foreach ($numbers as $number) {
            $this->messageBusInterface->dispatch(new SmsNotification($number, $message));
        }
    }
}