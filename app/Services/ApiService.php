<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;

class ApiService
{
    protected $client;

    public function __construct()
    {
        // Opsi 1: Menggunakan raw Guzzle Client
        $this->client = new Client([
            'base_uri' => config('services.third_party.base_url'),
            'timeout'  => 10.0,
        ]);
    }

    /**
     * Contoh menggunakan raw Guzzle
     */
    public function fetchDataWithGuzzle($endpoint)
    {
        $response = $this->client->request('GET', $endpoint);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Contoh menggunakan Laravel HTTP Facade (Guzzle Wrapper) - Lebih direkomendasikan
     */
    public function fetchDataWithFacade($endpoint)
    {
        $response = Http::timeout(10)
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->get(config('services.third_party.base_url') . $endpoint);

        return $response->json();
    }
}
