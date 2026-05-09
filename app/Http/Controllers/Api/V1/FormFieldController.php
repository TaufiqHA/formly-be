<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormFieldController extends Controller
{
    /**
     * Update the fields for the specified form in bulk.
     */
    public function updateBulk(Request $request, string $id): JsonResponse
    {
        // 1. Validasi input request
        $validated = $request->validate([
            'fields' => 'present|array',
            'fields.*.id' => 'nullable|uuid',
            'fields.*.label' => 'required|string',
            'fields.*.field_type' => 'required|string',
            'fields.*.placeholder' => 'nullable|string',
            'fields.*.is_required' => 'required|boolean',
            'fields.*.options' => 'nullable|array',
            'fields.*.sort_order' => 'required|integer',
        ]);

        // 2. Pastikan form ada dan milik user tersebut
        $form = Form::where('user_id', $request->user()->id)->findOrFail($id);

        $fieldsData = $validated['fields'] ?? [];

        // 3. Kumpulkan ID dari field yang dikirimkan
        $providedIds = collect($fieldsData)->pluck('id')->filter()->toArray();

        DB::beginTransaction();
        try {
            // 4. DELETE: Hapus field di database yang tidak ada di $providedIds
            $form->fields()->whereNotIn('id', $providedIds)->delete();

            // 5. CREATE / UPDATE: Loop data yang dikirim
            foreach ($fieldsData as $field) {
                if (isset($field['id']) && $field['id']) {
                    // Update field yang sudah ada
                    FormField::where('id', $field['id'])
                        ->where('form_id', $form->id)
                        ->update([
                            'label' => $field['label'],
                            'field_type' => $field['field_type'],
                            'placeholder' => $field['placeholder'] ?? null,
                            'is_required' => $field['is_required'],
                            'options' => isset($field['options']) ? json_encode($field['options']) : null,
                            'sort_order' => $field['sort_order'],
                        ]);
                } else {
                    // Create field baru jika tidak ada ID
                    $form->fields()->create([
                        'label' => $field['label'],
                        'field_type' => $field['field_type'],
                        'placeholder' => $field['placeholder'] ?? null,
                        'is_required' => $field['is_required'],
                        'options' => $field['options'] ?? null,
                        'sort_order' => $field['sort_order'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Struktur form berhasil disimpan',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data: '.$e->getMessage(),
            ], 500);
        }
    }
}
