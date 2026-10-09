<?php
// Installer sederhana
require_once __DIR__ . '/../app/config.php';
$config = require __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/src/Db.php';

try {
    $db = Db::connect($config['db']);
    $sqlFile = $config['db']['driver'] === 'sqlite' ? 'schema_sqlite.sql' : 'schema_mysql.sql';
    $sql = file_get_contents(__DIR__ . '/../app/src/' . $sqlFile);
    
    $now = date('Y-m-d H:i:s');
    $messages = [];

    // Cek apakah tabel admins sudah punya user
    $adminCount = $db->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    if ($adminCount == 0) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
        $db->exec("INSERT INTO admins (username, password_hash, created_at) VALUES ('admin', '$hash', '$now')");
        $messages[] = "Akun admin default berhasil dibuat: Username: <b>admin</b>, Password: <b>admin123</b>";
    } else {
        $messages[] = "Tabel admin sudah memiliki akun terdaftar.";
    }

    // Cek apakah default event sudah ada
    $eventCount = $db->query("SELECT COUNT(*) FROM events WHERE id = 1")->fetchColumn();
    if ($eventCount == 0) {
        $db->exec("INSERT INTO events (id, slug, title, event_date, access_token, created_at, updated_at) 
                   VALUES (1, 'rina-bima', 'Pernikahan Rina & Bima', '2026-10-17', 'dummytoken123', '$now', '$now')");
        $messages[] = "Data default event berhasil dibuat.";
    }

    // Pastikan folder media/f dan media/t siap
    $mediaDir = __DIR__ . '/../media';
    if (!is_dir("$mediaDir/f")) @mkdir("$mediaDir/f", 0777, true);
    if (!is_dir("$mediaDir/t")) @mkdir("$mediaDir/t", 0777, true);
    @chmod("$mediaDir/f", 0777);
    @chmod("$mediaDir/t", 0777);

    echo "<h1>Database Siap!</h1>";
    echo "<ul>";
    foreach ($messages as $msg) {
        echo "<li>$msg</li>";
    }
    echo "</ul>";
    echo "<p><a href='../admin/'>Ke Dashboard Admin</a> | <a href='../'>Ke Beranda Tamu</a></p>";
} catch (Exception $e) {
    echo "<h1>Gagal Menginstal</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
