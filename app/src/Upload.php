<?php
class Upload {
    private $db;
    private $config;
    private $mediaPath;
    
    public function __construct($db, $config) {
        $this->db = $db;
        $this->config = $config;
        $this->mediaPath = __DIR__ . '/../../media';
    }

    public function handle() {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response::error('upload_failed', 'Gagal mengunggah file.', 400);
        }

        $file = $_FILES['file'];
        $clientUuid = $_POST['client_uuid'] ?? '';
        $sha256 = $_POST['sha256'] ?? '';
        $guestNote = !empty($_POST['guest_note']) ? $_POST['guest_note'] : null;
        $eventId = 1; 

        // Validasi Duplikat
        $stmt = $this->db->prepare("SELECT id, file_key FROM photos WHERE event_id = ? AND (client_uuid = ? OR sha256 = ?)");
        $stmt->execute([$eventId, $clientUuid, $sha256]);
        $existing = $stmt->fetch();
        if ($existing) {
            Response::json(['status' => 'duplicate', 'id' => $existing['id']]);
        }

        // Validasi File
        if ($file['size'] > $this->config['limits']['max_file_size_mb'] * 1024 * 1024) {
            Response::error('file_too_large', 'Ukuran file melebihi batas.', 413);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mime, $allowedMimes)) {
            Response::error('unsupported_type', 'Format file tidak didukung.', 415);
        }

        // Setup Folder & Nama
        $ext = ($mime === 'image/gif') ? 'gif' : 'jpg';
        $fileKey = bin2hex(random_bytes(16));
        $subFolder = substr($fileKey, 0, 2);
        
        $targetFolderF = $this->mediaPath . '/f/' . $subFolder;
        $targetFolderT = $this->mediaPath . '/t/' . $subFolder;
        
        if (!is_dir($targetFolderF)) mkdir($targetFolderF, 0755, true);
        if (!is_dir($targetFolderT)) mkdir($targetFolderT, 0755, true);
        
        $targetFile = $targetFolderF . '/' . $fileKey . '.' . $ext;
        $thumbFile = $targetFolderT . '/' . $fileKey . '.' . $ext;

        if ($mime !== 'image/gif') {
            $img = imagecreatefromstring(file_get_contents($file['tmp_name']));
            if (!$img) Response::error('invalid_image', 'Gambar korup.', 422);
            
            $w = imagesx($img);
            $h = imagesy($img);
            
            imagejpeg($img, $targetFile, 85);
            
            $tw = 480;
            $th = (int)(($h * $tw) / $w);
            if ($w < $tw) { $tw = $w; $th = $h; }
            $thumb = imagecreatetruecolor($tw, $th);
            imagecopyresampled($thumb, $img, 0, 0, 0, 0, $tw, $th, $w, $h);
            imagejpeg($thumb, $thumbFile, 75);
            
            imagedestroy($img);
            imagedestroy($thumb);
        } else {
            move_uploaded_file($file['tmp_name'], $targetFile);
            copy($targetFile, $thumbFile);
            $sizes = getimagesize($targetFile);
            $w = $sizes[0];
            $h = $sizes[1];
        }

        // Database Transaction
        $this->db->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            // Seed event table for testing
            $this->db->exec("INSERT INTO events (id, slug, title, event_date, access_token, created_at, updated_at) 
                             SELECT 1, 'rina-bima', 'Pernikahan Rina & Bima', '2026-10-17', 'dummytoken123', '$now', '$now' 
                             WHERE NOT EXISTS (SELECT 1 FROM events WHERE id = 1)");
            
            $stmt = $this->db->prepare("INSERT INTO photos (event_id, guest_id, client_uuid, sha256, file_key, ext, width, height, size_bytes, thumb_bytes, src_format, guest_note, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $guestId = 'dummy-guest-id'; 
            $srcFormat = str_replace('image/', '', $mime);
            $stmt->execute([
                $eventId, $guestId, $clientUuid, $sha256, $fileKey, $ext, 
                $w, $h, filesize($targetFile), filesize($thumbFile), $srcFormat, $guestNote, $now
            ]);
            $photoId = $this->db->lastInsertId();
            $this->db->commit();
            
            Response::json([
                'id' => $photoId,
                'thumb' => "/media/t/$subFolder/$fileKey.$ext",
                'delete_token' => bin2hex(random_bytes(16))
            ], 201);
        } catch (Exception $e) {
            $this->db->rollBack();
            @unlink($targetFile);
            @unlink($thumbFile);
            Response::error('db_error', 'Gagal menyimpan ke database: ' . $e->getMessage(), 500);
        }
    }
}
