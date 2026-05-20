# StudyBuddy AI

StudyBuddy AI adalah aplikasi web PHP Native untuk membantu mahasiswa mengatur tugas, mencatat mood harian, menyusun jadwal belajar, melihat motivasi, dan memakai fitur curhat ringan berbasis rule-based response.

## Fitur Utama

- Study Space ringkasan tugas, mood terakhir, jadwal terbaru, dan motivasi harian.
- To-do list dengan deadline, status belum/selesai, dan hapus tugas.
- Mood Tracker dengan emoji, catatan singkat, riwayat mood, dan motivasi sederhana.
- Smart Study Plan Generator rule-based untuk membuat rencana belajar dari tugas, deadline, dan kondisi energi.
- Focus Timer / Pomodoro 25 menit fokus + 5 menit istirahat di dashboard.
- Jadwal Belajar untuk menyimpan topik, waktu belajar, dan target.
- Curhat AI rule-based tanpa API eksternal, dengan respons dukungan umum, clear chat, dan hapus riwayat.

## Tech Stack

- PHP Native
- SQLite dengan PDO
- HTML
- CSS custom responsif
- JavaScript vanilla

## Cara Menjalankan Lokal

1. Masuk ke folder project:

```bash
cd studybuddy-ai
```

2. Jalankan PHP built-in server:

```bash
php -S localhost:8000
```

3. Buka aplikasi di browser:

```text
http://localhost:8000
```

Database SQLite akan dibuat otomatis di `database/studybuddy.sqlite` saat aplikasi pertama kali dijalankan.

## Catatan Demo

StudyBuddy AI bukan aplikasi diagnosis kesehatan mental dan bukan pengganti layanan konseling profesional. Fitur Curhat AI hanya memberikan dukungan ringan, motivasi umum, dan saran belajar sederhana.
