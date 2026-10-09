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
            Response::json([
                'upload_enabled' => true,
                'gallery_enabled' => true,
                'max_file_size_mb' => $limits['max_file_size_mb'],
                'title' => 'Pernikahan Rina & Bima'
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
