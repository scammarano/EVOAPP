<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Instance;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Contact;
use App\Models\WebhookEvent;
use App\Core\DB;

class WebhookControllerComplete
{
    // Mapeo completo de eventos Evolution API
    private const EVENT_MAPPING = [
        'application-startup' => 'APPLICATION_STARTUP',
        'qrcode-updated' => 'QRCODE_UPDATED',
        'connection-update' => 'CONNECTION_UPDATE',
        'messages-set' => 'MESSAGES_SET',
        'messages-upsert' => 'MESSAGES_UPSERT',
        'messages-update' => 'MESSAGES_UPDATE',
        'messages-delete' => 'MESSAGES_DELETE',
        'send-message' => 'SEND_MESSAGE',
        'contacts-set' => 'CONTACTS_SET',
        'contacts-upsert' => 'CONTACTS_UPSERT',
        'contacts-update' => 'CONTACTS_UPDATE',
        'presence-update' => 'PRESENCE_UPDATE',
        'chats-set' => 'CHATS_SET',
        'chats-update' => 'CHATS_UPDATE',
        'chats-upsert' => 'CHATS_UPSERT',
        'chats-delete' => 'CHATS_DELETE',
        'groups-upsert' => 'GROUPS_UPSERT',
        'groups-update' => 'GROUPS_UPDATE',
        'group-participants-update' => 'GROUP_PARTICIPANTS_UPDATE',
        'new-jwt' => 'NEW_TOKEN'
    ];

    /**
     * Webhook principal - maneja todos los eventos Evolution API
     * Endpoint: /webhook/evolution
     */
    public function evolution()
    {
        header('Content-Type: application/json');
        
        $startTime = microtime(true);
        $instanceSlug = $_GET['instance'] ?? '';
        $payload = json_decode(file_get_contents('php://input'), true);
        
        if (!$instanceSlug || !$payload) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request - instance and payload required']);
            return;
        }
        
        // Validar instancia
        $instance = $this->validateInstance($instanceSlug);
        if (!$instance) {
            http_response_code(404);
            echo json_encode(['error' => 'Instance not found']);
            return;
        }
        
        // Validar webhook token si está configurado
        if (!$this->validateWebhookToken($instance)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid webhook token']);
            return;
        }
        
        // Procesar evento
        $eventId = $this->processWebhookEvent($instance, $payload);
        
        $processingTime = round((microtime(true) - $startTime) * 1000, 2);
        
        echo json_encode([
            'success' => true,
            'event_id' => $eventId,
            'processing_time_ms' => $processingTime,
            'instance' => $instanceSlug,
            'event_type' => $payload['event'] ?? 'unknown'
        ]);
    }
    
    /**
     * Webhook por eventos específicos (webhook_by_events = true)
     * Endpoints: /webhook/qrcode-updated, /webhook/messages-upsert, etc.
     */
    public function handleEventByType($eventType)
    {
        header('Content-Type: application/json');
        
        $instanceSlug = $_GET['instance'] ?? '';
        $payload = json_decode(file_get_contents('php://input'), true);
        
        if (!$instanceSlug || !$payload) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request']);
            return;
        }
        
        $instance = $this->validateInstance($instanceSlug);
        if (!$instance) {
            http_response_code(404);
            echo json_encode(['error' => 'Instance not found']);
            return;
        }
        
        // Validar que el evento coincida con el endpoint
        $expectedEvent = str_replace('-', '.', $eventType);
        if ($payload['event'] !== $expectedEvent) {
            http_response_code(400);
            echo json_encode(['error' => 'Event mismatch']);
            return;
        }
        
        $eventId = $this->processWebhookEvent($instance, $payload);
        
        echo json_encode([
            'success' => true,
            'event_id' => $eventId,
            'event_type' => $payload['event'],
            'handled_by' => 'event_specific_webhook'
        ]);
    }
    
    /**
     * Endpoint para encontrar webhook activo de instancia
     * GET: /webhook/find/[instance]
     */
    public function findWebhook($instanceSlug)
    {
        header('Content-Type: application/json');
        
        $instance = Instance::findBySlug($instanceSlug);
        if (!$instance) {
            http_response_code(404);
            echo json_encode(['error' => 'Instance not found']);
            return;
        }
        
        echo json_encode([
            'enabled' => (bool)$instance['webhook_enabled'],
            'url' => $instance['forward_webhook_url'] ?? null,
            'webhookByEvents' => false, // TODO: Obtener de configuración
            'events' => $this->getEnabledEvents($instance)
        ]);
    }
    
    /**
     * Procesamiento principal de eventos webhook
     */
    private function processWebhookEvent($instance, $payload)
    {
        $eventType = $payload['event'] ?? 'unknown';
        $timestamp = $payload['date_time'] ?? date('c');
        
        // Mapear evento a tipo estándar
        $mappedEventType = self::EVENT_MAPPING[$eventType] ?? $eventType;
        
        // Extraer información del payload
        $eventData = $this->extractEventData($instance, $payload);
        
        // Guardar evento en base de datos
        $eventId = WebhookEvent::create([
            'instance_id' => $instance['id'],
            'event_type' => $mappedEventType,
            'remote_jid' => $eventData['remote_jid'],
            'message_id' => $eventData['message_id'],
            'participant_jid' => $eventData['participant_jid'],
            'payload_json' => json_encode($payload),
            'received_at' => date('Y-m-d H:i:s', strtotime($timestamp))
        ]);
        
        // Actualizar timestamp de webhook en instancia
        $this->updateInstanceWebhookTimestamp($instance['id']);
        
        // Procesar evento específico
        $this->handleSpecificEvent($mappedEventType, $instance, $payload, $eventId);
        
        // Forward webhook si está configurado
        if ($instance['forward_webhook_enabled'] && $instance['forward_webhook_url']) {
            $this->forwardWebhook($instance, $payload);
        }
        
        return $eventId;
    }
    
    /**
     * Manejar eventos específicos con lógica dedicada
     */
    private function handleSpecificEvent($eventType, $instance, $payload, $eventId)
    {
        try {
            switch ($eventType) {
                case 'QRCODE_UPDATED':
                    $this->handleQrCodeUpdated($instance, $payload);
                    break;
                    
                case 'CONNECTION_UPDATE':
                    $this->handleConnectionUpdate($instance, $payload);
                    break;
                    
                case 'MESSAGES_SET':
                    $this->handleMessagesSet($instance, $payload);
                    break;
                    
                case 'MESSAGES_UPSERT':
                    $this->handleMessagesUpsert($instance, $payload);
                    break;
                    
                case 'MESSAGES_UPDATE':
                    $this->handleMessagesUpdate($instance, $payload);
                    break;
                    
                case 'MESSAGES_DELETE':
                    $this->handleMessagesDelete($instance, $payload);
                    break;
                    
                case 'SEND_MESSAGE':
                    $this->handleSendMessage($instance, $payload);
                    break;
                    
                case 'CONTACTS_SET':
                    $this->handleContactsSet($instance, $payload);
                    break;
                    
                case 'CONTACTS_UPSERT':
                    $this->handleContactsUpsert($instance, $payload);
                    break;
                    
                case 'CONTACTS_UPDATE':
                    $this->handleContactsUpdate($instance, $payload);
                    break;
                    
                case 'PRESENCE_UPDATE':
                    $this->handlePresenceUpdate($instance, $payload);
                    break;
                    
                case 'CHATS_SET':
                    $this->handleChatsSet($instance, $payload);
                    break;
                    
                case 'CHATS_UPSERT':
                    $this->handleChatsUpsert($instance, $payload);
                    break;
                    
                case 'CHATS_UPDATE':
                    $this->handleChatsUpdate($instance, $payload);
                    break;
                    
                case 'CHATS_DELETE':
                    $this->handleChatsDelete($instance, $payload);
                    break;
                    
                case 'GROUPS_UPSERT':
                    $this->handleGroupsUpsert($instance, $payload);
                    break;
                    
                case 'GROUPS_UPDATE':
                    $this->handleGroupsUpdate($instance, $payload);
                    break;
                    
                case 'GROUP_PARTICIPANTS_UPDATE':
                    $this->handleGroupParticipantsUpdate($instance, $payload);
                    break;
                    
                case 'NEW_TOKEN':
                    $this->handleNewToken($instance, $payload);
                    break;
                    
                default:
                    error_log("Unhandled webhook event: $eventType");
                    break;
            }
            
            // Marcar evento como procesado
            WebhookEvent::markProcessed($eventId);
            
        } catch (\Exception $e) {
            WebhookEvent::markProcessed($eventId, $e->getMessage());
            error_log("Webhook processing error for $eventType: " . $e->getMessage());
        }
    }
    
    /**
     * Manejar actualización de QR Code
     */
    private function handleQrCodeUpdated($instance, $payload)
    {
        $qrCode = $payload['data']['qrcode']['base64'] ?? null;
        if ($qrCode) {
            // Guardar QR Code en instancia o en tabla específica
            DB::q("UPDATE evo_instances SET qr_code = ?, qr_updated_at = NOW() WHERE id = ?", 
                  [$qrCode, $instance['id']]);
        }
    }
    
    /**
     * Manejar actualización de conexión
     */
    private function handleConnectionUpdate($instance, $payload)
    {
        $state = $payload['data']['state'] ?? 'unknown';
        $statusMap = [
            'open' => 'connected',
            'connecting' => 'connecting',
            'close' => 'disconnected',
            'qr' => 'qr_required'
        ];
        
        $connectionStatus = $statusMap[$state] ?? $state;
        
        // Actualizar estado de conexión en instancia
        DB::q("UPDATE evo_instances SET connection_status = ?, connection_updated_at = NOW() WHERE id = ?", 
              [$connectionStatus, $instance['id']]);
    }
    
    /**
     * Manejar carga inicial de mensajes
     */
    private function handleMessagesSet($instance, $payload)
    {
        $messages = $payload['data'] ?? [];
        
        foreach ($messages as $msgData) {
            $this->processMessageData($instance, $msgData, 'initial_load');
        }
    }
    
    /**
     * Manejar nuevos mensajes
     */
    private function handleMessagesUpsert($instance, $payload)
    {
        $msgData = $payload['data'] ?? null;
        if ($msgData) {
            $this->processMessageData($instance, $msgData, 'new_message');
        }
    }
    
    /**
     * Manejar actualizaciones de mensajes
     */
    private function handleMessagesUpdate($instance, $payload)
    {
        $msgData = $payload['data'] ?? null;
        if ($msgData && isset($msgData['key']['id'])) {
            $messageId = $msgData['key']['id'];
            $status = $msgData['status'] ?? null;
            
            if ($status) {
                Message::updateStatus($messageId, $status);
            }
        }
    }
    
    /**
     * Manejar eliminación de mensajes
     */
    private function handleMessagesDelete($instance, $payload)
    {
        $msgData = $payload['data'] ?? null;
        if ($msgData && isset($msgData['key']['id'])) {
            $messageId = $msgData['key']['id'];
            $message = Message::findByMessageId($instance['id'], $messageId);
            
            if ($message) {
                Message::delete($message['id']);
            }
        }
    }
    
    /**
     * Manejar envío de mensajes
     */
    private function handleSendMessage($instance, $payload)
    {
        $msgData = $payload['data'] ?? null;
        if ($msgData) {
            $this->processMessageData($instance, $msgData, 'sent_message');
        }
    }
    
    /**
     * Manejar carga inicial de contactos
     */
    private function handleContactsSet($instance, $payload)
    {
        $contacts = $payload['data'] ?? [];
        
        foreach ($contacts as $contactData) {
            $this->processContactData($instance, $contactData, 'initial_load');
        }
    }
    
    /**
     * Manejar actualización de contactos
     */
    private function handleContactsUpsert($instance, $payload)
    {
        $contacts = $payload['data'] ?? [];
        
        if (isset($payload['data']['remoteJid'])) {
            // Single contact
            $this->processContactData($instance, $payload['data'], 'update');
        } else {
            // Multiple contacts
            foreach ($contacts as $contactData) {
                $this->processContactData($instance, $contactData, 'update');
            }
        }
    }
    
    /**
     * Manejar actualización individual de contacto
     */
    private function handleContactsUpdate($instance, $payload)
    {
        $contactData = $payload['data'] ?? null;
        if ($contactData) {
            $this->processContactData($instance, $contactData, 'update');
        }
    }
    
    /**
     * Manejar actualización de presencia
     */
    private function handlePresenceUpdate($instance, $payload)
    {
        $presenceData = $payload['data'] ?? null;
        if ($presenceData && isset($presenceData['id'])) {
            $jid = $presenceData['id'];
            $presences = $presenceData['presences'] ?? [];
            
            foreach ($presences as $contactJid => $presence) {
                $lastKnownPresence = $presence['lastKnownPresence'] ?? 'unavailable';
                
                // Guardar estado de presencia en tabla de contactos o chat
                DB::q("UPDATE chats SET presence = ?, presence_updated_at = NOW() 
                       WHERE instance_id = ? AND remote_jid = ?", 
                      [$lastKnownPresence, $instance['id'], $contactJid]);
            }
        }
    }
    
    /**
     * Manejar carga inicial de chats
     */
    private function handleChatsSet($instance, $payload)
    {
        $chats = $payload['data'] ?? [];
        
        foreach ($chats as $chatData) {
            $this->processChatData($instance, $chatData, 'initial_load');
        }
    }
    
    /**
     * Manejar nuevos chats
     */
    private function handleChatsUpsert($instance, $payload)
    {
        $chatData = $payload['data'] ?? null;
        if ($chatData) {
            $this->processChatData($instance, $chatData, 'new_chat');
        }
    }
    
    /**
     * Manejar actualización de chats
     */
    private function handleChatsUpdate($instance, $payload)
    {
        $chatData = $payload['data'] ?? null;
        if ($chatData) {
            $this->processChatData($instance, $chatData, 'update');
        }
    }
    
    /**
     * Manejar eliminación de chats
     */
    private function handleChatsDelete($instance, $payload)
    {
        $chatData = $payload['data'] ?? null;
        if ($chatData && isset($chatData['id'])) {
            $chatId = $chatData['id'];
            $chat = DB::fetch("SELECT id FROM chats WHERE instance_id = ? AND remote_jid = ?", 
                            [$instance['id'], $chatId]);
            
            if ($chat) {
                DB::q("DELETE FROM chats WHERE id = ?", [$chat['id']]);
            }
        }
    }
    
    /**
     * Manejar creación de grupos
     */
    private function handleGroupsUpsert($instance, $payload)
    {
        $groupData = $payload['data'] ?? null;
        if ($groupData) {
            $this->processGroupData($instance, $groupData, 'create');
        }
    }
    
    /**
     * Manejar actualización de grupos
     */
    private function handleGroupsUpdate($instance, $payload)
    {
        $groupData = $payload['data'] ?? null;
        if ($groupData) {
            $this->processGroupData($instance, $groupData, 'update');
        }
    }
    
    /**
     * Manejar actualización de participantes de grupo
     */
    private function handleGroupParticipantsUpdate($instance, $payload)
    {
        $participantData = $payload['data'] ?? null;
        if ($participantData) {
            $this->processGroupParticipantData($instance, $participantData);
        }
    }
    
    /**
     * Manejar nuevo token JWT
     */
    private function handleNewToken($instance, $payload)
    {
        $token = $payload['data']['token'] ?? null;
        if ($token) {
            // Actualizar token en instancia
            DB::q("UPDATE evo_instances SET jwt_token = ?, token_updated_at = NOW() WHERE id = ?", 
                  [$token, $instance['id']]);
        }
    }
    
    /**
     * Procesar datos de mensaje
     */
    private function processMessageData($instance, $msgData, $source)
    {
        $key = $msgData['key'] ?? [];
        $message = $msgData['message'] ?? [];
        
        $remoteJid = $key['remoteJid'] ?? null;
        $messageId = $key['id'] ?? null;
        $fromMe = $key['fromMe'] ?? false;
        $participant = $msgData['participant'] ?? null;
        
        if (!$remoteJid || !$messageId) return;
        
        // Determinar si es grupo
        $isGroup = strpos($remoteJid, '@g.us') !== false;
        
        // Crear o actualizar chat
        $chatData = [
            'instance_id' => $instance['id'],
            'remote_jid' => $remoteJid,
            'is_group' => $isGroup,
            'title' => $msgData['pushname'] ?? $msgData['notifyName'] ?? ($isGroup ? 'Group' : $remoteJid),
            'last_message_at' => date('Y-m-d H:i:s', intval($msgData['messageTimestamp'] / 1000))
        ];
        
        $chatData['last_snippet'] = $this->getMessageSnippet($message);
        
        $chatId = Chat::createOrUpdate($chatData);
        
        // Crear mensaje
        $messageData = [
            'instance_id' => $instance['id'],
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'from_me' => $fromMe,
            'ts' => date('Y-m-d H:i:s', intval($msgData['messageTimestamp'] / 1000)),
            'msg_type' => $this->getMessageType($message),
            'body_text' => $this->getMessageBody($message),
            'participant_jid' => $participant,
            'media_url' => $this->getMediaUrl($message),
            'status' => 'delivered',
            'raw_json' => json_encode($message)
        ];
        
        Message::create($messageData);
        
        // Actualizar contador de no leídos para mensajes entrantes
        if (!$fromMe) {
            Chat::incrementUnread($chatId);
        }
    }
    
    /**
     * Procesar datos de contacto
     */
    private function processContactData($instance, $contactData, $source)
    {
        $remoteJid = $contactData['remoteJid'] ?? null;
        if (!$remoteJid) return;
        
        // Extraer teléfono del JID
        $phone = str_replace(['@s.whatsapp.net', '@g.us', '@lid'], '', $remoteJid);
        
        $contactInfo = [
            'instance_id' => $instance['id'],
            'phone_e164' => $phone,
            'remote_jid' => $remoteJid,
            'name' => $contactData['name'] ?? $contactData['pushname'] ?? null,
            'push_name' => $contactData['pushname'] ?? null,
            'company' => $contactData['company'] ?? null,
            'email' => $contactData['email'] ?? null
        ];
        
        Contact::createOrUpdate($contactInfo);
    }
    
    /**
     * Procesar datos de chat
     */
    private function processChatData($instance, $chatData, $source)
    {
        $remoteJid = $chatData['id'] ?? $chatData['remoteJid'] ?? null;
        if (!$remoteJid) return;
        
        $isGroup = strpos($remoteJid, '@g.us') !== false;
        
        $chatInfo = [
            'instance_id' => $instance['id'],
            'remote_jid' => $remoteJid,
            'is_group' => $isGroup,
            'title' => $chatData['name'] ?? $chatData['title'] ?? ($isGroup ? 'Group' : $remoteJid),
            'last_message_at' => $chatData['lastMessageTimestamp'] ? 
                date('Y-m-d H:i:s', intval($chatData['lastMessageTimestamp'] / 1000)) : null,
            'unread_count' => $chatData['unreadMessages'] ?? 0
        ];
        
        Chat::createOrUpdate($chatInfo);
    }
    
    /**
     * Procesar datos de grupo
     */
    private function processGroupData($instance, $groupData, $source)
    {
        $groupId = $groupData['id'] ?? null;
        if (!$groupId) return;
        
        $chatInfo = [
            'instance_id' => $instance['id'],
            'remote_jid' => $groupId,
            'is_group' => true,
            'title' => $groupData['subject'] ?? $groupData['name'] ?? 'Group',
            'last_message_at' => $groupData['lastMessageTimestamp'] ? 
                date('Y-m-d H:i:s', intval($groupData['lastMessageTimestamp'] / 1000)) : null
        ];
        
        Chat::createOrUpdate($chatInfo);
    }
    
    /**
     * Procesar datos de participante de grupo
     */
    private function processGroupParticipantData($instance, $participantData)
    {
        $groupId = $participantData['id'] ?? null;
        $action = $participantData['action'] ?? null;
        $participants = $participantData['participants'] ?? [];
        
        if (!$groupId || !$action) return;
        
        foreach ($participants as $participant) {
            $participantJid = $participant['userJid'] ?? $participant['jid'] ?? null;
            $participantName = $participant['name'] ?? null;
            
            if (!$participantJid) continue;
            
            switch ($action) {
                case 'add':
                    $this->addGroupParticipant($instance['id'], $groupId, $participantJid, $participantName);
                    break;
                case 'remove':
                    $this->removeGroupParticipant($instance['id'], $groupId, $participantJid);
                    break;
                case 'promote':
                    $this->updateGroupParticipantRole($instance['id'], $groupId, $participantJid, true);
                    break;
                case 'demote':
                    $this->updateGroupParticipantRole($instance['id'], $groupId, $participantJid, false);
                    break;
            }
        }
    }
    
    /**
     * Agregar participante a grupo
     */
    private function addGroupParticipant($instanceId, $groupId, $participantJid, $participantName)
    {
        DB::q("INSERT IGNORE INTO group_participants 
               (group_id, participant_jid, participant_name, is_admin, instance_id, joined_at) 
               VALUES (?, ?, ?, 0, ?, NOW())", 
              [$groupId, $participantJid, $participantName, $instanceId]);
    }
    
    /**
     * Eliminar participante de grupo
     */
    private function removeGroupParticipant($instanceId, $groupId, $participantJid)
    {
        DB::q("DELETE FROM group_participants 
               WHERE group_id = ? AND participant_jid = ? AND instance_id = ?", 
              [$groupId, $participantJid, $instanceId]);
    }
    
    /**
     * Actualizar rol de participante de grupo
     */
    private function updateGroupParticipantRole($instanceId, $groupId, $participantJid, $isAdmin)
    {
        DB::q("UPDATE group_participants 
               SET is_admin = ? 
               WHERE group_id = ? AND participant_jid = ? AND instance_id = ?", 
              [$isAdmin ? 1 : 0, $groupId, $participantJid, $instanceId]);
    }
    
    // Métodos auxiliares (existentes del controlador original)
    private function getMessageType($message)
    {
        if (isset($message['conversation'])) return 'text';
        if (isset($message['extendedTextMessage'])) return 'text';
        if (isset($message['imageMessage'])) return 'image';
        if (isset($message['videoMessage'])) return 'video';
        if (isset($message['audioMessage'])) return 'audio';
        if (isset($message['documentMessage'])) return 'document';
        if (isset($message['stickerMessage'])) return 'sticker';
        
        return 'unknown';
    }
    
    private function getMessageBody($message)
    {
        if (isset($message['conversation'])) return $message['conversation'];
        if (isset($message['extendedTextMessage']['text'])) return $message['extendedTextMessage']['text'];
        if (isset($message['imageMessage']['caption'])) return $message['imageMessage']['caption'];
        if (isset($message['videoMessage']['caption'])) return $message['videoMessage']['caption'];
        if (isset($message['documentMessage']['caption'])) return $message['documentMessage']['caption'];
        
        return null;
    }
    
    private function getMessageSnippet($message)
    {
        $body = $this->getMessageBody($message);
        if ($body) {
            return substr($body, 0, 50);
        }
        
        $type = $this->getMessageType($message);
        switch ($type) {
            case 'image': return '📷 Image';
            case 'video': return '🎥 Video';
            case 'audio': return '🎵 Audio';
            case 'document': return '📄 Document';
            case 'sticker': return '😀 Sticker';
            default: return '📎 Media';
        }
    }
    
    private function getMediaUrl($message)
    {
        if (isset($message['imageMessage']['url'])) return $message['imageMessage']['url'];
        if (isset($message['videoMessage']['url'])) return $message['videoMessage']['url'];
        if (isset($message['audioMessage']['url'])) return $message['audioMessage']['url'];
        if (isset($message['documentMessage']['url'])) return $message['documentMessage']['url'];
        
        return null;
    }
    
    /**
     * Validar instancia
     */
    private function validateInstance($instanceSlug)
    {
        return Instance::findBySlug($instanceSlug);
    }
    
    /**
     * Validar token de webhook
     */
    private function validateWebhookToken($instance)
    {
        if (!$instance['webhook_token']) {
            return true; // No token configured
        }
        
        $providedToken = $_SERVER['HTTP_X_WEBHOOK_TOKEN'] ?? '';
        return $providedToken === $instance['webhook_token'];
    }
    
    /**
     * Extraer datos del evento
     */
    private function extractEventData($instance, $payload)
    {
        $data = $payload['data'] ?? [];
        
        return [
            'remote_jid' => $data['key']['remoteJid'] ?? $data['remoteJid'] ?? $data['id'] ?? null,
            'message_id' => $data['key']['id'] ?? $data['messageId'] ?? null,
            'participant_jid' => $data['participant'] ?? $data['participantJid'] ?? null
        ];
    }
    
    /**
     * Actualizar timestamp de webhook
     */
    private function updateInstanceWebhookTimestamp($instanceId)
    {
        try {
            DB::q("UPDATE evo_instances SET webhook_timestamp = NOW() WHERE id = ?", [$instanceId]);
        } catch (\Exception $e) {
            error_log("Failed to update webhook_timestamp: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener eventos habilitados para instancia
     */
    private function getEnabledEvents($instance)
    {
        // TODO: Implementar configuración de eventos por instancia
        return array_values(self::EVENT_MAPPING);
    }
    
    /**
     * Forward webhook a URL externa
     */
    private function forwardWebhook($instance, $payload)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $instance['forward_webhook_url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Forwarded-From: EVOAPP-COMPLETE',
            'X-Instance-Slug: ' . $instance['slug']
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        curl_exec($ch);
        curl_close($ch);
    }
}
