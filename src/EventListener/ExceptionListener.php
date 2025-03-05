<?php

declare(strict_types=1);

namespace App\EventListener;

use JsonException;
use Psr\Log\LoggerInterface;
use App\Exceptions\InvalidInseeException;
use App\Exceptions\MissingInseeException;
use App\Exceptions\MissingMessageException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Listener pour la gestion des exceptions dans l'application.
 *
 * Ce listener intercepte les exceptions levées pendant l'exécution des requêtes
 * et renvoie une réponse JSON appropriée en fonction du type d'exception.
 * Il permet ainsi de gérer les erreurs de manière centralisée et d'assurer
 * des réponses cohérentes aux clients de l'API.
 *
 * - Les erreurs liées à des données manquantes (`MissingInseeException`, `MissingMessageException`)
 *   renvoient une réponse HTTP 400 (Bad Request).
 * - Les erreurs de format (`InvalidInseeException`) renvoient une réponse HTTP 422 (Unprocessable Entity).
 * - Toutes les autres exceptions renvoient une réponse HTTP 500 (Internal Server Error).
 *
 * @package App\EventListener
 */
class ExceptionListener
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $this->logger->error('Error!', ['exception' => $exception]);

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'An error occured';

        $this->logger->error($exception->getMessage());

        if (
            $exception instanceof MissingInseeException ||
            $exception instanceof MissingMessageException
        ) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $message = $exception->getMessage();
        } elseif ($exception instanceof InvalidInseeException) {
            $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
            $message = $exception->getMessage();
        } elseif ($exception instanceof HttpException) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $message = $exception->getMessage();
        }

        $response = new JsonResponse(['error' => $message, 'statusCode' => $statusCode], $statusCode);
        $event->setResponse($response);
    }
}
