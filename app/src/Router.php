<?php
class Router {
    private $db;
    private $config;

    public function __construct($db, $config) {
        $this->db = $db;
        $this->config = $config;
    }

    public function dispatch($method, $route) {
        $route = trim($route, '/');
        
        if ($method === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        if ($method === 'GET' && $route === 'config') {
            $limits = $this->config['limits'];
            $stmt = $this->db->query("SELECT title, upload_enabled, gallery_enabled FROM events WHERE id = 1");
            $event = $stmt->fetch();

            Response::json([
                'upload_enabled' => $event ? (bool)$event['upload_enabled'] : true,
                'gallery_enabled' => $event ? (bool)$event['gallery_enabled'] : true,
                'max_file_size_mb' => $limits['max_file_size_mb'],
                'title' => $event ? $event['title'] : 'Pernikahan Rina & Bima'
            ]);
        }

        if ($method === 'GET' && $route === 'photos') {
            require_once __DIR__ . '/Gallery.php';
            $gallery = new Gallery($this->db, $this->config);
            $gallery->getPhotos();
        }

        if ($method === 'GET' && preg_match('/^photos\/(\d+)\/download$/', $route, $matches)) {
            require_once __DIR__ . '/Gallery.php';
            $gallery = new Gallery($this->db, $this->config);
            $gallery->downloadPhoto($matches[1]);
        }

        if ($method === 'POST' && $route === 'photos') {
            $this->checkGuestAuth();
            $uploader = new Upload($this->db, $this->config);
            $uploader->handle();
            exit;
        }

        // ADMIN ROUTES
        if (strpos($route, 'admin/') === 0) {
            require_once __DIR__ . '/Auth.php';
            $auth = new Auth($this->db);
            
            if ($method === 'POST' && $route === 'admin/login') {
                $input = json_decode(file_get_contents('php://input'), true);
                if ($auth->login($input['username'] ?? '', $input['password'] ?? '')) {
                    Response::json(['token' => $_SESSION['csrf_token']]);
                } else {
                    Response::error('auth_failed', 'Username atau password salah.', 401);
                }
            }
            
            $auth->check();
            require_once __DIR__ . '/Admin.php';
            $admin = new Admin($this->db);

            if ($method === 'GET' && $route === 'admin/photos') {
                $admin->getPhotos();
            }
            
            if ($method === 'GET' && $route === 'admin/stats') {
                $admin->getStats();
            }
            
            if ($method === 'GET' && $route === 'admin/settings') {
                $admin->getSettings();
            }

            if ($method === 'GET' && $route === 'admin/export') {
                $admin->exportZip();
            }
            
            if ($method === 'POST' && $route === 'admin/settings') {
                $input = json_decode(file_get_contents('php://input'), true);
                $admin->updateSettings($input ?: []);
            }

            if ($method === 'POST' && $route === 'admin/password') {
                $input = json_decode(file_get_contents('php://input'), true);
                $admin->changePassword($input['old_password'] ?? '', $input['new_password'] ?? '');
            }

            if ($method === 'POST' && $route === 'admin/purge') {
                $admin->purgeExpiredPhotos();
            }

            if ($method === 'POST' && $route === 'admin/photos/bulk') {
                $input = json_decode(file_get_contents('php://input'), true);
                if (($input['action'] ?? '') === 'delete') {
                    $admin->deletePhotos($input['ids'] ?? []);
                }
            }
            
            Response::error('not_found', 'Admin API endpoint tidak ditemukan.', 404);
        }

        Response::error('not_found', 'Endpoint tidak ditemukan.', 404);
    }

    private function checkGuestAuth() {
        $headers = getallheaders();
        $requestedWith = $headers['X-Requested-With'] ?? $headers['x-requested-with'] ?? '';
        if ($requestedWith !== 'wpr') {
            Response::error('unauthorized', 'Akses ditolak.', 403);
        }
    }
}
