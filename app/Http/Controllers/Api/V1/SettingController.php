<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use App\Models\WaSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    /**
     * Get current user settings.
     */
    public function index(): JsonResponse
    {
        $userId = Auth::id();

        // 1. Ambil atau buat UserPreference default jika belum ada
        $preferences = UserPreference::firstOrCreate(
            ['user_id' => $userId],
            [
                'notif_email_new_order' => true,
                'notif_wa_auto_confirm' => false,
                'theme' => 'light',
            ]
        );

        // 2. Ambil atau buat WaSetting default jika belum ada
        $waSetting = WaSetting::firstOrCreate(
            ['user_id' => $userId],
            [
                'phone_number' => null,
                'api_key' => null,
                'connection_status' => 'disconnected',
                'wa_template_new_order' => 'Halo {nama}, pesanan {id} Anda diterima.',
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'preferences' => [
                    'notif_email_new_order' => (bool) $preferences->notif_email_new_order,
                    'notif_wa_auto_confirm' => (bool) $preferences->notif_wa_auto_confirm,
                    'theme' => $preferences->theme,
                ],
                'whatsapp' => [
                    'phone_number' => $waSetting->phone_number,
                    'connection_status' => $waSetting->connection_status,
                    'wa_template_new_order' => $waSetting->wa_template_new_order,
                    'has_api_key' => ! empty($waSetting->api_key),
                ],
            ],
        ]);
    }

    /**
     * Update user preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notif_email_new_order' => 'boolean',
            'notif_wa_auto_confirm' => 'boolean',
            'theme' => 'string|in:light,dark,system',
        ]);

        $userId = Auth::id();
        $preferences = UserPreference::where('user_id', $userId)->first();

        if ($preferences) {
            $preferences->update($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Preferensi berhasil disimpan',
        ]);
    }

    /**
     * Update WhatsApp configuration.
     */
    public function updateWhatsApp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'api_key' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'wa_template_new_order' => 'nullable|string',
        ]);

        $userId = Auth::id();
        $waSetting = WaSetting::where('user_id', $userId)->first();

        if ($waSetting) {
            $waSetting->update($validated);

            // Reset status koneksi jika ada perubahan API Key atau Nomor
            if ($request->has('api_key') || $request->has('phone_number')) {
                $waSetting->update(['connection_status' => 'disconnected']);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi WhatsApp berhasil disimpan',
        ]);
    }

    /**
     * Test WhatsApp connection.
     */
    public function testWhatsApp(): JsonResponse
    {
        $userId = Auth::id();
        $waSetting = WaSetting::where('user_id', $userId)->first();

        if (! $waSetting || ! $waSetting->api_key) {
            return response()->json([
                'success' => false,
                'message' => 'API Key belum dikonfigurasi',
            ], 400);
        }

        // Simulating WhatsApp API testing
        $waSetting->update([
            'connection_status' => 'connected',
            'last_tested_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Koneksi ke provider WhatsApp berhasil (Simulasi)',
        ]);
    }
}
