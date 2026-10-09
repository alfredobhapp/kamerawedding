<?php
class Admin {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getPhotos() {
        $stmt = $this->db->query("SELECT id, thumb_bytes, file_key, ext, status, created_at, guest_note FROM photos ORDER BY id DESC");
        $rows = $stmt->fetchAll();
        
        $items = array_map(function($r) {
            $subFolder = substr($r['file_key'], 0, 2);
            $ext = $r['ext'];
            $fileKey = $r['file_key'];
            return [
                'id' => $r['id'],
                'thumb' => "media/t/$subFolder/$fileKey.$ext",
                'status' => $r['status'],
                'note' => $r['guest_note'],
                'date' => $r['created_at']
            ];
        }, $rows);
        
        Response::json(['items' => $items]);
    }

    public function getStats() {
        $stmt = $this->db->query("SELECT COUNT(*) as total_photos, SUM(size_bytes) as total_size FROM photos");
        $row = $stmt->fetch();
        Response::json([
            'total_photos' => $row['total_photos'] ?? 0,
            'total_size_mb' => round(($row['total_size'] ?? 0) / 1024 / 1024, 2)
        ]);
    }
    
    public function deletePhotos($ids) {
        if (empty($ids)) return Response::json(['success' => true]);
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $this->db->prepare("SELECT id, file_key, ext FROM photos WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $photos = $stmt->fetchAll();
        
        $mediaPath = __DIR__ . '/../../media';
        
        $this->db->beginTransaction();
        try {
            $delStmt = $this->db->prepare("DELETE FROM photos WHERE id = ?");
            foreach ($photos as $p) {
                $delStmt->execute([$p['id']]);
                
                $subFolder = substr($p['file_key'], 0, 2);
                @unlink("$mediaPath/f/$subFolder/{$p['file_key']}.{$p['ext']}");
                @unlink("$mediaPath/t/$subFolder/{$p['file_key']}.{$p['ext']}");
            }
            $this->db->commit();
            Response::json(['success' => true]);
        } catch (Exception $e) {
            $this->db->rollBack();
            Response::error('db_error', 'Gagal menghapus.', 500);
        }
    }

    public function getSettings() {
        $stmt = $this->db->query("SELECT id, slug, title, event_date, access_token, upload_enabled, gallery_enabled, storage_quota_mb FROM events WHERE id = 1");
        $event = $stmt->fetch();
        Response::json(['event' => $event ?: []]);
    }

    public function updateSettings($data) {
        $uploadEnabled = !empty($data['upload_enabled']) ? 1 : 0;
        $galleryEnabled = !empty($data['gallery_enabled']) ? 1 : 0;
        $title = !empty($data['title']) ? trim($data['title']) : 'Pernikahan Rina & Bima';
        $eventDate = !empty($data['event_date']) ? trim($data['event_date']) : date('Y-m-d');

        $stmt = $this->db->prepare("UPDATE events SET title = ?, event_date = ?, upload_enabled = ?, gallery_enabled = ?, updated_at = ? WHERE id = 1");
        $stmt->execute([$title, $eventDate, $uploadEnabled, $galleryEnabled, date('Y-m-d H:i:s')]);

        Response::json(['success' => true]);
    }

    public function changePassword($oldPassword, $newPassword) {
        $adminId = $_SESSION['admin_id'] ?? 1;
        $stmt = $this->db->prepare("SELECT password_hash FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($oldPassword, $admin['password_hash'])) {
            Response::error('invalid_password', 'Password lama tidak cocok.', 400);
        }

        if (strlen($newPassword) < 6) {
            Response::error('short_password', 'Password baru minimal 6 karakter.', 400);
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $updateStmt = $this->db->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
        $updateStmt->execute([$newHash, $adminId]);

        Response::json(['success' => true]);
    }

    public function purgeExpiredPhotos() {
        // Hapus foto yang lebih dari 90 hari sesuai PRD retention
        $stmt = $this->db->query("SELECT id, file_key, ext FROM photos WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
        $photos = $stmt->fetchAll();

        $count = count($photos);
        if ($count > 0) {
            $mediaPath = __DIR__ . '/../../media';
            $ids = array_column($photos, 'id');
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';

            $delStmt = $this->db->prepare("DELETE FROM photos WHERE id IN ($placeholders)");
            $delStmt->execute($ids);

            foreach ($photos as $p) {
                $sub = substr($p['file_key'], 0, 2);
                @unlink("$mediaPath/f/$sub/{$p['file_key']}.{$p['ext']}");
                @unlink("$mediaPath/t/$sub/{$p['file_key']}.{$p['ext']}");
            }
        }

        Response::json(['success' => true, 'purged_count' => $count]);
    }

    public function exportZip() {
        if (!class_exists('ZipArchive')) {
            Response::error('zip_unsupported', 'Ekstensi ZipArchive tidak aktif pada server PHP hosting.', 500);
        }

        $stmt = $this->db->query("SELECT id, file_key, ext, guest_note FROM photos WHERE status = 'active' ORDER BY id ASC");
        $photos = $stmt->fetchAll();

        if (empty($photos)) {
            Response::error('no_photos', 'Belum ada foto untuk diunduh.', 404);
        }

        $mediaPath = __DIR__ . '/../../media';
        $tempZip = tempnam(sys_get_temp_dir(), 'wpr_zip_');
        $zip = new ZipArchive();

        if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Response::error('zip_failed', 'Gagal membuat file ZIP sementara.', 500);
        }

        $notesContent = "DAFTAR FOTO & UCAPAN TAMU PERNIKAHAN\n====================================\n\n";

        foreach ($photos as $p) {
            $subFolder = substr($p['file_key'], 0, 2);
            $filePath = "$mediaPath/f/$subFolder/{$p['file_key']}.{$p['ext']}";
            $zipEntryName = "foto_{$p['id']}.{$p['ext']}";

            if (file_exists($filePath)) {
                $zip->addFile($filePath, $zipEntryName);
            }

            if (!empty($p['guest_note'])) {
                $notesContent .= "Foto #{$p['id']} ({$zipEntryName}):\nUcapan: {$p['guest_note']}\n------------------------------------\n";
            }
        }

        $zip->addFromString("ucapan_tamu.txt", $notesContent);
        $zip->close();

        $filename = 'wedding_photos_' . date('Ymd_His') . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tempZip));
        header('Cache-Control: no-cache, no-store, must-revalidate');

        // Flush output buffer
        if (ob_get_level()) ob_end_clean();
        readfile($tempZip);
        @unlink($tempZip);
        exit;
    }
}
