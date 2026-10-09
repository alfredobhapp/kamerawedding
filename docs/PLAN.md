# Rencana Kerja (PLAN) - Wedding Photo Repository

Mengacu pada PRD, pengembangan MVP akan dilakukan secara bertahap. Setiap tahap akan diuji dan diverifikasi sebelum melanjutkan ke tahap berikutnya.

## Urutan Kerja (Fase MVP)

### Tahap 1: Fondasi (Estimasi 2 hari)
- Membuat struktur folder dan file dasar (di dalam `public_html` dan folder terproteksi `wpr_private` / `app`).
- Menulis file `config.sample.php`.
- Menyiapkan skema database (MySQL & SQLite) dan file koneksi PDO.
- Menyiapkan backend sederhana: Router minimalis, Helper JSON Response.
- Membuat skrip Installer & Requirement Checker (`/install/index.php`).
- Menulis file `.htaccess` utama untuk routing, HTTPS, dan security headers.

### Tahap 2: Akses Tamu & Beranda (Estimasi 1 hari)
- Endpoint `GET /?k={token}` untuk validasi QR token, mengatur cookie (`wpr_access`, `wpr_guest`), dan redirect 302.
- Pembuatan UI Beranda (HTML/CSS Vanilla) yang responsif (dua tombol "Ambil Foto" dan "Lihat Galeri").
- Endpoint `GET /api/config` untuk konfigurasi klien.
- Halaman status akses (galeri ditutup / token tidak valid).

### Tahap 3: Pipeline Klien & Antrean Upload (Estimasi 3.5 hari)
- Implementasi input file (kamera via `capture` dan pemilih galeri).
- Pemrosesan gambar sisi klien menggunakan Canvas & Vanilla JS: deteksi format, resize (max 2048px), konversi HEIC (lewat browser native atau `heic2any`), kompresi JPEG 0.82, perhitungan SHA-256 (Web Crypto API).
- Sistem antrean upload (maks 2 paralel), retry eksponensial otomatis, dan progress bar (`aria-live`).

### Tahap 4: Upload API (Estimasi 2.5 hari)
- Endpoint `POST /api/photos` untuk menerima file.
- Validasi berlapis: ukuran, MIME via `finfo`, validasi dimensi dengan `getimagesize`.
- Re-encoding dengan GD untuk menghapus hidden payload / metadata.
- Pembuatan nama acak 128 bit, pembuatan thumbnail (480px, q75).
- Pencegahan duplikasi berdasarkan `sha256` dan `client_uuid`.
- Sistem rate limiting dasar di database.

### Tahap 5: Galeri & Unduhan (Estimasi 3.5 hari)
- UI Galeri: Grid responsif CSS, lazy loading gambar (`IntersectionObserver`), Lightbox dengan navigasi touch/keyboard.
- Polling ke API setiap 20 detik (dengan ETag/`after_id`) untuk foto baru.
- Endpoint dan fungsi `GET /api/photos/{id}/download` dengan Web Share fallback untuk iOS.

### Tahap 6: Admin Dashboard (Estimasi 4 hari)
- Sistem Autentikasi Admin (Login dengan hash Argon2/bcrypt, rate limit login, dan token CSRF).
- UI Admin: Tab Foto (moderasi hapus tunggal/massal), Tab QR Code (pembuatan QR client-side), Tab Pengaturan.
- Fitur ekspor ZIP streaming (menggunakan `ZipStream-PHP`) yang membagi file ke bagian-bagian.
- Logika purge/penghapusan otomatis data saat masa retensi terlewati.
- Ganti password dan skrip darurat reset password.

### Tahap 7: Hardening, Dokumentasi & Pengujian Akhir (Estimasi 3.5 hari)
- Verifikasi `.htaccess` di folder `media/` (no script execution).
- Penulisan `README.md` (Panduan Instalasi FTP/cPanel).
- Pengujian akhir di mobile device browser dan environment simulasi shared hosting.

## Risiko Utama & Mitigasi
- **Konversi HEIC di Mobile:** Bisa memakan resource. Mitigasi: 3 lapis konversi (native -> WASM -> Server fallback) serta batasan ukuran file awal di klien.
- **Beban Server (Concurrency & ZIP):** Shared hosting bisa lambat saat ratusan user mengakses. Mitigasi: Upload antrean diatur di klien (maks 2 paralel), caching metadata, dan ZIP dialirkan tanpa temporary file besar.
- **Masalah Kompatibilitas ES2019:** Browser jadul mungkin gagal. Mitigasi: Gunakan Vanilla JS standar tanpa arrow function/sintaks rumit jika diperlukan, atau pastikan babel tidak dipakai namun kode ditulis sebersih mungkin sesuai target browser (iOS 15.4+, Chrome 100+).

## Daftar Pertanyaan (Hal yang perlu dikonfirmasi / Hal untuk diputuskan User)
1. Saya akan menaruh seluruh source (termasuk folder privat) ke `d:\Vincent\kamerawedding\`. Folder `wpr_private/` akan saya taruh di dalam folder project ini. Jika deployment di shared hosting, user tinggal mengupload isinya. Apakah setuju jika folder privat tersebut saya beri nama `app/` di level yang sama dengan `index.php` dan saya lindungi dengan `.htaccess` `Require all denied`? Ini mempermudah instalasi untuk user non-teknis.
2. Sesuai PRD, saya akan berhenti dan memverifikasi setiap selesai satu tahap. 

Silakan setujui Rencana Kerja ini, dan saya akan mulai mengerjakan Langkah 1 (Fondasi).
