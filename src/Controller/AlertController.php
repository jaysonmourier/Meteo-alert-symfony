<?php

declare(strict_types=1);

namespace App\Controller;

use RuntimeException;
use App\Repository\DestinataireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AlertController extends AbstractController
{
    #[Route('/alerter', methods:['POST'])]
    public function alerter(Request $request, DestinataireRepository $destinataireRepository): JsonResponse {
        try {
            $insee = intval($request->toArray()['insee']);
            
            $numbers = $destinataireRepository->getNumbersByInsee($insee);
            
            if (!empty($numbers)) {
                
            }
    
            return $this->json([
                "status" => "done",
                "sendTo" => count($numbers)
            ], JsonResponse::HTTP_OK);
        } catch (RuntimeException $e) {
            return new JsonResponse([
                "error" => "internal error",
                "detail" => $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
