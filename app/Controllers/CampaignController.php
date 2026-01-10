<?php

namespace App\Controllers;

use App\Models\Campaign;
use App\Models\Instance;
use App\Core\MessageSender;

class CampaignController
{
    private $campaignModel;
    
    public function __construct()
    {
        $this->campaignModel = new Campaign();
    }
    
    /**
     * Listar campañas
     */
    public function index()
    {
        try {
            $campaigns = $this->campaignModel->getAll();
            $stats = $this->campaignModel->getStatistics();
            
            include __DIR__ . '/../Views/campaigns/index.php';
        } catch (\Exception $e) {
            error_log("Error in campaign index: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar las campañas';
            header('Location: /dashboard');
            exit;
        }
    }
    
    /**
     * Crear campaña
     */
    public function create()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->store();
                return;
            }
            
            $instances = Instance::getAll();
            include __DIR__ . '/../Views/campaigns/create.php';
        } catch (\Exception $e) {
            error_log("Error in campaign create: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el formulario';
            header('Location: /campaigns');
            exit;
        }
    }
    
    /**
     * Guardar campaña
     */
    private function store()
    {
        try {
            // Validar CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new \Exception('Token CSRF inválido');
            }
            
            // Validar datos requeridos
            if (empty($_POST['name'])) {
                throw new \Exception('El nombre de la campaña es requerido');
            }
            
            $campaignData = [
                'name' => trim($_POST['name']),
                'description' => trim($_POST['description'] ?? ''),
                'message' => trim($_POST['message'] ?? ''),
                'instance_id' => (int)($_POST['instance_id'] ?? 0),
                'scheduled_at' => $_POST['scheduled_at'] ?? null,
                'valid_until' => $_POST['valid_until'] ?? null,
                'status' => 'draft'
            ];
            
            $campaignId = $this->campaignModel->create($campaignData);
            
            // Procesar mensajes individuales si existen
            if (!empty($_POST['individual_messages'])) {
                $this->processCampaignMessages($campaignId);
            }
            
            // Procesar adjuntos si existen
            if (!empty($_FILES['attachments'])) {
                $this->processCampaignAttachments($campaignId);
            }
            
            $_SESSION['success'] = 'Campaña creada correctamente';
            header('Location: /campaigns');
            exit;
            
        } catch (\Exception $e) {
            error_log("Error storing campaign: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: /campaigns/create');
            exit;
        }
    }
    
    /**
     * Enviar campaña
     */
    public function send($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }
            
            // Validar CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new \Exception('Token CSRF inválido');
            }
            
            $campaign = $this->campaignModel->getById($id);
            if (!$campaign) {
                throw new \Exception('Campaña no encontrada');
            }
            
            // Usar MessageSender para enviar campaña
            $this->sendCampaignWithMessageSender($campaign);
            
            $_SESSION['success'] = 'Campaña enviada correctamente';
            header('Location: /campaigns');
            exit;
            
        } catch (\Exception $e) {
            error_log("Error sending campaign: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header("Location: /campaigns");
            exit;
        }
    }
    
    /**
     * Enviar campaña usando MessageSender centralizado
     */
    private function sendCampaignWithMessageSender($campaign)
    {
        try {
            // Actualizar estado a enviando
            $this->campaignModel->update($campaign['id'], ['status' => 'sending']);
            
            // Obtener instancia
            $instance = Instance::getById($campaign['instance_id']);
            if (!$instance) {
                throw new \Exception('Instancia no encontrada');
            }
            
            // Crear MessageSender
            $sender = MessageSender::forInstance($campaign['instance_id']);
            
            // Obtener mensajes de la campaña
            $messages = $this->campaignModel->getMessages($campaign['id']);
            
            $sentCount = 0;
            $failedCount = 0;
            
            foreach ($messages as $message) {
                try {
                    // Validar número
                    $phone = MessageSender::formatPhone($message['phone']);
                    if (!MessageSender::validatePhone($phone)) {
                        error_log("Invalid phone number: {$message['phone']}");
                        $failedCount++;
                        continue;
                    }
                    
                    // Obtener adjuntos del mensaje
                    $attachments = $this->campaignModel->getMessageAttachments($message['id']);
                    
                    if (!empty($attachments)) {
                        // Enviar con adjuntos usando la lógica de diagnostic
                        foreach ($attachments as $attachment) {
                            $result = $sender->sendMedia(
                                $phone,
                                $attachment['filepath'],
                                $this->getMediaTypeFromPath($attachment['filepath']),
                                $attachment['mime_type'],
                                $attachment['caption'] ?? '',
                                $message['message'] ?? '',
                                'media_with_text' // Enviar texto + media
                            );
                            
                            if ($result['success']) {
                                $sentCount++;
                            } else {
                                $failedCount++;
                                error_log("Failed to send media to {$phone}: " . $result['error']);
                            }
                            
                            // Pequeña pausa entre mensajes
                            usleep(500000);
                        }
                    } else {
                        // Enviar solo texto
                        $result = $sender->sendText($phone, $message['message']);
                        
                        if ($result['success']) {
                            $sentCount++;
                        } else {
                            $failedCount++;
                            error_log("Failed to send text to {$phone}: " . $result['error']);
                        }
                    }
                    
                    // Pequeña pausa entre destinatarios
                    usleep(1000000); // 1 segundo
                    
                } catch (\Exception $e) {
                    error_log("Error sending message to {$message['phone']}: " . $e->getMessage());
                    $failedCount++;
                }
            }
            
            // Actualizar estado final de la campaña
            $finalStatus = ($failedCount === 0) ? 'sent' : 'partial';
            $this->campaignModel->update($campaign['id'], [
                'status' => $finalStatus,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'sent_at' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'success' => true,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'total_count' => count($messages)
            ];
            
        } catch (\Exception $e) {
            // Actualizar estado a error
            $this->campaignModel->update($campaign['id'], ['status' => 'error']);
            throw $e;
        }
    }
    
    /**
     * Obtener tipo de media desde ruta de archivo
     */
    private function getMediaTypeFromPath($filepath)
    {
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        $types = [
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'webp' => 'image',
            'mp3' => 'audio',
            'wav' => 'audio',
            'ogg' => 'audio',
            'mp4' => 'video',
            '3gp' => 'video',
            'mov' => 'video',
            'pdf' => 'document',
            'txt' => 'document',
            'doc' => 'document',
            'docx' => 'document',
            'xls' => 'document',
            'xlsx' => 'document'
        ];
        
        return $types[$extension] ?? 'document';
    }
    
    /**
     * Procesar mensajes de la campaña desde el formulario
     */
    private function processCampaignMessages($campaignId)
    {
        $messages = [];
        
        // Mensajes individuales
        if (!empty($_POST['individual_messages'])) {
            $phoneNumbers = explode("\n", trim($_POST['individual_messages']));
            
            foreach ($phoneNumbers as $index => $phoneNumber) {
                $phoneNumber = trim($phoneNumber);
                if (!empty($phoneNumber)) {
                    $messages[] = [
                        'phone' => MessageSender::formatPhone($phoneNumber),
                        'message' => $_POST['message'] ?? '',
                        'order_index' => $index + 1
                    ];
                }
            }
        }
        
        // Mensajes desde CSV
        if (!empty($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $csvMessages = $this->parseCsvMessages($_FILES['csv_file']['tmp_name']);
            $messages = array_merge($messages, $csvMessages);
        }
        
        // Guardar mensajes
        foreach ($messages as $message) {
            $this->campaignModel->addMessage($campaignId, $message);
        }
    }
    
    /**
     * Procesar adjuntos de la campaña
     */
    private function processCampaignAttachments($campaignId)
    {
        $uploadPath = __DIR__ . '/../../uploads/campaigns/' . $campaignId;
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        foreach ($_FILES['attachments']['name'] as $index => $name) {
            if ($_FILES['attachments']['error'][$index] === UPLOAD_ERR_OK) {
                $filename = uniqid() . '_' . basename($name);
                $filepath = $uploadPath . '/' . $filename;
                
                if (move_uploaded_file($_FILES['attachments']['tmp_name'][$index], $filepath)) {
                    $this->campaignModel->addAttachment($campaignId, [
                        'filename' => $name,
                        'filepath' => $filepath,
                        'mime_type' => $_FILES['attachments']['type'][$index],
                        'size' => $_FILES['attachments']['size'][$index],
                        'caption' => $_POST['attachment_captions'][$index] ?? '',
                        'order_index' => $index + 1
                    ]);
                }
            }
        }
    }
    
    /**
     * Parsear mensajes desde archivo CSV
     */
    private function parseCsvMessages($csvFile)
    {
        $messages = [];
        $handle = fopen($csvFile, 'r');
        
        if ($handle) {
            // Saltar encabezado si existe
            $header = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                if (empty($row[0])) continue;
                
                $phone = MessageSender::formatPhone(trim($row[0]));
                $message = trim($row[1] ?? '');
                
                if (!empty($phone)) {
                    $messages[] = [
                        'phone' => $phone,
                        'message' => $message,
                        'order_index' => count($messages) + 1
                    ];
                }
            }
            
            fclose($handle);
        }
        
        return $messages;
    }
    
    /**
     * Editar campaña
     */
    public function edit($id)
    {
        try {
            $campaign = $this->campaignModel->getById($id);
            
            if (!$campaign) {
                $_SESSION['error'] = 'Campaña no encontrada';
                header('Location: /campaigns');
                exit;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->update($id);
                return;
            }
            
            $instances = Instance::getAll();
            include __DIR__ . '/../Views/campaigns/edit.php';
        } catch (\Exception $e) {
            error_log("Error in campaign edit: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar la campaña';
            header('Location: /campaigns');
            exit;
        }
    }
    
    /**
     * Actualizar campaña
     */
    private function update($id)
    {
        try {
            // Validar CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new \Exception('Token CSRF inválido');
            }
            
            $campaignData = [
                'name' => trim($_POST['name']),
                'description' => trim($_POST['description'] ?? ''),
                'message' => trim($_POST['message'] ?? ''),
                'instance_id' => (int)($_POST['instance_id'] ?? 0),
                'scheduled_at' => $_POST['scheduled_at'] ?? null,
                'valid_until' => $_POST['valid_until'] ?? null
            ];
            
            if ($this->campaignModel->update($id, $campaignData)) {
                $_SESSION['success'] = 'Campaña actualizada correctamente';
                header('Location: /campaigns');
                exit;
            } else {
                throw new \Exception('Error al actualizar la campaña');
            }
            
        } catch (\Exception $e) {
            error_log("Error updating campaign: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header("Location: /campaigns/edit/{$id}");
            exit;
        }
    }
    
    /**
     * Ver detalles de campaña
     */
    public function view($id)
    {
        try {
            $campaign = $this->campaignModel->getById($id);
            
            if (!$campaign) {
                $_SESSION['error'] = 'Campaña no encontrada';
                header('Location: /campaigns');
                exit;
            }
            
            include __DIR__ . '/../Views/campaigns/view.php';
        } catch (\Exception $e) {
            error_log("Error viewing campaign: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar la campaña';
            header('Location: /campaigns');
            exit;
        }
    }
    
    /**
     * Eliminar campaña
     */
    public function delete($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }
            
            // Validar CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new \Exception('Token CSRF inválido');
            }
            
            if ($this->campaignModel->delete($id)) {
                $_SESSION['success'] = 'Campaña eliminada correctamente';
            } else {
                throw new \Exception('Error al eliminar la campaña');
            }
            
        } catch (\Exception $e) {
            error_log("Error deleting campaign: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: /campaigns');
        exit;
    }
}