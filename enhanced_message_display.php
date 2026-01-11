<?php
// Enhanced Message Display - Manejo mejorado de imágenes y media
class EnhancedMessageDisplay
{
    private $mediaHandler;
    
    public function __construct()
    {
        $this->mediaHandler = new MediaHandler();
    }
    
    /**
     * Formatea mensaje para display con soporte de media
     */
    public function formatMessageForDisplay($message)
    {
        $formatted = [
            'id' => $message['id'],
            'message_id' => $message['message_id'],
            'from_me' => $message['from_me'],
            'ts' => $message['ts'],
            'status' => $message['status'],
            'type' => $message['msg_type'],
            'body' => $message['body_text'],
            'media' => null,
            'display_content' => ''
        ];
        
        // Procesar según tipo de mensaje
        switch ($message['msg_type']) {
            case 'image':
                $formatted = $this->formatImageMessage($formatted, $message);
                break;
            case 'video':
                $formatted = $this->formatVideoMessage($formatted, $message);
                break;
            case 'audio':
                $formatted = $this->formatAudioMessage($formatted, $message);
                break;
            case 'document':
                $formatted = $this->formatDocumentMessage($formatted, $message);
                break;
            case 'sticker':
                $formatted = $this->formatStickerMessage($formatted, $message);
                break;
            default:
                $formatted['display_content'] = $message['body_text'] ?? '';
                break;
        }
        
        return $formatted;
    }
    
    /**
     * Formatea mensaje de imagen
     */
    private function formatImageMessage($formatted, $message)
    {
        $mediaUrl = $message['media_url'] ?? null;
        $rawJson = json_decode($message['raw_json'] ?? '{}', true);
        
        // Extraer información de la imagen del JSON
        $imageMessage = $rawJson['message']['imageMessage'] ?? [];
        $caption = $imageMessage['caption'] ?? '';
        $width = $imageMessage['width'] ?? 0;
        $height = $imageMessage['height'] ?? 0;
        
        // Si tenemos URL de media, procesarla
        if ($mediaUrl) {
            // Verificar si ya fue descargada
            $localPath = $message['local_path'] ?? null;
            $publicUrl = null;
            
            if ($localPath && file_exists($localPath)) {
                $publicUrl = $this->getPublicUrl($localPath);
            } else {
                // Intentar descargar si no existe localmente
                $mediaInfo = $this->mediaHandler->processMedia(
                    $mediaUrl,
                    $message['message_id'],
                    $this->getInstanceName($message['instance_id']),
                    'image'
                );
                
                if ($mediaInfo) {
                    $publicUrl = $mediaInfo['public_url'];
                    // Actualizar BD con la nueva ruta
                    $this->updateMessageMediaPath($message['id'], $mediaInfo['local_path']);
                }
            }
            
            if ($publicUrl) {
                $formatted['media'] = [
                    'type' => 'image',
                    'url' => $publicUrl,
                    'thumbnail_url' => $this->getThumbnailUrl($publicUrl),
                    'caption' => $caption,
                    'width' => $width,
                    'height' => $height,
                    'download_url' => $this->getDownloadUrl($mediaUrl, $message)
                ];
                
                $formatted['display_content'] = $caption ? $caption : '📷 Image';
            } else {
                $formatted['display_content'] = $caption ? "📷 $caption" : '📷 Image (download failed)';
            }
        } else {
            $formatted['display_content'] = $caption ? "📷 $caption" : '📷 Image';
        }
        
        return $formatted;
    }
    
    /**
     * Formatea mensaje de video
     */
    private function formatVideoMessage($formatted, $message)
    {
        $mediaUrl = $message['media_url'] ?? null;
        $rawJson = json_decode($message['raw_json'] ?? '{}', true);
        $videoMessage = $rawJson['message']['videoMessage'] ?? [];
        $caption = $videoMessage['caption'] ?? '';
        $duration = $videoMessage['seconds'] ?? 0;
        
        if ($mediaUrl) {
            $publicUrl = $this->getOrDownloadMedia($mediaUrl, $message, 'video');
            
            if ($publicUrl) {
                $formatted['media'] = [
                    'type' => 'video',
                    'url' => $publicUrl,
                    'caption' => $caption,
                    'duration' => $duration,
                    'download_url' => $this->getDownloadUrl($mediaUrl, $message)
                ];
                
                $formatted['display_content'] = $caption ? "🎥 $caption" : '🎥 Video';
            } else {
                $formatted['display_content'] = $caption ? "🎥 $caption" : '🎥 Video (download failed)';
            }
        } else {
            $formatted['display_content'] = $caption ? "🎥 $caption" : '🎥 Video';
        }
        
        return $formatted;
    }
    
    /**
     * Formatea mensaje de audio
     */
    private function formatAudioMessage($formatted, $message)
    {
        $mediaUrl = $message['media_url'] ?? null;
        $rawJson = json_decode($message['raw_json'] ?? '{}', true);
        $audioMessage = $rawJson['message']['audioMessage'] ?? [];
        $duration = $audioMessage['seconds'] ?? 0;
        
        if ($mediaUrl) {
            $publicUrl = $this->getOrDownloadMedia($mediaUrl, $message, 'audio');
            
            if ($publicUrl) {
                $formatted['media'] = [
                    'type' => 'audio',
                    'url' => $publicUrl,
                    'duration' => $duration,
                    'download_url' => $this->getDownloadUrl($mediaUrl, $message)
                ];
                
                $formatted['display_content'] = '🎵 Audio' . ($duration > 0 ? " ({$duration}s)" : '');
            } else {
                $formatted['display_content'] = '🎵 Audio (download failed)';
            }
        } else {
            $formatted['display_content'] = '🎵 Audio';
        }
        
        return $formatted;
    }
    
    /**
     * Formatea mensaje de documento
     */
    private function formatDocumentMessage($formatted, $message)
    {
        $mediaUrl = $message['media_url'] ?? null;
        $rawJson = json_decode($message['raw_json'] ?? '{}', true);
        $documentMessage = $rawJson['message']['documentMessage'] ?? [];
        $fileName = $documentMessage['fileName'] ?? 'Document';
        $fileSize = $documentMessage['fileSize'] ?? 0;
        $caption = $documentMessage['caption'] ?? '';
        
        if ($mediaUrl) {
            $publicUrl = $this->getOrDownloadMedia($mediaUrl, $message, 'document');
            
            if ($publicUrl) {
                $formatted['media'] = [
                    'type' => 'document',
                    'url' => $publicUrl,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'caption' => $caption,
                    'download_url' => $this->getDownloadUrl($mediaUrl, $message)
                ];
                
                $sizeText = $fileSize > 0 ? $this->formatFileSize($fileSize) : '';
                $formatted['display_content'] = "📄 $fileName" . ($sizeText ? " ($sizeText)" : '');
            } else {
                $formatted['display_content'] = "📄 $fileName (download failed)";
            }
        } else {
            $formatted['display_content'] = "📄 $fileName";
        }
        
        return $formatted;
    }
    
    /**
     * Formatea mensaje de sticker
     */
    private function formatStickerMessage($formatted, $message)
    {
        $mediaUrl = $message['media_url'] ?? null;
        
        if ($mediaUrl) {
            $publicUrl = $this->getOrDownloadMedia($mediaUrl, $message, 'sticker');
            
            if ($publicUrl) {
                $formatted['media'] = [
                    'type' => 'sticker',
                    'url' => $publicUrl,
                    'download_url' => $this->getDownloadUrl($mediaUrl, $message)
                ];
                
                $formatted['display_content'] = '😀 Sticker';
            } else {
                $formatted['display_content'] = '😀 Sticker (download failed)';
            }
        } else {
            $formatted['display_content'] = '😀 Sticker';
        }
        
        return $formatted;
    }
    
    /**
     * Obtiene o descarga media
     */
    private function getOrDownloadMedia($mediaUrl, $message, $type)
    {
        $localPath = $message['local_path'] ?? null;
        
        if ($localPath && file_exists($localPath)) {
            return $this->getPublicUrl($localPath);
        }
        
        // Descargar media
        $mediaInfo = $this->mediaHandler->processMedia(
            $mediaUrl,
            $message['message_id'],
            $this->getInstanceName($message['instance_id']),
            $type
        );
        
        if ($mediaInfo) {
            $this->updateMessageMediaPath($message['id'], $mediaInfo['local_path']);
            return $mediaInfo['public_url'];
        }
        
        return null;
    }
    
    /**
     * Obtiene URL pública para archivo local
     */
    private function getPublicUrl($localPath)
    {
        $fileName = basename($localPath);
        return "https://camcam.com.ve/evoappws/media_handler.php?action=serve&file=$fileName";
    }
    
    /**
     * Obtiene URL de thumbnail
     */
    private function getThumbnailUrl($publicUrl)
    {
        // Para thumbnails, podríamos generarlos o usar la misma imagen
        return $publicUrl;
    }
    
    /**
     * Obtiene URL de descarga
     */
    private function getDownloadUrl($mediaUrl, $message)
    {
        return "https://camcam.com.ve/evoappws/media_handler.php?action=download" .
               "&url=" . urlencode($mediaUrl) .
               "&message_id=" . $message['message_id'] .
               "&instance=" . $this->getInstanceName($message['instance_id']) .
               "&type=" . $message['msg_type'];
    }
    
    /**
     * Obtiene nombre de instancia
     */
    private function getInstanceName($instanceId)
    {
        // Obtener nombre de instancia desde BD
        $instance = DB::fetch("SELECT instance_name FROM instances WHERE id = ?", [$instanceId]);
        return $instance['instance_name'] ?? 'unknown';
    }
    
    /**
     * Actualiza ruta de media en BD
     */
    private function updateMessageMediaPath($messageId, $localPath)
    {
        DB::q("UPDATE messages SET local_path = ? WHERE id = ?", [$localPath, $messageId]);
    }
    
    /**
     * Formatea tamaño de archivo
     */
    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Incluir MediaHandler si no está incluido
if (!class_exists('MediaHandler')) {
    require_once __DIR__ . '/media_handler.php';
}
?>
