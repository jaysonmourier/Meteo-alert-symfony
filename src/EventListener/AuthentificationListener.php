<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Listener d'authentification basé sur une clé API.
 *
 * Ce listener intercepte toutes les requêtes HTTP de type POST au format JSON
 * et vérifie la présence et la validité de la clé API dans l'en-tête `X-API-KEY`.
 *
 * - Si la clé API est absente, une réponse JSON avec un code 401 (Unauthorized) est retournée.
 * - Si la clé API est invalide, une réponse JSON avec un code 401 est également retournée.
 * - Si la clé API est correcte, la requête continue son traitement normalement.
 *
 * @package App\EventListener
 */
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