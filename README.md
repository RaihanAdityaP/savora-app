# Savora App — Collaboration README

Dokumen ini ditujukan untuk **calon kolaborator** Savora agar proses onboarding cepat, urutan kerja jelas, dan debug lokal tidak membingungkan.

---

## Struktur Repository

```
savora-app/
├── savora-backend/    # Laravel API (dan rencana Laravel web/Blade)
└── savora-frontend/   # Flutter mobile app
```

---

## Urutan Pengembangan

> **Aturan utama tim:**
> 1. **Backend (Laravel) diselesaikan terlebih dahulu**
> 2. Baru lanjut ke **Frontend/Mobile App (Flutter)**

Alasannya sederhana — kontrak API harus stabil sebelum integrasi di Flutter dimulai, agar tidak terjadi perubahan bolak-balik.

---

## Pembagian Divisi

### Divisi Backend — Laravel

**Fokus:**
- Endpoint API (`routes/api.php`, controller, service)
- Desain database, migration, dan seeding
- Auth/token (Sanctum/Supabase)
- Integrasi AI (Groq/Hugging Face)
- Integrasi push notification (FCM)
- Persiapan versi web Laravel (Blade) untuk kebutuhan ke depan

**Output yang diharapkan:**
- API stabil dengan dokumentasi request/response
- Migration rapi dan repeatable
- Validasi dan error handling yang konsisten
- Test backend minimal berjalan

---

### Divisi Frontend — Flutter

**Fokus:**
- Implementasi UI/UX layar mobile
- Integrasi API ke service client Flutter
- Auth flow, state management, serta loading/error/empty state
- Integrasi fitur utama (AI assistant, favorit, notifikasi, dll.)

**Output yang diharapkan:**
- Aplikasi mobile stabil di emulator maupun device fisik
- Integrasi ke API konsisten
- Struktur komponen reusable

---

## Setup Lokal — Backend (Laravel)

### Prasyarat

- PHP 8.2+
- Composer
- SQLite / MySQL
- Node.js + npm *(opsional, untuk asset tooling)*

### Instalasi

```bash
cd savora-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Menjalankan Server

**Opsi 1 — Standar Laravel:**
```bash
php artisan serve
```

**Opsi 2 — Direkomendasikan untuk tes device fisik/LAN:**
```bash
php -S 0.0.0.0:8000 -t public
```

### Verifikasi Backend

Buka browser dan akses salah satu URL berikut:

| Mode | URL |
|------|-----|
| `php artisan serve` | `http://127.0.0.1:8000/up` |
| `php -S 0.0.0.0:8000` | `http://<IP_KAMU>:8000/up` |

Jika muncul **"Application up"**, backend siap digunakan.

### Environment Variables Penting

File: `savora-backend/.env`

| Variabel | Keterangan |
|----------|------------|
| `SUPABASE_URL`, `SUPABASE_KEY`, `SUPABASE_SERVICE_KEY`, `SUPABASE_JWT_SECRET` | Konfigurasi Supabase |
| `GROQ_API_KEY`, `GROQ_MODEL` | Integrasi Groq AI |
| `HF_API_KEY` | Hugging Face |
| `FCM_SERVER_KEY` | Firebase Cloud Messaging |

---

## Setup Lokal — Frontend (Flutter)

### Prasyarat

- Flutter SDK (stable)
- Android Studio / Xcode (sesuai target platform)
- Device fisik atau emulator yang siap dipakai

### Instalasi

```bash
cd savora-frontend
flutter pub get
cp .env.example .env
```

### Menjalankan Aplikasi

```bash
flutter devices   # cek device yang tersedia
flutter run
```

### Shortcut Debug (`flutter run`)

| Tombol | Aksi |
|--------|------|
| `r` | Hot reload |
| `R` | Hot restart |
| `q` | Stop app |

---

## Panduan Debug — Device Fisik + Laravel (LAN)

Ikuti langkah berikut setiap kali laptop di-restart atau berganti jaringan Wi-Fi.

### Langkah 1 — Cek IP Laptop

Buka CMD atau PowerShell, lalu jalankan:

```bash
ipconfig
```

Cari bagian **Wireless LAN adapter Wi-Fi** → catat nilai **IPv4 Address**.

Contoh: `192.168.137.211`

---

### Langkah 2 — Jalankan Backend di LAN

```bash
cd savora-backend
php -S 0.0.0.0:8000 -t public
```

Verifikasi dari laptop maupun HP *(harus satu jaringan Wi-Fi)*:

```
http://<IP_KAMU>:8000/up
```

Jika muncul **"Application up"** → backend siap.

---

### Langkah 3 — Set Base URL di Flutter

Edit file: `savora-frontend/lib/services/api_service.dart`

```dart
static const String _baseUrlDebug = 'http://<IP_KAMU>:8000/api/v1';
```

Contoh:

```dart
static const String _baseUrlDebug = 'http://192.168.137.211:8000/api/v1';
```

---

### Langkah 4 — Jalankan Flutter

```bash
cd savora-frontend
flutter pub get
flutter devices
flutter run
```

---

## Aturan Penting Integrasi

- Setiap kali **restart laptop**, **ganti Wi-Fi**, atau **restart jaringan** → ulangi cek IP laptop.
- Backend **harus aktif terlebih dahulu** sebelum menjalankan Flutter.
- Jika ada perubahan endpoint, segera update client di `lib/services/*_client.dart`.

---

## Workflow Kolaborasi

1. Selesaikan scope backend terlebih dahulu.
2. Freeze kontrak API (request/response + status code).
3. Implementasi frontend berdasarkan kontrak final.
4. Buat PR kecil per fitur agar review lebih cepat.
5. Jalankan checklist sebelum merge:

| Divisi | Checklist |
|--------|-----------|
| Backend | Migration berjalan, endpoint utama aktif, auth flow bekerja |
| Frontend | Login/register, list/detail, error handling berfungsi |

---

## Catatan Roadmap

- Backend Laravel saat ini fokus pada pengembangan API.
- Ke depan akan ditambahkan **versi website menggunakan Laravel Blade** dalam lingkup divisi backend.

---

Semoga kolaborasi berjalan lancar, cepat, dan minim revisi berulang. 🚀
