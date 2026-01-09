<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Instance;

class DiagnosticController
{
    public function index()
    {
        if (!Auth::isLoggedIn()) {
            header('Location: index.php?r=auth/login');
            exit;
        }
        
        // Obtener todas las instancias
        $instances = Instance::getAll(false); // false para obtener todas, no solo activas
        
        View::set('instances', $instances);
        View::set('user', Auth::getCurrentUser());
        View::render('diagnostic/index');
    }
    
    public function testInstance()
    {
        header('Content-Type: application/json');
        
        if (!Auth::isLoggedIn()) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $instanceId = (int)($_POST['instance_id'] ?? 0);
        $testNumber = $_POST['test_number'] ?? '+10000000000';
        $testText = $_POST['test_text'] ?? 'Mensaje de prueba - EVOAPP Diagnostic';
        $testType = $_POST['test_type'] ?? 'single'; // single, burst, media
        $testCaption = $_POST['test_caption'] ?? '';
        
        if (!$instanceId) {
            echo json_encode(['error' => 'Instance ID required']);
            return;
        }
        
        $instance = Instance::findById($instanceId);
        if (!$instance) {
            echo json_encode(['error' => 'Instance not found']);
            return;
        }
        
        $mediaUpload = null;
        try {
            $mediaUpload = $this->prepareUploadedMedia('test_media');
            $result = $this->testInstanceConnection($instance, $testNumber, $testText, $testType, $testCaption, $mediaUpload);
            echo json_encode(['success' => true, 'result' => $result]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        } finally {
            if (!empty($mediaUpload['cleanup']) && !empty($mediaUpload['path']) && file_exists($mediaUpload['path'])) {
                @unlink($mediaUpload['path']);
            }
        }
    }
    
    public function testAll()
    {
        header('Content-Type: application/json');
        
        if (!Auth::isLoggedIn()) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $testNumber = $_POST['test_number'] ?? '+10000000000';
        $testText = $_POST['test_text'] ?? 'Test masivo - EVOAPP';
        $testType = $_POST['test_type'] ?? 'single';
        $testCaption = $_POST['test_caption'] ?? '';
        $requestedIds = array_filter(array_map('intval', explode(',', $_POST['instance_ids'] ?? '')));
        
        $instances = Instance::getAll(false);
        if (!empty($requestedIds)) {
            $instances = array_values(array_filter($instances, function ($instance) use ($requestedIds) {
                return in_array((int)$instance['id'], $requestedIds, true);
            }));
        }
        $results = [];
        
        $mediaUpload = null;
        try {
            $mediaUpload = $this->prepareUploadedMedia('test_media');
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            return;
        }

        foreach ($instances as $instance) {
            try {
                // Pausa entre pruebas para no sobrecargar
                usleep(500000); // 0.5 segundos de pausa

                $result = $this->testInstanceConnection($instance, $testNumber, $testText, $testType, $testCaption, $mediaUpload);
                $results[] = [
                    'instance' => $instance,
                    'result' => $result,
                    'success' => true
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'instance' => $instance,
                    'result' => ['error' => $e->getMessage()],
                    'success' => false
                ];
            }
        }

        if (!empty($mediaUpload['cleanup']) && !empty($mediaUpload['path']) && file_exists($mediaUpload['path'])) {
            @unlink($mediaUpload['path']);
        }
        
        echo json_encode(['success' => true, 'results' => $results]);
    }
    
    private function testInstanceConnection($instance, $number, $text, $testType = 'single', $caption = '', $mediaUpload = null)
    {
        $baseUrl = $instance['base_url'] ?: EVO_BASE_URL;
        $apiKey = $instance['api_key'];
        
        // Log para debugging
        error_log("Diagnostic: Testing instance {$instance['slug']} with URL: $baseUrl, Type: $testType");
        
        if (!$apiKey) {
            throw new \Exception('API Key no configurada');
        }
        
        $results = [];
        
        // Test 1: Conexión básica
        error_log("Diagnostic: Testing connection for {$instance['slug']}");
        $results['connection'] = $this->makeRequest($baseUrl, $instance['slug'], $apiKey, '+10000000000', 'TEST_CONNECTION');
        
        // Test 2: Envío según tipo
        if ($results['connection']['code'] !== 404 && $results['connection']['code'] !== -1) {
            error_log("Diagnostic: Testing send messages for {$instance['slug']}, Type: $testType");
            
            switch ($testType) {
                case 'burst':
                    $results['burst'] = $this->testBurstMessages($baseUrl, $instance['slug'], $apiKey, $number, $text);
                    break;
                case 'media':
                    $results['media'] = $this->testMediaMessage(
                        $baseUrl,
                        $instance['slug'],
                        $apiKey,
                        $number,
                        $caption !== '' ? $caption : $text,
                        $mediaUpload
                    );
                    break;
                case 'single':
                default:
                    $results['send'] = $this->makeRequest($baseUrl, $instance['slug'], $apiKey, $number, $text);
                    break;
            }
        }
        
        return [
            'connection' => $results['connection'],
            'send' => $results['send'] ?? null,
            'burst' => $results['burst'] ?? null,
            'media' => $results['media'] ?? null,
            'baseUrl' => $baseUrl,
            'connected' => $results['connection']['code'] !== 404 && $results['connection']['code'] !== -1,
            'instance_slug' => $instance['slug'],
            'test_type' => $testType
        ];
    }
    
    private function testBurstMessages($baseUrl, $slug, $apiKey, $number, $baseText)
    {
        $burstResults = [];
        $messageCount = 3;
        
        for ($i = 1; $i <= $messageCount; $i++) {
            $text = $baseText . " [Mensaje {$i}/{$messageCount} - Ráfaga]";
            $result = $this->makeRequest($baseUrl, $slug, $apiKey, $number, $text);
            $burstResults[] = $result;
            
            // Pequeña pausa entre mensajes
            usleep(200000); // 0.2 segundos
        }
        
        return $burstResults;
    }
    
    private function testMediaMessage($baseUrl, $slug, $apiKey, $number, $caption, $mediaUpload = null)
    {
        $trimmedCaption = trim((string)$caption);
        $payloadCaption = $trimmedCaption === '' ? null : $trimmedCaption;

        $mediaPath = null;
        $mimeType = 'image/png';
        $mediaType = 'image';
        $shouldCleanup = false;

        if ($mediaUpload && !empty($mediaUpload['path']) && file_exists($mediaUpload['path'])) {
            $mediaPath = $mediaUpload['path'];
            $mimeType = $mediaUpload['mimeType'] ?? 'application/octet-stream';
            $mediaType = $mediaUpload['mediaType'] ?? 'document';
            $shouldCleanup = !empty($mediaUpload['cleanup']);
        } else {
            $mediaPath = $this->createDiagnosticMediaTempFile();
            if (!$mediaPath) {
                return [
                    'code' => -1,
                    'time' => 0,
                    'error' => 'No se pudo generar el archivo de prueba',
                    'response' => null,
                    'url' => null
                ];
            }
            $shouldCleanup = true;
        }

        try {
            return $this->makeMediaRequest(
                $baseUrl,
                $slug,
                $apiKey,
                $number,
                $mediaPath,
                $mediaType,
                $mimeType,
                $payloadCaption
            );
        } finally {
            if ($shouldCleanup && $mediaPath && file_exists($mediaPath)) {
                unlink($mediaPath);
            }
            if ($shouldCleanup && $mediaPath) {
                @unlink($mediaPath);
            }
        }
    }
    
    private function makeRequest($baseUrl, $slug, $apiKey, $number, $text)
    {
        $url = $baseUrl . "/message/sendText/" . $slug;
        $payload = json_encode([
            'number' => $number,
            'text' => $text
        ]);
        
        error_log("Diagnostic: Making request to URL: $url");
        error_log("Diagnostic: Payload: $payload");
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);
        
        error_log("Diagnostic: Response code: $httpCode, Error: $error, Time: $time");
        
        $result = [
            'code' => $httpCode,
            'time' => round($time * 1000),
            'error' => $error,
            'response' => null,
            'url' => $url
        ];
        
        if ($error) {
            error_log("Diagnostic: CURL Error: $error");
            $result['error'] = $error;
        } else {
            error_log("Diagnostic: Raw response: $response");
            try {
                $result['response'] = json_decode($response, true);
            } catch (\Exception $e) {
                error_log("Diagnostic: JSON decode error: " . $e->getMessage());
                $result['response'] = $response;
            }
        }
        
        return $result;
    }

    private function makeMediaRequest($baseUrl, $slug, $apiKey, $number, $mediaPath, $mediaType, $mimeType, $caption = null)
    {
        $url = $baseUrl . "/message/sendMedia/" . $slug;
        $postFields = [
            'number' => $number,
            'mediatype' => $mediaType,
            'mimetype' => $mimeType,
            'file' => new \CURLFile($mediaPath, $mimeType, basename($mediaPath))
        ];

        if ($caption !== null && $caption !== '') {
            $postFields['caption'] = $caption;
        }

        error_log("Diagnostic: Making media request to URL: $url");
        error_log("Diagnostic: Media path: $mediaPath");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);

        error_log("Diagnostic: Media response code: $httpCode, Error: $error, Time: $time");

        $result = [
            'code' => $httpCode,
            'time' => round($time * 1000),
            'error' => $error,
            'response' => null,
            'url' => $url
        ];

        if ($error) {
            $result['error'] = $error;
        } else {
            try {
                $result['response'] = json_decode($response, true);
            } catch (\Exception $e) {
                $result['response'] = $response;
            }
        }

        return $result;
    }

    private function createDiagnosticMediaTempFile()
    {
        $base64Png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $binary = base64_decode($base64Png, true);
        if ($binary === false) {
            return null;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'diag_media_');
        if ($tempPath === false) {
            return null;
        }

        $pngPath = $tempPath . '.png';
        if (file_put_contents($pngPath, $binary) === false) {
            unlink($tempPath);
            if (file_exists($pngPath)) {
                unlink($pngPath);
            }
            return null;
        }

        unlink($tempPath);
        if (@file_put_contents($pngPath, $binary) === false) {
            @unlink($tempPath);
            return null;
        }

        @unlink($tempPath);
        return $pngPath;
    }

    private function prepareUploadedMedia($fileKey)
    {
        if (!isset($_FILES[$fileKey]) || !is_array($_FILES[$fileKey])) {
            return null;
        }

        $file = $_FILES[$fileKey];
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Error al subir el archivo adjunto');
        }

        $extension = pathinfo($file['name'] ?? '', PATHINFO_EXTENSION);
        $tempPath = tempnam(sys_get_temp_dir(), 'diag_upload_');
        if ($tempPath === false) {
            throw new \Exception('No se pudo preparar el archivo adjunto');
        }

        $targetPath = $extension ? $tempPath . '.' . $extension : $tempPath;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            @unlink($tempPath);
            throw new \Exception('No se pudo guardar el archivo adjunto');
        }

        if ($targetPath !== $tempPath && file_exists($tempPath)) {
            @unlink($tempPath);
        }

        $mimeType = $file['type'] ?? '';
        if ($mimeType === '' && function_exists('mime_content_type')) {
            $mimeType = mime_content_type($targetPath) ?: 'application/octet-stream';
        }

        return [
            'path' => $targetPath,
            'mimeType' => $mimeType ?: 'application/octet-stream',
            'mediaType' => $this->resolveMediaType($mimeType ?: 'application/octet-stream'),
            'cleanup' => true
        ];
    }

    private function resolveMediaType($mimeType)
    {
        if (strpos($mimeType, 'image/') === 0) {
            return 'image';
        }
        if (strpos($mimeType, 'video/') === 0) {
            return 'video';
        }
        if (strpos($mimeType, 'audio/') === 0) {
            return 'audio';
        }
        return 'document';
    }
}
