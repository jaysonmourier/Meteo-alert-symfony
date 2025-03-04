<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exceptions\InvalidInseeException;
use App\Exceptions\MissingInseeException;
use App\Exceptions\MissingMessageException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ExceptionListener
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $this->logger->error('Error!', ['exception' => $exception]);

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'An error occured';

        if ($exception instanceof MissingInseeException ||
            $exception instanceof MissingMessageException) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $message = $exception->getMessage();
            } elseif ($exception instanceof InvalidInseeException) {
                $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
                $message = $exception->getMessage();
            }

        $response = new JsonResponse(['error' => $message], $statusCode);
        $event->setResponse($response);
    }
}
