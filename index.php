<?php
$token = $_GET['k'] ?? null;
if ($token) {
    // Simpan access token ke cookie selama 30 hari
    setcookie('wpr_access', htmlspecialchars($token), [
        'expires' => time() + 30 * 86400,
        'path' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    // Redirect bersih tanpa query string k=...
    $redirectUri = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirectUri);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Pernikahan Rina & Bima</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <div class="sunflower-bg"></div>
    <div class="sunflower-bg bottom-left"></div>
    
    <!-- TAMPILAN BERANDA -->
    <main class="home-container" id="home-view">
        <header class="home-header">
            <h1>Pernikahan Rina & Bima</h1>
            <p class="date">Sabtu, 17 Oktober 2026</p>
        </header>
        
        <section class="info-section">
            <p>Foto kamu langsung masuk ke galeri bersama. Semua tamu bisa melihat dan mengunduhnya.</p>
            <p class="photo-count">128 foto terkumpul</p>
        </section>

        <section class="action-section">
            <!-- Label ini akan memicu kamera (menggunakan capture) atau dialihkan ke logic JS -->
            <label class="btn btn-primary" id="btn-take-photo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                Ambil Foto
                <input type="file" id="camera-input" accept="image/*" capture="environment" style="display: none;">
            </label>
            
            <p class="text-center helper-text">
                atau <label for="upload-gallery" class="link-label">pilih dari galeri HP</label>
                <input type="file" id="upload-gallery" multiple accept="image/*,.heic,.heif" style="display: none;">
            </p>

            <a href="#/galeri" class="btn btn-secondary">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                Lihat Galeri
            </a>
        </section>
        
        <footer class="home-footer">
            <button id="btn-privacy" class="link-button">Foto terlihat oleh semua tamu. Pelajari</button>
        </footer>
    </main>

    <!-- Upload Progress Bottom Sheet -->
    <div id="upload-sheet" class="upload-sheet hidden">
        <div class="upload-sheet-content">
            <div class="sheet-header">
                <h3>Mengunggah <span id="upload-count">0</span> foto</h3>
                <button id="btn-close-sheet" class="btn-icon">&times;</button>
            </div>
            
            <div class="guest-note-container">
                <label for="guest-note">Catatan/Ucapan untuk Pengantin (Opsional):</label>
                <textarea id="guest-note" rows="2" placeholder="Selamat menempuh hidup baru..."></textarea>
            </div>

            <div class="upload-list" id="upload-list" aria-live="polite">
                <!-- Baris progress akan masuk ke sini -->
            </div>
        </div>
    </div>

    <!-- TAMPILAN GALERI -->
    <div id="gallery-view" style="display: none;">
        <header class="gallery-header">
            <a href="#" class="btn-back">&larr; Kembali</a>
            <h2>Galeri Foto</h2>
        </header>
        <div id="gallery-grid" class="gallery-grid"></div>
        <div id="gallery-sentinel" class="gallery-sentinel">Memuat lebih banyak...</div>
    </div>

    <!-- LIGHTBOX -->
    <div id="lightbox" class="lightbox hidden">
        <div class="lb-header">
            <button id="lb-close" class="btn-icon">&times;</button>
        </div>
        <div class="lb-content">
            <img id="lb-img" src="" alt="Full foto">
        </div>
        <div class="lb-footer">
            <p id="lb-note" class="lb-note"></p>
            <button id="lb-download" class="btn btn-primary">Unduh Foto</button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/pipeline.js"></script>
    <script src="assets/js/queue.js"></script>
    <script src="assets/js/gallery.js"></script>
</body>
</html>
