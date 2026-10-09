<?php
class Auth {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            // Nonaktifkan secure untuk local dev HTTP, aktifkan nanti untuk production
            // ini_set('session.cookie_secure', 1);
            ini_set('session.cookie_samesite', 'Strict');
            session_start();
        }
    }
    
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT id, password_hash FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['last_activity'] = time();
            
            $now = date('Y-m-d H:i:s');
            $this->db->prepare("UPDATE admins SET last_login_at = ? WHERE id = ?")
                     ->execute([$now, $admin['id']]);
            return true;
        }
        return false;
    }
    
    public function logout() {
        session_destroy();
    }
    
    public function check() {
        if (!isset($_SESSION['admin_id'])) {
            Response::error('unauthorized', 'Sesi berakhir, silakan login kembali.', 401);
        }
        
        $timeout = 2 * 3600; // 2 hours idle timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
            session_destroy();
            Response::error('unauthorized', 'Sesi kedaluwarsa.', 401);
        }
        $_SESSION['last_activity'] = time();
        
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE', 'PUT'])) {
            $headers = getallheaders();
            $csrf = $headers['X-Csrf-Token'] ?? $headers['x-csrf-token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
                Response::error('forbidden', 'Token CSRF tidak valid.', 403);
            }
        }
    }
}
