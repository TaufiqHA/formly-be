<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\SubmissionNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $query = Submission::whereHas('form', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->with('form:id,title');

        // 1. Filter berdasarkan form_id
        if ($request->filled('form_id')) {
            $query->where('form_id', $request->form_id);
        }

        // 2. Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. Search berdasarkan customer_name, customer_phone, atau submission_number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('submission_number', 'like', "%{$search}%");
            });
        }

        // 4. Pagination (default limit 25)
        $limit = $request->input('limit', 25);
        $submissions = $query->latest('submitted_at')->paginate($limit);

        // 5. Format Output
        return response()->json([
            'success' => true,
            'data' => [
                'items' => $submissions->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'submission_number' => $sub->submission_number,
                        'customer_name' => $sub->customer_name,
                        'customer_phone' => $sub->customer_phone,
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
        $userId = Auth::id();

        // Eager load relasi yang diperlukan (values, notes.user)
        $submission = Submission::whereHas('form', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->with([
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

        $userId = Auth::id();
        $submission = Submission::whereHas('form', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->find($id);

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

        $userId = Auth::id();
        $submission = Submission::whereHas('form', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->find($id);

        if (! $submission) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

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
        $userId = Auth::id();
        $submission = Submission::whereHas('form', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->find($id);

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
     * Export submissions with dynamic form values.
     */
    public function export(Request $request)
    {
        $userId = Auth::id();

        // 1. Ambil query dengan filter dan EAGER LOAD relasi values & field
        $query = Submission::whereHas('form', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->with(['form:id,title', 'values.field']); // [UPDATE] Tambahkan 'values.field'

        // Filter berdasarkan form_id
        if ($request->filled('form_id')) {
            $query->where('form_id', $request->form_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('submission_number', 'like', "%{$search}%");
            });
        }

        $submissions = $query->latest('submitted_at')->get();

        // 2. Kumpulkan Dynamic Headers (Label Pertanyaan Unik)
        $dynamicHeaders = [];
        foreach ($submissions as $sub) {
            foreach ($sub->values as $val) {
                $label = $val->field_label ?? ($val->field ? $val->field->label : 'Unknown Field');
                if (! in_array($label, $dynamicHeaders)) {
                    $dynamicHeaders[] = $label;
                }
            }
        }

        // 3. Buat response stream untuk CSV
        $fileName = 'submissions_export_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($submissions, $dynamicHeaders) {
            $file = fopen('php://output', 'w');

            // 4. Susun Header: Statis + Dinamis
            $staticHeaders = ['ID', 'Submission Number', 'Customer Name', 'Customer Phone', 'Form Title', 'Status', 'Submitted At'];
            fputcsv($file, array_merge($staticHeaders, $dynamicHeaders));

            // 5. Tulis baris data
            foreach ($submissions as $sub) {
                // Siapkan data statis
                $rowData = [
                    $sub->id,
                    $sub->submission_number,
                    $sub->customer_name,
                    $sub->customer_phone,
                    $sub->form ? $sub->form->title : '',
                    $sub->status,
                    $sub->submitted_at ?? $sub->created_at,
                ];

                // Siapkan data dinamis (jawaban)
                // Buat array asosiatif [Label => Jawaban] untuk memudahkan pencocokan
                $subValues = [];
                foreach ($sub->values as $val) {
                    $label = $val->field_label ?? ($val->field ? $val->field->label : 'Unknown Field');
                    $subValues[$label] = $val->value_text ?? json_encode($val->value_json);
                }

                // Masukkan jawaban sesuai urutan Dynamic Headers
                foreach ($dynamicHeaders as $header) {
                    $rowData[] = $subValues[$header] ?? ''; // Jika tidak ada jawaban, isi kosong
                }

                fputcsv($file, $rowData);
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
