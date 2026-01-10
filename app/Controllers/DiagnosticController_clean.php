<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\MessageSender;
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
        $sendMode = $_POST['send_mode'] ?? 'media_only';
        
        if (!$instanceId) {
            echo json_encode(['error' => 'Instance ID required']);
            return;
        }
        
        // Validar y formatear número
        $testNumber = MessageSender::formatPhone($testNumber);
        if (!MessageSender::validatePhone($testNumber)) {
            echo json_encode(['error' => 'Invalid phone number format']);
            return;
        }
        
        try {
            $sender = MessageSender::forInstance($instanceId);
            $result = [];
            
            switch ($testType) {
                case 'single':
                    $result = $sender->sendText($testNumber, $testText);
                    break;
                    
                case 'burst':
                    $result = $sender->sendBurst($testNumber, $testText, 3);
                    break;
                    
                case 'media':
                    if (isset($_FILES['test_media']) && $_FILES['test_media']['error'] === UPLOAD_ERR_OK) {
                        $result = $sender->sendMediaFromFile(
                            $testNumber,
                            $_FILES['test_media'],
                            $testCaption,
                            $testText,
                            $sendMode
                        );
                    } else {
                        echo json_encode(['error' => 'Media file required for media test']);
                        return;
                    }
                    break;
                    
                default:
                    echo json_encode(['error' => 'Invalid test type']);
                    return;
            }
            
            // Formatear respuesta para compatibilidad con UI existente
            echo json_encode([
                'success' => $result['success'],
                'result' => [
                    'instance_slug' => $sender->instance['slug'],
                    'baseUrl' => $sender->instance['base_url'],
                    'test_type' => $testType,
                    'send_mode' => $sendMode,
                    'number' => $testNumber,
                    'message' => $result['message'] ?? '',
                    'type' => $result['type'] ?? '',
                    'results' => $result['results'] ?? [],
                    'error' => $result['error'] ?? null
                ]
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function testAll()
    {
        header('Content-Type: application/json');
        
        if (!Auth::isLoggedIn()) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $instanceIds = $_POST['instance_ids'] ?? '';
        $testNumber = $_POST['test_number'] ?? '+10000000000';
        $testText = $_POST['test_text'] ?? 'Mensaje de prueba - EVOAPP Diagnostic';
        $testType = $_POST['test_type'] ?? 'single';
        $testCaption = $_POST['test_caption'] ?? '';
        $sendMode = $_POST['send_mode'] ?? 'media_only';
        
        if (empty($instanceIds)) {
            echo json_encode(['error' => 'Instance IDs required']);
            return;
        }
        
        $instanceIdArray = explode(',', $instanceIds);
        $results = [];
        
        foreach ($instanceIdArray as $instanceId) {
            $instanceId = (int)$instanceId;
            
            try {
                $sender = MessageSender::forInstance($instanceId);
                $result = [];
                
                switch ($testType) {
                    case 'single':
                        $result = $sender->sendText($testNumber, $testText);
                        break;
                        
                    case 'burst':
                        $result = $sender->sendBurst($testNumber, $testText, 3);
                        break;
                        
                    case 'media':
                        if (isset($_FILES['test_media']) && $_FILES['test_media']['error'] === UPLOAD_ERR_OK) {
                            $result = $sender->sendMediaFromFile(
                                $testNumber,
                                $_FILES['test_media'],
                                $testCaption,
                                $testText,
                                $sendMode
                            );
                        } else {
                            $result = ['success' => false, 'error' => 'Media file required'];
                        }
                        break;
                        
                    default:
                        $result = ['success' => false, 'error' => 'Invalid test type'];
                }
                
                $results[] = [
                    'instance' => [
                        'id' => $instanceId,
                        'slug' => $sender->instance['slug']
                    ],
                    'success' => $result['success'],
                    'result' => $result
                ];
                
            } catch (\Exception $e) {
                $results[] = [
                    'instance' => [
                        'id' => $instanceId,
                        'slug' => 'Unknown'
                    ],
                    'success' => false,
                    'result' => ['error' => $e->getMessage()]
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'results' => $results
        ]);
    }
}
