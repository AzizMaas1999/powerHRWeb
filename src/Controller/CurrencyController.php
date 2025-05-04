<?php

namespace App\Controller;

use App\Service\CurrencyConverterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class CurrencyController extends AbstractController
{
    private $currencyConverter;

    public function __construct(CurrencyConverterService $currencyConverter)
    {
        $this->currencyConverter = $currencyConverter;
    }

    #[Route('/convert', name: 'api_currency_convert', methods: ['GET'])]
    public function convert(Request $request): JsonResponse
    {
        $amount = $request->query->get('amount');
        $from = $request->query->get('from', 'TND');
        $to = $request->query->get('to', 'EUR');

        if (!is_numeric($amount)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Le montant doit être un nombre'
            ], 400);
        }

        $amount = (float) $amount;
        $converted = $this->currencyConverter->convert($amount, $from, $to);

        if ($converted === null) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Impossible de convertir le montant'
            ], 503);
        }

        return new JsonResponse([
            'success' => true,
            'amount' => $amount,
            'from' => $from,
            'to' => $to,
            'result' => $converted
        ]);
    }

    #[Route('/currencies', name: 'api_currencies_list', methods: ['GET'])]
    public function getCurrencies(): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'currencies' => $this->currencyConverter->getSupportedCurrencies()
        ]);
    }
}