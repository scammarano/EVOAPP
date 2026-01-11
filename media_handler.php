<?php
// Media Handler para Evolution API - Descarga y manejo de imágenes
class MediaHandler
{
    private $mediaDir;
    private $baseUrl;
    
    public function __construct()
    {
        $this->mediaDir = __DIR__ . '/media/';
        $this->baseUrl = 'https://camcam.com.ve/evoappws/media/';
        
        // Crear directorio si no existe
        if (!file_exists($this->mediaDir)) {
            mkdir($this->mediaDir, 0755, true);
        }
    }
    
    /**
     * Procesa y descarga media desde Evolution API
     */
    public function processMedia($mediaUrl, $messageId, $instanceName, $messageType = 'image')
    {
        if (empty($mediaUrl)) {
            return null;
        }
        
        // Generar nombre de archivo único
        $extension = $this->getExtensionFromType($messageType);
        $fileName = $instanceName . '_' . $messageId . '_' . time() . '.' . $extension;
        $localPath = $this->mediaDir . $fileName;
        $publicUrl = $this->baseUrl . $fileName;
        
        // Descargar el archivo
        if ($this->downloadMedia($mediaUrl, $localPath)) {
            return [
                'local_path' => $localPath,
                'public_url' => $publicUrl,
                'file_name' => $fileName,
                'file_size' => filesize($localPath),
                'mime_type' => $this->getMimeType($localPath)
            ];
        }
        
        return null;
    }
    
    /**
     * Descarga archivo desde URL de Evolution API
     */
    private function downloadMedia($url, $localPath)
    {
        try {
            // Configurar cURL para descargar
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'EVOAPP-Media-Handler/1.0');
            
            // Si la URL requiere autenticación
            if (strpos($url, 'evolution-api') !== false) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'apikey: YOUR_API_KEY', // Reemplazar con tu API key
                    'Content-Type: application/json'
                ]);
            }
            
            $fileData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode === 200 && !empty($fileData)) {
                // Guardar archivo localmente
                if (file_put_contents($localPath, $fileData)) {
                    return true;
                }
            }
            
            error_log("Media download failed: HTTP $httpCode, Error: $error");
            return false;
            
        } catch (Exception $e) {
            error_log("Media download exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene extensión según tipo de mensaje
     */
    private function getExtensionFromType($messageType)
    {
        $extensions = [
            'image' => 'jpg',
            'video' => 'mp4',
            'audio' => 'mp3',
            'document' => 'pdf',
            'sticker' => 'webp'
        ];
        
        return $extensions[$messageType] ?? 'jpg';
    }
    
    /**
     * Obtiene MIME type del archivo
     */
    private function getMimeType($filePath)
    {
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            return $mimeType;
        }
        
        // Fallback basado en extensión
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
    
    /**
     * Genera thumbnail para imágenes
     */
    public function generateThumbnail($imagePath, $thumbPath, $width = 200, $height = 200)
    {
        if (!extension_loaded('gd')) {
            return false;
        }
        
        try {
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                return false;
            }
            
            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            $mimeType = $imageInfo['mime'];
            
            // Calcular dimensiones del thumbnail manteniendo proporción
            $ratio = min($width / $originalWidth, $height / $originalHeight);
            $newWidth = intval($originalWidth * $ratio);
            $newHeight = intval($originalHeight * $ratio);
            
            // Crear imagen original
            switch ($mimeType) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($imagePath);
                    break;
                case 'image/png':
                    $source = imagecreatefrompng($imagePath);
                    break;
                case 'image/gif':
                    $source = imagecreatefromgif($imagePath);
                    break;
                case 'image/webp':
                    $source = imagecreatefromwebp($imagePath);
                    break;
                default:
                    return false;
            }
            
            if (!$source) {
                return false;
            }
            
            // Crear thumbnail
            $thumb = imagecreatetruecolor($newWidth, $newHeight);
            
            // Mantener transparencia para PNG
            if ($mimeType === 'image/png') {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
                imagefill($thumb, 0, 0, $transparent);
            }
            
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
            
            // Guardar thumbnail
            $result = imagejpeg($thumb, $thumbPath, 85);
            
            // Liberar memoria
            imagedestroy($source);
            imagedestroy($thumb);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Thumbnail generation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Limpia archivos antiguos
     */
    public function cleanupOldFiles($daysOld = 30)
    {
        $files = glob($this->mediaDir . '*');
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        $deleted = 0;
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
}

// Endpoint para descargar media
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'download':
            $mediaUrl = $_GET['url'] ?? '';
            $messageId = $_GET['message_id'] ?? '';
            $instanceName = $_GET['instance'] ?? '';
            $messageType = $_GET['type'] ?? 'image';
            
            if (empty($mediaUrl) || empty($messageId) || empty($instanceName)) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                exit;
            }
            
            $handler = new MediaHandler();
            $result = $handler->processMedia($mediaUrl, $messageId, $instanceName, $messageType);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'media_info' => $result
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to download media'
                ]);
            }
            break;
            
        case 'serve':
            $fileName = $_GET['file'] ?? '';
            $filePath = __DIR__ . '/media/' . basename($fileName);
            
            if (!file_exists($filePath)) {
                http_response_code(404);
                echo 'File not found';
                exit;
            }
            
            // Determinar MIME type
            $handler = new MediaHandler();
            $mimeType = $handler->getMimeType($filePath);
            
            // Servir archivo
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: public, max-age=31536000'); // 1 año cache
            
            readfile($filePath);
            exit;
            break;
            
        case 'cleanup':
            $handler = new MediaHandler();
            $deleted = $handler->cleanupOldFiles();
            echo json_encode(['deleted_files' => $deleted]);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}
?>
