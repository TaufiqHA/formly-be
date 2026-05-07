# Rencana Implementasi (Implementation Plan) - User Seeder

Dokumen ini berisi panduan langkah demi langkah untuk membuat `UserSeeder` pada project Formly. Tujuannya adalah menyiapkan data admin *default* agar sistem dapat langsung diuji coba (login) menggunakan kredensial yang tertulis di `API_REFERENCE.md`. Panduan ini dirancang agar mudah dieksekusi oleh Junior Developer maupun AI model.

## Tujuan
Membuat seeder untuk tabel `users` yang akan menyediakan satu akun admin dengan rincian:
- **Email:** `admin@orderly.app` (Sesuai dengan `API_REFERENCE.md`)
- **Password:** `password123`
- **Name:** `Orderly Admin`

---

## Langkah 1: Buat Class Seeder

Gunakan Artisan command untuk men-generate class seeder baru.

**Perintah CLI:**
```bash
php artisan make:seeder UserSeeder
```

---

## Langkah 2: Implementasi UserSeeder

Buka file `database/seeders/UserSeeder.php`. Di dalam method `run()`, tambahkan kode untuk membuat user admin.
Gunakan metode `updateOrCreate` agar seeder ini aman meskipun dijalankan berulang kali (tidak akan terjadi duplikasi data *email* yang sama).

**Kode untuk `database/seeders/UserSeeder.php`:**
```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // updateOrCreate mencari berdasarkan parameter pertama (email). 
        // Jika tidak ada, buat baru dengan parameter kedua. Jika ada, perbarui datanya.
        User::updateOrCreate(
            ['email' => 'admin@orderly.app'],
            [
                'name' => 'Orderly Admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
    }
}
```

---

## Langkah 3: Daftarkan ke DatabaseSeeder

Buka file `database/seeders/DatabaseSeeder.php`. Pastikan class `UserSeeder` di-register agar ikut tereksekusi saat perintah `db:seed` umum dijalankan.

**Kode untuk `database/seeders/DatabaseSeeder.php`:**
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            // (Opsional) tambahkan pemanggilan seeder lain di bawah sini jika diperlukan
        ]);
    }
}
```

---

## Langkah 4: Format File (Laravel Pint)

Setelah file selesai di-edit, pastikan Anda merapikan formatnya dengan Laravel Pint (berdasarkan aturan di ruleset Formly).

**Perintah CLI:**
```bash
vendor/bin/pint --dirty --format agent
```

---

## Langkah 5: Jalankan Seeder ke Database

Terakhir, jalankan seeder untuk memasukkan data tersebut ke database secara nyata. Pastikan database Anda sudah menyala dan tabel `users` sudah ada (`php artisan migrate`).

**Perintah CLI:**
```bash
php artisan db:seed --class=UserSeeder
```

*(Catatan Tambahan: Anda juga bisa menggunakan `php artisan migrate:fresh --seed` jika ke depannya ingin mereset seluruh database dari awal).*

---

## Kriteria Selesai (Definition of Done)
- [ ] File `database/seeders/UserSeeder.php` berhasil terbuat dengan logika `updateOrCreate`.
- [ ] Class `UserSeeder` telah didaftarkan di dalam `DatabaseSeeder.php`.
- [ ] Perintah eksekusi seeder berjalan tanpa error.
- [ ] Akun `admin@orderly.app` dengan kata sandi `password123` sukses digunakan untuk masuk (login) ke sistem melalui endpoint `POST /api/v1/auth/login`.
