# Perencanaan Implementasi Endpoint Public (Customer-Facing)

Dokumen ini berisi langkah-langkah detail untuk mengimplementasikan endpoint API Public (untuk customer) pada project Formly. Langkah-langkah ini disusun agar sangat mudah diikuti, baik oleh Junior Developer maupun oleh AI Assistant.

Referensi dari `API_REFERENCE.md` bagian **4. Public**.

---

## Tahap 1: Pembuatan Controller

Kita butuh satu controller untuk menangani endpoint public ini.

**Langkah:**
1. Buka terminal/command prompt.
2. Jalankan perintah artisan berikut:
   ```bash
   php artisan make:controller Api/V1/PublicFormController
   ```

---

## Tahap 2: Mendaftarkan Route

Buka file `routes/api.php` dan tambahkan route baru untuk endpoint public. Pastikan route ini **TIDAK** dibungkus dalam middleware `auth:sanctum` karena ini untuk pengunjung publik.

**Langkah:**
Tambahkan kode berikut di dalam `routes/api.php`:
```php
use App\Http\Controllers\Api\V1\PublicFormController;

Route::prefix('v1')->group(function () {
    // ... route auth atau admin lainnya ...

    // Public Routes (Tanpa Auth)
    Route::prefix('public/forms')->group(function () {
        Route::get('/{slug}', [PublicFormController::class, 'show']);
        Route::post('/{slug}/submit', [PublicFormController::class, 'submit']);
    });
});
```

---

## Tahap 3: Implementasi GET `/public/forms/{slug}` (Mengambil Konfigurasi Form)

Buka file `app/Http/Controllers/Api/V1/PublicFormController.php` dan buat method `show`.

**Langkah:**
1. Tambahkan use statement untuk Model `Form`:
   ```php
   use App\Models\Form;
   ```
2. Buat method `show($slug)`:
   ```php
   public function show($slug)
   {
       // 1. Cari form berdasarkan slug beserta relasi fields-nya
       // Pastikan hanya form yang 'active' yang bisa diakses (opsional sesuai bisnis logik)
       $form = Form::with(['fields' => function ($query) {
           $query->orderBy('sort_order', 'asc'); // Urutkan field
       }])->where('slug', $slug)->first();

       // 2. Jika tidak ditemukan, kembalikan error 404
       if (!$form) {
           return response()->json([
               'success' => false,
               'message' => 'Form tidak ditemukan'
           ], 404);
       }

       // 3. Format response sesuai API_REFERENCE.md
       return response()->json([
           'success' => true,
           'data' => [
               'id' => $form->id,
               'title' => $form->title,
               'description' => $form->description,
               'fields' => $form->fields->map(function ($field) {
                   return [
                       'id' => $field->id,
                       'label' => $field->label,
                       'field_type' => $field->field_type,
                       'is_required' => $field->is_required,
                       'options' => $field->options
                   ];
               })
           ]
       ]);
   }
   ```

---

## Tahap 4: Implementasi POST `/public/forms/{slug}/submit` (Submit Form)

Method `submit` akan menangani pengiriman data oleh user. Kita butuh Model `Submission` dan `SubmissionValue`, serta `DB` facade untuk transaksi.

**Langkah:**
1. Tambahkan use statement di atas file `PublicFormController.php`:
   ```php
   use App\Models\Submission;
   use App\Models\SubmissionValue;
   use Illuminate\Http\Request;
   use Illuminate\Support\Facades\DB;
   use Illuminate\Support\Str;
   ```
2. Buat method `submit(Request $request, $slug)`:
   ```php
   public function submit(Request $request, $slug)
   {
       // 1. Validasi input request (pastikan ada field 'values')
       $request->validate([
           'values' => 'required|array'
       ]);

       // 2. Cari Form berdasarkan slug
       $form = Form::where('slug', $slug)->first();
       if (!$form) {
           return response()->json([
               'success' => false,
               'message' => 'Form tidak ditemukan'
           ], 404);
       }

       try {
           DB::beginTransaction();

           // 3. Generate Submission Number (contoh: SUB-2023-XXXX)
           $submissionNumber = 'SUB-' . date('Y') . '-' . strtoupper(Str::random(4));

           // 4. Simpan ke tabel submissions
           $submission = Submission::create([
               'form_id' => $form->id,
               'submission_number' => $submissionNumber,
               'status' => 'new',
               // (Opsional) extract customer name/phone jika field ID diketahui, 
               // atau biarkan null dulu lalu diupdate lewat job/event.
           ]);

           // 5. Looping data 'values' dan simpan ke submission_values
           // Bentuk request: "values": { "field-uuid-1": "Budi Santoso", "field-uuid-2": ["Basic"] }
           foreach ($request->values as $fieldId => $value) {
               $isJson = is_array($value);
               
               SubmissionValue::create([
                   'submission_id' => $submission->id,
                   'form_field_id' => $fieldId,
                   'value_text' => $isJson ? null : (string) $value,
                   'value_json' => $isJson ? $value : null,
               ]);
           }

           DB::commit();

           // 6. Kembalikan response berhasil
           return response()->json([
               'success' => true,
               'data' => [
                   'submission_id' => $submission->id,
                   'submission_number' => $submission->submission_number,
                   'status' => 'new',
                   // Opsional: Generate WA Redirect URL jika disetup
                   'wa_redirect_url' => null 
               ],
               'message' => 'Pesanan berhasil dikirim'
           ], 201);

       } catch (\Exception $e) {
           DB::rollBack();
           return response()->json([
               'success' => false,
               'message' => 'Gagal mengirim form: ' . $e->getMessage()
           ], 500);
       }
   }
   ```

---

## Checklist Eksekusi (Untuk Junior/AI)

- [ ] Jalankan command pembuatan controller.
- [ ] Daftarkan 2 routes public di `routes/api.php` tanpa auth middleware.
- [ ] Tulis code pada method `show` di `PublicFormController`.
- [ ] Tulis code pada method `submit` di `PublicFormController`.
- [ ] (Opsional) Jalankan format `vendor/bin/pint --dirty --format agent`.
- [ ] (Opsional) Lakukan test manual dengan Postman atau buat unit test `php artisan make:test PublicFormApiTest`.
