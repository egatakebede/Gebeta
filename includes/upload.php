<?php
class ImageUpload {
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    private $maxSize = 5 * 1024 * 1024; // 5MB
    private $uploadDir = UPLOAD_DIR;
    
    public function upload($file, $subDir = 'general') {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error code: ' . $file['error']);
        }
        if ($file['size'] > $this->maxSize) {
            throw new Exception('File too large (Max 5MB)');
        }
        if (!in_array($file['type'], $this->allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and WebP are allowed.');
        }
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('gebeta_', true) . '.' . $ext;
        
        $targetDir = $this->uploadDir . $subDir . '/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $fullPath = $targetDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            // Return the path relative to the root for DB storage
            return 'uploads/' . $subDir . '/' . $filename;
        }
        
        throw new Exception('Failed to move uploaded file.');
    }
    
    public function delete($path) {
        $fullPath = ROOT_DIR . '/' . $path;
        if (file_exists($fullPath) && is_file($fullPath)) {
            unlink($fullPath);
        }
    }
}