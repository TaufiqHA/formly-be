# Rencana Implementasi: Penambahan `customer_name` & `customer_phone` pada Proses Submission

Dokumen ini berisi panduan langkah demi langkah untuk menambahkan `customer_name` dan `customer_phone` ke dalam proses penyimpanan (*submit*) data `Submission`. Panduan ini dirancang agar mudah diikuti oleh Junior Developer atau AI.

## 1. Latar Belakang & Status Saat Ini
- **Tabel Database**: Tabel `submissions` **sudah memiliki** kolom `customer_name` dan `customer_phone`. Keduanya bersifat *nullable* (opsional).
- **Model Eloquent**: Model `app/Models/Submission.php` **sudah menyertakan** `customer_name` dan `customer_phone` di dalam array `$fillable`.
- **Kekurangan Saat Ini**: Endpoint pengiriman form di `PublicFormController@submit` belum menangkap dan menyimpan data ini dari `Request` ke database.

## 2. Tujuan
Memastikan endpoint API pengiriman form publik (`POST /api/v1/forms/{slug}/submit`) dapat menerima parameter `customer_name` dan `customer_phone`, serta menyimpannya ke tabel `submissions`.

## 3. Langkah-Langkah Implementasi

### Langkah 1: Update Validasi Request di Controller
Buka file **`app/Http/Controllers/Api/V1/PublicFormController.php`**.
Pada method `submit()`, tambahkan aturan validasi untuk `customer_name` dan `customer_phone`. Aturan validasi dapat dibuat opsional (`nullable`) dengan format string.

**Cari kode berikut:**
```php
$request->validate([
    'values' => 'required|array',
]);
```

**Ubah menjadi:**
```php
$request->validate([
    'values' => 'required|array',
    'customer_name' => 'nullable|string|max:255',
    'customer_phone' => 'nullable|string|max:50',
]);
```

### Langkah 2: Simpan Data ke dalam Submission
Masih di file **`PublicFormController.php`** dalam method `submit()`, temukan bagian pembuatan data (insert) ke tabel `submissions`.

**Cari kode berikut:**
```php
$submission = Submission::create([
    'form_id' => $form->id,
    'submission_number' => $submissionNumber,
    'status' => 'new',
    'ip_address' => $request->ip(),
    'submitted_at' => now(),
]);
```

**Ubah menjadi:**
```php
$submission = Submission::create([
    'form_id' => $form->id,
    'submission_number' => $submissionNumber,
    'customer_name' => $request->input('customer_name'),
    'customer_phone' => $request->input('customer_phone'),
    'status' => 'new',
    'ip_address' => $request->ip(),
    'submitted_at' => now(),
]);
```

### Langkah 3: Update Unit/Feature Test (Opsional namun Sangat Disarankan)
Untuk memastikan kode berjalan dengan baik, pastikan pengujian (*testing*) juga disesuaikan.
Buka file test yang sesuai, kemungkinan besar di **`tests/Feature/Api/V1/PublicFormTest.php`** atau **`tests/Feature/Api/V1/SubmissionTest.php`**.

Tambahkan parameter `customer_name` dan `customer_phone` pada simulasi pengiriman form (JSON payload).
Lalu tambahkan asersi (`assertDatabaseHas`) bahwa data tersebut benar-benar tersimpan di database pada tabel `submissions`.

**Contoh Payload Test:**
```php
$payload = [
    'customer_name' => 'Budi Santoso',
    'customer_phone' => '081234567890',
    'values' => [
        $fieldId => 'Nilai isian',
    ],
];

// ... eksekusi POST request ke /api/v1/forms/{slug}/submit ...

// ... setelah assertStatus(201), tambahkan ...
$this->assertDatabaseHas('submissions', [
    'customer_name' => 'Budi Santoso',
    'customer_phone' => '081234567890',
]);
```

## 4. Cara Menjalankan & Memverifikasi
Setelah kode diubah, verifikasi fungsionalitasnya:

1. **Jalankan formatter**:
   ```bash
   vendor/bin/pint --format agent
   ```
2. **Jalankan spesifik test terkait**:
   ```bash
   php artisan test --compact --filter=PublicFormTest
   ```
   (Sesuaikan nama file test dengan yang kamu ubah di Langkah 3).
3. **Cek database**: Jika diuji secara manual via Postman/Insomnia, pastikan melakukan request dengan body JSON berisi `customer_name` dan `customer_phone` dan periksa database SQLite / MySQL untuk memastikan nilainya masuk ke tabel `submissions`.