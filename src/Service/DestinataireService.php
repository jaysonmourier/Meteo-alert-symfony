<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\DestinataireRepository;
use Psr\Log\LoggerInterface;

class DestinataireService
{
    public function __construct(
        private LoggerInterface $logger,
        private DestinataireRepository $destinataireRepository
    ) {
    }

    /**
     * Permet de persister un ensemble de destinataires
     *
     * @param array $data
     * @return int
     */
    public function persistDestinataires(array $destinataires): int
    {
        return $this->destinataireRepository->insertBulk($destinataires);
    }
}
