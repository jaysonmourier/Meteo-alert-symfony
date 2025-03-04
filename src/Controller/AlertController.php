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
    #[Route('/alerter', methods: ['POST'])]
    public function alerter(
        Request $request,
        AlertService $alertService,
        LoggerInterface $logger
    ): JsonResponse {
        $data = $request->toArray();

        $logger->info('Received request', ['data' => $data]);

        $insee = $alertService->getInsee($data);
        $message = $alertService->getMessage($data);
        $numbers = $alertService->getNumbersFromInsee($insee);

        $alertService->dispatchSmsNotification($numbers, $message);

        return $this->json(
            [
                'status' => 'done',
                'sendTo' => count($numbers),
            ],
            Response::HTTP_OK
        );
    }
}
