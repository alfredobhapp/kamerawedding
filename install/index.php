<?php
// Installer sederhana
require_once __DIR__ . '/../app/config.php';
$config = require __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/src/Db.php';

try {
    $db = Db::connect($config['db']);
    $sqlFile = $config['db']['driver'] === 'sqlite' ? 'schema_sqlite.sql' : 'schema_mysql.sql';
    $sql = file_get_contents(__DIR__ . '/../app/src/' . $sqlFile);
    
    // Hanya eksekusi jika database kosong (cek tabel events)
    try {
        $check = $db->query("SELECT 1 FROM events LIMIT 1");
    } catch (Exception $e) {
        // Tabel tidak ada, eksekusi SQL
        $db->exec($sql);
        
        // Create default admin: admin / admin123
        $hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
        $now = date('Y-m-d H:i:s');
        $db->exec("INSERT INTO admins (username, password_hash, created_at) VALUES ('admin', '$hash', '$now')");
        
        echo "<h1>Instalasi Berhasil</h1>";
        echo "<p>Tabel telah dibuat. Silakan login dengan Username: <b>admin</b>, Password: <b>admin123</b></p>";
    }
    
    echo "<a href='../admin/'>Ke Dashboard Admin</a>";
} catch (Exception $e) {
    echo "<h1>Gagal Menginstal</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
