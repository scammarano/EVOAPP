<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Instance;
use App\Core\MessageSender;

class GroupController extends Controller
{
    public function index()
    {
        // Obtener instancia actual
        $instanceId = $_GET['instance_id'] ?? 1;
        $instance = Instance::findById($instanceId);
        
        if (!$instance) {
            $this->view('groups/index', [
                'error' => 'Instancia no encontrada'
            ]);
            return;
        }
        
        // Obtener grupos de la base de datos
        $groups = $this->getGroupsFromDatabase($instanceId);
        
        // Obtener grupos de la API
        $apiGroups = $this->getGroupsFromAPI($instance);
        
        $this->view('groups/index', [
            'instance' => $instance,
            'groups' => $groups,
            'apiGroups' => $apiGroups
        ]);
    }
    
    public function extractGroupContacts()
    {
        $instanceId = $_POST['instance_id'] ?? 1;
        $groupId = $_POST['group_id'] ?? '';
        $groupName = $_POST['group_name'] ?? '';
        
        if (empty($groupId) || empty($instanceId)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            return;
        }
        
        try {
            $instance = Instance::findById($instanceId);
            if (!$instance) {
                echo json_encode(['success' => false, 'message' => 'Instancia no encontrada']);
                return;
            }
            
            // Obtener participantes del grupo desde la API
            $participants = $this->getGroupParticipantsFromAPI($instance, $groupId);
            
            if (empty($participants)) {
                echo json_encode(['success' => false, 'message' => 'No se encontraron participantes']);
                return;
            }
            
            // Guardar participantes en la base de datos
            $saved = $this->saveGroupParticipants($instanceId, $groupId, $groupName, $participants);
            
            echo json_encode([
                'success' => true, 
                'message' => "Se guardaron {$saved} participantes del grupo '{$groupName}'",
                'saved_count' => $saved
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    public function extractChatContacts()
    {
        $instanceId = $_POST['instance_id'] ?? 1;
        
        if (empty($instanceId)) {
            echo json_encode(['success' => false, 'message' => 'ID de instancia requerido']);
            return;
        }
        
        try {
            $instance = Instance::findById($instanceId);
            if (!$instance) {
                echo json_encode(['success' => false, 'message' => 'Instancia no encontrada']);
                return;
            }
            
            // Obtener todos los chats desde la API
            $chats = $this->getAllChatsFromAPI($instance);
            
            if (empty($chats)) {
                echo json_encode(['success' => false, 'message' => 'No se encontraron chats']);
                return;
            }
            
            // Extraer contactos de todos los chats
            $saved = $this->saveContactsFromChats($instanceId, $chats);
            
            echo json_encode([
                'success' => true, 
                'message' => "Se extrajeron contactos de {$saved} chats",
                'saved_count' => $saved
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    private function getGroupsFromDatabase($instanceId)
    {
        $pdo = \App\Core\Database::getInstance();
        
        $stmt = $pdo->prepare("
            SELECT id, name, remote_jid, created_at 
            FROM chats 
            WHERE instance_id = ? AND is_group = 1 
            ORDER BY name ASC
        ");
        $stmt->execute([$instanceId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    private function getGroupsFromAPI($instance)
    {
        try {
            $client = $instance->evoClient();
            
            // Obtener todos los chats
            $response = $client->get('/chats');
            
            if (!$response || !isset($response['data'])) {
                return [];
            }
            
            // Filtrar solo grupos
            $groups = array_filter($response['data'], function($chat) {
                return isset($chat['id']) && strpos($chat['id'], '@g.us') !== false;
            });
            
            return array_values($groups);
            
        } catch (Exception $e) {
            error_log("Error obteniendo grupos de API: " . $e->getMessage());
            return [];
        }
    }
    
    private function getGroupParticipantsFromAPI($instance, $groupId)
    {
        try {
            $client = $instance->evoClient();
            
            // Obtener participantes del grupo
            $response = $client->get("/group/{$groupId}/participants");
            
            if (!$response || !isset($response['data'])) {
                return [];
            }
            
            return $response['data'];
            
        } catch (Exception $e) {
            error_log("Error obteniendo participantes del grupo {$groupId}: " . $e->getMessage());
            return [];
        }
    }
    
    private function getAllChatsFromAPI($instance)
    {
        try {
            $client = $instance->evoClient();
            
            // Obtener todos los chats
            $response = $client->get('/chats');
            
            if (!$response || !isset($response['data'])) {
                return [];
            }
            
            return $response['data'];
            
        } catch (Exception $e) {
            error_log("Error obteniendo chats de API: " . $e->getMessage());
            return [];
        }
    }
    
    private function saveGroupParticipants($instanceId, $groupId, $groupName, $participants)
    {
        $pdo = \App\Core\Database::getInstance();
        $saved = 0;
        
        // Crear tabla de participantes si no existe
        $this->createParticipantsTable();
        
        foreach ($participants as $participant) {
            $participantJid = $participant['id'] ?? '';
            $participantName = $participant['pushName'] ?? $participant['name'] ?? '';
            $isAdmin = $participant['isAdmin'] ?? ($participant['type'] === 'admin' ? 1 : 0);
            
            if (empty($participantJid)) continue;
            
            // Extraer teléfono del participante
            $participantPhone = str_replace(['@s.whatsapp.net', '@g.us', '@lid'], '', $participantJid);
            
            // Verificar si ya existe
            $stmt = $pdo->prepare("SELECT id FROM group_participants WHERE group_id = ? AND participant_jid = ?");
            $stmt->execute([$groupId, $participantJid]);
            
            if ($stmt->rowCount() == 0) {
                // Insertar nuevo participante
                $stmt = $pdo->prepare("
                    INSERT INTO group_participants (group_id, group_name, participant_jid, participant_name, participant_phone, is_admin, instance_id, joined_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $groupId,
                    $groupName,
                    $participantJid,
                    $participantName,
                    $participantPhone,
                    $isAdmin,
                    $instanceId
                ]);
                $saved++;
                
                // También guardar como contacto si tiene nombre
                if (!empty($participantName)) {
                    $this->saveAsContact($instanceId, $participantJid, $participantName, $participantPhone);
                }
            }
        }
        
        return $saved;
    }
    
    private function saveContactsFromChats($instanceId, $chats)
    {
        $pdo = \App\Core\Database::getInstance();
        $saved = 0;
        
        foreach ($chats as $chat) {
            $chatId = $chat['id'] ?? '';
            $remoteJid = $chat['remoteJid'] ?? '';
            $chatName = $chat['name'] ?? '';
            
            // Saltar grupos
            if (strpos($chatId, '@g.us') !== false) continue;
            
            // Guardar el chat si tiene nombre
            if (!empty($chatName)) {
                $stmt = $pdo->prepare("
                    INSERT INTO chats (id, instance_id, name, remote_jid, is_group, created_at) 
                    VALUES (?, ?, ?, ?, 0, NOW())
                    ON DUPLICATE KEY UPDATE name = VALUES(name), updated_at = NOW()
                ");
                $stmt->execute([$chatId, $instanceId, $chatName, $remoteJid]);
                $saved++;
            }
            
            // Guardar como contacto si tenemos datos
            if (!empty($remoteJid)) {
                $phone = str_replace(['@s.whatsapp.net', '@g.us', '@lid'], '', $remoteJid);
                $this->saveAsContact($instanceId, $remoteJid, $chatName, $phone);
            }
        }
        
        return $saved;
    }
    
    private function saveAsContact($instanceId, $remoteJid, $name, $phone)
    {
        $pdo = \App\Core\Database::getInstance();
        
        // Verificar si ya existe
        $stmt = $pdo->prepare("SELECT id FROM contacts WHERE remote_jid = ?");
        $stmt->execute([$remoteJid]);
        
        if ($stmt->rowCount() == 0) {
            // Insertar nuevo contacto
            $stmt = $pdo->prepare("
                INSERT INTO contacts (instance_id, phone_e164, name, push_name, remote_jid, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$instanceId, $phone, $name, $name, $remoteJid]);
        } else {
            // Actualizar si tiene nombre
            if (!empty($name)) {
                $stmt = $pdo->prepare("
                    UPDATE contacts SET name = ?, push_name = ?, updated_at = NOW() 
                    WHERE remote_jid = ? AND (name IS NULL OR name = '')
                ");
                $stmt->execute([$name, $name, $remoteJid]);
            }
        }
    }
    
    private function createParticipantsTable()
    {
        $pdo = \App\Core\Database::getInstance();
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'group_participants'");
        
        if ($stmt->rowCount() == 0) {
            $createTable = "
            CREATE TABLE group_participants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                group_id VARCHAR(255) NOT NULL,
                group_name VARCHAR(255),
                participant_jid VARCHAR(255) NOT NULL,
                participant_name VARCHAR(255),
                participant_phone VARCHAR(50),
                is_admin TINYINT(1) DEFAULT 0,
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                instance_id INT,
                INDEX idx_group (group_id),
                INDEX idx_participant (participant_jid),
                INDEX idx_instance (instance_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            $pdo->query($createTable);
        }
    }
}
?>
