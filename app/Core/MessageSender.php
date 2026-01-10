<?php

namespace App\Core;

use App\Models\Instance;

/**
 * Clase centralizada para envío de mensajes
 * Reutiliza la lógica probada en diagnostic para todos los sistemas
 */
class MessageSender
{
    // VERSIÓN DEL MODELO
    const MODEL_VERSION = '1.0';
    const LAST_MODIFIED = '2025-01-10 16:30:00';
    const FEATURES = ['Centralized Sending', 'Phone Validation', 'Media Support', 'Burst Mode'];
    private $instance;
    private $client;
    
    public function __construct($instance)
    {
        $this->instance = $instance;
        $this->client = Instance::evoClient($instance);
    }
    
    /**
     * Enviar mensaje de texto simple
     */
    public function sendText($number, $text)
    {
        try {
            $result = $this->client->sendText($this->instance['slug'], $number, $text);
            
            return [
                'success' => true,
                'result' => $result,
                'type' => 'text',
                'number' => $number,
                'message' => $text
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'type' => 'text',
                'number' => $number,
                'message' => $text
            ];
        }
    }
    
    /**
     * Enviar mensaje con adjunto (lógica de diagnostic)
     * @param string $number Número de teléfono
     * @param string $mediaPath Ruta al archivo
     * @param string $mediaType Tipo de media (image, video, audio, document)
     * @param string $mimeType MIME type del archivo
     * @param string $caption Caption del adjunto
     * @param string $text Mensaje de texto adicional
     * @param string $sendMode 'media_only' o 'media_with_text'
     */
    public function sendMedia($number, $mediaPath, $mediaType, $mimeType, $caption = '', $text = '', $sendMode = 'media_only')
    {
        try {
            $results = [];
            
            // Usar la lógica probada en diagnostic
            if ($sendMode === 'media_with_text' && !empty($text)) {
                // 1. Enviar texto primero
                $textResult = $this->client->sendText($this->instance['slug'], $number, $text);
                $results['text'] = $textResult;
                
                // Pequeña pausa entre mensajes
                usleep(500000); // 0.5 segundos
                
                // 2. Enviar media con caption
                $mediaResult = $this->client->sendMedia(
                    $this->instance['slug'],
                    $number,
                    $mediaPath,
                    $mediaType,
                    $mimeType,
                    $caption
                );
                $results['media'] = $mediaResult;
                
                $message = 'Text and media sent successfully';
                $type = 'media_with_text';
                
            } else {
                // Solo enviar media con caption
                $mediaResult = $this->client->sendMedia(
                    $this->instance['slug'],
                    $number,
                    $mediaPath,
                    $mediaType,
                    $mimeType,
                    $caption
                );
                $results['media'] = $mediaResult;
                
                $message = 'Media sent successfully';
                $type = 'media_only';
            }
            
            return [
                'success' => true,
                'message' => $message,
                'type' => $type,
                'send_mode' => $sendMode,
                'results' => $results,
                'number' => $number,
                'caption' => $caption,
                'text' => $text,
                'media_info' => [
                    'path' => $mediaPath,
                    'type' => $mediaType,
                    'mime_type' => $mimeType
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'type' => 'media',
                'send_mode' => $sendMode,
                'number' => $number,
                'caption' => $caption,
                'text' => $text
            ];
        }
    }
    
    /**
     * Enviar mensaje con archivo desde $_FILES
     */
    public function sendMediaFromFile($number, $file, $caption = '', $text = '', $sendMode = 'media_only')
    {
        // Validar archivo
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => 'File upload error: ' . $file['error'],
                'type' => 'media'
            ];
        }
        
        // Determinar tipo de media
        $mediaType = $this->getMediaType($file['type']);
        if (!$mediaType) {
            return [
                'success' => false,
                'error' => 'Unsupported file type: ' . $file['type'],
                'type' => 'media'
            ];
        }
        
        // Enviar usando el método principal
        return $this->sendMedia(
            $number,
            $file['tmp_name'],
            $mediaType,
            $file['type'],
            $caption,
            $text,
            $sendMode
        );
    }
    
    /**
     * Enviar ráfaga de mensajes (lógica de diagnostic)
     */
    public function sendBurst($number, $text, $count = 3)
    {
        $results = [];
        $success = true;
        
        for ($i = 0; $i < $count; $i++) {
            $burstText = $text . " (" . ($i + 1) . "/{$count})";
            $result = $this->sendText($number, $burstText);
            $results[] = $result;
            
            if (!$result['success']) {
                $success = false;
            }
            
            // Pequeña pausa entre mensajes
            usleep(200000); // 0.2 segundos
        }
        
        return [
            'success' => $success,
            'type' => 'burst',
            'count' => $count,
            'results' => $results,
            'number' => $number,
            'message' => $text
        ];
    }
    
    /**
     * Determinar tipo de media desde MIME type
     */
    private function getMediaType($mimeType)
    {
        $types = [
            'image/jpeg' => 'image',
            'image/png' => 'image',
            'image/gif' => 'image',
            'image/webp' => 'image',
            'audio/mpeg' => 'audio',
            'audio/mp3' => 'audio',
            'audio/wav' => 'audio',
            'audio/ogg' => 'audio',
            'video/mp4' => 'video',
            'video/3gpp' => 'video',
            'video/quicktime' => 'video',
            'application/pdf' => 'document',
            'text/plain' => 'document',
            'application/msword' => 'document',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'document',
            'application/vnd.ms-excel' => 'document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'document'
        ];
        
        return $types[$mimeType] ?? null;
    }
    
    /**
     * Validar número de teléfono
     */
    public static function validatePhone($phone)
    {
        // Eliminar caracteres no numéricos excepto +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Validar formato básico
        if (!preg_match('/^\+\d{10,15}$/', $phone)) {
            return false;
        }
        
        return $phone;
    }
    
    /**
     * Formatear número a formato E164
     */
    public static function formatPhone($phone)
    {
        // Eliminar caracteres no numéricos excepto +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Si no empieza con +, agregar código de país (Venezuela por defecto)
        if (!str_starts_with($phone, '+')) {
            if (strlen($phone) === 10) {
                $phone = '+58' . $phone;
            } else {
                $phone = '+' . $phone;
            }
        }
        
        return $phone;
    }
    
    /**
     * Crear instancia para una instancia específica
     */
    public static function forInstance($instanceId)
    {
        $instance = Instance::findById($instanceId);
        if (!$instance) {
            throw new \Exception('Instance not found');
        }
        
        return new self($instance);
    }
    
    /**
     * Crear instancia para una instancia por slug
     */
    public static function forSlug($slug)
    {
        $instance = Instance::findBySlug($slug);
        if (!$instance) {
            throw new \Exception('Instance not found');
        }
        
        return new self($instance);
    }
    
    /**
     * Enviar mensaje usando instancia específica (método estático conveniente)
     */
    public static function sendMessage($instanceId, $number, $text)
    {
        $sender = self::forInstance($instanceId);
        return $sender->sendText($number, $text);
    }
    
    /**
     * Enviar media usando instancia específica (método estático conveniente)
     */
    public static function sendMediaMessage($instanceId, $number, $mediaPath, $mediaType, $mimeType, $caption = '', $text = '', $sendMode = 'media_only')
    {
        $sender = self::forInstance($instanceId);
        return $sender->sendMedia($number, $mediaPath, $mediaType, $mimeType, $caption, $text, $sendMode);
    }
    
    /**
     * Enviar ráfaga usando instancia específica (método estático conveniente)
     */
    public static function sendBurstMessages($instanceId, $number, $text, $count = 3)
    {
        $sender = self::forInstance($instanceId);
        return $sender->sendBurst($number, $text, $count);
    }
}
