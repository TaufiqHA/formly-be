# Rencana Implementasi (Implementation Plan) - Authentication API

Dokumen ini berisi panduan langkah demi langkah untuk mengimplementasikan fitur autentikasi pada project Formly (berbasis Laravel 11 + Sanctum). Panduan ini dirancang agar mudah diikuti oleh Junior Developer atau AI model lainnya.

## Tujuan
Membuat `AuthController` dan mendefinisikan endpoint untuk Authentication sesuai dengan `API_REFERENCE.md`:
1. `POST /api/v1/auth/login` (Public)
2. `POST /api/v1/auth/logout` (Membutuhkan Token JWT/Sanctum)
3. `GET /api/v1/auth/me` (Membutuhkan Token JWT/Sanctum)

## Prasyarat
- Memahami konsep dasar Laravel (Controller, Routing, Request Validation).
- Laravel Sanctum sudah terinstal (bawaan Laravel 11) dan tabel `personal_access_tokens` sudah ada di database.

---

## Langkah 1: Buat AuthController

Gunakan Artisan command untuk membuat controller baru.

**Perintah CLI:**
```bash
php artisan make:controller Api/V1/AuthController
```

---

## Langkah 2: Tambahkan Route API

Buka file `routes/api.php` (jika belum ada, jalankan `php artisan install:api`). Tambahkan konfigurasi routing untuk endpoint autentikasi dengan prefix `v1`.

**Kode untuk `routes/api.php`:**
```php
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function () {
    // Public route
    Route::post('/login', [AuthController::class, 'login']);

    // Protected routes (Butuh Token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});
```

---

## Langkah 3: Implementasi Method di AuthController

Buka file `app/Http/Controllers/Api/V1/AuthController.php` dan implementasikan 3 method utama: `login`, `logout`, dan `me`. Gunakan standar format respons JSON Formly: `{"success": true, "data": {...}, "message": "..."}`.

### 3.1. Method `login`
Tugas: Memvalidasi input (email, password), mengecek kredensial, dan membuat token Sanctum jika berhasil.

**Kode Implementasi:**
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Cari user berdasarkan email
        $user = User::where('email', $request->email)->first();

        // 3. Cek user dan verifikasi password
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan tidak cocok dengan data kami.'],
            ]);
        }

        // 4. Hapus token lama (opsional, jika ingin single device login)
        // $user->tokens()->delete();

        // 5. Buat token baru menggunakan Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6. Kembalikan respons sesuai format API_REFERENCE.md
        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    // 'role' => 'admin' // Sesuaikan jika ada implementasi Spatie Role/Permission
                ]
            ],
            'message' => 'Login berhasil'
        ]);
    }
```

### 3.2. Method `logout`
Tugas: Menghapus token yang sedang digunakan saat ini.

**Lanjutan Kode (Tambahkan di dalam kelas yang sama):**
```php
    public function logout(Request $request)
    {
        // Hapus token yang digunakan untuk request saat ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }
```

### 3.3. Method `me`
Tugas: Mengambil profil pengguna yang saat ini sedang login.

**Lanjutan Kode (Tambahkan di dalam kelas yang sama):**
```php
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                // tambahkan field lain yang diperlukan sesuai API_REFERENCE.md
                // seperti phone, location, avatar_url, role
                'created_at' => $user->created_at,
            ]
        ]);
    }
}
```

---

## Langkah 4: Format File (Laravel Pint)
Setelah semua kode ditulis, pastikan menjalankan Laravel Pint untuk merapikan kode agar sesuai dengan standar Laravel (PSR-12/Laravel Style).

**Perintah CLI:**
```bash
vendor/bin/pint --dirty --format agent
```

---

## Kriteria Selesai (Definition of Done)
- [ ] File `app/Http/Controllers/Api/V1/AuthController.php` berhasil dibuat dan berisi logic 3 method tersebut.
- [ ] File `routes/api.php` memiliki routing untuk `/login` (public) dan `/logout`, `/me` (protected auth:sanctum).
- [ ] Format kembalian (JSON Response) sudah mengikuti standar yang ditentukan di `API_REFERENCE.md`.
- [ ] Kode sudah di-format rapi menggunakan Laravel Pint.
