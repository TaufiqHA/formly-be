# Rencana Implementasi (Implementation Plan) - Form Fields API

Dokumen ini berisi panduan langkah demi langkah untuk mengimplementasikan fitur Form Fields (manajemen kolom pada formulir) di project Formly. Panduan ini dirancang agar mudah diikuti oleh Junior Developer atau AI model lainnya.

## Tujuan
Membuat `FormFieldController` dan mendefinisikan endpoint untuk memanipulasi *field* pada suatu form sesuai dengan `API_REFERENCE.md`:
- `PUT /api/v1/forms/{id}/fields` - Menyimpan struktur *fields* secara massal (*bulk update*).

Berdasarkan aturan di API Reference:
1. Jika *field* di dalam *payload* memiliki `id`, maka data akan di-**update**.
2. Jika *field* di dalam *payload* **tidak** memiliki `id`, maka data akan di-**create** (buat baru).
3. Jika *field* sudah ada di database namun **tidak dikirimkan** di dalam *payload*, maka data akan di-**delete** (dihapus).

---

## Langkah 1: Buat FormFieldController

Gunakan Artisan command untuk membuat controller baru.

**Perintah CLI:**
```bash
php artisan make:controller Api/V1/FormFieldController
```

---

## Langkah 2: Tambahkan Route API

Buka file `routes/api.php` dan tambahkan routing untuk endpoint *form fields* di dalam middleware `auth:sanctum`.

**Kode untuk ditambahkan di `routes/api.php`:**
```php
use App\Http\Controllers\Api\V1\FormFieldController;
use Illuminate\Support\Facades\Route;

// Pastikan kode ini berada di dalam block Route::middleware('auth:sanctum')->group(...)
Route::prefix('v1')->group(function () {
    // ... route form lainnya

    // Route untuk bulk update form fields
    Route::put('forms/{id}/fields', [FormFieldController::class, 'updateBulk']);
});
```

---

## Langkah 3: Implementasi Logika di FormFieldController

Buka `app/Http/Controllers/Api/V1/FormFieldController.php`. Implementasikan method `updateBulk`. Kita perlu memvalidasi input array, mengambil *form* berdasarkan ID, dan menjalankan operasi Create/Update/Delete (Sync) menggunakan database transaction agar aman.

**Kode untuk `FormFieldController.php`:**
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormFieldController extends Controller
{
    public function updateBulk(Request $request, $id)
    {
        // 1. Validasi input request
        $validated = $request->validate([
            'fields' => 'present|array',
            'fields.*.id' => 'nullable|uuid',
            'fields.*.label' => 'required|string',
            'fields.*.field_type' => 'required|string', // misalnya: text, radio, checkbox
            'fields.*.placeholder' => 'nullable|string',
            'fields.*.is_required' => 'required|boolean',
            'fields.*.options' => 'nullable|array', // opsi untuk radio/dropdown
            'fields.*.sort_order' => 'required|integer',
        ]);

        // 2. Pastikan form ada (dan bisa ditambahkan pengecekan kepemilikan user_id jika perlu)
        $form = Form::findOrFail($id);

        $fieldsData = $validated['fields'] ?? [];

        // 3. Kumpulkan ID dari field yang dikirimkan (yang sudah ada/punya ID)
        $providedIds = collect($fieldsData)->pluck('id')->filter()->toArray();

        // Gunakan Transaction agar jika ada yang gagal, semua perubahan di-rollback
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
                            'options' => $field['options'] ?? null,
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
                'message' => 'Struktur form berhasil disimpan'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

---

## Langkah 4: Format File (Laravel Pint)
Setelah menyalin kode di atas, pastikan untuk merapikannya menggunakan *formatter* bawaan.

**Perintah CLI:**
```bash
vendor/bin/pint --dirty --format agent
```

---

## Kriteria Selesai (Definition of Done)
- [ ] File `app/Http/Controllers/Api/V1/FormFieldController.php` terbuat.
- [ ] Method `updateBulk` memuat logika *Create, Update, dan Delete* yang dibungkus dalam `DB::beginTransaction()`.
- [ ] Routing `PUT /api/v1/forms/{id}/fields` sudah terdaftar di `routes/api.php`.
- [ ] Kode bebas dari *syntax error* dan lulus *Laravel Pint*.
