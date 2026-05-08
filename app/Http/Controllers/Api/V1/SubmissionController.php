<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\SubmissionNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Submission::with('form:id,title');

        // 1. Filter status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 2. Search berdasarkan customer_name atau submission_number
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('submission_number', 'like', "%{$search}%");
            });
        }

        // 3. Pagination (default limit 25)
        $limit = $request->input('limit', 25);
        $submissions = $query->latest('submitted_at')->paginate($limit);

        // 4. Format Output
        return response()->json([
            'success' => true,
            'data' => [
                'items' => $submissions->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'submission_number' => $sub->submission_number,
                        'customer_name' => $sub->customer_name,
                        'form_title' => $sub->form ? $sub->form->title : null,
                        'status' => $sub->status,
                        'submitted_at' => $sub->submitted_at ?? $sub->created_at,
                    ];
                }),
                'pagination' => [
                    'page' => $submissions->currentPage(),
                    'limit' => $submissions->perPage(),
                    'total' => $submissions->total(),
                ],
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        // Eager load relasi yang diperlukan (values, notes.user)
        $submission = Submission::with([
            'values.field:id,label',
            'notes.user:id,name',
        ])->find($id);

        if (! $submission) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $submission->id,
                'submission_number' => $submission->submission_number,
                'customer_name' => $submission->customer_name,
                'customer_phone' => $submission->customer_phone,
                'customer_email' => $submission->customer_email,
                'customer_company' => $submission->customer_company,
                'status' => $submission->status,
                'submitted_at' => $submission->submitted_at ?? $submission->created_at,
                'values' => $submission->values->map(function ($val) {
                    return [
                        'field_label' => $val->field_label ?? ($val->field ? $val->field->label : 'Unknown Field'),
                        'value_text' => $val->value_text,
                        'value_json' => $val->value_json,
                    ];
                }),
                'notes' => $submission->notes->map(function ($note) {
                    return [
                        'id' => $note->id,
                        'user_name' => $note->user ? $note->user->name : 'Sistem',
                        'content' => $note->content,
                        'created_at' => $note->created_at,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate(['status' => 'required|string']);

        $submission = Submission::find($id);
        if (! $submission) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $submission->status = $request->status;
        $submission->save();

        return response()->json([
            'success' => true,
            'message' => 'Status diperbarui',
        ]);
    }

    /**
     * Add a note to the submission.
     */
    public function addNote(Request $request, string $id): JsonResponse
    {
        $request->validate(['content' => 'required|string']);

        $submission = Submission::find($id);
        if (! $submission) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $userId = Auth::id();

        SubmissionNote::create([
            'submission_id' => $submission->id,
            'user_id' => $userId,
            'content' => $request->content,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Catatan ditambahkan',
        ], 201);
    }

    /**
     * Resend WhatsApp notification.
     */
    public function resendWa(string $id): JsonResponse
    {
        $submission = Submission::find($id);
        if (! $submission) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        // TODO: Dispatch Job pengiriman WA di sini.
        // SendWhatsAppNotification::dispatch($submission);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi WhatsApp dimasukkan ke antrean',
        ]);
    }

    /**
     * Export submissions.
     */
    public function export(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Fitur export sedang dalam pengembangan',
        ], 501);
    }
}
