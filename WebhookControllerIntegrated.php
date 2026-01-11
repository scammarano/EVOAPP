<?php
namespace App\Controllers;

use App\Core\DB;
use PDO;
use PDOException;

/**
 * Webhook Controller Integrated - Procesamiento REAL de todos los eventos Evolution API
 * 
 * Este controller integra el procesamiento completo de los 26 eventos Evolution API
 * con acciones específicas para cada tipo de evento y almacenamiento en base de datos.
 */
class WebhookControllerIntegrated
{
    private $db;
    private $instance;
    private $event;
    private $data;
    
    public function __construct()
    {
        $this->db = DB::getInstance();
    }
    
    /**
     * Método principal para procesar webhooks
     */
    public function processWebhook()
    {
        header('Content-Type: application/json');
        
        try {
            // Validar petición
            $validation = $this->validateRequest();
            if (!$validation['success']) {
                return $this->response(false, $validation['message'], null, $validation);
            }
            
            // Extraer datos
            $this->instance = $_GET['instance'] ?? null;
            $input = json_decode(file_get_contents('php://input'), true);
            $this->event = $input['event'] ?? null;
            $this->data = $input['data'] ?? null;
            
            // Procesar evento específico
            $result = $this->processEvent();
            
            // Responder
            return $this->response(
                $result['success'], 
                $result['message'], 
                $result['data'] ?? null, 
                $result['debug'] ?? []
            );
            
        } catch (Exception $e) {
            return $this->response(false, 'Error processing webhook: ' . $e->getMessage(), null, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Validar la petición webhook
     */
    private function validateRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $instance = $_GET['instance'] ?? null;
        $input = file_get_contents('php://input');
        $jsonInput = json_decode($input, true);
        
        if ($method !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }
        
        if (empty($instance)) {
            return ['success' => false, 'message' => 'Missing instance parameter'];
        }
        
        if (empty($input)) {
            return ['success' => false, 'message' => 'Missing JSON input'];
        }
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()];
        }
        
        if (empty($jsonInput['event'])) {
            return ['success' => false, 'message' => 'Missing event field'];
        }
        
        if (empty($jsonInput['data'])) {
            return ['success' => false, 'message' => 'Missing data field'];
        }
        
        return ['success' => true, 'message' => 'Request validated successfully'];
    }
    
    /**
     * Procesar el evento específico
     */
    private function processEvent()
    {
        try {
            // Log del evento recibido
            $this->logWebhookEvent();
            
            // Procesar según el tipo de evento
            switch ($this->event) {
                // System Events
                case 'QRCODE_UPDATED':
                    return $this->handleQrCodeUpdated();
                case 'CONNECTION_UPDATE':
                    return $this->handleConnectionUpdate();
                case 'NEW_TOKEN':
                    return $this->handleNewToken();
                case 'APPLICATION_STARTUP':
                    return $this->handleApplicationStartup();
                    
                // Message Events
                case 'MESSAGES_SET':
                    return $this->handleMessagesSet();
                case 'MESSAGES_UPSERT':
                    return $this->handleMessagesUpsert();
                case 'MESSAGES_UPDATE':
                    return $this->handleMessagesUpdate();
                case 'MESSAGES_DELETE':
                    return $this->handleMessagesDelete();
                case 'SEND_MESSAGE':
                    return $this->handleSendMessage();
                    
                // Contact Events
                case 'CONTACTS_SET':
                    return $this->handleContactsSet();
                case 'CONTACTS_UPSERT':
                    return $this->handleContactsUpsert();
                case 'CONTACTS_UPDATE':
                    return $this->handleContactsUpdate();
                    
                // Chat Events
                case 'CHATS_SET':
                    return $this->handleChatsSet();
                case 'CHATS_UPSERT':
                    return $this->handleChatsUpsert();
                case 'CHATS_UPDATE':
                    return $this->handleChatsUpdate();
                case 'CHATS_DELETE':
                    return $this->handleChatsDelete();
                    
                // Group Events
                case 'GROUPS_UPSERT':
                    return $this->handleGroupsUpsert();
                case 'GROUPS_UPDATE':
                    return $this->handleGroupsUpdate();
                    
                // Typebot Events
                case 'TYPEBOT_START':
                    return $this->handleTypebotStart();
                case 'TYPEBOT_CHANGE_STATUS':
                    return $this->handleTypebotChangeStatus();
                    
                // Additional Events
                case 'PRESENCE_UPDATE':
                    return $this->handlePresenceUpdate();
                case 'GROUP_PARTICIPANTS_UPDATE':
                    return $this->handleGroupParticipantsUpdate();
                case 'STATUS_MESSAGE':
                    return $this->handleStatusMessage();
                case 'LABELS_EDIT':
                    return $this->handleLabelsEdit();
                case 'LABELS_ASSOCIATION':
                    return $this->handleLabelsAssociation();
                case 'CALL_WEBHOOK':
                    return $this->handleCallWebhook();
                    
                default:
                    return $this->handleUnknownEvent();
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error processing event ' . $this->event . ': ' . $e->getMessage(),
                'data' => null,
                'debug' => ['error' => $e->getMessage()]
            ];
        }
    }
    
    // ==================== SYSTEM EVENTS ====================
    
    private function handleQrCodeUpdated()
    {
        // Actualizar QR code en la instancia
        $qrCode = $this->data['qrcode']['base64'] ?? null;
        
        if ($qrCode) {
            $sql = "UPDATE evo_instances SET qr_code = ?, updated_at = NOW() WHERE instance_name = ?";
            $this->db->q($sql, [$qrCode, $this->instance]);
        }
        
        return [
            'success' => true,
            'message' => 'QR Code updated successfully',
            'data' => ['qr_code_updated' => true]
        ];
    }
    
    private function handleConnectionUpdate()
    {
        $state = $this->data['state'] ?? 'unknown';
        $connected = in_array($state, ['open', 'connected']);
        
        // Actualizar estado de conexión
        $sql = "UPDATE evo_instances SET connection_status = ?, is_connected = ?, updated_at = NOW() WHERE instance_name = ?";
        $this->db->q($sql, [$state, $connected ? 1 : 0, $this->instance]);
        
        return [
            'success' => true,
            'message' => 'Connection status updated',
            'data' => ['connection_state' => $state, 'connected' => $connected]
        ];
    }
    
    private function handleNewToken()
    {
        $token = $this->data['token'] ?? null;
        
        if ($token) {
            $sql = "UPDATE evo_instances SET jwt_token = ?, updated_at = NOW() WHERE instance_name = ?";
            $this->db->q($sql, [$token, $this->instance]);
        }
        
        return [
            'success' => true,
            'message' => 'Token updated successfully',
            'data' => ['token_updated' => true]
        ];
    }
    
    private function handleApplicationStartup()
    {
        // Registrar inicio de aplicación
        $sql = "INSERT INTO application_logs (instance_name, event_type, data, created_at) VALUES (?, ?, ?, NOW())";
        $this->db->q($sql, [$this->instance, 'APPLICATION_STARTUP', json_encode($this->data)]);
        
        return [
            'success' => true,
            'message' => 'Application startup logged',
            'data' => ['startup_logged' => true]
        ];
    }
    
    // ==================== MESSAGE EVENTS ====================
    
    private function handleMessagesSet()
    {
        $processed = 0;
        $messages = is_array($this->data) ? $this->data : [$this->data];
        
        foreach ($messages as $message) {
            if ($this->processMessageData($message, 'SET')) {
                $processed++;
            }
        }
        
        return [
            'success' => true,
            'message' => "Messages set processed: {$processed} messages",
            'data' => ['processed' => $processed]
        ];
    }
    
    private function handleMessagesUpsert()
    {
        $messageKey = $this->data['key'] ?? [];
        $remoteJid = $messageKey['remoteJid'] ?? null;
        $messageId = $messageKey['id'] ?? null;
        
        if ($remoteJid && $messageId) {
            // Verificar si ya existe
            $sql = "SELECT id FROM messages WHERE instance_name = ? AND message_id = ?";
            $existing = $this->db->fetch($sql, [$this->instance, $messageId]);
            
            if ($existing) {
                return $this->handleMessagesUpdate();
            } else {
                return $this->processMessageData($this->data, 'UPSERT') ? 
                    ['success' => true, 'message' => 'Message inserted successfully'] :
                    ['success' => false, 'message' => 'Failed to insert message'];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid message data'];
    }
    
    private function handleMessagesUpdate()
    {
        $messageKey = $this->data['key'] ?? [];
        $messageId = $messageKey['id'] ?? null;
        $status = $this->data['status'] ?? null;
        
        if ($messageId) {
            $sql = "UPDATE messages SET status = ?, updated_at = NOW() WHERE instance_name = ? AND message_id = ?";
            $this->db->q($sql, [$status, $this->instance, $messageId]);
            
            return [
                'success' => true,
                'message' => 'Message status updated',
                'data' => ['message_id' => $messageId, 'status' => $status]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid message update data'];
    }
    
    private function handleMessagesDelete()
    {
        $messageKey = $this->data['key'] ?? [];
        $messageId = $messageKey['id'] ?? null;
        
        if ($messageId) {
            $sql = "DELETE FROM messages WHERE instance_name = ? AND message_id = ?";
            $this->db->q($sql, [$this->instance, $messageId]);
            
            return [
                'success' => true,
                'message' => 'Message deleted successfully',
                'data' => ['message_id' => $messageId]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid message delete data'];
    }
    
    private function handleSendMessage()
    {
        // Procesar mensaje enviado
        return $this->processMessageData($this->data, 'SENT') ? 
            ['success' => true, 'message' => 'Sent message processed'] :
            ['success' => false, 'message' => 'Failed to process sent message'];
    }
    
    // ==================== CONTACT EVENTS ====================
    
    private function handleContactsSet()
    {
        $processed = 0;
        $contacts = is_array($this->data) ? $this->data : [$this->data];
        
        foreach ($contacts as $contact) {
            if ($this->processContactData($contact, 'SET')) {
                $processed++;
            }
        }
        
        return [
            'success' => true,
            'message' => "Contacts set processed: {$processed} contacts",
            'data' => ['processed' => $processed]
        ];
    }
    
    private function handleContactsUpsert()
    {
        $remoteJid = $this->data['remoteJid'] ?? null;
        
        if ($remoteJid) {
            // Verificar si ya existe
            $sql = "SELECT id FROM contacts WHERE instance_name = ? AND remote_jid = ?";
            $existing = $this->db->fetch($sql, [$this->instance, $remoteJid]);
            
            if ($existing) {
                return $this->handleContactsUpdate();
            } else {
                return $this->processContactData($this->data, 'UPSERT') ? 
                    ['success' => true, 'message' => 'Contact inserted successfully'] :
                    ['success' => false, 'message' => 'Failed to insert contact'];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid contact data'];
    }
    
    private function handleContactsUpdate()
    {
        $remoteJid = $this->data['remoteJid'] ?? null;
        $pushname = $this->data['pushname'] ?? null;
        $profilePicUrl = $this->data['profilePicUrl'] ?? null;
        
        if ($remoteJid) {
            $sql = "UPDATE contacts SET pushname = ?, profile_pic_url = ?, updated_at = NOW() WHERE instance_name = ? AND remote_jid = ?";
            $this->db->q($sql, [$pushname, $profilePicUrl, $this->instance, $remoteJid]);
            
            return [
                'success' => true,
                'message' => 'Contact updated successfully',
                'data' => ['remote_jid' => $remoteJid, 'pushname' => $pushname]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid contact update data'];
    }
    
    // ==================== CHAT EVENTS ====================
    
    private function handleChatsSet()
    {
        $processed = 0;
        $chats = is_array($this->data) ? $this->data : [$this->data];
        
        foreach ($chats as $chat) {
            if ($this->processChatData($chat, 'SET')) {
                $processed++;
            }
        }
        
        return [
            'success' => true,
            'message' => "Chats set processed: {$processed} chats",
            'data' => ['processed' => $processed]
        ];
    }
    
    private function handleChatsUpsert()
    {
        $chatId = $this->data['id'] ?? null;
        
        if ($chatId) {
            // Verificar si ya existe
            $sql = "SELECT id FROM chats WHERE instance_name = ? AND chat_id = ?";
            $existing = $this->db->fetch($sql, [$this->instance, $chatId]);
            
            if ($existing) {
                return $this->handleChatsUpdate();
            } else {
                return $this->processChatData($this->data, 'UPSERT') ? 
                    ['success' => true, 'message' => 'Chat inserted successfully'] :
                    ['success' => false, 'message' => 'Failed to insert chat'];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid chat data'];
    }
    
    private function handleChatsUpdate()
    {
        $chatId = $this->data['id'] ?? $this->data['remoteJid'] ?? null;
        $lastMessageTimestamp = $this->data['lastMessageTimestamp'] ?? null;
        
        if ($chatId) {
            $sql = "UPDATE chats SET last_message_timestamp = ?, updated_at = NOW() WHERE instance_name = ? AND chat_id = ?";
            $this->db->q($sql, [$lastMessageTimestamp, $this->instance, $chatId]);
            
            return [
                'success' => true,
                'message' => 'Chat updated successfully',
                'data' => ['chat_id' => $chatId]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid chat update data'];
    }
    
    private function handleChatsDelete()
    {
        $chatId = $this->data['id'] ?? null;
        
        if ($chatId) {
            $sql = "DELETE FROM chats WHERE instance_name = ? AND chat_id = ?";
            $this->db->q($sql, [$this->instance, $chatId]);
            
            return [
                'success' => true,
                'message' => 'Chat deleted successfully',
                'data' => ['chat_id' => $chatId]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid chat delete data'];
    }
    
    // ==================== GROUP EVENTS ====================
    
    private function handleGroupsUpsert()
    {
        $groupId = $this->data['id'] ?? null;
        
        if ($groupId) {
            // Verificar si ya existe
            $sql = "SELECT id FROM groups WHERE instance_name = ? AND group_id = ?";
            $existing = $this->db->fetch($sql, [$this->instance, $groupId]);
            
            if ($existing) {
                return $this->handleGroupsUpdate();
            } else {
                return $this->processGroupData($this->data, 'UPSERT') ? 
                    ['success' => true, 'message' => 'Group inserted successfully'] :
                    ['success' => false, 'message' => 'Failed to insert group'];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid group data'];
    }
    
    private function handleGroupsUpdate()
    {
        $groupId = $this->data['id'] ?? null;
        $subject = $this->data['subject'] ?? null;
        $desc = $this->data['desc'] ?? null;
        
        if ($groupId) {
            $sql = "UPDATE groups SET subject = ?, description = ?, updated_at = NOW() WHERE instance_name = ? AND group_id = ?";
            $this->db->q($sql, [$subject, $desc, $this->instance, $groupId]);
            
            return [
                'success' => true,
                'message' => 'Group updated successfully',
                'data' => ['group_id' => $groupId, 'subject' => $subject]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid group update data'];
    }
    
    // ==================== TYPEBOT EVENTS ====================
    
    private function handleTypebotStart()
    {
        $typebotId = $this->data['typebot_id'] ?? null;
        $remoteJid = $this->data['remoteJid'] ?? null;
        $typebotName = $this->data['typebot_name'] ?? null;
        
        if ($typebotId && $remoteJid) {
            $sql = "INSERT INTO typebot_sessions (instance_name, typebot_id, remote_jid, typebot_name, status, created_at) VALUES (?, ?, ?, ?, 'started', NOW())";
            $this->db->q($sql, [$this->instance, $typebotId, $remoteJid, $typebotName]);
            
            return [
                'success' => true,
                'message' => 'Typebot session started',
                'data' => ['typebot_id' => $typebotId, 'remote_jid' => $remoteJid]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid typebot start data'];
    }
    
    private function handleTypebotChangeStatus()
    {
        $typebotId = $this->data['typebot_id'] ?? null;
        $status = $this->data['status'] ?? null;
        $remoteJid = $this->data['remoteJid'] ?? null;
        
        if ($typebotId && $status) {
            $sql = "UPDATE typebot_sessions SET status = ?, updated_at = NOW() WHERE instance_name = ? AND typebot_id = ? AND remote_jid = ?";
            $this->db->q($sql, [$status, $this->instance, $typebotId, $remoteJid]);
            
            return [
                'success' => true,
                'message' => 'Typebot status updated',
                'data' => ['typebot_id' => $typebotId, 'status' => $status]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid typebot status data'];
    }
    
    // ==================== ADDITIONAL EVENTS ====================
    
    private function handlePresenceUpdate()
    {
        $remoteJid = $this->data['id'] ?? null;
        $presences = $this->data['presences'] ?? [];
        
        if ($remoteJid && !empty($presences)) {
            foreach ($presences as $jid => $presence) {
                $lastKnownPresence = $presence['lastKnownPresence'] ?? null;
                $lastSeen = $presence['lastSeen'] ?? null;
                
                $sql = "INSERT INTO presence_updates (instance_name, remote_jid, presence, last_seen, created_at) VALUES (?, ?, ?, ?, NOW())";
                $this->db->q($sql, [$this->instance, $jid, $lastKnownPresence, $lastSeen]);
            }
            
            return [
                'success' => true,
                'message' => 'Presence updates processed',
                'data' => ['remote_jid' => $remoteJid, 'presences_count' => count($presences)]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid presence data'];
    }
    
    private function handleGroupParticipantsUpdate()
    {
        $groupId = $this->data['id'] ?? null;
        $action = $this->data['action'] ?? null;
        $participants = $this->data['participants'] ?? [];
        
        if ($groupId && $action && !empty($participants)) {
            foreach ($participants as $participant) {
                $userJid = $participant['userJid'] ?? null;
                $name = $participant['name'] ?? null;
                $isAdmin = $participant['isAdmin'] ?? false;
                
                $sql = "INSERT INTO group_participants (instance_name, group_id, user_jid, name, is_admin, action, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $this->db->q($sql, [$this->instance, $groupId, $userJid, $name, $isAdmin ? 1 : 0, $action]);
            }
            
            return [
                'success' => true,
                'message' => 'Group participants updated',
                'data' => ['group_id' => $groupId, 'action' => $action, 'participants_count' => count($participants)]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid group participants data'];
    }
    
    private function handleStatusMessage()
    {
        $statusId = $this->data['id'] ?? null;
        $remoteJid = $this->data['remoteJid'] ?? null;
        $message = $this->data['message'] ?? null;
        $type = $this->data['type'] ?? null;
        
        if ($statusId && $remoteJid) {
            $sql = "INSERT INTO status_messages (instance_name, status_id, remote_jid, message, type, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $this->db->q($sql, [$this->instance, $statusId, $remoteJid, $message, $type]);
            
            return [
                'success' => true,
                'message' => 'Status message processed',
                'data' => ['status_id' => $statusId, 'remote_jid' => $remoteJid]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid status message data'];
    }
    
    private function handleLabelsEdit()
    {
        $labelId = $this->data['labelId'] ?? null;
        $name = $this->data['name'] ?? null;
        $color = $this->data['color'] ?? null;
        $remoteJid = $this->data['remoteJid'] ?? null;
        
        if ($labelId && $name) {
            $sql = "INSERT INTO labels (instance_name, label_id, name, color, remote_jid, created_at) VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE name = ?, color = ?, updated_at = NOW()";
            $this->db->q($sql, [$this->instance, $labelId, $name, $color, $remoteJid, $name, $color]);
            
            return [
                'success' => true,
                'message' => 'Label processed',
                'data' => ['label_id' => $labelId, 'name' => $name]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid label data'];
    }
    
    private function handleLabelsAssociation()
    {
        $labelId = $this->data['labelId'] ?? null;
        $remoteJid = $this->data['remoteJid'] ?? null;
        $action = $this->data['action'] ?? null;
        
        if ($labelId && $remoteJid && $action) {
            $sql = "INSERT INTO label_associations (instance_name, label_id, remote_jid, action, created_at) VALUES (?, ?, ?, ?, NOW())";
            $this->db->q($sql, [$this->instance, $labelId, $remoteJid, $action]);
            
            return [
                'success' => true,
                'message' => 'Label association processed',
                'data' => ['label_id' => $labelId, 'remote_jid' => $remoteJid, 'action' => $action]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid label association data'];
    }
    
    private function handleCallWebhook()
    {
        $callId = $this->data['callId'] ?? null;
        $remoteJid = $this->data['remoteJid'] ?? null;
        $status = $this->data['status'] ?? null;
        $type = $this->data['type'] ?? null;
        $duration = $this->data['duration'] ?? 0;
        
        if ($callId && $remoteJid) {
            $sql = "INSERT INTO call_events (instance_name, call_id, remote_jid, status, type, duration, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $this->db->q($sql, [$this->instance, $callId, $remoteJid, $status, $type, $duration]);
            
            return [
                'success' => true,
                'message' => 'Call event processed',
                'data' => ['call_id' => $callId, 'status' => $status, 'type' => $type]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid call data'];
    }
    
    private function handleUnknownEvent()
    {
        // Log de evento desconocido
        $sql = "INSERT INTO unknown_events (instance_name, event_type, data, created_at) VALUES (?, ?, ?, NOW())";
        $this->db->q($sql, [$this->instance, $this->event, json_encode($this->data)]);
        
        return [
            'success' => true,
            'message' => 'Unknown event logged',
            'data' => ['event_type' => $this->event]
        ];
    }
    
    // ==================== HELPER METHODS ====================
    
    private function processMessageData($message, $operation)
    {
        $key = $message['key'] ?? [];
        $remoteJid = $key['remoteJid'] ?? null;
        $messageId = $key['id'] ?? null;
        $fromMe = $key['fromMe'] ?? false;
        $messageContent = $message['message'] ?? [];
        $messageTimestamp = $message['messageTimestamp'] ?? null;
        $pushname = $message['pushname'] ?? null;
        $status = $message['status'] ?? 'received';
        
        if ($remoteJid && $messageId) {
            $sql = "INSERT INTO messages (instance_name, message_id, remote_jid, from_me, message_content, message_timestamp, pushname, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $this->db->q($sql, [
                $this->instance,
                $messageId,
                $remoteJid,
                $fromMe ? 1 : 0,
                json_encode($messageContent),
                $messageTimestamp,
                $pushname,
                $status
            ]);
            return true;
        }
        
        return false;
    }
    
    private function processContactData($contact, $operation)
    {
        $remoteJid = $contact['remoteJid'] ?? null;
        $pushname = $contact['pushname'] ?? null;
        $profilePicUrl = $contact['profilePicUrl'] ?? null;
        
        if ($remoteJid) {
            $sql = "INSERT INTO contacts (instance_name, remote_jid, pushname, profile_pic_url, created_at) VALUES (?, ?, ?, ?, NOW())";
            $this->db->q($sql, [$this->instance, $remoteJid, $pushname, $profilePicUrl]);
            return true;
        }
        
        return false;
    }
    
    private function processChatData($chat, $operation)
    {
        $chatId = $chat['id'] ?? null;
        $name = $chat['name'] ?? null;
        $unreadMessages = $chat['unreadMessages'] ?? 0;
        $lastMessageTimestamp = $chat['lastMessageTimestamp'] ?? null;
        
        if ($chatId) {
            $sql = "INSERT INTO chats (instance_name, chat_id, name, unread_messages, last_message_timestamp, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $this->db->q($sql, [$this->instance, $chatId, $name, $unreadMessages, $lastMessageTimestamp]);
            return true;
        }
        
        return false;
    }
    
    private function processGroupData($group, $operation)
    {
        $groupId = $group['id'] ?? null;
        $subject = $group['subject'] ?? null;
        $desc = $group['desc'] ?? null;
        $owner = $group['owner'] ?? null;
        
        if ($groupId) {
            $sql = "INSERT INTO groups (instance_name, group_id, subject, description, owner, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $this->db->q($sql, [$this->instance, $groupId, $subject, $desc, $owner]);
            return true;
        }
        
        return false;
    }
    
    private function logWebhookEvent()
    {
        $sql = "INSERT INTO webhook_logs (instance_name, event_type, data, created_at) VALUES (?, ?, ?, NOW())";
        $this->db->q($sql, [$this->instance, $this->event, json_encode([
            'event' => $this->event,
            'data' => $this->data,
            'timestamp' => date('Y-m-d H:i:s')
        ])]);
    }
    
    private function response($success, $message, $data = null, $debug = [])
    {
        $response = [
            'success' => $success,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'instance' => $this->instance,
            'event' => $this->event
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if (!empty($debug)) {
            $response['debug'] = $debug;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        return $response;
    }
}

// Endpoint para procesar webhooks
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $controller = new WebhookControllerIntegrated();
    $controller->processWebhook();
}
?>
