# Asumsi Pengembangan Wedding Photo Repository

Berdasarkan PRD yang diberikan, berikut adalah asumsi-asumsi teknis yang diambil selama proses pengembangan:

1. **Lingkungan Deployment (Hosting):**
   - Server menggunakan Apache atau LiteSpeed yang mendukung `.htaccess`. Nginx tanpa akses konfigurasi server-block tidak direkomendasikan karena kita sangat bergantung pada `.htaccess` untuk keamanan folder `media/` dan pengalihan (routing).
   - Struktur folder privat (`config.php`, `src/`, dll.) akan ditempatkan sebagai default di dalam root instalasi (misal `app/` atau `wpr_private/` di dalam `public_html`) dengan proteksi `.htaccess` (`Require all denied`).

2. **Database:**
   - Kita akan mengembangkan dengan dukungan **SQLite** untuk kemudahan uji coba lokal, namun struktur kode (PDO) akan kompatibel penuh dengan **MySQL/MariaDB**.

3. **Pemrosesan Gambar (GD vs Imagick):**
   - Ekstensi `GD` aktif sebagai baseline wajib. Ekstensi `Imagick` dianggap opsional dan hanya akan digunakan jika tersedia untuk fallback HEIC di sisi server.

4. **Dependencies & Pustaka Pihak Ketiga:**
   - Pustaka pihak ketiga untuk frontend (`heic2any`, `qrcode-generator`) disertakan secara statis di `assets/vendor/`.
   - Pustaka backend (`ZipStream-PHP`) akan disertakan utuh (`vendor/`) tanpa Composer di server.

5. **Local Development:**
   - Pengembangan lokal menggunakan PHP built-in web server. URL rewrite akan ditangani melalui script router PHP lokal.
