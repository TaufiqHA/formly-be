<div align="center">
  <h1>📝 Formly Backend API</h1>
  <p><strong>Sistem Manajemen Formulir Digital & Order Dinamis (Berbasis Laravel 11)</strong></p>

  <!-- Badges -->
  <p>
    <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP Version" />
    <img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel Version" />
    <img src="https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square" alt="License" />
  </p>
</div>

---

## 🚀 Apa itu Formly?
**Formly** adalah *backend API* modern yang dirancang untuk membuat, mengelola, dan mempublikasikan formulir digital secara dinamis (mirip dengan Google Forms atau Typeform). Sistem ini menyediakan *endpoint* RESTful yang handal untuk menangani pembuatan *form*, tipe *field* yang beragam, hingga manajemen *submission* data secara aman.

## ✨ Fitur Utama
- **🔐 Autentikasi Aman:** Menggunakan Laravel Sanctum untuk manajemen Token berbasis API.
- **🏗️ Dynamic Form Builder:** Buat *form* dengan *field* tak terbatas (Text, Radio, Checkbox, dll).
- **📊 Submission Tracking:** Lacak data yang masuk dengan mudah dilengkapi analitik sederhana.
- **📜 Audit Trail System:** Semua perubahan (*Create, Update, Delete*) pada data penting dicatat secara otomatis.
- **🚀 API-First Design:** Struktur JSON respons yang konsisten di semua *endpoint*.

---

## 🛠️ Tech Stack
- **Framework:** [Laravel 11](https://laravel.com/)
- **Bahasa:** PHP 8.3
- **Autentikasi:** Laravel Sanctum
- **Database:** MySQL / PostgreSQL
- **Code Formatter:** Laravel Pint

---

## 🏁 Mulai Menggunakan (Getting Started)

Ikuti langkah-langkah di bawah ini untuk menjalankan *backend* Formly di komputer lokal Anda:

### 1. Kloning Repositori
```bash
git clone https://github.com/TaufiqHA/formly-be.git
cd formly-be
```

### 2. Instalasi Dependensi
```bash
composer install
```

### 3. Konfigurasi Environment
Buat salinan file konfigurasi bawaan dan *generate* Application Key.
```bash
cp .env.example .env
php artisan key:generate
```
*(Pastikan Anda menyesuaikan konfigurasi database `DB_*` di dalam file `.env` dengan database lokal Anda).*

### 4. Migrasi & Seeding Database
Buat struktur tabel dan masukkan data bawaan (*admin user*).
```bash
php artisan migrate --seed
```

### 5. Jalankan Server Lokal
```bash
php artisan serve
```
API dapat diakses melalui `http://localhost:8000/api/v1/...`

---

## 🔑 Kredensial Login Default
Setelah Anda menjalankan perintah *seed* di atas, Anda dapat login menggunakan kredensial berikut:
- **Email:** `admin@orderly.app` *(atau `admin@mail.com` bergantung pada data seeder)*
- **Password:** `password` / `password123`

---

## 📖 Dokumentasi Referensi API
Untuk melihat daftar lengkap endpoint (*Routes, Payload, JSON Response*), silakan merujuk ke dokumen:
👉 **[API_REFERENCE.md](./API_REFERENCE.md)**

---
<div align="center">
  Dibuat dengan ❤️ menggunakan Laravel
</div>
