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
            if (!empty($evt['payload_json'])) {
                $payload = json_decode($evt['payload_json'], true);
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
        $eventData = [
            'instance_id' => $instance['id'],
            'event_type' => $payload['event'] ?? 'unknown',
            'remote_jid' => $payload['data']['key']['remoteJid'] ?? null,
            'message_id' => $payload['data']['key']['id'] ?? null,
            'participant_jid' => $payload['data']['participant'] ?? null,
            'payload' => $payload
        ];
        
        $eventId = WebhookEvent::create($eventData);
        
        // Update instance webhook timestamp
        Instance::updateWebhookTimestamp($instance['id']);
        
        // Forward webhook if configured
        if ($instance['forward_webhook_enabled'] && $instance['forward_webhook_url']) {
            $this->forwardWebhook($instance, $payload);
        }
        
        // Process event immediately for real-time updates
        $this->processWebhookEvent($eventId);
        
        echo json_encode(['success' => true, 'event_id' => $eventId]);
    }
    
    private function processWebhookEvent($eventId)
    {
        $event = DB::fetch("SELECT * FROM webhook_events WHERE id = ?", [$eventId]);
        if (!$event || $event['status'] !== 'pending') {
            return;
        }
        
        try {
            $payload = json_decode($event['payload_json'], true);
            $eventType = $event['event_type'];
            
            switch ($eventType) {
                case 'messages.upsert':
                    $this->processMessageUpsert($event, $payload);
                    break;
                    
                case 'messages.update':
                    $this->processMessageUpdate($event, $payload);
                    break;
                    
                case 'messages.delete':
                    $this->processMessageDelete($event, $payload);
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
            'last_message_at' => date('Y-m-d H:i:s', $data['messageTimestamp'] / 1000)
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
            'ts' => date('Y-m-d H:i:s', $data['messageTimestamp'] / 1000),
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
        $messageId = $data['key']['id'];
        
        // Update message status
        if (isset($data['status'])) {
            Message::updateStatus($messageId, $data['status']);
        }
    }
    
    private function processMessageDelete($event, $payload)
    {
        $data = $payload['data'];
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
}
