<?php
class ImagenService {
    public static function subir(?array $file): ?string {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new DomainException('No se pudo subir la imagen. Comprueba el tamaño y vuelve a seleccionarla.');
        }
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) throw new DomainException('La imagen debe pesar como máximo 5 MB.');
        $temp = $file['tmp_name'] ?? '';
        if (!is_string($temp) || !is_uploaded_file($temp)) throw new DomainException('Selecciona un archivo de imagen válido.');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temp);
        $formatos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!isset($formatos[$mime]) || !@getimagesize($temp)) throw new DomainException('Usa una imagen JPG, PNG, WebP o GIF.');
        $directory = __DIR__ . '/../../Frontend/assets/uploads/productos';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new DomainException('No se pudo crear la carpeta de imágenes.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $formatos[$mime];
        if (!move_uploaded_file($temp, $directory . '/' . $filename)) throw new DomainException('No se pudo guardar la imagen.');
        return 'assets/uploads/productos/' . $filename;
    }

    public static function descartar(string $path): void {
        if (preg_match('#^assets/uploads/productos/[a-f0-9]{32}\.(jpg|png|webp|gif)$#D', $path)) {
            $file = __DIR__ . '/../../Frontend/' . $path;
            if (is_file($file)) unlink($file);
        }
    }
}
