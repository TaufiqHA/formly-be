# Perencanaan Implementasi Endpoint Submissions

Dokumen ini berisi langkah-langkah teknis untuk mengimplementasikan controller dan endpoint **Submissions** pada project Formly. Langkah-langkah ini disusun sesederhana mungkin agar mudah dipahami dan dieksekusi langsung oleh Junior Developer atau AI Model.

Referensi dari `API_REFERENCE.md` bagian **5. Submissions**.

---

## Tahap 1: Pembuatan Controller

Kita akan membuat satu controller utama untuk mengelola seluruh operasi submissions.

**Langkah:**
1. Buka terminal/command prompt.
2. Jalankan perintah artisan berikut:
   ```bash
   php artisan make:controller Api/V1/SubmissionController
   ```

---

## Tahap 2: Mendaftarkan Route API

Endpoint submissions wajib diproteksi dengan middleware `auth:sanctum`.

**Langkah:**
Buka `routes/api.php` dan tambahkan baris berikut di dalam grup `auth:sanctum`:

```php
use App\Http\Controllers\Api\V1\SubmissionController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // ... rute lain seperti forms ...
    
    // Submissions
    Route::prefix('submissions')->group(function () {
        Route::get('/', [SubmissionController::class, 'index']);
        Route::get('/export', [SubmissionController::class, 'export']); // Harus ditaruh sebelum /{id}
        Route::get('/{id}', [SubmissionController::class, 'show']);
        Route::patch('/{id}/status', [SubmissionController::class, 'updateStatus']);
        Route::post('/{id}/notes', [SubmissionController::class, 'addNote']);
        Route::post('/{id}/resend-wa', [SubmissionController::class, 'resendWa']);
    });
});
```

---

## Tahap 3: Implementasi Method pada `SubmissionController`

Buka file `app/Http/Controllers/Api/V1/SubmissionController.php`. Tambahkan import model-model yang diperlukan:
```php
use App\Models\Submission;
use App\Models\SubmissionNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
```

### 1. Method `index` (List Submissions dengan Pagination & Filter)
```php
public function index(Request $request)
{
    $query = Submission::with('form:id,title'); // Asumsi ada relasi form()

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
                    'customer_name' => $sub->customer_name, // Pastikan field ini ada di tabel/disimpan saat form submit
                    'form_title' => $sub->form ? $sub->form->title : null,
                    'status' => $sub->status,
                    'submitted_at' => $sub->submitted_at ?? $sub->created_at,
                ];
            }),
            'pagination' => [
                'page' => $submissions->currentPage(),
                'limit' => $submissions->perPage(),
                'total' => $submissions->total()
            ]
        ]
    ]);
}
```

### 2. Method `show` (Detail Submission)
```php
public function show($id)
{
    // Eager load relasi yang diperlukan (values, notes.user)
    $submission = Submission::with([
        'values.formField:id,label', // asumsi model SubmissionValue memiliki relasi formField
        'notes.user:id,name'         // asumsi model SubmissionNote memiliki relasi user
    ])->find($id);

    if (!$submission) {
        return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'id' => $submission->id,
            'submission_number' => $submission->submission_number,
            'customer_name' => $submission->customer_name,
            'customer_phone' => $submission->customer_phone,
            'status' => $submission->status,
            'submitted_at' => $submission->submitted_at ?? $submission->created_at,
            'values' => $submission->values->map(function ($val) {
                return [
                    'field_label' => $val->formField ? $val->formField->label : 'Unknown Field',
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
            })
        ]
    ]);
}
```

### 3. Method `updateStatus` (Ubah Status Submission)
```php
public function updateStatus(Request $request, $id)
{
    $request->validate(['status' => 'required|string']);

    $submission = Submission::find($id);
    if (!$submission) {
        return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
    }

    $submission->status = $request->status;
    $submission->save();

    return response()->json([
        'success' => true,
        'message' => 'Status diperbarui'
    ]);
}
```

### 4. Method `addNote` (Tambah Catatan Internal)
```php
public function addNote(Request $request, $id)
{
    $request->validate(['content' => 'required|string']);

    $submission = Submission::find($id);
    if (!$submission) {
        return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
    }

    // Pastikan user sedang login
    $userId = Auth::id();

    SubmissionNote::create([
        'submission_id' => $submission->id,
        'user_id' => $userId,
        'content' => $request->content
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Catatan ditambahkan'
    ], 201);
}
```

### 5. Method `resendWa` (Kirim Ulang Notifikasi WA)
```php
public function resendWa($id)
{
    $submission = Submission::find($id);
    if (!$submission) {
        return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
    }

    // TODO: Dispatch Job pengiriman WA di sini. Contoh:
    // SendWhatsAppNotification::dispatch($submission);

    return response()->json([
        'success' => true,
        'message' => 'Notifikasi WhatsApp dimasukkan ke antrean'
    ]);
}
```

### 6. Method `export` (Download CSV/Excel)
```php
public function export(Request $request)
{
    // TODO: Implementasi export file bisa menggunakan package seperti maatwebsite/excel
    // Untuk tahap ini, kita hanya return placeholder.
    
    return response()->json([
        'success' => false,
        'message' => 'Fitur export sedang dalam pengembangan'
    ], 501);
}
```

---

## Checklist Eksekusi (Untuk Junior/AI)

- [ ] Jalankan command pembuatan controller `SubmissionController`.
- [ ] Daftarkan 6 route submission di `routes/api.php` di bawah middleware `auth:sanctum`.
- [ ] Implementasikan method `index` (pastikan fitur filter dan search berfungsi).
- [ ] Implementasikan method `show` (pastikan eager loading `values` dan `notes` di-load).
- [ ] Implementasikan method `updateStatus` dan `addNote`.
- [ ] Implementasikan method `resendWa` (opsional: panggil queue job jika sudah siap).
- [ ] Implementasikan method `export` (bisa return response 501 Not Implemented sementara).
- [ ] Jalankan formatter PHP Pint (`vendor/bin/pint --dirty --format agent`).
