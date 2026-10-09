<?php
class Db {
    public static function connect($config) {
        try {
            if ($config['driver'] === 'sqlite') {
                // Ensure directory exists
                $dir = dirname($config['sqlite_path']);
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                
                $pdo = new PDO('sqlite:' . $config['sqlite_path']);
            } else {
                $dsn = "mysql:host={$config['mysql']['host']};port={$config['mysql']['port']};dbname={$config['mysql']['database']};charset={$config['mysql']['charset']}";
                $pdo = new PDO($dsn, $config['mysql']['username'], $config['mysql']['password']);
            }
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            return $pdo;
        } catch (PDOException $e) {
            die(json_encode(['error' => [
                'code' => 'db_error',
                'message' => 'Koneksi database gagal: ' . $e->getMessage()
            ]]));
        }
    }
}
