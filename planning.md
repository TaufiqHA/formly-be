# Rencana Implementasi (Implementation Plan) - Forms API

Dokumen ini berisi panduan langkah demi langkah untuk mengimplementasikan fitur Forms (Formulir) pada project Formly. Panduan ini dirancang agar mudah diikuti oleh Junior Developer atau AI model lainnya.

## Tujuan
Membuat `FormController` dan mendefinisikan seluruh endpoint terkait `Forms` sesuai dengan `API_REFERENCE.md`. Endpoint ini dilindungi oleh autentikasi (`auth:sanctum`):
1. `GET /api/v1/forms` - Menampilkan daftar form pengguna.
2. `POST /api/v1/forms` - Membuat form baru.
3. `GET /api/v1/forms/{id}` - Menampilkan detail form beserta field-nya.
4. `PUT /api/v1/forms/{id}` - Memperbarui judul & deskripsi form.
5. `DELETE /api/v1/forms/{id}` - Menghapus form.
6. `PATCH /api/v1/forms/{id}/status` - Mengubah status form (draft/active).
7. `GET /api/v1/forms/{id}/stats` - Menampilkan statistik form (views vs submissions).

---

## Langkah 1: Buat FormController

Gunakan Artisan command untuk membuat controller. Gunakan flag `--api` agar otomatis membuat method standard API (index, store, show, update, destroy).

**Perintah CLI:**
```bash
php artisan make:controller Api/V1/FormController --api
```

---

## Langkah 2: Tambahkan Route API

Buka file `routes/api.php` dan tambahkan routing untuk `FormController` di dalam middleware `auth:sanctum`.

**Kode untuk ditambahkan di `routes/api.php`:**
```php
use App\Http\Controllers\Api\V1\FormController;
use Illuminate\Support\Facades\Route;

// Pastikan blok ini berada di dalam Route::middleware('auth:sanctum')->group(...)
Route::prefix('v1')->group(function () {
    // Standard CRUD (Otomatis mencakup index, store, show, update, destroy)
    Route::apiResource('forms', FormController::class);
    
    // Custom routes untuk status dan stats
    Route::patch('forms/{id}/status', [FormController::class, 'updateStatus']);
    Route::get('forms/{id}/stats', [FormController::class, 'stats']);
});
```

---

## Langkah 3: Implementasi Method di FormController

Buka `app/Http/Controllers/Api/V1/FormController.php`. Implementasikan method-method berikut dengan mengembalikan response JSON sesuai struktur: `{"success": true, "data": {...}, "message": "..."}`. Pastikan menggunakan *dependency injection* atau *eloquent* yang tepat.

### 3.1. Method `index`
Tugas: Menampilkan semua form yang dimiliki oleh user yang sedang login.

```php
public function index(Request $request)
{
    // Mengambil form milik user yang sedang login
    $forms = \App\Models\Form::where('user_id', $request->user()->id)->get();
    
    // Jika ada perhitungan total submissions, bisa di-load dengan withCount('submissions')
    
    return response()->json([
        'success' => true,
        'data' => $forms
    ]);
}
```

### 3.2. Method `store`
Tugas: Membuat form baru dengan status default 'draft' dan `slug` otomatis.

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
    ]);

    $form = \App\Models\Form::create([
        'user_id' => $request->user()->id,
        'title' => $validated['title'],
        'slug' => \Illuminate\Support\Str::slug($validated['title'] . '-' . uniqid()),
        'description' => $validated['description'] ?? null,
        'status' => 'draft',
    ]);

    return response()->json([
        'success' => true,
        'data' => $form
    ], 201);
}
```

### 3.3. Method `show`
Tugas: Menampilkan detail form beserta relasi `fields`.

```php
public function show($id)
{
    // Eager load fields dan urutkan berdasarkan sort_order
    $form = \App\Models\Form::with(['fields' => function($query) {
        $query->orderBy('sort_order', 'asc');
    }])->findOrFail($id);

    return response()->json([
        'success' => true,
        'data' => $form
    ]);
}
```

### 3.4. Method `update`
Tugas: Update judul & deskripsi form.

```php
public function update(Request $request, $id)
{
    $form = \App\Models\Form::findOrFail($id);

    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
    ]);

    $form->update($validated);

    return response()->json([
        'success' => true,
        'data' => $form
    ]);
}
```

### 3.5. Method `destroy`
Tugas: Menghapus form.

```php
public function destroy($id)
{
    $form = \App\Models\Form::findOrFail($id);
    $form->delete();

    return response()->json([
        'success' => true,
        'message' => 'Form berhasil dihapus'
    ]);
}
```

### 3.6. Method `updateStatus` (Custom)
Tugas: Ubah status form dari draft ke active atau sebaliknya.

```php
public function updateStatus(Request $request, $id)
{
    $form = \App\Models\Form::findOrFail($id);

    $validated = $request->validate([
        'status' => 'required|in:draft,active'
    ]);

    $form->update(['status' => $validated['status']]);

    return response()->json([
        'success' => true,
        // Di API Reference, kembaliannya berupa data form (bisa disesuaikan jika hanya butuh pesan)
        'data' => $form,
        'message' => 'Status form berhasil diubah'
    ]);
}
```

### 3.7. Method `stats` (Custom)
Tugas: Menampilkan statistik form (sementara menggunakan dummy views jika belum ada fitur tracking views).

```php
public function stats($id)
{
    $form = \App\Models\Form::findOrFail($id);
    
    // Contoh perhitungan sederhana
    $totalViews = 5000; // Mock data karena belum ada tabel views
    // $totalSubmissions = $form->submissions()->count(); // Jika ada relasi
    $totalSubmissions = 1248; // Mock data sesuai dokumentasi
    $conversionRate = $totalViews > 0 ? ($totalSubmissions / $totalViews) * 100 : 0;

    return response()->json([
        'success' => true,
        'data' => [
            'total_views' => $totalViews,
            'total_submissions' => $totalSubmissions,
            'conversion_rate' => round($conversionRate, 2)
        ]
    ]);
}
```

---

## Langkah 4: Format File (Laravel Pint)
Setelah kode selesai, pastikan kodenya diformat agar sesuai standar PSR-12 dan style guide Laravel.

**Perintah CLI:**
```bash
vendor/bin/pint --dirty --format agent
```

---

## Kriteria Selesai (Definition of Done)
- [ ] File `app/Http/Controllers/Api/V1/FormController.php` berhasil dibuat beserta 7 method-nya.
- [ ] File `routes/api.php` memuat `Route::apiResource` dan 2 custom route untuk `forms`.
- [ ] Seluruh format Response JSON sudah sesuai dengan struktur di `API_REFERENCE.md`.
- [ ] Kode sudah bebas dari error (syntax & logic) dan lolos pengecekan `Laravel Pint`.
