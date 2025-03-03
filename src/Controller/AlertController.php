<?php

declare(strict_types=1);

namespace App\Controller;

use App\Message\SmsNotification;
use RuntimeException;
use App\Repository\DestinataireRepository;
use App\Service\SmsService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class AlertController extends AbstractController
{
    #[Route('/alerter', methods:['POST'])]
    public function alerter(Request $request, DestinataireRepository $destinataireRepository, MessageBusInterface $messageBusInterface, LoggerInterface $logger): JsonResponse {
        try {
            $insee = intval($request->toArray()['insee']);
            
            $numbers = $destinataireRepository->getNumbersByInsee($insee);
            
            if (!empty($numbers)) {
                foreach ($numbers as $number) {
                    $logger->info($number);
                    $messageBusInterface->dispatch(new SmsNotification($number, "(INSEE: " . $insee . ") Alerte météo!"));
                }
            }
    
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
