<?php

declare(strict_types=1);

namespace App\Controller;

use RuntimeException;
use Psr\Log\LoggerInterface;
use App\Service\AlertService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AlertController extends AbstractController
{
    #[Route('/alerter', methods:['POST'])]
    public function alerter(
        Request $request, 
        LoggerInterface $logger,
        AlertService $alertService
    ): JsonResponse {
        try {
            $data = $request->toArray();
            
            $insee = $alertService->getInseeFromRequest($data);

            $message = $alertService->getMessageFromRequest($data);

            $numbers = $alertService->getNumbersFromInsee($insee);
            
            $alertService->dispatch($numbers, $message);
    
            return $this->json([
                "status" => "done",
                "sendTo" => count($numbers)
            ], JsonResponse::HTTP_OK);
        } catch (RuntimeException $e) {
            $logger->error('Erreur lors de l\'alerte', ['exception' => $e]);
            return new JsonResponse([
                "error" => "internal error",
                "details" => $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
