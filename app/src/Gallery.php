<?php
class Gallery {
    private $db;
    private $config;
    
    public function __construct($db, $config) {
        $this->db = $db;
        $this->config = $config;
    }
    
    public function getPhotos() {
        $cursor = isset($_GET['cursor']) ? (int)$_GET['cursor'] : 0;
        $limit = 40;
        $eventId = 1; // Dummy untuk MVP
        
        $sql = "SELECT id, thumb_bytes as thumb_size, file_key, ext, width, height, created_at, guest_note 
                FROM photos 
                WHERE event_id = ? AND status = 'active' ";
                
        $params = [$eventId];
        
        if ($cursor > 0) {
            $sql .= " AND id < ? ";
            $params[] = $cursor;
        }
        
        $sql .= " ORDER BY id DESC LIMIT " . ($limit + 1);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        
        $hasNext = count($rows) > $limit;
        if ($hasNext) {
            array_pop($rows);
            $nextCursor = $rows[count($rows) - 1]['id'];
        } else {
            $nextCursor = null;
        }
        
        $items = array_map(function($r) {
            $subFolder = substr($r['file_key'], 0, 2);
            $ext = $r['ext'];
            $fileKey = $r['file_key'];
            return [
                'id' => $r['id'],
                'thumb' => "/media/t/$subFolder/$fileKey.$ext",
                'full' => "/media/f/$subFolder/$fileKey.$ext",
                'w' => $r['width'],
                'h' => $r['height'],
                'note' => $r['guest_note'],
                't' => $r['created_at']
            ];
        }, $rows);
        
        Response::json([
            'items' => $items,
            'next_cursor' => $nextCursor
        ]);
    }
    
    public function downloadPhoto($id) {
        $stmt = $this->db->prepare("SELECT file_key, ext FROM photos WHERE id = ? AND status = 'active'");
        $stmt->execute([$id]);
        $photo = $stmt->fetch();
        
        if (!$photo) {
            Response::error('not_found', 'Foto tidak ditemukan.', 404);
        }
        
        $subFolder = substr($photo['file_key'], 0, 2);
        $path = __DIR__ . "/../../media/f/$subFolder/{$photo['file_key']}.{$photo['ext']}";
        
        if (!file_exists($path)) {
            Response::error('not_found', 'File fisik tidak ditemukan.', 404);
        }
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="foto-' . $id . '.' . $photo['ext'] . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
