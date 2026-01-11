<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Instance;
use App\Models\Chat;
use App\Models\Message;
use App\Models\WebhookEvent;
use App\Core\DB;

class WebhookController
{
    // Mapeo COMPLETO de 26 eventos Evolution API
    private const EVENT_MAPPING = [
        // System Events
        'application-startup' => 'APPLICATION_STARTUP',
        'qrcode-updated' => 'QRCODE_UPDATED',
        'connection-update' => 'CONNECTION_UPDATE',
        'new-jwt' => 'NEW_TOKEN',
        
        // Message Events
        'messages-set' => 'MESSAGES_SET',
        'messages-upsert' => 'MESSAGES_UPSERT',
        'messages-update' => 'MESSAGES_UPDATE',
        'messages-delete' => 'MESSAGES_DELETE',
        'send-message' => 'SEND_MESSAGE',
        
        // Contact Events
        'contacts-set' => 'CONTACTS_SET',
        'contacts-upsert' => 'CONTACTS_UPSERT',
        'contacts-update' => 'CONTACTS_UPDATE',
        
        // Chat Events
        'chats-set' => 'CHATS_SET',
        'chats-upsert' => 'CHATS_UPSERT',
        'chats-update' => 'CHATS_UPDATE',
        'chats-delete' => 'CHATS_DELETE',
        
        // Group Events
        'groups-upsert' => 'GROUPS_UPSERT',
        'groups-update' => 'GROUPS_UPDATE',
        'group-participants-update' => 'GROUP_PARTICIPANTS_UPDATE',
        
        // Typebot Events
        'typebot-start' => 'TYPEBOT_START',
        'typebot-change-status' => 'TYPEBOT_CHANGE_STATUS',
        
        // Additional Events
        'presence-update' => 'PRESENCE_UPDATE',
        'status-message' => 'STATUS_MESSAGE',
        'labels-edit' => 'LABELS_EDIT',
        'labels-association' => 'LABELS_ASSOCIATION',
        'call-webhook' => 'CALL_WEBHOOK',
        
        // Legacy mappings (mantener compatibilidad)
        'contacts.update' => 'CONTACTS_UPDATE',
        'chats.upsert' => 'CHATS_UPSERT',
        'chats.update' => 'CHATS_UPDATE',
        'presence.update' => 'PRESENCE_UPDATE',
        'messages.upsert' => 'MESSAGES_UPSERT',
        'messages.update' => 'MESSAGES_UPDATE',
        'messages.delete' => 'MESSAGES_DELETE',
        'statusMessage' => 'STATUS_MESSAGE',
        
        // Eventos con formato diferente (evolution-api)
        'send.message' => 'SEND_MESSAGE',
        'send-message' => 'SEND_MESSAGE',
        'message.update' => 'MESSAGES_UPDATE',
        'message-update' => 'MESSAGES_UPDATE'
    ];
    
    public function events()
    {
        header('Content-Type: application/json');

        $instanceSlug = $_GET['instance'] ?? '';
        $since = (int)($_GET['since'] ?? 0);

        if (!$instanceSlug) {
            echo json_encode(['success' => false, 'error' => 'Instance required']);
            return;
        }

        $instance = Instance::findBySlug($instanceSlug);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $events = WebhookEvent::getEventsSince($instance['id'], $since, 50);

        $normalized = [];
        foreach ($events as $evt) {
            $payload = null;
            if (!empty($evt['data'])) {
                $payload = json_decode($evt['data'], true);
            }

            $normalized[] = [
                'id' => (int)$evt['id'],
                'event_type' => $evt['event_type'],
                'remote_jid' => $evt['remote_jid'],
                'message_id' => $evt['message_id'],
                'participant_jid' => $evt['participant_jid'],
                'payload' => $payload,
                'received_at' => $evt['received_at'] ?? null,
            ];
        }

        echo json_encode([
            'success' => true,
            'events' => $normalized,
        ]);
    }

    public function evolution()
    {
        header('Content-Type: application/json');
        
        $instanceSlug = $_GET['instance'] ?? '';
        $payload = json_decode(file_get_contents('php://input'), true);
        
        if (!$instanceSlug || !$payload) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request']);
            return;
        }
        
        // Find instance
        $instance = Instance::findBySlug($instanceSlug);
        if (!$instance) {
            http_response_code(404);
            echo json_encode(['error' => 'Instance not found']);
            return;
        }
        
        // Validate webhook token if configured
        if ($instance['webhook_token']) {
            $providedToken = $_SERVER['HTTP_X_WEBHOOK_TOKEN'] ?? '';
            if ($providedToken !== $instance['webhook_token']) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid webhook token']);
                return;
            }
        }
        
        // Store webhook event
        $eventType = $payload['event'] ?? 'unknown';
        
        // Mapear evento usando el mapeo COMPLETO de 26 eventos
        $mappedEventType = self::EVENT_MAPPING[strtolower($eventType)] ?? $eventType;
        
        $eventData = [
            'instance_id' => $instance['id'],
            'event_type' => $mappedEventType,
            'remote_jid' => $this->extractRemoteJid($payload),
            'message_id' => $this->extractMessageId($payload),
            'participant_jid' => $payload['data']['participant'] ?? null,
            'payload' => $payload
        ];
        
        $eventId = WebhookEvent::create($eventData);
        
        // Update instance webhook timestamp
        $timestampUpdated = Instance::updateWebhookTimestamp($instance['id']);
        
        // Si no se pudo actualizar el timestamp, registrar en log
        if (!$timestampUpdated) {
            error_log("Failed to update webhook_timestamp for instance {$instance['id']} - column may not exist");
        }
        
        // Forward webhook if configured
        if ($instance['forward_webhook_enabled'] && $instance['forward_webhook_url']) {
            $this->forwardWebhook($instance, $payload);
        }
        
        // Process event immediately for real-time updates
        $this->processWebhookEvent($eventId);
        
        echo json_encode([
            'success' => true, 
            'event_id' => $eventId,
            'event_type' => $mappedEventType
        ]);
    }
    
    private function processWebhookEvent($eventId)
    {
        $event = DB::fetch("SELECT *, payload_json as data FROM webhook_events WHERE id = ?", [$eventId]);
        if (!$event || $event['status'] !== 'pending') {
            return;
        }
        
        try {
            $payload = json_decode($event['data'], true);
            $eventType = $event['event_type'];
            
            switch ($eventType) {
                // System Events
                case 'QRCODE_UPDATED':
                    $this->processQrCodeUpdated($event, $payload);
                    break;
                case 'CONNECTION_UPDATE':
                    $this->processConnectionUpdate($event, $payload);
                    break;
                case 'NEW_TOKEN':
                    $this->processNewToken($event, $payload);
                    break;
                case 'APPLICATION_STARTUP':
                    $this->processApplicationStartup($event, $payload);
                    break;
                    
                // Message Events (mantener lógica existente)
                case 'MESSAGES_UPSERT':
                case 'messages.upsert':
                case 'message_received':
                    $this->processMessageUpsert($event, $payload);
                    break;
                    
                case 'MESSAGES_UPDATE':
                case 'messages.update':
                case 'message_update':
                    $this->processMessageUpdate($event, $payload);
                    break;
                    
                case 'MESSAGES_DELETE':
                case 'messages.delete':
                    $this->processMessageDelete($event, $payload);
                    break;
                    
                case 'SEND_MESSAGE':
                    $this->processSendMessage($event, $payload);
                    break;
                    
                // Contact Events
                case 'CONTACTS_SET':
                    $this->processContactsSet($event, $payload);
                    break;
                case 'CONTACTS_UPSERT':
                case 'contacts.upsert':
                case 'CONTACTS_UPDATE':
                case 'contacts.update':
                case 'contact_update':
                    $this->processContactUpdate($event, $payload);
                    break;
                    
                // Chat Events
                case 'CHATS_SET':
                    $this->processChatsSet($event, $payload);
                    break;
                case 'CHATS_UPSERT':
                case 'chats.upsert':
                case 'CHATS_UPDATE':
                case 'chats.update':
                case 'chat_update':
                    $this->processChatUpdate($event, $payload);
                    break;
                    
                case 'CHATS_DELETE':
                case 'chats.delete':
                    $this->processChatsDelete($event, $payload);
                    break;
                    
                // Group Events
                case 'GROUPS_UPSERT':
                case 'groups.upsert':
                    $this->processGroupsUpsert($event, $payload);
                    break;
                    
                case 'GROUPS_UPDATE':
                case 'groups.update':
                    $this->processGroupsUpdate($event, $payload);
                    break;
                    
                case 'GROUP_PARTICIPANTS_UPDATE':
                case 'group-participants-update':
                    $this->processGroupParticipantsUpdate($event, $payload);
                    break;
                    
                // Typebot Events
                case 'TYPEBOT_START':
                    $this->processTypebotStart($event, $payload);
                    break;
                    
                case 'TYPEBOT_CHANGE_STATUS':
                    $this->processTypebotChangeStatus($event, $payload);
                    break;
                    
                // Additional Events
                case 'PRESENCE_UPDATE':
                case 'presence-update':
                case 'presence_update':
                    $this->processPresenceUpdate($event, $payload);
                    break;
                    
                case 'STATUS_MESSAGE':
                case 'status-message':
                case 'status_update':
                    $this->processStatusMessage($event, $payload);
                    break;
                    
                case 'LABELS_EDIT':
                    $this->processLabelsEdit($event, $payload);
                    break;
                    
                case 'LABELS_ASSOCIATION':
                    $this->processLabelsAssociation($event, $payload);
                    break;
                    
                case 'CALL_WEBHOOK':
                    $this->processCallWebhook($event, $payload);
                    break;
                    
                default:
                    // Log unknown event type
                    error_log("Unknown webhook event type: $eventType");
                    break;
            }
            
            WebhookEvent::markProcessed($eventId);
            
        } catch (\Exception $e) {
            WebhookEvent::markProcessed($eventId, $e->getMessage());
            error_log("Webhook processing error: " . $e->getMessage());
        }
    }
    
    private function processMessageUpsert($event, $payload)
    {
        $data = $payload['data'];
        $message = $data['message'];
        $key = $data['key'];
        
        $remoteJid = $key['remoteJid'];
        $messageId = $key['id'];
        $fromMe = $key['fromMe'] ?? false;
        $participant = $data['participant'] ?? null;
        
        // Determine if it's a group
        $isGroup = strpos($remoteJid, '@g.us') !== false;
        
        // Find or create chat
        $chatData = [
            'instance_id' => $event['instance_id'],
            'remote_jid' => $remoteJid,
            'is_group' => $isGroup,
            'last_message_at' => date('Y-m-d H:i:s', intval($data['messageTimestamp'] / 1000))
        ];
        
        // Set chat title
        if ($isGroup) {
            $chatData['title'] = $data['pushname'] ?? $data['notifyName'] ?? 'Group';
        } else {
            $chatData['title'] = $data['pushname'] ?? $data['notifyName'] ?? $remoteJid;
        }
        
        // Set message snippet
        $chatData['last_snippet'] = $this->getMessageSnippet($message);
        
        $chatId = Chat::createOrUpdate($chatData);
        
        // Create message record
        $messageData = [
            'instance_id' => $event['instance_id'],
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'from_me' => $fromMe,
            'ts' => date('Y-m-d H:i:s', intval($data['messageTimestamp'] / 1000)),
            'msg_type' => $this->getMessageType($message),
            'body_text' => $this->getMessageBody($message),
            'participant_jid' => $participant,
            'media_url' => $this->getMediaUrl($message),
            'status' => 'delivered',
            'raw_json' => json_encode($message)
        ];
        
        Message::create($messageData);
        
        // Update unread count for incoming messages
        if (!$fromMe) {
            Chat::incrementUnread($chatId);
        }
    }
    
    private function processMessageUpdate($event, $payload)
    {
        $data = $payload['data'];
        
        // Manejar diferentes estructuras de message update
        $messageId = null;
        
        // Estructura 1: con key
        if (isset($data['key']) && is_array($data['key']) && isset($data['key']['id'])) {
            $messageId = $data['key']['id'];
        }
        // Estructura 2: directo messageId
        elseif (isset($data['messageId'])) {
            $messageId = $data['messageId'];
        }
        // Estructura 3: en el nivel superior
        elseif (isset($data['id'])) {
            $messageId = $data['id'];
        }
        
        if (!$messageId) {
            error_log("Message update event - no message ID found: " . json_encode($data));
            return;
        }
        
        // Update message status
        if (isset($data['status'])) {
            Message::updateStatus($messageId, $data['status']);
            error_log("Message status updated: $messageId -> {$data['status']}");
        }
    }
    
    private function processMessageDelete($event, $payload)
    {
        $data = $payload['data'];
        
        // Validar que data tenga la estructura esperada
        if (!isset($data['key']) || !is_array($data['key']) || !isset($data['key']['id'])) {
            error_log("Message delete event missing key structure: " . json_encode($data));
            return;
        }
        
        $messageId = $data['key']['id'];
        
        // Find and delete message
        $message = Message::findByMessageId($event['instance_id'], $messageId);
        if ($message) {
            Message::delete($message['id']);
        }
    }
    
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
    
    private function forwardWebhook($instance, $payload)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $instance['forward_webhook_url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Forwarded-From: EVOAPP'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        curl_exec($ch);
        curl_close($ch);
    }
    
    // ==================== NUEVOS MÉTODOS PARA 26 EVENTOS ====================
    
    private function processQrCodeUpdated($event, $payload)
    {
        $qrCode = $payload['data']['qrcode']['base64'] ?? null;
        if ($qrCode) {
            // Actualizar QR code en la instancia
            $sql = "UPDATE instances SET qr_code = ?, updated_at = NOW() WHERE id = ?";
            DB::q($sql, [$qrCode, $event['instance_id']]);
        }
        
        error_log("QR Code updated for instance {$event['instance_id']}");
    }
    
    private function processConnectionUpdate($event, $payload)
    {
        $state = $payload['data']['state'] ?? 'unknown';
        $connected = in_array($state, ['open', 'connected']);
        
        // Actualizar estado de conexión
        $sql = "UPDATE instances SET connection_status = ?, is_connected = ?, updated_at = NOW() WHERE id = ?";
        DB::q($sql, [$state, $connected ? 1 : 0, $event['instance_id']]);
        
        error_log("Connection status updated to {$state} for instance {$event['instance_id']}");
    }
    
    private function processNewToken($event, $payload)
    {
        $token = $payload['data']['token'] ?? null;
        if ($token) {
            $sql = "UPDATE instances SET jwt_token = ?, updated_at = NOW() WHERE id = ?";
            DB::q($sql, [$token, $event['instance_id']]);
        }
        
        error_log("New token received for instance {$event['instance_id']}");
    }
    
    private function processApplicationStartup($event, $payload)
    {
        error_log("Application startup for instance {$event['instance_id']}: " . json_encode($payload['data']));
    }
    
    private function processSendMessage($event, $payload)
    {
        // Procesar mensaje enviado (similar a messages.upsert pero marcando como enviado)
        $this->processMessageUpsert($event, $payload);
        
        // Actualizar estado específico para mensajes enviados
        $messageId = $this->extractMessageId($payload);
        if ($messageId) {
            Message::updateStatus($messageId, 'sent');
        }
    }
    
    private function processContactsSet($event, $payload)
    {
        $contacts = is_array($payload['data']) ? $payload['data'] : [$payload['data']];
        
        foreach ($contacts as $contactData) {
            $this->processContactUpdate($event, ['data' => $contactData]);
        }
        
        error_log("Contacts set processed: " . count($contacts) . " contacts for instance {$event['instance_id']}");
    }
    
    private function processChatsSet($event, $payload)
    {
        $chats = is_array($payload['data']) ? $payload['data'] : [$payload['data']];
        
        foreach ($chats as $chatData) {
            $this->processChatUpdate($event, ['data' => $chatData]);
        }
        
        error_log("Chats set processed: " . count($chats) . " chats for instance {$event['instance_id']}");
    }
    
    private function processChatsDelete($event, $payload)
    {
        $chatId = $payload['data']['id'] ?? null;
        if ($chatId) {
            // Buscar y eliminar chat
            $chat = DB::fetch("SELECT id FROM chats WHERE instance_id = ? AND remote_jid = ?", [$event['instance_id'], $chatId]);
            if ($chat) {
                DB::q("DELETE FROM chats WHERE id = ?", [$chat['id']]);
                DB::q("DELETE FROM messages WHERE chat_id = ?", [$chat['id']]);
            }
        }
        
        error_log("Chat deleted: {$chatId} for instance {$event['instance_id']}");
    }
    
    private function processGroupsUpsert($event, $payload)
    {
        $groupData = $payload['data'];
        $groupId = $groupData['id'] ?? null;
        $subject = $groupData['subject'] ?? null;
        
        if ($groupId) {
            // Crear o actualizar chat de grupo
            $chatData = [
                'instance_id' => $event['instance_id'],
                'remote_jid' => $groupId,
                'is_group' => 1,
                'title' => $subject,
                'last_message_at' => date('Y-m-d H:i:s')
            ];
            
            Chat::createOrUpdate($chatData);
        }
        
        error_log("Group upsert: {$groupId} - {$subject} for instance {$event['instance_id']}");
    }
    
    private function processGroupsUpdate($event, $payload)
    {
        $this->processGroupsUpsert($event, $payload);
    }
    
    private function processGroupParticipantsUpdate($event, $payload)
    {
        $groupId = $payload['data']['id'] ?? null;
        $action = $payload['data']['action'] ?? null;
        $participants = $payload['data']['participants'] ?? [];
        
        error_log("Group participants update: {$groupId} - {$action} - " . count($participants) . " participants");
        
        // Aquí podrías agregar lógica específica para manejar participantes
        // Por ahora solo logueamos el evento
    }
    
    private function processTypebotStart($event, $payload)
    {
        $typebotId = $payload['data']['typebot_id'] ?? null;
        $remoteJid = $payload['data']['remoteJid'] ?? null;
        
        error_log("Typebot started: {$typebotId} for {$remoteJid} in instance {$event['instance_id']}");
        
        // Aquí podrías agregar lógica para manejar sesiones de Typebot
    }
    
    private function processTypebotChangeStatus($event, $payload)
    {
        $typebotId = $payload['data']['typebot_id'] ?? null;
        $status = $payload['data']['status'] ?? null;
        
        error_log("Typebot status changed: {$typebotId} to {$status} in instance {$event['instance_id']}");
    }
    
    private function processStatusMessage($event, $payload)
    {
        $remoteJid = $payload['data']['remoteJid'] ?? null;
        $message = $payload['data']['message'] ?? null;
        
        error_log("Status message from {$remoteJid}: {$message} in instance {$event['instance_id']}");
    }
    
    private function processLabelsEdit($event, $payload)
    {
        $labelId = $payload['data']['labelId'] ?? null;
        $name = $payload['data']['name'] ?? null;
        
        error_log("Label edited: {$labelId} - {$name} in instance {$event['instance_id']}");
    }
    
    private function processLabelsAssociation($event, $payload)
    {
        $labelId = $payload['data']['labelId'] ?? null;
        $remoteJid = $payload['data']['remoteJid'] ?? null;
        $action = $payload['data']['action'] ?? null;
        
        error_log("Label association: {$labelId} - {$action} - {$remoteJid} in instance {$event['instance_id']}");
    }
    
    private function processCallWebhook($event, $payload)
    {
        $callId = $payload['data']['callId'] ?? null;
        $status = $payload['data']['status'] ?? null;
        $remoteJid = $payload['data']['remoteJid'] ?? null;
        
        error_log("Call webhook: {$callId} - {$status} from {$remoteJid} in instance {$event['instance_id']}");
    }
    
    private function processContactUpdate($event, $payload)
    {
        $data = $payload['data'];
        $remoteJid = $data['remoteJid'] ?? null;
        $pushname = $data['pushname'] ?? null;
        $profilePicUrl = $data['profilePicUrl'] ?? null;
        
        if ($remoteJid) {
            // Actualizar o crear contacto
            $contactData = [
                'instance_id' => $event['instance_id'],
                'remote_jid' => $remoteJid,
                'pushname' => $pushname,
                'profile_pic_url' => $profilePicUrl
            ];
            
            // Aquí podrías usar un método Contact::createOrUpdate() si existe
            // Por ahora solo logueamos
            error_log("Contact update: {$remoteJid} - {$pushname} in instance {$event['instance_id']}");
        }
    }
    
    private function processChatUpdate($event, $payload)
    {
        $data = $payload['data'];
        $remoteJid = $data['id'] ?? $data['remoteJid'] ?? null;
        $lastMessageTimestamp = $data['lastMessageTimestamp'] ?? null;
        
        if ($remoteJid) {
            // Actualizar chat
            $chatData = [
                'instance_id' => $event['instance_id'],
                'remote_jid' => $remoteJid,
                'last_message_at' => $lastMessageTimestamp ? date('Y-m-d H:i:s', intval($lastMessageTimestamp / 1000)) : date('Y-m-d H:i:s')
            ];
            
            Chat::createOrUpdate($chatData);
        }
    }
    
    private function processPresenceUpdate($event, $payload)
    {
        $data = $payload['data'];
        $remoteJid = $data['id'] ?? null;
        $presences = $data['presences'] ?? [];
        
        error_log("Presence update for {$remoteJid}: " . json_encode($presences));
    }
    
    // ==================== HELPER METHODS ====================
    
    private function extractRemoteJid($payload)
    {
        // Intentar extraer remoteJid de diferentes estructuras
        if (isset($payload['data']['key']['remoteJid'])) {
            return $payload['data']['key']['remoteJid'];
        }
        if (isset($payload['data']['remoteJid'])) {
            return $payload['data']['remoteJid'];
        }
        if (isset($payload['data']['id'])) {
            return $payload['data']['id'];
        }
        return null;
    }
    
    private function extractMessageId($payload)
    {
        if (isset($payload['data']['key']['id'])) {
            return $payload['data']['key']['id'];
        }
        if (isset($payload['data']['id'])) {
            return $payload['data']['id'];
        }
        return null;
    }
}
