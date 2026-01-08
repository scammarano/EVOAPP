<?php
use App\Core\Auth;
$title = 'Inbox - ' . ($instance['slug'] ?? '') . ' - ' . APP_NAME;
?>

<!-- Chat List -->
<div class="chat-list" data-page="1">
    <?php foreach ($chats as $chat): ?>
        <div class="chat-item <?= ($selectedChat['id'] ?? null) == $chat['id'] ? 'active' : '' ?>" 
             data-chat-id="<?= $viewHelper->escape($chat['id']) ?>" 
             data-remote-jid="<?= $viewHelper->escape($chat['remote_jid']) ?>">
            <div class="chat-avatar <?= $chat['is_group'] ? 'group' : '' ?>">
                <span class="icon-<?= $chat['is_group'] ? 'group' : 'person' ?>"></span>
            </div>
            <div class="chat-info">
                <div class="chat-name"><?= $viewHelper->escape($chat['title'] ?: $chat['remote_jid']) ?></div>
                <div class="chat-last-message"><?= $viewHelper->escape($chat['last_snippet'] ?: '') ?></div>
            </div>
            <div class="chat-meta">
                <div class="chat-time"><?= $viewHelper->timeAgo($chat['last_message_at']) ?></div>
                <?php if ($chat['has_unread']): ?>
                    <div class="chat-unread">!</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if (empty($chats)): ?>
        <div class="loading">
            No chats found. Start a conversation to see it here.
        </div>
    <?php endif; ?>
</div>

<!-- Conversation -->
<div class="conversation">
    <?php if ($selectedChat): ?>
        <!-- Conversation Header -->
        <div class="conversation-header">
            <div class="conversation-title"><?= $viewHelper->escape($selectedChat['title'] ?: $selectedChat['remote_jid']) ?></div>
            <div class="conversation-actions">
                <button class="btn-icon" title="Search messages">
                    <span class="icon-search"></span>
                </button>
                <button class="btn-icon" title="More options">
                    <span class="icon-menu"></span>
                </button>
            </div>
        </div>
        
        <!-- Messages Container -->
        <div class="messages-container">
            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="message <?= $message['from_me'] ? 'sent' : 'received' ?>" data-ts="<?= $viewHelper->escape($message['ts']) ?>">
                        <div class="message-bubble">
                            <?php if ($message['participant_jid'] && !$message['from_me']): ?>
                                <div class="message-author"><?= $viewHelper->escape($message['display_name']) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($message['msg_type'] === 'text'): ?>
                                <div class="message-text"><?= $viewHelper->escape($message['body_text']) ?></div>
                            <?php else: ?>
                                <?= $this->renderMediaMessage($message) ?>
                            <?php endif; ?>
                            
                            <div class="message-meta">
                                <span><?= $viewHelper->formatTime($message['ts'], 'H:i') ?></span>
                                <?php if ($message['from_me']): ?>
                                    <span>✓✓</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="loading">
                    No messages yet. Start the conversation!
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Message Input -->
        <div class="message-input-container">
            <button class="btn-icon" onclick="document.getElementById('file-input').click()" title="Attach file">
                <span class="icon-attach"></span>
            </button>
            
            <input type="file" id="file-input" style="display: none;" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
            
            <form id="message-form" style="flex: 1; display: flex; gap: 0.5rem;">
                <input type="text" 
                       id="message-input" 
                       class="message-input" 
                       placeholder="Type a message..."
                       autocomplete="off">
                
                <button type="submit" class="btn-icon btn-send" title="Send message">
                    <span class="icon-send"></span>
                </button>
            </form>
        </div>
    <?php else: ?>
        <!-- No chat selected -->
        <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-secondary);">
            <div style="text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
                <h3>Select a conversation</h3>
                <p>Choose a chat from the list to start messaging</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Set current instance for JavaScript
window.evoapp.currentInstance = '<?= $viewHelper->escape($instance['slug']) ?>';

<?php if ($selectedChat): ?>
// Set current chat for JavaScript
window.evoapp.currentChat = {
    id: <?= $viewHelper->escape($selectedChat['id']) ?>,
    remoteJid: '<?= $viewHelper->escape($selectedChat['remote_jid']) ?>'
};
<?php endif; ?>

// Auto-focus message input
document.addEventListener('DOMContentLoaded', () => {
    const messageInput = document.getElementById('message-input');
    if (messageInput) {
        messageInput.focus();
    }
});

// Handle keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + Enter to send message
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        const form = document.getElementById('message-form');
        if (form) {
            form.dispatchEvent(new Event('submit'));
        }
    }
});
</script>

<?php
// Helper function for rendering media messages
function renderMediaMessage($message) {
    $content = '';
    
    switch ($message['msg_type']) {
        case 'image':
            if ($message['media_url']) {
                $content = '<img src="' . htmlspecialchars($message['media_url'], ENT_QUOTES, 'UTF-8') . '" style="max-width: 200px; border-radius: 0.5rem;" alt="Image">';
            } else {
                $content = '<div class="message-text">📷 Image' . ($message['body_text'] ? ': ' . htmlspecialchars($message['body_text'], ENT_QUOTES, 'UTF-8') : '') . '</div>';
            }
            break;
            
        case 'video':
            if ($message['media_url']) {
                $content = '<video controls style="max-width: 200px; border-radius: 0.5rem;"><source src="' . htmlspecialchars($message['media_url'], ENT_QUOTES, 'UTF-8') . '"></video>';
            } else {
                $content = '<div class="message-text">🎥 Video' . ($message['body_text'] ? ': ' . htmlspecialchars($message['body_text'], ENT_QUOTES, 'UTF-8') : '') . '</div>';
            }
            break;
            
        case 'audio':
            if ($message['media_url']) {
                $content = '<audio controls style="max-width: 200px;"><source src="' . htmlspecialchars($message['media_url'], ENT_QUOTES, 'UTF-8') . '"></audio>';
            } else {
                $content = '<div class="message-text">🎵 Audio' . ($message['body_text'] ? ': ' . htmlspecialchars($message['body_text'], ENT_QUOTES, 'UTF-8') : '') . '</div>';
            }
            break;
            
        case 'document':
            if ($message['media_url']) {
                $content = '<div class="message-text">📄 <a href="' . htmlspecialchars($message['media_url'], ENT_QUOTES, 'UTF-8') . '" target="_blank">' . htmlspecialchars($message['body_text'] ?: 'Document', ENT_QUOTES, 'UTF-8') . '</a></div>';
            } else {
                $content = '<div class="message-text">📄 ' . htmlspecialchars($message['body_text'] ?: 'Document', ENT_QUOTES, 'UTF-8') . '</div>';
            }
            break;
            
        default:
            $content = '<div class="message-text">📎 ' . htmlspecialchars($message['body_text'] ?: 'Media', ENT_QUOTES, 'UTF-8') . '</div>';
    }
    
    return $content;
}
?>
