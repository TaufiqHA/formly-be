<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use Illuminate\Http\JsonResponse;

class IntegrationController extends Controller
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function getData(): JsonResponse
    {
        try {
            // Gunakan metode yang dibuat di service
            $data = $this->apiService->fetchDataWithFacade('/users');

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: '.$e->getMessage(),
            ], 500);
        }
    }
}
