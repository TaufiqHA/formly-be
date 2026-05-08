<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Submission;
use App\Models\SubmissionValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicFormController extends Controller
{
    /**
     * Get form configuration by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $form = Form::with(['fields' => function ($query) {
            $query->orderBy('sort_order', 'asc');
        }])
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (! $form) {
            return response()->json([
                'success' => false,
                'message' => 'Form tidak ditemukan atau tidak aktif',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $form->id,
                'title' => $form->title,
                'description' => $form->description,
                'fields' => $form->fields->map(fn ($field) => [
                    'id' => $field->id,
                    'label' => $field->label,
                    'field_type' => $field->field_type,
                    'placeholder' => $field->placeholder,
                    'is_required' => $field->is_required,
                    'options' => $field->options,
                    'sort_order' => $field->sort_order,
                ]),
            ],
        ]);
    }

    /**
     * Submit form data.
     */
    public function submit(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'values' => 'required|array',
        ]);

        $form = Form::with('fields')->where('slug', $slug)->first();

        if (! $form || $form->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Form tidak ditemukan atau tidak aktif',
            ], 404);
        }

        try {
            return DB::transaction(function () use ($request, $form) {
                // Generate Submission Number (SUB-YYYY-RAND)
                $submissionNumber = 'SUB-'.date('Y').'-'.strtoupper(Str::random(6));

                $submission = Submission::create([
                    'form_id' => $form->id,
                    'submission_number' => $submissionNumber,
                    'status' => 'new',
                    'ip_address' => $request->ip(),
                    'submitted_at' => now(),
                ]);

                $fields = $form->fields->keyBy('id');

                foreach ($request->values as $fieldId => $value) {
                    $field = $fields->get($fieldId);

                    if (! $field) {
                        continue;
                    }

                    $isJson = is_array($value);

                    SubmissionValue::create([
                        'submission_id' => $submission->id,
                        'form_field_id' => $fieldId,
                        'field_label' => $field->label,
                        'value_text' => $isJson ? null : (string) $value,
                        'value_json' => $isJson ? $value : null,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'submission_id' => $submission->id,
                        'submission_number' => $submission->submission_number,
                        'status' => $submission->status,
                        'wa_redirect_url' => null, // Placeholder for future feature
                    ],
                    'message' => 'Pesanan berhasil dikirim',
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim form: '.$e->getMessage(),
            ], 500);
        }
    }
}
