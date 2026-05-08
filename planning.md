# Perencanaan Implementasi Endpoint Analytics

Dokumen ini berisi langkah-langkah teknis untuk mengimplementasikan controller dan endpoint **Analytics** (Statistik/Dashboard) pada project Formly. Langkah-langkah ini disusun sesederhana mungkin agar mudah dipahami dan dieksekusi langsung oleh Junior Developer atau AI Model.

Referensi dari `API_REFERENCE.md` bagian **7. Analytics**.

---

## Tahap 1: Pembuatan Controller

Kita akan membuat satu controller utama untuk mengembalikan data agregasi statistik dashboard.

**Langkah:**
1. Buka terminal/command prompt.
2. Jalankan perintah artisan berikut:
   ```bash
   php artisan make:controller Api/V1/AnalyticsController
   ```

---

## Tahap 2: Mendaftarkan Route API

Data analitik bersifat sensitif, sehingga rute ini wajib diproteksi dengan middleware `auth:sanctum`.

**Langkah:**
Buka `routes/api.php` dan tambahkan baris berikut di dalam grup `auth:sanctum`:

```php
use App\Http\Controllers\Api\V1\AnalyticsController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // ... rute lain ...
    
    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/summary', [AnalyticsController::class, 'summary']);
        Route::get('/trend', [AnalyticsController::class, 'trend']);
        Route::get('/status-distribution', [AnalyticsController::class, 'statusDistribution']);
    });
});
```

---

## Tahap 3: Implementasi Method pada `AnalyticsController`

Buka file `app/Http/Controllers/Api/V1/AnalyticsController.php`. Tambahkan import model dan facade yang dibutuhkan:
```php
use App\Models\Form;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
```

### 1. Method `summary` (Ringkasan KPI)
Mengembalikan total respon, form aktif, dan konversi rata-rata.
```php
public function summary()
{
    // 1. Hitung total form yang statusnya active
    $activeForms = Form::where('status', 'active')->count();

    // 2. Hitung total semua submission
    $totalResponses = Submission::count();

    // 3. Konversi (Conversion Rate). 
    // Catatan: Jika saat ini belum ada tabel 'views' untuk melacak total pengunjung form, 
    // kita bisa buat nilai konversi dummy atau 0 sementara, lalu update ketika fitur view selesai.
    // Misal: (total_submissions / total_views) * 100
    
    $averageConversion = 0; // Default placeholder, akan diimplementasikan nanti saat data views tersedia.

    return response()->json([
        'success' => true,
        'data' => [
            'total_responses' => $totalResponses,
            'active_forms' => $activeForms,
            'average_conversion' => $averageConversion
        ]
    ]);
}
```

### 2. Method `trend` (Data Chart: Respon per Hari)
Mengambil data jumlah submission per hari, misalnya untuk 7 hari terakhir.
```php
public function trend(Request $request)
{
    // Mengambil rentang 7 hari ke belakang dari hari ini
    $startDate = Carbon::today()->subDays(6);
    $endDate = Carbon::today()->endOfDay();

    // Lakukan grouping by date menggunakan fitur database
    $trends = Submission::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(id) as count')
        )
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('date')
        ->orderBy('date', 'asc')
        ->get();

    // Format output ke bentuk yang diinginkan chart frontend (misal: "Sen", "Sel")
    // atau biarkan tanggal aslinya dan biar frontend yang format. Di API reference berbentuk "name", "value".
    $formattedTrends = $trends->map(function ($item) {
        $carbonDate = Carbon::parse($item->date);
        
        // Translasi hari ke bahasa Indonesia (opsional, frontend bisa handle ini juga)
        $hariIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $name = substr($hariIndo[$carbonDate->dayOfWeek], 0, 3); // Ambil 3 huruf awal ("Sen", "Sel")

        return [
            'name' => $name, 
            'value' => (int) $item->count
        ];
    });

    // Opsional: Untuk hari yang kosong (0 submission), mungkin perlu logic tambahan 
    // untuk mengisi tanggal kosong dengan value 0 agar grafik tidak putus.
    
    return response()->json([
        'success' => true,
        'data' => $formattedTrends
    ]);
}
```

### 3. Method `statusDistribution` (Distribusi Status Order)
Mengembalikan jumlah submission dikelompokkan berdasarkan status (`new`, `read`, `done`, dll).
```php
public function statusDistribution()
{
    // Agregasi jumlah per status
    $distribution = Submission::select('status', DB::raw('COUNT(id) as count'))
        ->groupBy('status')
        ->get();

    $formattedDistribution = $distribution->map(function ($item) {
        return [
            'status' => $item->status,
            'count' => (int) $item->count
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $formattedDistribution
    ]);
}
```

---

## Checklist Eksekusi (Untuk Junior/AI)

- [ ] Jalankan command pembuatan controller `AnalyticsController`.
- [ ] Daftarkan 3 route analytics di `routes/api.php` di bawah middleware `auth:sanctum`.
- [ ] Tulis logika `summary` menggunakan fungsi agregasi Eloquent (`count()`).
- [ ] Tulis logika `trend` menggunakan query builder dan raw DB expression untuk grouping per tanggal.
- [ ] Tulis logika `statusDistribution` menggunakan Group By pada status submission.
- [ ] Jalankan formatter PHP Pint (`vendor/bin/pint --dirty --format agent`).
