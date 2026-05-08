# Perencanaan Implementasi Endpoint Settings

Dokumen ini berisi langkah-langkah teknis untuk mengimplementasikan controller dan endpoint **Settings** pada project Formly. Langkah-langkah ini disusun sesederhana mungkin agar mudah dipahami dan dieksekusi langsung oleh Junior Developer atau AI Model.

Referensi dari `API_REFERENCE.md` bagian **6. Settings**.

---

## Tahap 1: Pembuatan Controller

Kita akan membuat satu controller utama untuk mengelola pengaturan dan preferensi User (termasuk setting WhatsApp).

**Langkah:**
1. Buka terminal/command prompt.
2. Jalankan perintah artisan berikut:
   ```bash
   php artisan make:controller Api/V1/SettingController
   ```

---

## Tahap 2: Mendaftarkan Route API

Endpoint settings berkaitan dengan user yang sedang login, sehingga wajib diproteksi dengan middleware `auth:sanctum`.

**Langkah:**
Buka `routes/api.php` dan tambahkan baris berikut di dalam grup `auth:sanctum`:

```php
use App\Http\Controllers\Api\V1\SettingController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // ... rute lain seperti forms & submissions ...
    
    // Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index']);
        Route::put('/', [SettingController::class, 'updatePreferences']);
        Route::put('/whatsapp', [SettingController::class, 'updateWhatsApp']);
        Route::post('/whatsapp/test', [SettingController::class, 'testWhatsApp']);
    });
});
```

---

## Tahap 3: Implementasi Method pada `SettingController`

Buka file `app/Http/Controllers/Api/V1/SettingController.php`. Tambahkan import model dan facade yang diperlukan:
```php
use App\Models\UserPreference;
use App\Models\WaSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http; // Untuk testing WhatsApp API
```

### 1. Method `index` (Mengambil Pengaturan User Saat Ini)
```php
public function index()
{
    $userId = Auth::id();

    // 1. Ambil atau buat UserPreference default jika belum ada
    $preferences = UserPreference::firstOrCreate(
        ['user_id' => $userId],
        [
            'notif_email_new_order' => true,
            'notif_wa_auto_confirm' => false,
            'theme' => 'light'
        ]
    );

    // 2. Ambil atau buat WaSetting default jika belum ada
    $waSetting = WaSetting::firstOrCreate(
        ['user_id' => $userId],
        [
            'phone_number' => null,
            'api_key' => null,
            'connection_status' => 'disconnected',
            'wa_template_new_order' => 'Halo {nama}, pesanan {id} Anda diterima.'
        ]
    );

    // 3. Format Output
    return response()->json([
        'success' => true,
        'data' => [
            'preferences' => [
                'notif_email_new_order' => (bool) $preferences->notif_email_new_order,
                'notif_wa_auto_confirm' => (bool) $preferences->notif_wa_auto_confirm,
                'theme' => $preferences->theme,
            ],
            'whatsapp' => [
                'phone_number' => $waSetting->phone_number,
                'connection_status' => $waSetting->connection_status,
                'wa_template_new_order' => $waSetting->wa_template_new_order,
            ]
        ]
    ]);
}
```

### 2. Method `updatePreferences` (Simpan Preferensi Web/Email)
```php
public function updatePreferences(Request $request)
{
    // Validasi Input
    $request->validate([
        'notif_email_new_order' => 'boolean',
        'notif_wa_auto_confirm' => 'boolean',
        'theme' => 'string|in:light,dark,system'
    ]);

    $userId = Auth::id();
    $preferences = UserPreference::where('user_id', $userId)->first();

    if ($preferences) {
        $preferences->update($request->only([
            'notif_email_new_order', 
            'notif_wa_auto_confirm', 
            'theme'
        ]));
    }

    return response()->json([
        'success' => true,
        'message' => 'Preferensi berhasil disimpan'
    ]);
}
```

### 3. Method `updateWhatsApp` (Simpan Konfigurasi WA)
```php
public function updateWhatsApp(Request $request)
{
    // Validasi Input
    $request->validate([
        'api_key' => 'nullable|string',
        'phone_number' => 'nullable|string',
        'wa_template_new_order' => 'nullable|string'
    ]);

    $userId = Auth::id();
    $waSetting = WaSetting::where('user_id', $userId)->first();

    if ($waSetting) {
        $waSetting->update($request->only([
            'api_key', 
            'phone_number', 
            'wa_template_new_order'
        ]));
        
        // Reset status koneksi jika ada perubahan API Key atau Nomor (opsional)
        if ($request->has('api_key') || $request->has('phone_number')) {
            $waSetting->update(['connection_status' => 'disconnected']);
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'Konfigurasi WhatsApp berhasil disimpan'
    ]);
}
```

### 4. Method `testWhatsApp` (Test Koneksi)
```php
public function testWhatsApp()
{
    $userId = Auth::id();
    $waSetting = WaSetting::where('user_id', $userId)->first();

    if (!$waSetting || !$waSetting->api_key) {
        return response()->json([
            'success' => false,
            'message' => 'API Key belum dikonfigurasi'
        ], 400);
    }

    // TODO: Ganti URL dan request body sesuai dengan API Provider WhatsApp yang digunakan
    // Contoh sederhana menggunakan Http Client:
    /*
    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $waSetting->api_key
        ])->post('https://api.whatsapp-provider.com/v1/test');

        if ($response->successful()) {
            $waSetting->update(['connection_status' => 'connected']);
            return response()->json([
                'success' => true, 
                'message' => 'Koneksi ke provider WhatsApp berhasil'
            ]);
        }
        
        $waSetting->update(['connection_status' => 'failed']);
        return response()->json([
            'success' => false, 
            'message' => 'Gagal terhubung ke provider'
        ], 400);
        
    } catch (\Exception $e) {
        $waSetting->update(['connection_status' => 'failed']);
        return response()->json([
            'success' => false, 
            'message' => 'Terjadi kesalahan saat test koneksi: ' . $e->getMessage()
        ], 500);
    }
    */

    // Placeholder response untuk saat ini
    return response()->json([
        'success' => true, 
        'message' => 'Koneksi ke provider WhatsApp berhasil (Simulasi)'
    ]);
}
```

---

## Checklist Eksekusi (Untuk Junior/AI)

- [ ] Jalankan command pembuatan controller `SettingController`.
- [ ] Daftarkan 4 route settings di `routes/api.php` di bawah middleware `auth:sanctum`.
- [ ] Implementasikan method `index` menggunakan `firstOrCreate` untuk inisiasi awal jika row belum ada di DB.
- [ ] Implementasikan method `updatePreferences` dengan memvalidasi tipe boolean dan string enum.
- [ ] Implementasikan method `updateWhatsApp` dan pastikan data tersimpan.
- [ ] Implementasikan method `testWhatsApp` (termasuk try-catch boilerplate untuk HTTP Request).
- [ ] Jalankan formatter PHP Pint (`vendor/bin/pint --dirty --format agent`).
