<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AlertService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AlertController extends AbstractController
{
    #[Route('/alerter', methods:['POST'])]
    /**
     * Point d'entrée qui permet de propager un message en fonction du code INSEE.
     * Le contrôleur extrait les données de la requête, récupère les numéros associés
     * et dispatch les messages via la méthode 'dispatch' de 'App\Service\AlertService'.
     * 
     * Si une exception est levée, elle est gérée par 'App\EventListener\ExceptionListener'.
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Service\AlertService $alertService
     * @param \Psr\Log\LoggerInterface $logger
     * @return JsonResponse
     */
    public function alerter(
        Request $request, 
        AlertService $alertService,
        LoggerInterface $logger
        ): JsonResponse 
    {
        $data = $request->toArray();

        $logger->info("received request", ["data" => $data]);

        $insee = $alertService->getInsee($data);
        $message = $alertService->getMessage($data);
        $numbers = $alertService->getNumbersFromInsee($insee);
        $alertService->dispatchSmsNotification($numbers, $message);
    
        return $this->json([
            "status" => "done",
            "sendTo" => count($numbers)
        ], Response::HTTP_OK);
    }
}
