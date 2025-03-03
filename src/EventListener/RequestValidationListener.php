<?php 

declare(strict_types=1);

namespace App\EventListener;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestValidationListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->getMethod() === 'POST' && $request->getContentTypeFormat() === 'json') {
            try {
                $data = $request->toArray();
            } catch (Exception $e) {
                $event->setResponse(new JsonResponse([
                    'error'   => 'Invalid JSON payload',
                    'message' => 'The request body contains invalid JSON.',
                    'details' => $e->getMessage(),
                ], JsonResponse::HTTP_BAD_REQUEST));
                return;
            }

            if (!isset($data['insee']) || empty($data['insee'])) {
                $event->setResponse(new JsonResponse([
                    'error' => 'Missing "insee" field',
                    'message' => 'The "insee" field is required in the request body.',
                ], JsonResponse::HTTP_BAD_REQUEST));
                return;
            }
    
            if (!preg_match('/^\d{5}$/', $data['insee'])) {
                $event->setResponse(new JsonResponse([
                    'error'   => 'Invalid "insee" format',
                    'message' => 'The "insee" field must be a 5-digit code.',
                ], JsonResponse::HTTP_BAD_REQUEST));
                return;
            }
        }
    }
}