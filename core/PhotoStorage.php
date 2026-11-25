<?php
// core/PhotoStorage.php

declare(strict_types=1);

class PhotoStorage
{
    public function __construct(private array $config) {}

    public function upload(array $file): ?string
    {
        if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Erreur lors du transfert de l’image.');
        }

        $maxBytes = ($this->config['max_photo_mb'] ?? 2) * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxBytes) {
            throw new RuntimeException('L’image dépasse la taille maximale autorisée.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $allowed = $this->config['allowed_mime'] ?? [];

        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Format d’image non autorisé (JPG, PNG ou WEBP uniquement).');
        }

        if (!is_dir($this->config['uploads_dir'])) {
            mkdir($this->config['uploads_dir'], 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $destination = $this->config['uploads_dir'] . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Impossible d’enregistrer la photo sur le serveur.');
        }

        return $filename;
    }

    public function delete(?string $filename): void
    {
        if (empty($filename)) {
            return;
        }
        $safeName = basename($filename);
        $path = $this->config['uploads_dir'] . DIRECTORY_SEPARATOR . $safeName;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}