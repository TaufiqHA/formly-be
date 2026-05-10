<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Dapatkan daftar notifikasi milik user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Ambil notifikasi (unread dulu, baru read, lalu urut dari terbaru)
        // Kita juga bisa melakukan pagination
        $notifications = $user->notifications()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $user->unreadNotifications()->count(),
                'items' => $notifications->map(function ($notif) {
                    return [
                        'id' => $notif->id,
                        'type' => class_basename($notif->type),
                        'data' => $notif->data,
                        'read_at' => $notif->read_at,
                        'created_at' => $notif->created_at,
                    ];
                }),
                'pagination' => [
                    'page' => $notifications->currentPage(),
                    'limit' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ],
            ],
        ]);
    }

    /**
     * Tandai notifikasi spesifik sebagai sudah dibaca, atau semua jika tidak ada ID.
     */
    public function markAsRead(Request $request, ?string $id = null): JsonResponse
    {
        $user = $request->user();

        if ($id) {
            // Tandai 1 notifikasi
            $notification = $user->notifications()->find($id);
            if ($notification) {
                $notification->markAsRead();
            }
        } else {
            // Tandai semua notifikasi belum terbaca
            $user->unreadNotifications->markAsRead();
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca',
        ]);
    }
}
