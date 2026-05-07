# Technical Design Document
## Sistem Formulir Order Digital dengan Integrasi WhatsApp & Dashboard Admin

**Versi:** 1.0 | **Tanggal:** Mei 2026 | **Status:** Draft

---

## Daftar Isi

1. [Tech Stack & Arsitektur](#1-tech-stack--arsitektur)
2. [Database Schema](#2-database-schema)
3. [API Documentation](#3-api-documentation)
4. [Alur Sistem & Keputusan Arsitektur](#4-alur-sistem--keputusan-arsitektur)

---

## 1. Tech Stack & Arsitektur

Berikut adalah keputusan teknologi yang dipilih berdasarkan kebutuhan sistem, skalabilitas, dan kemudahan maintenance.

| Layer | Teknologi Utama | Fungsi | Library Pendukung | Catatan |
|-------|-----------------|--------|-------------------|---------|
| Frontend (Form & Dashboard) | React 19 + Vite 6 | SPA dengan State-based Routing | Tailwind CSS 4, Motion, Lucide, Recharts | Deployment: Vercel / Netlify |
| AI Integration | Google Gemini API | LLM untuk automasi / smart form | @google/genai | API Key required |
| Backend API | Laravel 11 (PHP 8.3) | REST API, Business Logic | Form Requests, API Resources | Deployment: VPS / Laravel Forge |
| Database | PostgreSQL 16 | RDBMS utama | Eloquent ORM | Managed: Supabase/Neon/AWS |
| Queue & Cache | Redis 7 | In-memory store | Laravel Horizon | Upstash / Self-hosted |
| WhatsApp - Mode A | wa.me Link | Redirect ke WA app | — | — |
| WhatsApp - Mode B | Fonnte / WATI | Business API | REST webhook | API Key required |
| Google Sheets | Google Sheets API v4 | Sinkronisasi order | Laravel Socialite | — |
| Auth (Admin) | Laravel Sanctum | API Token Authentication | HTTP-only Cookie / Bearer Token | Built-in Hash::make |
| File Upload | Cloudflare R2 / S3 | Object storage | Laravel Flysystem | S3 Compatible API |
| Monitoring | Sentry + Uptime Robot | Error & uptime | — | — |

### Daftar Package Laravel Rekomendasi (Accelerator)

Untuk mempercepat proses development di backend Laravel, berikut adalah package pihak ketiga yang direkomendasikan:

1. **`laravel/sanctum`**: Standard resmi Laravel untuk autentikasi API SPA yang ringan dan aman.
2. **`laravel/horizon`**: Dashboard UI dan code-driven configuration untuk monitoring Redis queue (worker background jobs).
3. **`spatie/laravel-query-builder`**: Membangun fitur filter, sort, dan search di REST API menjadi sangat mudah hanya via parameter URL (sangat cocok untuk endpoint `/submissions`).
4. **`owen-it/laravel-auditing`**: Memudahkan pembuatan audit trail otomatis untuk merekam jejak perubahan data pada model Eloquent (create/update/delete).
5. **`maatwebsite/excel`**: Solusi andal untuk mengekspor data submissions ke format CSV atau Excel (mendukung file berukuran besar).
6. **`spatie/laravel-permission`**: Mengatur role dan permission (Admin vs Operator) dengan mudah tanpa membuat authorization dari awal.
7. **`guzzlehttp/guzzle`**: HTTP client (via Laravel HTTP Facade) yang optimal untuk integrasi 3rd party seperti WhatsApp API dan Google API.

### Justifikasi Pilihan Utama

- **React 19 + Vite 6:** Performa tinggi dengan HMR yang cepat, struktur SPA state-based routing untuk UX yang seamless, serta ekosistem modern (Motion, Lucide, Recharts).
- **Google Gemini API:** Terintegrasi menggunakan `@google/genai` untuk menambahkan kemampuan AI pada aplikasi.
- **Laravel 11:** Framework yang matang dengan *developer experience* (DX) terbaik, mempercepat iterasi dengan fitur bawaan lengkap (ORM, routing, validation, file storage).
- **PostgreSQL + Eloquent ORM:** Kombinasi kuat untuk integritas data. Eloquent mendukung manipulasi tipe data kompleks seperti JSONB untuk arsitektur *form dinamis* EAV.
- **Laravel Horizon + Redis:** Mekanisme queue yang handal dengan dashboard bawaan untuk menjalankan job async (kirim pesan WA, sync Google Sheets) tanpa menghalangi *response time* API.

---

## 2. Database Schema

Menggunakan PostgreSQL. Semua tabel mengikuti konvensi `snake_case`. ID menggunakan UUID v4.

### 2.1 Entitas Utama & Relasi

| Tabel | Relasi ke | Tipe | Keterangan |
|-------|-----------|------|------------|
| `users` | — | — | Admin/operator dengan role-based access |
| `forms` | `users` | N:1 | Setiap form dimiliki oleh satu user |
| `form_fields` | `forms` | N:1 | Field-field dari sebuah form |
| `submissions` | `forms` | N:1 | Respon/submission hasil pengisian form |
| `submission_values` | `submissions` + `form_fields` | N:N | Nilai tiap field per submission (EAV pattern) |
| `submission_notes` | `submissions` + `users` | N:1 | Catatan internal operator per submission |
| `wa_settings` | `users` | 1:1 | Konfigurasi WhatsApp API per user |
| `user_preferences` | `users` | 1:1 | Preferensi notifikasi & tampilan user |
| `sync_jobs` | `submissions` | N:1 | Audit & retry untuk job async (WA, Sheets) |

---

### 2.2 Tabel: `users`

Berdasarkan halaman Login (`Login.tsx`) dan Profil (`Profile.tsx`).

| Column | Type | Constraint | Deskripsi |
|--------|------|------------|-----------|
| `id` | UUID | PK | Primary key |
| `name` | VARCHAR(100) | NOT NULL | Nama lengkap (ditampilkan di header profil) |
| `email` | VARCHAR(150) | UNIQUE NOT NULL | Email login (digunakan sebagai credential utama) |
| `password_hash` | TEXT | NOT NULL | Bcrypt hash password |
| `phone` | VARCHAR(20) | NULLABLE | Nomor telepon user |
| `location` | VARCHAR(200) | NULLABLE | Lokasi kantor user (misal: "Jakarta Selatan, Indonesia") |
| `avatar_url` | TEXT | NULLABLE | URL foto profil user |
| `role` | ENUM(`admin`,`operator`) | DEFAULT `operator` | Role user dalam sistem |
| `is_active` | BOOLEAN | DEFAULT TRUE | Status aktif akun (ditunjukkan badge "Aktif" di profil) |
| `last_login_at` | TIMESTAMPTZ | NULLABLE | Timestamp login terakhir |
| `created_at` | TIMESTAMPTZ | DEFAULT NOW() | Tanggal bergabung (ditampilkan di profil) |
| `updated_at` | TIMESTAMPTZ | DEFAULT NOW() | — |

---

### 2.3 Tabel: `forms`

Berdasarkan FormList (`FormList.tsx`) dan FormBuilder (`FormBuilder.tsx`).

| Column | Type | Constraint | Deskripsi |
|--------|------|------------|-----------|
| `id` | UUID | PK | — |
| `user_id` | UUID | FK → users | Pemilik/pembuat form |
| `title` | VARCHAR(200) | NOT NULL | Judul form (ditampilkan di card FormList & header FormBuilder) |
| `description` | TEXT | NULLABLE | Deskripsi singkat form (ditampilkan di bawah judul FormBuilder) |
| `slug` | VARCHAR(150) | UNIQUE NOT NULL | Slug URL untuk public form: `domain.com/f/{slug}` |
| `status` | ENUM(`draft`,`active`) | DEFAULT `draft` | Status publikasi (Aktif/Draft pada FormList) |
| `total_submissions` | INTEGER | DEFAULT 0 | Counter submission berhasil (ditampilkan di card FormList) |
| `created_at` | TIMESTAMPTZ | DEFAULT NOW() | — |
| `updated_at` | TIMESTAMPTZ | DEFAULT NOW() | Ditampilkan sebagai "lastUpdated" di card FormList |

---

### 2.4 Tabel: `form_fields`

Berdasarkan interface `FormElement` di FormBuilder (`FormBuilder.tsx`). Tipe field sesuai dengan toolbox komponen yang tersedia.

| Column | Type | Constraint | Deskripsi |
|--------|------|------------|-----------|
| `id` | UUID | PK | — |
| `form_id` | UUID | FK → forms ON DELETE CASCADE | Form pemilik field |
| `label` | VARCHAR(200) | NOT NULL | Label tampilan field (editable inline di builder) |
| `field_type` | ENUM(`text`,`para`,`drop`,`check`,`radio`,`email`,`phone`,`address`) | NOT NULL | Tipe input — sesuai toolbox FormBuilder |
| `placeholder` | VARCHAR(200) | NULLABLE | Placeholder teks (format: "Masukkan {label}...") |
| `is_required` | BOOLEAN | DEFAULT FALSE | Wajib diisi? (toggle di panel edit field) |
| `options` | JSONB | NULLABLE | Untuk `drop`/`check`/`radio`: `["Opsi 1", "Opsi 2", ...]` |
| `sort_order` | SMALLINT | DEFAULT 0 | Urutan tampil field (berdasarkan posisi di canvas builder) |
| `created_at` | TIMESTAMPTZ | DEFAULT NOW() | — |

> **Catatan:** Kolom `field_key`, `validation_rules`, dan `conditional_logic` dari TDD sebelumnya tidak diimplementasikan di frontend saat ini. Dapat ditambahkan di versi mendatang.

---

### 2.5 Tabel: `submissions`

Berdasarkan Submissions (`Submissions.tsx`), SubmissionDetails (`SubmissionDetails.tsx`), dan Dashboard (`Dashboard.tsx`).

| Column | Type | Constraint | Deskripsi |
|--------|------|------------|-----------|
| `id` | UUID | PK | — |
| `form_id` | UUID | FK → forms | Form asal submission |
| `submission_number` | VARCHAR(20) | UNIQUE NOT NULL | Format: `SUB-YYYY-XXXX` (ditampilkan sebagai ID Respon) |
| `customer_name` | VARCHAR(150) | NULLABLE | Nama pengirim (ditampilkan di kolom "Pengirim") |
| `customer_phone` | VARCHAR(20) | NULLABLE | Nomor WhatsApp pelanggan |
| `customer_email` | VARCHAR(150) | NULLABLE | Email pelanggan (ditampilkan di SubmissionDetails) |
| `customer_company` | VARCHAR(200) | NULLABLE | Nama perusahaan pelanggan (ditampilkan di SubmissionDetails) |
| `status` | ENUM(`new`,`read`,`follow_up`,`done`,`archived`,`spam`) | DEFAULT `new` | Status respon — sesuai dropdown di SubmissionDetails |
| `wa_notif_sent` | BOOLEAN | DEFAULT FALSE | Apakah notifikasi WA sudah terkirim |
| `wa_notif_sent_at` | TIMESTAMPTZ | NULLABLE | Timestamp pengiriman notif WA |
| `ip_address` | INET | NULLABLE | IP submitter untuk rate limiting |
| `submitted_at` | TIMESTAMPTZ | DEFAULT NOW() | Waktu submission (ditampilkan sebagai "Waktu" di tabel) |
| `updated_at` | TIMESTAMPTZ | DEFAULT NOW() | — |

> **Mapping Status UI:** `new` → "Baru/Respon Baru", `read` → "Dibaca/Sudah Dibaca", `follow_up` → "Perlu Follow Up", `done` → "Selesai", `archived` → "Diarsipkan", `spam` → "Spam/Tandai Spam"

---

### 2.6 Tabel: `submission_values`

Menyimpan nilai setiap field per submission (EAV pattern). Berdasarkan data yang ditampilkan di SubmissionDetails (`SubmissionDetails.tsx`).

| Column | Type | Constraint | Deskripsi |
|--------|------|------------|-----------|
| `id` | UUID | PK | — |
| `submission_id` | UUID | FK → submissions ON DELETE CASCADE | Submission pemilik nilai |
| `form_field_id` | UUID | FK → form_fields | Referensi field asalnya |
| `field_label` | VARCHAR(200) | NOT NULL | Label saat submit (snapshot, agar tetap terbaca jika field diedit) |
| `value_text` | TEXT | NULLABLE | Nilai untuk text/para/drop/radio/email/phone/address |
| `value_json` | JSONB | NULLABLE | Untuk checkbox (array) atau file attachment (metadata) |
| `created_at` | TIMESTAMPTZ | DEFAULT NOW() | — |

---

### 2.7 Tabel: `submission_notes` (Catatan Internal)

Berdasarkan panel "Catatan Internal" di SubmissionDetails (`SubmissionDetails.tsx`), yang mendukung banyak catatan per submission dengan nama author dan timestamp.

| Column | Type | Constraint | Deskripsi |
|--------|------|------------|-----------|
| `id` | UUID | PK | — |
| `submission_id` | UUID | FK → submissions ON DELETE CASCADE | Submission terkait |
| `user_id` | UUID | FK → users | Penulis catatan (ditampilkan sebagai "Admin Pusat (Sarah)") |
| `content` | TEXT | NOT NULL | Isi catatan internal |
| `created_at` | TIMESTAMPTZ | DEFAULT NOW() | Timestamp catatan (ditampilkan sebagai "12 Okt, 15:00") |

---

### 2.8 Tabel: `wa_settings` (Konfigurasi WhatsApp API)

Berdasarkan halaman WhatsApp API (`WhatsAppSettings.tsx`).

| Column | Type | Constraint | Deskripsi |
|--------|------|------------|-----------|
| `id` | UUID | PK | — |
| `user_id` | UUID | FK → users, UNIQUE | Satu konfigurasi per user |
| `api_key` | TEXT | NULLABLE ENCRYPTED | WhatsApp API Key (input type password, AES-256 encrypted) |
| `phone_number` | VARCHAR(20) | NULLABLE | Nomor pengirim WhatsApp (contoh: "+62 812-3456-7890") |
| `connection_status` | ENUM(`connected`,`disconnected`) | DEFAULT `disconnected` | Status koneksi terakhir |
| `wa_template_new_order` | TEXT | NULLABLE | Template notifikasi pesanan baru (dengan variabel `{nama}`, `{id}`, `{total}`, `{tanggal}`) |
| `last_tested_at` | TIMESTAMPTZ | NULLABLE | Timestamp terakhir kali tes koneksi berhasil |
| `created_at` | TIMESTAMPTZ | DEFAULT NOW() | — |
| `updated_at` | TIMESTAMPTZ | DEFAULT NOW() | — |

---

### 2.9 Tabel: `user_preferences` (Pengaturan Aplikasi)

Berdasarkan halaman Settings (`Settings.tsx`).

| Column | Type | Constraint | Deskripsi |
|--------|------|------------|-----------|
| `id` | UUID | PK | — |
| `user_id` | UUID | FK → users, UNIQUE | Satu preferensi per user |
| `notif_email_new_order` | BOOLEAN | DEFAULT TRUE | Toggle: terima email saat ada pesanan baru |
| `notif_wa_auto_confirm` | BOOLEAN | DEFAULT TRUE | Toggle: kirim konfirmasi WA otomatis ke pelanggan |
| `theme` | ENUM(`light`,`dark`) | DEFAULT `light` | Tema tampilan aplikasi (Terang/Gelap) |
| `created_at` | TIMESTAMPTZ | DEFAULT NOW() | — |
| `updated_at` | TIMESTAMPTZ | DEFAULT NOW() | — |

---

### 2.10 Tabel: `sync_jobs` (untuk audit & retry)

| Column | Type | Constraint | Deskripsi |
|--------|------|------------|-----------|
| `id` | UUID | PK | — |
| `submission_id` | UUID | FK → submissions | Submission terkait |
| `job_type` | ENUM(`wa_notify`,`sheets_sync`,`wa_confirm`) | NOT NULL | Jenis job async |
| `status` | ENUM(`pending`,`processing`,`done`,`failed`) | DEFAULT `pending` | — |
| `attempts` | SMALLINT | DEFAULT 0 | Jumlah percobaan |
| `last_error` | TEXT | NULLABLE | Pesan error terakhir |
| `next_retry_at` | TIMESTAMPTZ | NULLABLE | Jadwal retry berikutnya |
| `created_at` | TIMESTAMPTZ | DEFAULT NOW() | — |
| `updated_at` | TIMESTAMPTZ | DEFAULT NOW() | — |

---

### 2.11 Index yang Direkomendasikan

| Index | Tabel.Kolom | Alasan |
|-------|-------------|--------|
| `idx_submissions_user_status` | `submissions(form_id, status)` | Filter utama halaman Data Masuk |
| `idx_submissions_submitted` | `submissions(form_id, submitted_at DESC)` | Query tren respon per form (chart Dashboard) |
| `idx_form_fields_sort` | `form_fields(form_id, sort_order)` | Render form terurut di FormBuilder & PublicForm |
| `idx_submission_values_sub` | `submission_values(submission_id)` | Lookup semua nilai satu submission |
| `idx_submission_notes_sub` | `submission_notes(submission_id, created_at)` | Tampil catatan internal terurut |
| `idx_forms_slug` | `forms(slug)` | Lookup form by URL (unique) untuk PublicForm |
| `idx_forms_user` | `forms(user_id, updated_at DESC)` | List form milik user terurut (FormList) |
| `idx_sync_jobs_retry` | `sync_jobs(status, next_retry_at)` | Queue worker polling |

---

## 3. API Documentation

**Base URL:** `https://api.orderform.app/v1` | **Format:** JSON | **Auth:** Bearer JWT *(kecuali endpoint Public)*

### 3.1 Daftar Endpoint

| Method | Auth | Path | Deskripsi |
|--------|------|------|-----------|
| **── AUTH ──** | | | |
| POST | Public | `/auth/login` | Login admin/operator, mengembalikan JWT |
| POST | JWT | `/auth/logout` | Invalidate session |
| GET | JWT | `/auth/me` | Data user yang sedang login |
| **── FORMS ──** | | | |
| GET | JWT | `/forms` | List semua form milik bisnis user |
| POST | JWT | `/forms` | Buat form baru |
| GET | JWT | `/forms/:id` | Detail form (termasuk semua field) |
| PUT | JWT | `/forms/:id` | Update konfigurasi form |
| DELETE | JWT | `/forms/:id` | Hapus form (soft-delete) |
| PATCH | JWT | `/forms/:id/status` | Aktifkan/nonaktifkan form |
| GET | JWT | `/forms/:id/stats` | Statistik form (views, submissions, rate) |
| **── FORM FIELDS ──** | | | |
| PUT | JWT | `/forms/:id/fields` | Simpan/update semua field sekaligus (batch upsert) |
| POST | JWT | `/forms/:id/fields` | Tambah satu field baru |
| DELETE | JWT | `/forms/:formId/fields/:fieldId` | Hapus satu field |
| **── PUBLIC (Customer-Facing) ──** | | | |
| GET | Public | `/public/forms/:slug` | Ambil struktur form berdasarkan slug URL |
| POST | Public | `/public/forms/:slug/submit` | Submit form order (rate-limited: 10/IP/menit) |
| **── SUBMISSIONS ──** | | | |
| GET | JWT | `/submissions` | List submission dengan filter (status, tanggal, form, search) |
| GET | JWT | `/submissions/:id` | Detail satu submission + semua nilai field |
| PATCH | JWT | `/submissions/:id/status` | Update status submission |
| PATCH | JWT | `/submissions/:id/notes` | Update catatan internal operator |
| POST | JWT | `/submissions/:id/resend-wa` | Kirim ulang notifikasi WA ke admin |
| GET | JWT | `/submissions/export` | Export submissions ke CSV/Excel |
| **── SETTINGS ──** | | | |
| GET | JWT | `/settings` | Ambil konfigurasi bisnis saat ini |
| PUT | JWT | `/settings` | Update konfigurasi bisnis (brand, dsb) |
| PUT | JWT | `/settings/whatsapp` | Update konfigurasi & template API WhatsApp |
| POST | JWT | `/settings/whatsapp/test` | Tes koneksi credential API WhatsApp |
| POST | JWT | `/settings/google/connect` | Inisiasi OAuth Google untuk Sheets |
| GET | Public | `/settings/google/callback` | OAuth callback dari Google |
| DELETE | JWT | `/settings/google/disconnect` | Cabut koneksi Google Sheets |
| GET | JWT | `/settings/operators` | List operator bisnis |
| POST | JWT | `/settings/operators` | Tambah operator baru |
| DELETE | JWT | `/settings/operators/:id` | Hapus operator |
| **── ANALYTICS ──** | | | |
| GET | JWT | `/analytics/summary` | Ringkasan: total order hari ini/minggu/bulan |
| GET | JWT | `/analytics/trend` | Data tren order per periode (untuk chart) |
| GET | JWT | `/analytics/status-distribution` | Distribusi order per status (pie chart) |

---

### 3.2 Referensi Request & Response Lengkap

Mengingat banyaknya jumlah endpoint (lebih dari 30 endpoint) untuk modul Forms, Submissions, Settings, dan Analytics, seluruh contoh spesifikasi request body, parameter, dan JSON response secara lengkap telah dipindahkan ke file terpisah agar TDD ini tetap bersih.

👉 **Silakan lihat [API_REFERENCE.md](./API_REFERENCE.md) untuk melihat spesifikasi detail dari seluruh API.**

### 3.3 Error Code Standard

| HTTP | Error Code | Deskripsi |
|------|------------|-----------|
| 400 | `BAD_REQUEST` | Input tidak dapat diparse |
| 401 | `UNAUTHORIZED` | Token tidak valid / expired |
| 403 | `FORBIDDEN` | Akses tidak diizinkan (beda bisnis) |
| 404 | `NOT_FOUND` | Resource tidak ditemukan |
| 422 | `VALIDATION_FAILED` | Validasi field gagal (dengan detail error per field) |
| 429 | `RATE_LIMITED` | Terlalu banyak request (rate limit) |
| 500 | `INTERNAL_ERROR` | Error server internal |
| 503 | `SERVICE_UNAVAILABLE` | Dependency (WA/Sheets) tidak tersedia |

---

### 3.4 Autentikasi

Semua endpoint bertanda JWT memerlukan header:

```
Authorization: Bearer <access_token>
```

- Token diperoleh dari `POST /auth/login`
- Masa berlaku token: **24 jam**
- Payload JWT berisi: `{ user_id, business_id, role, iat, exp }`
- Refresh token tidak diimplementasikan di v1.0 — user login ulang saat expired

---

## 4. Alur Sistem & Keputusan Arsitektur

### 4.1 Alur Submit Order (Async)

```
Customer → POST /public/forms/:slug/submit
    │
    ├── Validasi input (Zod schema)
    ├── Rate limit check (10/IP/menit via Redis)
    ├── Simpan ke DB: orders + order_values
    ├── Enqueue jobs ke BullMQ:
    │       ├── job: wa_notify  (kirim WA ke admin)
    │       └── job: sheets_sync (append row ke Sheets)
    └── Response 201 + wa_redirect_url (jika Mode A)

Queue Worker (background):
  wa_notify → cek mode (A/B)
      Mode A: simpan wa_redirect_url di DB (sudah dilakukan)
      Mode B: POST ke provider API → update wa_notif_sent=true
      Gagal   → retry 3x (backoff 1m, 5m, 30m) → status=failed

  sheets_sync → Google Sheets API append row
      Gagal → retry setiap 5m selama max 30m → notif admin
```

---

### 4.2 Keputusan Desain Penting

| Keputusan | Pilihan | Alasan |
|-----------|---------|--------|
| Struktur field order | EAV (`order_values`) | Field form bersifat dinamis per bisnis — skema relasional rigid tidak memungkinkan |
| Job queue | BullMQ + Redis | Retry otomatis, delay, dead-letter queue, monitoring UI tersedia |
| Multi-tenant | Shared DB + `business_id` FK | Cukup untuk v1.0; row-level isolation via query filter |
| Enkripsi secrets | AES-256 di application layer | API key WA & Google token sensitif — enkripsi sebelum masuk DB |
| Snapshot field label | Simpan di `order_values` | Jika admin edit/hapus field, data order lama tetap terbaca dengan benar |
| Slug form | VARCHAR UNIQUE | URL yang mudah dibagikan: `domain.com/f/order-kaos-2026` |

---

*Dokumen ini bersifat living document dan akan diperbarui seiring perkembangan proyek.*
