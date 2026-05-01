<div align="center">

# 🍽️ Savora

**Temukan, Simpan, dan Masak Resep Favoritmu — Dibantu AI**

Savora adalah aplikasi mobile berbasis Flutter yang menghubungkan para pecinta masak dengan ribuan resep, komunitas, dan asisten AI yang siap membantu kapan saja.

[![Flutter](https://img.shields.io/badge/Flutter-3.x-02569B?logo=flutter)](https://flutter.dev)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel)](https://laravel.com)
[![Supabase](https://img.shields.io/badge/Supabase-Auth-3ECF8E?logo=supabase)](https://supabase.com)
[![License](https://img.shields.io/badge/License-MIT-yellow)](LICENSE)

</div>

---

## ✨ Apa itu Savora?

Savora hadir untuk menjawab pertanyaan sehari-hari: *"Hari ini masak apa ya?"*

Dengan Savora, kamu bisa menjelajahi resep dari komunitas, menyimpan favorit, memberi rating, dan bahkan bertanya langsung ke **AI Chef** yang akan merekomendasikan resep berdasarkan bahan yang kamu punya.

---

## 🚀 Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| 🔍 **Jelajahi Resep** | Feed resep dari komunitas, bisa difilter berdasarkan kategori & tag |
| 🤖 **AI Chef Assistant** | Chat dengan AI untuk rekomendasi resep, tips memasak, dan substitusi bahan |
| ❤️ **Favorit** | Simpan resep yang kamu suka untuk diakses kapan saja |
| ⭐ **Rating & Komentar** | Beri ulasan dan baca pengalaman orang lain |
| 🔔 **Notifikasi** | Update real-time dari aktivitas komunitas |
| 👤 **Profil & Resep Pribadi** | Buat dan kelola resep milikmu sendiri |
| 🏷️ **Tag & Kategori** | Navigasi resep yang terorganisir dan mudah ditemukan |
| 🔐 **Auth Aman** | Login via Supabase dengan JWT yang terenkripsi |

---

## 🏗️ Arsitektur

```
savora-app/
├── savora-backend/     # Laravel 11 — REST API + Admin Panel (Blade)
└── savora-frontend/    # Flutter — Mobile App (Android & iOS)
```

**Stack Teknologi:**

- **Backend** — Laravel 11, Supabase (Auth + DB), Groq AI, Firebase Cloud Messaging
- **Frontend** — Flutter, Dart, Supabase Auth Client
- **Deployment** — Railway (backend)

---

## ⚡ Quick Start

### Backend (Laravel)

```bash
cd savora-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Cek di browser: `http://127.0.0.1:8000/up` → harus muncul **"Application up"**

### Frontend (Flutter)

```bash
cd savora-frontend
flutter pub get
flutter run
```

> Untuk testing di device fisik via LAN, jalankan backend dengan `php -S 0.0.0.0:8000 -t public` dan set `_baseUrlDebug` di `lib/services/api_service.dart` ke IP laptop kamu.

---

## 📄 Lisensi

Copyright © 2026 Tim Savora. All rights reserved.

Source code ini bersifat proprietary. Dilarang menggunakan, menyalin, memodifikasi, atau mendistribusikan sebagian maupun seluruh kode tanpa izin tertulis dari pemilik.

---

<div align="center">
  <sub>Dibuat dengan ❤️ oleh tim Savora</sub>
</div>
