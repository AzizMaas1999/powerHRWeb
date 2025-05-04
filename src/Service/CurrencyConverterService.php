<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class CurrencyConverterService
{
    private $httpClient;
    private $cache;
    private const API_KEY = 'YOUR_API_KEY'; // Remplacer par votre clé API
    private const FALLBACK_RATES = [
        'EUR' => 0.30,  // 1 TND ≈ 0.30 EUR
        'USD' => 0.32,  // 1 TND ≈ 0.32 USD
        'GBP' => 0.26,  // 1 TND ≈ 0.26 GBP
        'JPY' => 47.95, // 1 TND ≈ 47.95 JPY
        'AED' => 1.18,  // 1 TND ≈ 1.18 AED
        'SAR' => 1.20,  // 1 TND ≈ 1.20 SAR
        'MAD' => 3.27,  // 1 TND ≈ 3.27 MAD
        'DZD' => 43.51, // 1 TND ≈ 43.51 DZD
        'CAD' => 0.44   // 1 TND ≈ 0.44 CAD
    ];

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->cache = new FilesystemAdapter('currency', 0, __DIR__ . '/../../var/cache/currency');
    }

    public function convert(float $amount, string $from, string $to): ?float
    {
        if ($from === $to) {
            return $amount;
        }

        try {
            $rate = $this->getExchangeRate($from, $to);
            if ($rate === null) {
                // Si pas de taux en ligne, utiliser les taux de secours
                $rate = $this->getFallbackRate($from, $to);
                if ($rate === null) {
                    return null;
                }
            }
            return round($amount * $rate, 2);
        } catch (\Exception $e) {
            // Log l'erreur si nécessaire
            return null;
        }
    }

    private function getExchangeRate(string $from, string $to): ?float
    {
        $cacheKey = "exchange_rate_{$from}_{$to}_" . date('Y-m-d');
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($from, $to) {
            $item->expiresAfter(86400); // Cache pour 24 heures
            
            try {
                // Essayer d'abord avec l'API principale
                $response = $this->httpClient->request('GET', 
                    "https://api.exchangerate.host/convert?from={$from}&to={$to}"
                );
                
                $data = $response->toArray();
                if (isset($data['result']) && is_numeric($data['result'])) {
                    return (float) $data['result'];
                }

                // Si la première API échoue, essayer une API de secours
                $response = $this->httpClient->request('GET', 
                    "https://open.er-api.com/v6/latest/{$from}"
                );
                
                $data = $response->toArray();
                if (isset($data['rates'][$to])) {
                    return (float) $data['rates'][$to];
                }
                
                return null;
            } catch (TransportExceptionInterface $e) {
                return null;
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    private function getFallbackRate(string $from, string $to): ?float
    {
        if ($from === 'TND') {
            return self::FALLBACK_RATES[$to] ?? null;
        } elseif ($to === 'TND') {
            return 1 / (self::FALLBACK_RATES[$from] ?? 0);
        }
        
        // Conversion via TND comme monnaie intermédiaire
        $rateFromTND = self::FALLBACK_RATES[$from] ?? null;
        $rateToTND = self::FALLBACK_RATES[$to] ?? null;
        
        if ($rateFromTND && $rateToTND) {
            return $rateToTND / $rateFromTND;
        }
        
        return null;
    }

    public function getSupportedCurrencies(): array
    {
        return [
            'TND' => 'Dinar Tunisien',
            'EUR' => 'Euro',
            'USD' => 'Dollar US',
            'GBP' => 'Livre Sterling',
            'JPY' => 'Yen Japonais',
            'AED' => 'Dirham UAE',
            'SAR' => 'Riyal Saoudien',
            'MAD' => 'Dirham Marocain',
            'DZD' => 'Dinar Algérien',
            'CAD' => 'Dollar Canadien'
        ];
    }
}