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
