<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class AuthentificationListener
{
    private string $apiKey;
    
    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->getMethod() === 'POST' && $request->getContentTypeFormat() === 'json') {
            $apiKeyHeader = $request->headers->get('X-API-KEY');
            
            if (empty($apiKeyHeader)) {
                $event->setResponse(new JsonResponse([
                    "error" => "Missing API key",
                    "message" => "You must provide an API key in the 'X-API-KEY' header"
                ], JsonResponse::HTTP_UNAUTHORIZED));
                return;
            }

            if (!hash_equals($this->apiKey, $apiKeyHeader)) {
                $event->setResponse(new JsonResponse([
                    "error" => "Invalid API key",
                    "message" => "The provided API key is not valid"
                ], JsonResponse::HTTP_UNAUTHORIZED));
                return;
            }
        }
    }
}