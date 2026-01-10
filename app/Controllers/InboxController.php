<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\MessageSender;
use App\Models\Instance;
use App\Models\Chat;
use App\Models\Message;

class InboxController
{
    public function index()
    {
        $instanceSlug = $_GET['instance'] ?? '';
        $chatId = (int)($_GET['chat_id'] ?? 0);
        
        if (!$instanceSlug) {
            header('Location: index.php?r=dashboard/index');
            exit;
        }
        
        $instance = Instance::findBySlug($instanceSlug);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            View::set('error', 'Instancia no encontrada o sin permisos');
            View::render('error/404');
            return;
        }
        
        // Get first page of chats
        $chats = Chat::getChatsByInstance($instance['id'], 1, CHATS_PER_PAGE);
        
        // Get selected chat and messages if chat_id is provided
        $selectedChat = null;
        $messages = [];
        
        if ($chatId) {
            $selectedChat = Chat::findById($chatId);
            if ($selectedChat && $selectedChat['instance_id'] == $instance['id']) {
                $messages = array_reverse(Message::getMessagesByChat($chatId, 1, MESSAGES_PER_PAGE));
                
                // Mark chat as read
                $user = Auth::getCurrentUser();
                Chat::markAsRead($chatId, $user['id']);
            }
        }
        
        View::set('instance', $instance);
        View::set('chats', $chats);
        View::set('selectedChat', $selectedChat);
        View::set('messages', $messages);
        
        View::render('inbox/index');
    }
    
    public function chatsAjax()
    {
        header('Content-Type: application/json');
        
        $instanceSlug = $_GET['instance'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        
        if (!$instanceSlug) {
            echo json_encode(['error' => 'Instance required']);
            return;
        }
        
        $instance = Instance::findBySlug($instanceSlug);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $chats = Chat::getChatsByInstance($instance['id'], $page, CHATS_PER_PAGE);
        
        echo json_encode([
            'success' => true,
            'chats' => $chats,
            'hasMore' => count($chats) === CHATS_PER_PAGE
        ]);
    }
    
    public function messages()
    {
        header('Content-Type: application/json');
        
        $instanceSlug = $_GET['instance'] ?? '';
        $chatId = (int)($_GET['chat_id'] ?? 0);
        $page = (int)($_GET['page'] ?? 1);
        $beforeTs = $_GET['before_ts'] ?? null;
        
        if (!$instanceSlug || !$chatId) {
            echo json_encode(['error' => 'Instance and chat_id required']);
            return;
        }
        
        $instance = Instance::findBySlug($instanceSlug);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $chat = Chat::findById($chatId);
        if (!$chat || $chat['instance_id'] != $instance['id']) {
            echo json_encode(['error' => 'Chat not found']);
            return;
        }
        
        if ($beforeTs) {
            $messages = Message::getMessagesByChatAsc($chatId, $beforeTs, MESSAGES_PER_PAGE);
        } else {
            $messages = Message::getMessagesByChat($chatId, $page, MESSAGES_PER_PAGE);
        }
        
        // Mark chat as read
        $user = Auth::getCurrentUser();
        Chat::markAsRead($chatId, $user['id']);
        
        echo json_encode([
            'success' => true,
            'messages' => array_reverse($messages), // Show oldest first
            'hasMore' => count($messages) === MESSAGES_PER_PAGE
        ]);
    }

    public function statsAjax()
    {
        header('Content-Type: application/json');

        $instanceSlug = $_GET['instance'] ?? '';

        if (!$instanceSlug) {
            echo json_encode(['error' => 'Instance required']);
            return;
        }

        $instance = Instance::findBySlug($instanceSlug);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $stats = Instance::getStatsByInstance($instance['id']);

        if (!$stats) {
            echo json_encode(['error' => 'Stats unavailable']);
            return;
        }

        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
    }
    
    public function sendAjax()
    {
        header('Content-Type: application/json');
        
        $instanceSlug = $_POST['instance'] ?? '';
        $chatId = (int)($_POST['chat_id'] ?? 0);
        $text = $_POST['text'] ?? '';
        
        if (!$instanceSlug || !$chatId || !$text) {
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        
        if (!Auth::isLoggedIn()) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $instance = Instance::findBySlug($instanceSlug);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $chat = Chat::findById($chatId);
        if (!$chat || $chat['instance_id'] != $instance['id']) {
            echo json_encode(['error' => 'Chat not found']);
            return;
        }
        
        try {
            // Usar MessageSender centralizado
            $sender = MessageSender::forInstance($instance['id']);
            $result = $sender->sendText($chat['remote_jid'], $text);
            
            // Log action
            Auth::logAction('send_message', 'message', null, [
                'chat_id' => $chatId,
                'text' => $text,
                'result' => $result
            ]);
            
            echo json_encode([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Message sent successfully' : $result['error'],
                'result' => $result['result'] ?? null
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function sendMediaAjax()
    {
        header('Content-Type: application/json');
        
        if (!Auth::hasPermission('inbox.send_media')) {
            echo json_encode(['error' => 'No permission to send media']);
            return;
        }
        
        $instanceSlug = $_POST['instance'] ?? '';
        $chatId = (int)($_POST['chat_id'] ?? 0);
        $caption = $_POST['caption'] ?? '';
        $text = $_POST['text'] ?? '';
        $sendMode = $_POST['send_mode'] ?? 'media_only'; // media_only, media_with_text
        
        if (!$instanceSlug || !$chatId || !isset($_FILES['media'])) {
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        
        $instance = Instance::findBySlug($instanceSlug);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $chat = Chat::findById($chatId);
        if (!$chat || $chat['instance_id'] != $instance['id']) {
            echo json_encode(['error' => 'Chat not found']);
            return;
        }
        
        try {
            // Usar MessageSender centralizado
            $sender = MessageSender::forInstance($instance['id']);
            $result = $sender->sendMediaFromFile(
                $chat['remote_jid'],
                $_FILES['media'],
                $caption,
                $text,
                $sendMode
            );
            
            // Log action
            Auth::logAction('send_media', 'message', null, [
                'chat_id' => $chatId,
                'file' => $_FILES['media']['name'],
                'caption' => $caption,
                'text' => $text,
                'send_mode' => $sendMode,
                'result' => $result
            ]);
            
            echo json_encode([
                'success' => $result['success'],
                'message' => $result['success'] ? $result['message'] : $result['error'],
                'send_mode' => $sendMode,
                'results' => $result['results'] ?? [],
                'file_info' => [
                    'name' => $_FILES['media']['name'],
                    'size' => $_FILES['media']['size'],
                    'type' => $result['media_info']['type'] ?? 'unknown'
                ]
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function sendEmojiAjax()
    {
        header('Content-Type: application/json');
        
        if (!Auth::isLoggedIn()) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $instanceSlug = $_POST['instance'] ?? '';
        $chatId = (int)($_POST['chat_id'] ?? 0);
        $emoji = $_POST['emoji'] ?? '';
        
        if (!$instanceSlug || !$chatId || !$emoji) {
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }
        
        if (!Auth::hasPermission('inbox.send_text')) {
            echo json_encode(['error' => 'No permission to send messages']);
            return;
        }
        
        $instance = Instance::findBySlug($instanceSlug);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $chat = Chat::findById($chatId);
        if (!$chat || $chat['instance_id'] != $instance['id']) {
            echo json_encode(['error' => 'Chat not found']);
            return;
        }
        
        try {
            $client = Instance::evoClient($instance);
            $result = $client->sendText($instanceSlug, $chat['remote_jid'], $emoji);
            
            // Log action
            Auth::logAction('send_emoji', 'message', null, [
                'chat_id' => $chatId,
                'emoji' => $emoji,
                'result' => $result
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Emoji sent successfully',
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function markRead()
    {
        header('Content-Type: application/json');
        
        $chatId = (int)($_POST['chat_id'] ?? 0);
        
        if (!$chatId) {
            echo json_encode(['error' => 'Chat ID required']);
            return;
        }
        
        $user = Auth::getCurrentUser();
        Chat::markAsRead($chatId, $user['id']);
        
        echo json_encode(['success' => true]);
    }
    
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
}
