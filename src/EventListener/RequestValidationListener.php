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
                    'error' => 'Requête JSON invalide',
                    'details' => $e->getMessage()
                ], JsonResponse::HTTP_BAD_REQUEST));
                return;
            }

            if (!isset($data['insee']) || empty($data['insee'])) {
                $event->setResponse(new JsonResponse([
                    'error' => "Le champ 'insee' est requis"
                ], JsonResponse::HTTP_BAD_REQUEST));
                return;
            }
    
            if (!preg_match('/^\d{5}$/', $data['insee'])) {
                $event->setResponse(new JsonResponse([
                    'error' => "Le champ 'insee' doit être un code à 5 chiffres"
                ], JsonResponse::HTTP_BAD_REQUEST));
                return;
            }
        }
    }
}