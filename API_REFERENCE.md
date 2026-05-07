# Dokumentasi Referensi API - Orderly

**Base URL:** `https://api.orderform.app/v1`
**Format:** JSON
**Autentikasi:** Laravel Sanctum (Bearer Token) `Authorization: Bearer <token>` (Kecuali endpoint bertanda Public)

## Daftar Isi
1. [Authentication](#1-authentication)
2. [Forms](#2-forms)
3. [Form Fields](#3-form-fields)
4. [Public (Customer-Facing)](#4-public)
5. [Submissions](#5-submissions)
6. [Settings](#6-settings)
7. [Analytics](#7-analytics)

---

## 1. Authentication

### POST `/auth/login` (Public)
Login admin/operator untuk mendapatkan token.

**Request Body:**
```json
{
  "email": "admin@orderly.app",
  "password": "password123"
}
```

**Response 200 OK:**
```json
{
  "success": true,
  "data": {
    "token": "1|abcdef1234567890...",
    "user": {
      "id": "e8e12345-e89b-12d3-a456-426614174000",
      "name": "Orderly Admin",
      "email": "admin@orderly.app",
      "role": "admin"
    }
  },
  "message": "Login berhasil"
}
```

### POST `/auth/logout` (JWT)
Invalidate session/token saat ini.

**Response 200 OK:**
```json
{
  "success": true,
  "message": "Logout berhasil"
}
```

### GET `/auth/me` (JWT)
Ambil data profil user yang sedang login.

**Response 200 OK:**
```json
{
  "success": true,
  "data": {
    "id": "e8e12345...",
    "name": "Orderly Admin",
    "email": "admin@orderly.app",
    "phone": "+62 812-3456-7890",
    "location": "Jakarta Selatan, Indonesia",
    "avatar_url": "https://...",
    "role": "admin",
    "created_at": "2023-01-01T00:00:00Z"
  }
}
```

---

## 2. Forms

### GET `/forms` (JWT)
List semua form milik user.

**Response 200 OK:**
```json
{
  "success": true,
  "data": [
    {
      "id": "f47ac10b-58cc-4372-a567-0e02b2c3d479",
      "title": "Formulir Pemesanan Barang",
      "slug": "formulir-pemesanan-barang",
      "status": "active",
      "total_submissions": 1248,
      "updated_at": "2023-10-12T10:00:00Z"
    }
  ]
}
```

### POST `/forms` (JWT)
Buat form baru (biasanya kosong / template default).

**Request Body:**
```json
{
  "title": "Survey Kepuasan Pelanggan",
  "description": "Bantu kami menjadi lebih baik"
}
```

**Response 201 Created:**
```json
{
  "success": true,
  "data": {
    "id": "new-uuid",
    "title": "Survey Kepuasan Pelanggan",
    "slug": "survey-kepuasan-pelanggan",
    "status": "draft"
  }
}
```

### GET `/forms/:id` (JWT)
Detail form beserta struktur field-nya (digunakan di FormBuilder).

**Response 200 OK:**
```json
{
  "success": true,
  "data": {
    "id": "f47ac10b...",
    "title": "Formulir Pemesanan Barang",
    "description": "Silakan isi detail pesanan Anda",
    "status": "active",
    "fields": [
      {
        "id": "field-uuid-1",
        "label": "Nama Lengkap",
        "field_type": "text",
        "placeholder": "Masukkan nama lengkap...",
        "is_required": true,
        "options": null,
        "sort_order": 0
      }
    ]
  }
}
```

### PUT `/forms/:id` (JWT)
Update judul, deskripsi form.

**Request Body:**
```json
{
  "title": "Formulir Pemesanan Barang (Update)",
  "description": "Silakan isi detail pesanan."
}
```

**Response 200 OK:** (Format sama dengan GET `/forms/:id`)

### DELETE `/forms/:id` (JWT)
Hapus form.

**Response 200 OK:**
```json
{
  "success": true,
  "message": "Form berhasil dihapus"
}
```

### PATCH `/forms/:id/status` (JWT)
Ubah status form dari draft ke active atau sebaliknya.

**Request Body:**
```json
{
  "status": "active"
}
```

### GET `/forms/:id/stats` (JWT)
Statistik form (Total views vs submissions).

**Response 200 OK:**
```json
{
  "success": true,
  "data": {
    "total_views": 5000,
    "total_submissions": 1248,
    "conversion_rate": 24.96
  }
}
```

---

## 3. Form Fields

### PUT `/forms/:id/fields` (JWT)
Simpan / Update keseluruhan field sekaligus (dari canvas FormBuilder).

**Request Body:**
```json
{
  "fields": [
    {
      "id": "field-uuid-1", 
      "label": "Nama Lengkap",
      "field_type": "text",
      "placeholder": "Masukkan nama lengkap...",
      "is_required": true,
      "options": null,
      "sort_order": 0
    },
    {
      "label": "Pilihan Paket", 
      "field_type": "radio",
      "is_required": true,
      "options": ["Basic", "Premium"],
      "sort_order": 1
    }
  ]
}
```
*(Catatan: field tanpa `id` akan di-create, dengan `id` akan di-update, yang tidak dikirim akan di-delete).*

**Response 200 OK:**
```json
{
  "success": true,
  "message": "Struktur form berhasil disimpan"
}
```

---

## 4. Public

### GET `/public/forms/:slug` (Public)
Ambil konfigurasi form untuk dirender ke pengunjung.

**Response 200 OK:**
```json
{
  "success": true,
  "data": {
    "id": "f47ac10b...",
    "title": "Formulir Pemesanan",
    "description": "Isi data Anda",
    "fields": [
      {
        "id": "field-uuid-1",
        "label": "Nama",
        "field_type": "text",
        "is_required": true,
        "options": null
      }
    ]
  }
}
```

### POST `/public/forms/:slug/submit` (Public)
Customer mensubmit formulir.

**Request Body:**
```json
{
  "values": {
    "field-uuid-1": "Budi Santoso",
    "field-uuid-2": "+6281234567890",
    "field-uuid-3": ["Basic"]
  }
}
```

**Response 201 Created:**
```json
{
  "success": true,
  "data": {
    "submission_id": "sub-uuid...",
    "submission_number": "SUB-2023-0891",
    "status": "new",
    "wa_redirect_url": "https://wa.me/6281234567890?text=..."
  },
  "message": "Pesanan berhasil dikirim"
}
```

---

## 5. Submissions

### GET `/submissions` (JWT)
List submission (dengan pagination, filter, search).

**Query Params:** `?page=1&limit=25&status=new&search=Budi`

**Response 200 OK:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": "sub-uuid-1",
        "submission_number": "SUB-2023-0891",
        "customer_name": "Budi Santoso",
        "form_title": "Formulir Pemesanan Barang",
        "status": "new",
        "submitted_at": "2023-10-12T14:30:00Z"
      }
    ],
    "pagination": { "page": 1, "limit": 25, "total": 1248 }
  }
}
```

### GET `/submissions/:id` (JWT)
Detail submission.

**Response 200 OK:**
```json
{
  "success": true,
  "data": {
    "id": "sub-uuid-1",
    "submission_number": "SUB-2023-0891",
    "customer_name": "Budi Santoso",
    "customer_phone": "+6281234567890",
    "status": "new",
    "submitted_at": "2023-10-12T14:30:00Z",
    "values": [
      {
        "field_label": "Pilihan Paket",
        "value_text": null,
        "value_json": ["Basic"]
      }
    ],
    "notes": [
      {
        "id": "note-1",
        "user_name": "Admin Pusat (Sarah)",
        "content": "Follow up segera",
        "created_at": "2023-10-12T15:00:00Z"
      }
    ]
  }
}
```

### PATCH `/submissions/:id/status` (JWT)
Update status submission.

**Request Body:**
```json
{
  "status": "read"
}
```

**Response 200 OK:**
```json
{ "success": true, "message": "Status diperbarui" }
```

### POST `/submissions/:id/notes` (JWT)
Tambah catatan internal.

**Request Body:**
```json
{
  "content": "Sudah diteruskan ke tim sales."
}
```

**Response 201 Created:**
```json
{ "success": true, "message": "Catatan ditambahkan" }
```

### POST `/submissions/:id/resend-wa` (JWT)
Kirim ulang notifikasi WA ke pelanggan/admin.

**Response 200 OK:**
```json
{ "success": true, "message": "Notifikasi WhatsApp dimasukkan ke antrean" }
```

### GET `/submissions/export` (JWT)
Download CSV/Excel data masuk. (Mengembalikan file stream `text/csv` atau `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`).

---

## 6. Settings

### GET `/settings` (JWT)
Ambil pengaturan pengguna saat ini.

**Response 200 OK:**
```json
{
  "success": true,
  "data": {
    "preferences": {
      "notif_email_new_order": true,
      "notif_wa_auto_confirm": true,
      "theme": "light"
    },
    "whatsapp": {
      "phone_number": "+62 812-3456-7890",
      "connection_status": "connected",
      "wa_template_new_order": "Halo {nama}, pesanan {id} Anda diterima."
    }
  }
}
```

### PUT `/settings` (JWT)
Simpan preferensi notifikasi/tema.

**Request Body:**
```json
{
  "notif_email_new_order": true,
  "notif_wa_auto_confirm": false,
  "theme": "dark"
}
```

### PUT `/settings/whatsapp` (JWT)
Simpan konfigurasi WhatsApp.

**Request Body:**
```json
{
  "api_key": "sk_live_wa_...",
  "phone_number": "+6281234567890",
  "wa_template_new_order": "Halo {nama}, terima kasih telah pesan dengan ID #{id}."
}
```

### POST `/settings/whatsapp/test` (JWT)
Test koneksi (menggunakan API key & phone number saat ini).

**Response 200 OK:**
```json
{ "success": true, "message": "Koneksi ke provider WhatsApp berhasil" }
```

---

## 7. Analytics

### GET `/analytics/summary` (JWT)
Ringkasan KPI.

**Response 200 OK:**
```json
{
  "success": true,
  "data": {
    "total_responses": 1248,
    "active_forms": 12,
    "average_conversion": 8.4
  }
}
```

### GET `/analytics/trend` (JWT)
Data chart (respon per hari).

**Response 200 OK:**
```json
{
  "success": true,
  "data": [
    { "name": "Sen", "value": 30 },
    { "name": "Sel", "value": 55 },
    { "name": "Rab", "value": 85 }
  ]
}
```

### GET `/analytics/status-distribution` (JWT)
Distribusi status order.

**Response 200 OK:**
```json
{
  "success": true,
  "data": [
    { "status": "new", "count": 42 },
    { "status": "read", "count": 20 },
    { "status": "done", "count": 156 }
  ]
}
```
