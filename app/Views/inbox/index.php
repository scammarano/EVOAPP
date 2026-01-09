<?php
use App\Core\Auth;
use App\Models\Instance;

$title = 'Inbox - ' . ($instance['slug'] ?? '') . ' - ' . APP_NAME;

// Obtener estadísticas de la instancia
$selectedInstanceStats = Instance::getStatsByInstance($instance['id']) ?? [
    'chat_count' => 0,
    'message_count' => 0,
    'total_unread' => 0,
];

// Obtener perfil y estado actual de la instancia
$instanceProfile = Instance::getInstanceProfile($instance['id']);
$instanceStatus = null;
if ($instanceProfile) {
    try {
        $evoClient = Instance::evoClient($instance);
        $instanceStatus = $evoClient->getStatus($instance['slug']);
    } catch (Exception $e) {
        // Error al obtener estado desde API
        $instanceStatus = null;
    }
}
?>

<!-- Instance Header - Reducido -->
<div class="instance-header inbox-instance-header">
    <div class="instance-info">
        <div class="instance-avatar">
            <?php if ($instanceProfile && $instanceProfile['profile_image_url']): ?>
                <img src="<?= $viewHelper->escape($instanceProfile['profile_image_url']) ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            <?php else: ?>
                <div class="avatar-placeholder">📱</div>
            <?php endif; ?>
        </div>
        
        <div class="instance-details">
            <h2 style="margin: 0; font-size: 1.2rem;"><?= $viewHelper->escape($instance['name'] ?? $instance['slug']) ?></h2>
            
            <div class="instance-status">
                <?php if ($instanceStatus): ?>
                    <div class="status-item">
                        <span class="status-label">Estado</span>
                        <span class="status-value"><?= $viewHelper->escape($instanceStatus['text'] ?? 'No status') ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="status-item">
                    <span class="status-label">Conexión</span>
                    <span class="status-value <?= $instance['last_webhook_at'] ? 'online' : 'offline' ?>">
                        <?= $instance['last_webhook_at'] ? '🟢' : '🔴' ?>
                        <?= $instance['last_webhook_at'] ? 'Online' : 'Offline' ?>
                    </span>
                </div>
                
                <div class="status-item">
                    <span class="status-label">QR</span>
                    <button class="btn btn-small" onclick="regenerateQR()">
                        <span class="icon-refresh"></span>
                        Regenerar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Estadísticas Compactas -->
    <div class="instance-stats">
        <div class="stat-card instance-stat-card">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="font-size: 1.5rem;">💬</span>
                <div>
                    <div id="instance-chat-count" style="font-weight: bold;"><?= $selectedInstanceStats['chat_count'] ?? 0 ?></div>
                    <div style="font-size: 0.8rem; color: #666;">Chats</div>
                </div>
            </div>
        </div>
        
        <div class="stat-card instance-stat-card">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="font-size: 1.5rem;">🔔</span>
                <div>
                    <div id="instance-unread-count" style="font-weight: bold; color: #dc3545;"><?= $selectedInstanceStats['total_unread'] ?? 0 ?></div>
                    <div style="font-size: 0.8rem; color: #666;">No leídos</div>
                </div>
            </div>
        </div>
        
        <div class="stat-card instance-stat-card">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="font-size: 1.5rem;">📨</span>
                <div>
                    <div id="instance-message-count" style="font-weight: bold;"><?= $selectedInstanceStats['message_count'] ?? 0 ?></div>
                    <div style="font-size: 0.8rem; color: #666;">Mensajes</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Botones de Navegación -->
    <div class="navigation-buttons">
        <a href="index.php?r=campaigns/index&instance=<?= $viewHelper->escape($instance['slug']) ?>" class="btn btn-primary">
            📢 Campañas
        </a>
        <a href="index.php?r=contacts/index&instance=<?= $viewHelper->escape($instance['slug']) ?>" class="btn btn-secondary">
            👥 Contactos
        </a>
        <a href="index.php?r=audit/index" class="btn btn-info">
            📋 Logs
        </a>
    </div>
</div>

<style>
/* WhatsApp-style Layout */
.whatsapp-container {
    display: flex;
    height: calc(100vh - 260px);
    min-height: 420px;
    background: #f0f2f5;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    border-radius: 12px;
    overflow: hidden;
}

/* Instance Header */
.inbox-instance-header {
    padding: 0.5rem 0;
    margin-bottom: 0.75rem;
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
}

.instance-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1 1 280px;
    min-width: 0;
}

.instance-details h2 {
    font-size: 1.25rem;
    font-weight: 600;
}

.instance-status {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 0.75rem;
    font-size: 0.85rem;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.15rem 0.4rem;
    background: #f5f6f7;
    border-radius: 999px;
}

.status-label {
    font-weight: 600;
    color: #495057;
}

.instance-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 0;
}

.instance-stat-card {
    padding: 0.5rem 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
    min-width: 120px;
}

.navigation-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0;
}

.navigation-buttons .btn {
    padding: 0.35rem 0.75rem;
    font-size: 0.85rem;
}

/* Left Sidebar */
.chat-sidebar {
    width: 350px;
    background: white;
    border-right: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
    transition: width 0.2s ease;
}

.chat-sidebar.collapsed {
    width: 84px;
}

.chat-sidebar.collapsed .user-details,
.chat-sidebar.collapsed .chat-info,
.chat-sidebar.collapsed .chat-meta,
.chat-sidebar.collapsed #search-bar {
    display: none;
}

.chat-sidebar.collapsed .chat-item {
    justify-content: center;
}

.chat-sidebar.collapsed .chat-avatar {
    margin-right: 0;
}

.sidebar-header {
    padding: 1rem;
    background: #075e54;
    color: white;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.user-details {
    flex: 1;
}

.user-name {
    font-weight: 600;
    font-size: 0.875rem;
}

.user-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-indicator.online {
    background: #25d366;
}

.status-indicator.offline {
    background: #dc3545;
}

.sidebar-actions {
    display: flex;
    gap: 0.5rem;
}

.sidebar-toggle-icon {
    font-size: 1rem;
    line-height: 1;
}

.chat-sidebar.collapsed .sidebar-toggle-icon {
    transform: rotate(180deg);
    display: inline-block;
}

.btn-icon {
    width: 32px;
    height: 32px;
    border: none;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    transition: background 0.2s;
}

.btn-icon:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Search Bar */
.search-bar {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    gap: 0.5rem;
}

.search-bar input {
    flex: 1;
    padding: 0.5rem;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    outline: none;
}

/* Chat List */
.chat-list {
    flex: 1;
    overflow-y: auto;
    background: white;
}

.chat-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: background 0.2s;
    border-bottom: 1px solid #f0f2f5;
}

.chat-item:hover {
    background: #f8f9fa;
}

.chat-item.active {
    background: #e9ecef;
}

.chat-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    margin-right: 0.75rem;
    overflow: hidden;
    flex-shrink: 0;
    background: #f0f2f5;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chat-avatar.group {
    background: #d1f2e1;
}

.chat-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.chat-avatar .avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: #495057;
}

.chat-avatar .avatar-placeholder.group {
    background: #25d366;
    color: white;
}

.chat-info {
    flex: 1;
    min-width: 0;
}

.chat-name {
    font-weight: 600;
    font-size: 0.875rem;
    color: #111b21;
    margin-bottom: 0.25rem;
}

.chat-last-message {
    font-size: 0.875rem;
    color: #666;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.message-sent {
    color: #25d366;
}

.message-text {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}

.chat-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
}

.chat-time {
    font-size: 0.75rem;
    color: #666;
}

.chat-unread-badge {
    background: #25d366;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Right Content - Conversation */
.conversation-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #e5ddd5;
    min-width: 0;
}

.conversation-header {
    padding: 1rem;
    background: white;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chat-header-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.chat-header-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
}

.chat-header-details {
    flex: 1;
}

.chat-header-name {
    font-weight: 600;
    font-size: 1rem;
    color: #111b21;
}

.chat-header-status {
    font-size: 0.875rem;
    color: #666;
}

.group-info {
    color: #666;
}

.last-seen {
    color: #666;
}

.conversation-actions {
    display: flex;
    gap: 0.5rem;
}

/* Media Preview Column */
.media-preview {
    width: 320px;
    background: white;
    border-left: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
}

.media-preview-header {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 600;
    color: #111b21;
}

.media-preview-body {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.media-preview-empty {
    color: #666;
    text-align: center;
    margin-top: 3rem;
}

.media-preview-content img,
.media-preview-content video,
.media-preview-content iframe {
    width: 100%;
    border-radius: 8px;
}

.media-preview-content audio {
    width: 100%;
}

.media-preview.hidden {
    display: none;
}

.message-media-trigger {
    cursor: pointer;
    display: inline-flex;
    flex-direction: column;
    gap: 0.5rem;
}

/* Messages */
.messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    background: url('data:image/svg+xml;base64,PHN2ZyB4aWxucz0iaHR0cDovL3d3dy53My5vcmC0Zy9lbW4uc2Z5LzEwIDAgMSIgZW5naW5ib3JkPSJNMDAiIGhlaWdodD0iMTAwIiB2aWV3aWQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiPjwvc3ZnPg==');
    background-size: cover;
    background-position: center;
}

.message {
    display: flex;
    margin-bottom: 0.5rem;
    max-width: 80%;
}

.message.sent {
    justify-content: flex-end;
    margin-left: auto;
}

.message.received {
    justify-content: flex-start;
}

.message-bubble {
    background: white;
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    max-width: 100%;
    position: relative;
}

.message.sent .message-bubble {
    background: #dcf8c6;
    border-bottom-right-radius: 0.25rem;
}

.message.received .message-bubble {
    background: white;
    border-bottom-left-radius: 0.25rem;
}

.message-author {
    font-size: 0.75rem;
    font-weight: 600;
    color: #075e54;
    margin-bottom: 0.25rem;
}

.message-text {
    color: #111b21;
    line-height: 1.4;
    word-wrap: break-word;
}

.message-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.25rem;
    font-size: 0.75rem;
}

.message-time {
    color: #666;
}

.message-status {
    color: #4fc3f7;
}

/* Message Input */
.message-input-container {
    padding: 1rem;
    background: white;
    border-top: 1px solid #e9ecef;
}

.input-toolbar {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.input-area {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.message-input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #e9ecef;
    border-radius: 1.5rem;
    outline: none;
    font-size: 0.875rem;
}

.btn-send {
    background: #25d366;
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-send:hover {
    background: #128c7e;
}

/* Load More */
.load-more-container {
    text-align: center;
    padding: 1rem;
}

.btn-load-more {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    cursor: pointer;
    color: #666;
}

.btn-load-more:hover {
    background: #f8f9fa;
}

/* Empty States */
.empty-chats,
.empty-messages,
.empty-conversation {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #666;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-chats h3,
.empty-messages h3,
.empty-conversation h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.empty-chats p,
.empty-messages p,
.empty-conversation p {
    margin: 0.5rem 0 0 0;
    text-align: center;
}

/* Emoji Picker */
.emoji-picker {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    min-width: 300px;
    max-width: 400px;
}

.emoji-picker-header {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
}

.close-picker {
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    cursor: pointer;
    font-size: 1rem;
}

.emoji-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 0.25rem;
    padding: 1rem;
    max-height: 200px;
    overflow-y: auto;
}

.emoji-item {
    padding: 0.5rem;
    border: 1px solid #f0f2f5;
    border-radius: 0.25rem;
    background: white;
    cursor: pointer;
    font-size: 1.5rem;
    text-align: center;
    transition: all 0.2s;
}

.emoji-item:hover {
    background: #f8f9fa;
    transform: scale(1.1);
}

/* Loading states */
.loading-more {
    text-align: center;
    padding: 1rem;
    color: #666;
    font-style: italic;
}

/* File upload enhancements */
.input-toolbar {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.input-toolbar .btn-icon {
    width: 36px;
    height: 36px;
}

.input-area {
    flex: 1;
    display: flex;
    align-items: center;
}

.message-input:disabled {
    background: #f8f9fa;
    color: #666;
}

/* Enhanced message bubbles */
.message-bubble {
    position: relative;
    transition: all 0.2s ease;
}

.message-bubble:hover {
    transform: translateY(-2px);
}

/* Media message styling */
.message-bubble img,
.message-bubble video {
    max-width: 200px;
    border-radius: 0.5rem;
    margin-top: 0.5rem;
}

.message-bubble audio {
    max-width: 200px;
    margin-top: 0.5rem;
}

.message-bubble .message-text a {
    color: #25d366;
    text-decoration: underline;
}

.message-bubble .message-text a:hover {
    color: #1d4ed8;
}

/* Responsive improvements */
@media (max-width: 480px) {
    .emoji-grid {
        grid-template-columns: repeat(6, 1fr);
    }
    
    .emoji-picker {
        min-width: 280px;
        max-width: 90vw;
    }
    
    .input-toolbar {
        flex-wrap: wrap;
    }
}

@media (max-width: 1200px) {
    .media-preview {
        display: none;
    }
}

@media (max-width: 900px) {
    .whatsapp-container {
        height: auto;
    }
}
</style>

<!-- WhatsApp-style Layout -->
<div class="whatsapp-container">
    <!-- Left Sidebar - Chat List -->
    <div class="chat-sidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <div class="user-info">
                <div class="user-avatar">
                    <?php if ($instanceProfile && $instanceProfile['profile_image_url']): ?>
                        <img src="<?= $viewHelper->escape($instanceProfile['profile_image_url']) ?>" alt="Profile">
                    <?php else: ?>
                        <div class="avatar-placeholder">👤</div>
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?= $viewHelper->escape($instance['name'] ?? $instance['slug']) ?></div>
                    <div class="user-status">
                        <span class="status-indicator <?= $instance['last_webhook_at'] ? 'online' : 'offline' ?>"></span>
                        <span class="status-text"><?= $instance['last_webhook_at'] ? 'Online' : 'Offline' ?></span>
                    </div>
                </div>
            </div>
            <div class="sidebar-actions">
                <button class="btn-icon" onclick="toggleSearch()" title="Search chats">
                    <span class="icon-search"></span>
                </button>
                <button class="btn-icon" onclick="newChat()" title="New chat">
                    <span class="icon-plus"></span>
                </button>
                <button class="btn-icon sidebar-toggle" onclick="toggleSidebar()" title="Collapse sidebar">
                    <span class="sidebar-toggle-icon">⇤</span>
                </button>
                <button class="btn-icon" onclick="toggleMenu()" title="Menu">
                    <span class="icon-menu"></span>
                </button>
            </div>
        </div>
        
        <!-- Search Bar (Hidden by default) -->
        <div id="search-bar" class="search-bar" style="display: none;">
            <input type="text" placeholder="Search chats..." id="chat-search">
            <button class="btn-icon" onclick="clearSearch()">
                <span class="icon-close"></span>
            </button>
        </div>
        
        <!-- Chat List -->
        <div class="chat-list" data-page="1">
            <?php foreach ($chats as $chat): ?>
                <?php
                $chatDisplayName = $chat['title'] ?: ($chat['contact_name'] ?: $chat['remote_jid']);
                if ($chat['is_group'] && empty($chatDisplayName)) {
                    $chatDisplayName = 'Grupo sin nombre';
                }
                ?>
                <div class="chat-item <?= ($selectedChat['id'] ?? null) == $chat['id'] ? 'active' : '' ?>" 
                     data-chat-id="<?= $viewHelper->escape($chat['id']) ?>" 
                     data-remote-jid="<?= $viewHelper->escape($chat['remote_jid']) ?>"
                     onclick="selectChat(<?= $chat['id'] ?>, '<?= $viewHelper->escape($chat['remote_jid']) ?>')">
                    <div class="chat-avatar <?= $chat['is_group'] ? 'group' : '' ?>">
                        <?php if (!empty($chat['avatar_url'])): ?>
                            <img src="<?= $viewHelper->escape($chat['avatar_url']) ?>" alt="Avatar">
                        <?php else: ?>
                            <div class="avatar-placeholder <?= $chat['is_group'] ? 'group' : 'person' ?>">
                                <?= $chat['is_group'] ? '👥' : '👤' ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="chat-info">
                        <div class="chat-name"><?= $viewHelper->escape($chatDisplayName ?: 'Sin nombre') ?></div>
                        <div class="chat-last-message">
                            <?php if (!empty($chat['from_me'])): ?>
                                <span class="message-sent">✓</span>
                            <?php endif; ?>
                            <span class="message-text"><?= $viewHelper->escape($chat['last_snippet'] ?: '') ?></span>
                        </div>
                    </div>
                    <div class="chat-meta">
                        <div class="chat-time"><?= date('H:i', strtotime($chat['last_message_at'])) ?></div>
                        <?php if (!empty($chat['has_unread'])): ?>
                            <div class="chat-unread-badge"><?= $chat['unread_count'] ?? 1 ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($chats)): ?>
                <div class="empty-chats">
                    <div class="empty-icon">💬</div>
                    <h3>No conversations yet</h3>
                    <p>Start a conversation to see it here</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Right Content - Conversation -->
    <div class="conversation-area">
        <?php if ($selectedChat): ?>
            <?php
            $selectedChatDisplayName = $selectedChat['title'] ?: ($selectedChat['contact_name'] ?? $selectedChat['remote_jid']);
            if ($selectedChat['is_group'] && empty($selectedChatDisplayName)) {
                $selectedChatDisplayName = 'Grupo sin nombre';
            }
            ?>
            <!-- Conversation Header -->
            <div class="conversation-header">
                <div class="chat-header-info">
                    <div class="chat-header-avatar">
                        <?php if ($selectedChat['avatar_url']): ?>
                            <img src="<?= $viewHelper->escape($selectedChat['avatar_url']) ?>" alt="Avatar">
                        <?php else: ?>
                            <div class="avatar-placeholder <?= $selectedChat['is_group'] ? 'group' : 'person' ?>">
                                <?= $selectedChat['is_group'] ? '👥' : '👤' ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="chat-header-details">
                        <div class="chat-header-name"><?= $viewHelper->escape($selectedChatDisplayName ?: 'Sin nombre') ?></div>
                        <div class="chat-header-status">
                            <?php if ($selectedChat['is_group']): ?>
                                <span class="group-info">👥 <?= $selectedChat['participant_count'] ?? 0 ?> participants</span>
                            <?php else: ?>
                                <span class="last-seen">Last seen <?= $viewHelper->timeAgo($selectedChat['last_message_at']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="conversation-actions">
                    <button class="btn-icon" onclick="refreshInstanceData(this)" title="Refrescar mensajes">
                        <span class="icon-refresh"></span>
                    </button>
                    <button class="btn-icon" onclick="searchInChat()" title="Search messages">
                        <span class="icon-search"></span>
                    </button>
                    <button class="btn-icon" onclick="showChatOptions()" title="More options">
                        <span class="icon-menu"></span>
                    </button>
                </div>
            </div>
            
            <!-- Messages Container -->
            <div class="messages-container" id="messages-container">
                <!-- Load More Button -->
                <div id="load-more-container" class="load-more-container" style="display: none;">
                    <button class="btn-load-more" onclick="loadMoreMessages()">
                        <span class="icon-arrow-up"></span>
                        Cargar más antiguos
                    </button>
                </div>
                
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?= $message['from_me'] ? 'sent' : 'received' ?>" data-ts="<?= $viewHelper->escape($message['ts']) ?>" data-message-id="<?= $viewHelper->escape($message['message_id'] ?? '') ?>">
                            <div class="message-bubble">
                                <?php if ($message['participant_jid'] && !$message['from_me'] && $selectedChat['is_group']): ?>
                                    <div class="message-author"><?= $viewHelper->escape($message['display_name']) ?></div>
                                <?php endif; ?>
                                
                                <?php if ($message['msg_type'] === 'text'): ?>
                                    <div class="message-text"><?= $viewHelper->escape($message['body_text']) ?></div>
                                <?php else: ?>
                                    <?= renderMediaMessage($message) ?>
                                <?php endif; ?>
                                
                                <div class="message-meta">
                                    <span class="message-time"><?= date('H:i', strtotime($message['ts'])) ?></span>
                                    <?php if ($message['from_me']): ?>
                                        <span class="message-status"><?= $message['status'] === 'read' ? '✓✓' : '✓' ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-chat-state" style="text-align: center; padding: 3rem; color: #666;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
                        <div style="font-size: 1.1rem; margin-bottom: 0.5rem;">Selecciona un chat para comenzar</div>
                        <div style="font-size: 0.9rem; color: #999;">Los mensajes aparecerán aquí</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Message Input -->
            <div class="message-input-container">
                <div class="input-toolbar">
                    <button class="btn-icon" onclick="document.getElementById('file-input').click()" title="Attach file">
                        <span class="icon-attach"></span>
                    </button>
                    <button class="btn-icon" onclick="showEmojiPicker()" title="Emoji">
                        <span class="icon-smile"></span>
                    </button>
                </div>
                
                <div class="input-area">
                    <input type="file" id="file-input" style="display: none;" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                    
                    <form id="message-form" style="display: flex; align-items: center;">
                        <input type="text" 
                               id="message-input" 
                               class="message-input" 
                               placeholder="Escribe un mensaje..."
                               autocomplete="off">
                        
                        <button type="submit" class="btn-send" title="Send message">
                            <span class="icon-send"></span>
                        </button>
                    </form>
                </div>
            </div>
            
        <?php else: ?>
            <!-- No chat selected -->
            <div class="empty-conversation">
                <div class="empty-icon">💬</div>
                <h3>Selecciona una conversación</h3>
                <p>Elige un chat de la lista para empezar a conversar</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="media-preview hidden" id="media-preview">
        <div class="media-preview-header">
            <span>Vista previa</span>
            <button class="btn-icon" onclick="clearMediaPreview()" title="Cerrar">
                <span class="icon-close"></span>
            </button>
        </div>
        <div class="media-preview-body">
            <div class="media-preview-empty" id="media-preview-empty">
                Selecciona un archivo para ver detalles.
            </div>
            <div class="media-preview-content" id="media-preview-content"></div>
        </div>
    </div>
</div>

<script>
// WhatsApp-style functions
function selectChat(chatId, remoteJid) {
    window.location.href = `index.php?r=inbox/index&instance=${window.evoappInitialInstance}&chat_id=${chatId}`;
}

function toggleSearch() {
    const searchBar = document.getElementById('search-bar');
    if (searchBar.style.display === 'none') {
        searchBar.style.display = 'flex';
        document.getElementById('chat-search').focus();
    } else {
        searchBar.style.display = 'none';
    }
}

function toggleSidebar() {
    const sidebar = document.querySelector('.chat-sidebar');
    if (sidebar) {
        sidebar.classList.toggle('collapsed');
    }
}

function clearSearch() {
    document.getElementById('chat-search').value = '';
}

function newChat() {
    // Implement new chat functionality
    console.log('New chat clicked');
}

function toggleMenu() {
    // Implement menu functionality
    console.log('Menu clicked');
}

function searchInChat() {
    // Implement chat search functionality
    console.log('Search in chat clicked');
}

function showChatOptions() {
    // Implement chat options functionality
    console.log('Chat options clicked');
}

function showEmojiPicker() {
    // Implement emoji picker functionality
    console.log('Emoji picker clicked');
}

function loadMoreMessages() {
    const container = document.getElementById('load-more-container');
    const messagesContainer = document.getElementById('messages-container');
    
    // Show loading state
    container.innerHTML = '<div class="loading-more">Cargando...</div>';
    
    // Get current oldest message timestamp
    const messages = messagesContainer.querySelectorAll('.message');
    const oldestMessage = messages[0];
    const oldestTs = oldestMessage ? oldestMessage.dataset.ts : null;
    
    if (oldestTs) {
        fetch(`index.php?r=inbox/messages&instance=${window.evoappInitialInstance}&chat_id=${window.evoappInitialChat.id}&before_ts=${oldestTs}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages) {
                    // Insert new messages at the beginning
                    const fragment = document.createDocumentFragment();
                    data.messages.forEach(message => {
                        const messageEl = createMessageElement(message);
                        fragment.appendChild(messageEl);
                    });
                    
                    messagesContainer.insertBefore(fragment, messagesContainer.firstChild);
                    
                    // Hide load more button if no more messages
                    if (!data.hasMore) {
                        container.style.display = 'none';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading more messages:', error);
                container.innerHTML = '<button class="btn-load-more" onclick="loadMoreMessages()">Error - Reintentar</button>';
            });
    }
}

function createMessageElement(message) {
    const div = document.createElement('div');
    div.className = `message ${message.from_me ? 'sent' : 'received'}`;
    div.dataset.ts = message.ts;
    if (message.message_id) {
        div.dataset.messageId = message.message_id;
    }
    
    div.innerHTML = `
        <div class="message-bubble">
            ${message.participant_jid && !message.from_me && window.evoappInitialChat.is_group ? 
                `<div class="message-author">${message.display_name}</div>` : ''}
            ${message.msg_type === 'text' ? 
                `<div class="message-text">${message.body_text}</div>` : 
                renderMediaMessageHTML(message)}
            <div class="message-meta">
                <span class="message-time">${formatTime(message.ts, 'H:i')}</span>
                ${message.from_me ? 
                    `<span class="message-status">${message.status === 'read' ? '✓✓' : '✓'}</span>` : ''}
            </div>
        </div>
    `;
    
    return div;
}

function formatTime(timestamp, format) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('es-ES', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: false 
    });
}

function renderMediaMessageHTML(message) {
    const mediaUrl = message.media_url || message.local_path || '';
    const name = message.body_text || 'Archivo';
    const safeUrl = mediaUrl ? encodeURI(mediaUrl) : '';

    const buildTrigger = (inner, type) => `
        <div class="message-media-trigger" data-media-url="${safeUrl}" data-media-type="${type}" data-media-name="${name}">
            ${inner}
        </div>
    `;

    switch (message.msg_type) {
        case 'image':
            return mediaUrl ?
                buildTrigger(`<img src="${safeUrl}" style="max-width: 200px; border-radius: 0.5rem;" alt="Image">`, 'image') :
                `<div class="message-text">📷${message.body_text ? ': ' + message.body_text : ''}</div>`;
        case 'video':
            return mediaUrl ?
                buildTrigger(`<video controls style="max-width: 200px; border-radius: 0.5rem;"><source src="${safeUrl}"></video>`, 'video') :
                `<div class="message-text">🎥${message.body_text ? ': ' + message.body_text : ''}</div>`;
        case 'audio':
            return mediaUrl ?
                buildTrigger(`<audio controls style="max-width: 200px;"><source src="${safeUrl}"></audio>`, 'audio') :
                `<div class="message-text">🎵${message.body_text ? ': ' + message.body_text : ''}</div>`;
        case 'document': {
            if (!mediaUrl) {
                return `<div class="message-text">📄 ${name}</div>`;
            }
            const lowerUrl = safeUrl.toLowerCase();
            const isImageDoc = ['.jpg', '.jpeg', '.png', '.gif', '.webp'].some(ext => lowerUrl.includes(ext));
            const isPdf = lowerUrl.includes('.pdf');
            if (isImageDoc) {
                return buildTrigger(`<img src="${safeUrl}" style="max-width: 200px; border-radius: 0.5rem;" alt="Document">`, 'image');
            }
            return buildTrigger(`<div class="message-text">📄 <a href="${safeUrl}" target="_blank">${name}</a></div>`, isPdf ? 'pdf' : 'document');
        }
        case 'sticker':
            return mediaUrl ?
                buildTrigger(`<img src="${safeUrl}" style="max-width: 200px; border-radius: 0.5rem;" alt="Sticker">`, 'image') :
                `<div class="message-text">😀 Sticker</div>`;
        default:
            if (mediaUrl) {
                return buildTrigger(`<div class="message-text">📎 <a href="${safeUrl}" target="_blank">${name}</a></div>`, 'document');
            }
            return `<div class="message-text">📎 ${name}</div>`;
    }
}

function getChatDisplayName(chat) {
    const name = (chat.title && chat.title.trim()) ||
        (chat.contact_name && chat.contact_name.trim()) ||
        chat.remote_jid;
    if (!name && chat.is_group) {
        return 'Grupo sin nombre';
    }
    return name || 'Sin nombre';
}

function formatChatTime(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    });
}

function buildChatElement(chat, isActive) {
    const chatItem = document.createElement('div');
    chatItem.className = `chat-item${isActive ? ' active' : ''}`;
    chatItem.dataset.chatId = chat.id;
    chatItem.dataset.remoteJid = chat.remote_jid;
    chatItem.addEventListener('click', () => selectChat(chat.id, chat.remote_jid));

    const avatar = document.createElement('div');
    avatar.className = `chat-avatar${chat.is_group ? ' group' : ''}`;

    if (chat.avatar_url) {
        const img = document.createElement('img');
        img.src = chat.avatar_url;
        img.alt = 'Avatar';
        avatar.appendChild(img);
    } else {
        const placeholder = document.createElement('div');
        placeholder.className = `avatar-placeholder ${chat.is_group ? 'group' : 'person'}`;
        placeholder.textContent = chat.is_group ? '👥' : '👤';
        avatar.appendChild(placeholder);
    }

    const info = document.createElement('div');
    info.className = 'chat-info';

    const name = document.createElement('div');
    name.className = 'chat-name';
    name.textContent = getChatDisplayName(chat);

    const lastMessage = document.createElement('div');
    lastMessage.className = 'chat-last-message';
    const messageText = document.createElement('span');
    messageText.className = 'message-text';
    messageText.textContent = chat.last_snippet || '';
    lastMessage.appendChild(messageText);

    info.appendChild(name);
    info.appendChild(lastMessage);

    const meta = document.createElement('div');
    meta.className = 'chat-meta';

    const time = document.createElement('div');
    time.className = 'chat-time';
    time.textContent = formatChatTime(chat.last_message_at);
    meta.appendChild(time);

    if (Number(chat.unread_count || 0) > 0 || chat.has_unread) {
        const unread = document.createElement('div');
        unread.className = 'chat-unread-badge';
        unread.textContent = chat.unread_count || 1;
        meta.appendChild(unread);
    }

    chatItem.appendChild(avatar);
    chatItem.appendChild(info);
    chatItem.appendChild(meta);

    return chatItem;
}

function renderChatList(chats) {
    const chatList = document.querySelector('.chat-list');
    if (!chatList) return;

    chatList.innerHTML = '';
    chatList.dataset.page = '1';
    if (!chats.length) {
        const empty = document.createElement('div');
        empty.className = 'empty-chats';
        empty.innerHTML = `
            <div class="empty-icon">💬</div>
            <h3>No conversations yet</h3>
            <p>Start a conversation to see it here</p>
        `;
        chatList.appendChild(empty);
        return;
    }

    const activeChatId = window.evoappInitialChat?.id ? String(window.evoappInitialChat.id) : null;
    chats.forEach(chat => {
        const chatElement = buildChatElement(chat, activeChatId && String(chat.id) === activeChatId);
        chatList.appendChild(chatElement);
    });
}

function refreshChatList() {
    if (!window.evoappInitialInstance) return Promise.resolve();

    return fetch(`index.php?r=inbox/chats&instance=${window.evoappInitialInstance}&page=1`)
        .then(response => response.json())
        .then(data => {
            if (data.success && Array.isArray(data.chats)) {
                renderChatList(data.chats);
            }
        })
        .catch(error => console.error('Error refreshing chat list:', error));
}

function refreshCurrentMessages() {
    if (!window.evoappInitialChat || !window.evoappInitialChat.id) return Promise.resolve();
    const container = document.getElementById('messages-container');
    if (!container) return Promise.resolve();

    return fetch(`index.php?r=inbox/messages&instance=${window.evoappInitialInstance}&chat_id=${window.evoappInitialChat.id}&page=1`)
        .then(response => response.json())
        .then(data => {
            if (!data.success || !Array.isArray(data.messages)) return;

            const existingIds = new Set(
                Array.from(container.querySelectorAll('.message[data-message-id]'))
                    .map(el => el.dataset.messageId)
                    .filter(Boolean)
            );
            const existingTs = new Set(
                Array.from(container.querySelectorAll('.message'))
                    .map(el => el.dataset.ts)
                    .filter(Boolean)
            );

            const isNearBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 80;
            let added = false;

            data.messages.forEach(message => {
                if (message.message_id && existingIds.has(message.message_id)) {
                    return;
                }
                if (!message.message_id && message.ts && existingTs.has(message.ts)) {
                    return;
                }

                const messageEl = createMessageElement(message);
                container.appendChild(messageEl);
                added = true;
            });

            if (added && isNearBottom) {
                scrollToBottom();
            }
        })
        .catch(error => console.error('Error refreshing messages:', error));
}

function refreshInstanceStats() {
    if (!window.evoappInitialInstance) return Promise.resolve();

    return fetch(`index.php?r=inbox/stats&instance=${window.evoappInitialInstance}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.stats) return;
            const chatCount = document.getElementById('instance-chat-count');
            const unreadCount = document.getElementById('instance-unread-count');
            const messageCount = document.getElementById('instance-message-count');

            if (chatCount) chatCount.textContent = data.stats.chat_count ?? 0;
            if (unreadCount) unreadCount.textContent = data.stats.total_unread ?? 0;
            if (messageCount) messageCount.textContent = data.stats.message_count ?? 0;
        })
        .catch(error => console.error('Error refreshing stats:', error));
}

function refreshInstanceData(triggerButton = null) {
    if (triggerButton) {
        triggerButton.classList.add('is-loading');
        triggerButton.disabled = true;
    }

    return Promise.allSettled([
        refreshChatList(),
        refreshCurrentMessages(),
        refreshInstanceStats()
    ]).finally(() => {
        if (triggerButton) {
            triggerButton.classList.remove('is-loading');
            triggerButton.disabled = false;
        }
    });
}

let inboxRefreshTimer = null;

function startInboxPolling() {
    if (!window.evoappInitialInstance) return;
    if (inboxRefreshTimer) {
        clearInterval(inboxRefreshTimer);
    }
    inboxRefreshTimer = setInterval(() => {
        refreshChatList();
        refreshCurrentMessages();
        refreshInstanceStats();
    }, 7000);
}

function resolvePreviewType(type, url = '') {
    const lowerUrl = url.toLowerCase();
    if (type === 'document') {
        if (['.jpg', '.jpeg', '.png', '.gif', '.webp'].some(ext => lowerUrl.includes(ext))) {
            return 'image';
        }
        if (lowerUrl.includes('.pdf')) {
            return 'pdf';
        }
    }
    return type;
}

function showMediaPreview({ url, type, name }) {
    const preview = document.getElementById('media-preview');
    const emptyState = document.getElementById('media-preview-empty');
    const content = document.getElementById('media-preview-content');

    if (!preview || !content || !url) return;

    const resolvedType = resolvePreviewType(type, url);
    preview.classList.remove('hidden');
    emptyState.style.display = 'none';
    content.innerHTML = '';

    let mediaMarkup = '';
    switch (resolvedType) {
        case 'image':
            mediaMarkup = `<img src="${url}" alt="${name}">`;
            break;
        case 'video':
            mediaMarkup = `<video controls><source src="${url}"></video>`;
            break;
        case 'audio':
            mediaMarkup = `<audio controls><source src="${url}"></audio>`;
            break;
        case 'pdf':
            mediaMarkup = `<iframe src="${url}" style="min-height: 480px;" title="${name}"></iframe>`;
            break;
        default:
            mediaMarkup = `<a href="${url}" target="_blank">Abrir ${name}</a>`;
    }

    content.innerHTML = `
        <div><strong>${name}</strong></div>
        <div>${mediaMarkup}</div>
        <div><a href="${url}" target="_blank">Descargar archivo</a></div>
    `;
}

function clearMediaPreview() {
    const preview = document.getElementById('media-preview');
    const emptyState = document.getElementById('media-preview-empty');
    const content = document.getElementById('media-preview-content');

    if (!preview || !content || !emptyState) return;
    preview.classList.add('hidden');
    content.innerHTML = '';
    emptyState.style.display = 'block';
}

// Preload current instance for app.js
window.evoappInitialInstance = '<?= $viewHelper->escape($instance['slug']) ?>';

<?php if ($selectedChat): ?>
// Preload current chat for app.js
window.evoappInitialChat = {
    id: <?= $viewHelper->escape($selectedChat['id']) ?>,
    remoteJid: '<?= $viewHelper->escape($selectedChat['remote_jid']) ?>',
    is_group: <?= $selectedChat['is_group'] ? 'true' : 'false' ?>
};
<?php endif; ?>

// Auto-focus message input
document.addEventListener('DOMContentLoaded', () => {
    const messageInput = document.getElementById('message-input');
    if (messageInput) {
        messageInput.focus();
    }

    startInboxPolling();
    scrollToBottom();
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
    
    // Escape to close search
    if (e.key === 'Escape') {
        const searchBar = document.getElementById('search-bar');
        if (searchBar && searchBar.style.display !== 'none') {
            toggleSearch();
        }
    }
});

document.addEventListener('click', (e) => {
    const trigger = e.target.closest('.message-media-trigger');
    if (!trigger) return;

    const url = trigger.dataset.mediaUrl;
    const type = trigger.dataset.mediaType || 'document';
    const name = trigger.dataset.mediaName || 'Archivo';

    if (url) {
        showMediaPreview({ url, type, name });
    }
});

// Handle message form submission
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('message-form');
    const fileInput = document.getElementById('file-input');
    const messageInput = document.getElementById('message-input');
    
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const text = messageInput.value.trim();
            
            if (!text) return;
            
            // Send message via AJAX
            sendMessage(text);
            
            // Clear input
            messageInput.value = '';
            messageInput.focus();
        });
    }
    
    // Handle file input change
    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                uploadFile(file);
            }
        });
    }
});

// Send text message
function sendMessage(text) {
    // Validate we have a selected chat
    if (!window.evoappInitialChat || !window.evoappInitialChat.id) {
        showError('No chat selected');
        return;
    }
    
    const formData = new FormData();
    formData.append('instance', window.evoappInitialInstance);
    formData.append('chat_id', window.evoappInitialChat.id);
    formData.append('text', text);
    
    // Show sending indicator
    showSending(true);
    
    fetch('index.php?r=inbox/send', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showSending(false);
        
        if (data.success) {
            // Add message to UI
            addMessageToUI({
                from_me: true,
                msg_type: 'text',
                body_text: text,
                ts: new Date().toISOString(),
                status: 'sent'
            });
            
            // Scroll to bottom
            scrollToBottom();
        } else {
            showError(data.error || 'Error sending message');
            console.error('Send error:', data);
        }
    })
    .catch(error => {
        showSending(false);
        console.error('Network error:', error);
        showError('Network error sending message');
    });
}

// Show sending indicator
function showSending(show) {
    const form = document.getElementById('message-form');
    const sendBtn = form?.querySelector('.btn-send');
    
    if (sendBtn) {
        if (show) {
            sendBtn.innerHTML = '<span class="icon-loading">⏳</span>';
            sendBtn.disabled = true;
        } else {
            sendBtn.innerHTML = '<span class="icon-send"></span>';
            sendBtn.disabled = false;
        }
    }
}

// Show error message
function showError(message) {
    // Create error toast
    const errorDiv = document.createElement('div');
    errorDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #dc3545;
        color: white;
        padding: 1rem;
        border-radius: 4px;
        z-index: 9999;
        max-width: 300px;
    `;
    errorDiv.textContent = message;
    
    document.body.appendChild(errorDiv);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (errorDiv.parentNode) {
            errorDiv.parentNode.removeChild(errorDiv);
        }
    }, 3000);
}

// Upload and send media file
function uploadFile(file) {
    const formData = new FormData();
    formData.append('instance', window.evoappInitialInstance);
    formData.append('chat_id', window.evoappInitialChat.id);
    formData.append('media', file);
    formData.append('caption', ''); // Can be enhanced with caption input
    
    // Show uploading indicator
    showUploading(true);
    
    fetch('index.php?r=inbox/sendMedia', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showUploading(false);
        
        if (data.success) {
            // Add message to UI
            addMessageToUI({
                from_me: true,
                msg_type: data.file_info.type,
                body_text: data.file_info.name,
                media_url: data.result.media_url || null,
                ts: new Date().toISOString(),
                status: 'sent'
            });
            
            // Scroll to bottom
            scrollToBottom();
        } else {
            showError(data.error || 'Error uploading file');
        }
    })
    .catch(error => {
        showUploading(false);
        console.error('Error:', error);
        showError('Error uploading file');
    });
}

// Add message to UI
function addMessageToUI(message) {
    const messagesContainer = document.getElementById('messages-container');
    if (!messagesContainer) return;
    
    const messageEl = createMessageElement(message);
    messagesContainer.appendChild(messageEl);
    
    // Scroll to bottom
    scrollToBottom();
}

// Show uploading indicator
function showUploading(show) {
    const messageInput = document.getElementById('message-input');
    if (messageInput) {
        messageInput.placeholder = show ? 'Enviando archivo...' : 'Escribe un mensaje...';
        messageInput.disabled = show;
    }
}

// Scroll to bottom of messages
function scrollToBottom() {
    const messagesContainer = document.getElementById('messages-container');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
}

// Show error message
function showError(message) {
    // Simple error display - can be enhanced with toast notifications
    alert(message);
    console.error(message);
}

// Enhanced emoji picker (simplified)
function showEmojiPicker() {
    const commonEmojis = ['😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🥰', '😘', '😚', '😙', '🥲', '😱', '😨', '😰', '😥', '😓', '😭', '😪', '😵', '🤔', '🤗', '🤤', '🤢', '👍', '👎', '👌', '🙌', '🌟', '⭐', '💫', '💥', '💢', '💯', '🚀', '🎉', '🎊', '🎈', '🎆', '🎃', '🎂', '🎄', '🎁', '🎀', '🎯', '🎰', '🎱', '🎲', '🎮', '🎰', '🎲', '🎸', '🎺', '🎻', '🎼', '🎽', '🎾', '🎿', '🏀', '🏁', '🏂', '🏃', '🏄', '🏅', '🏆', '🏇', '🏈', '🏉', '🏊', '🏋', '🏌', '🏍', '🏎', '🏏', '🏐', '🏑', '🏒', '🏓', '🏔', '🏕', '🏖', '🏗', '🏘', '🏙', '🏚', '🏛', '🏜', '🏝', '🏞', '🏟', '🏠', '🏡', '🏢', '🏣', '🏤', '🏥', '🏦', '🏧', '🏨', '🏩', '🏪', '🏫', '🏬', '🏭', '🏮', '🏯', '🏰', '🏱', '🏲', '🏳', '🏴', '🏵', '🐶', '🐸', '🦀', '🦁', '🦂', '🦃', '🦄', '🦅', '🦆', '🦇', '🦈', '🦉', '🦊', '🦋', '🦌', '🦍', '🦎', '🦏', '🦐', '🦑', '🦒', '🦓', '🦔', '🦕', '🦖', '🦗', '🦘', '🦙', '🦚', '🦛', '🦜', '🦝', '🦞', '🦟', '🦠', '🦡', '🦢', '🦣', '🦤', '🦥', '🦦', '🦧', '🦨', '🦩', '🦪', '🦫', '🦭', '🦮', '🦯', '🦰', '🦱', '🦲', '🦳', '🦴', '🦵', '🦶', '🦷', '🦸', '🦹', '🦺', '🦻', '🦼', '🦽', '🦾', '🦿', '🧀', '🧁', '🧂', '🧃', '🧄', '🧅', '🧆', '🧇', '🧈', '🧉', '🧊', '🧋', '🧌', '🧍', '🧎', '🧏', '🧐', '🧑', '🧒', '🧓', '🧔', '🧕', '🧖', '🧗', '🧘', '🧙', '🧚', '🧛', '🧜', '🧝', '🧞', '🧟', '🧠', '🧡', '🧢', '🧣', '🧤', '🧥', '🧦', '🧧', '🧨', '🧩', '🧪', '🧫', '🧭', '🧮', '🧯', '🧰', '🧱', '🧲', '🧳', '🧴', '🧵', '🧶', '🧷', '🧸', '🧹', '🧺', '🧻', '🧼', '🧽', '🧾', '🧿', '🩀', '🩁', '🩂', '🩃', '🩄', '🩅', '🩆', '🩇', '🩈', '🩉', '🩊', '🩋', '🩌', '🩍', '🩎', '🩏', '🩐', '🩑', '🩒', '🩓', '🩔', '🩕', '🩖', '🩗', '🩘', '🩙', '🩚', '🩛', '🩜', '🩝', '🩞', '🩟', '🩠', '🩡', '🩢', '🩣', '🩤', '🩥', '🩦', '🩧', '🩨', '🩩', '🩪', '🩫', '🩭', '🩮', '🩯', '🩰', '🩱', '🩲', '🩳', '🩴', '🩵', '🩶', '🩷', '🩸', '🩹', '🩺', '🩻', '🩼', '🩽', '🩾', '🩿', '🪀', '🪁', '🪂', '🪃', '🪄', '🪅', '🪆', '🪇', '🪈', '🪉', '🪊', '🪋', '🪌', '🪍', '🪎', '🪏', '🪐', '🪑', '🪒', '🪓', '🪔', '🪕', '🪖', '🪗', '🪘', '🪙', '🪚', '🪛', '🪜', '🪝', '🪞', '🪟', '🪠', '🪡', '🪢', '🪣', '🪤', '🪥', '🪦', '🪧', '🪨', '🪩', '🪪', '🪫', '🪭', '🪮', '🪯', '🪰', '🪱', '🪲', '🪳', '🪴', '🪵', '🪶', '🪷', '🪸', '🪹', '🪺', '🪻', '🪼', '🪽', '🪾', '🪿', '🫀', '🫁', '🫂', '🫃', '🫄', '🫅', '🫆', '🫇', '🫈', '🫉', '🫊', '🫋', '🫌', '🫍', '🫎', '🫏', '🫐', '🫑', '🫒', '🫓', '🫔', '🫕', '🫖', '🫗', '🫘', '🫙', '🫚', '🫛', '🫜', '🫝', '🫞', '🫟', '🫠', '🫡', '🫢', '🫣', '🫤', '🫥', '🫦', '🫧', '🫨', '🫩', '🫪', '🫫', '🫭', '🫮', '🫯', '🫰', '🫱', '🫲', '🫳', '🫴', '🫵', '🫶', '🫷', '🫸', '🫹', '🫺', '🫻', '🫼', '🫽', '🫾', '🫿', '🬀', '🬁', '🬂', '🬃', '🬄', '🬅', '🬆', '🬇', '🬈', '🬉', '🬊', '🬋', '🬌', '🬍', '🬎', '🬏', '🬐', '🬑', '🬒', '🬓', '🬔', '🬕', '🬖', '🬗', '🬘', '🬙', '🬚', '🬛', '🬜', '🬝', '🬞', '🬟', '🬠', '🬡', '🬢', '🬣', '🬤', '🬥', '🬦', '🬧', '🬨', '🬩', '🬪', '🬫', '🬭', '🬮', '🬯', '🬰', '🬱', '🬲', '🬳', '🬴', '🬵', '🬶', '🬷', '🬸', '🬹', '🬺', '🬻', '🬼', '🬽', '🬾', '🬿', '🭀', '🭁', '🭂', '🭃', '🭄', '🭅', '🭆', '🭇', '🭈', '🭉', '🭊', '🭋', '🭌', '🭍', '🭎', '🭏', '🭐', '🭑', '🭒', '🭓', '🭔', '🭕', '🭖', '🭗', '🭘', '🭙', '🭚', '🭛', '🭜', '🭝', '🭞', '🭟', '🭠', '🭡', '🭢', '🭣', '🭤', '🭥', '🭦', '🭧', '🭨', '🭩', '🭪', '🭫', '🭭', '🭮', '🭯', '🭰', '🭱', '🭲', '🭳', '🭴', '🭵', '🭶', '🭷', '🭸', '🭹', '🭺', '🭻', '🭼', '🭽', '🭾', '🭿', '🮀', '🮁', '🮂', '🮃', '🮄', '🮅', '🮆', '🮇', '🮈', '🮉', '🮊', '🮋', '🮌', '🮍', '🮎', '🮏', '🮐', '🮑', '🮒', '🮓', '🮔', '🮕', '🮖', '🮗', '🮘', '🮙', '🮚', '🮛', '🮜', '🮝', '🮞', '🮟', '🮠', '🮡', '🮢', '🮣', '🮤', '🮥', '🮦', '🮧', '🮨', '🮩', '🮪', '🮫', '🮭', '🮮', '🮯', '🮰', '🮱', '🮲', '🮳', '🮴', '🮵', '🮶', '🮷', '🮸', '🮹', '🮺', '🮻', '🮼', '🮽', '🮾', '🮿', '🯀', '🯁', '🯂', '🯃', '🯄', '🯅', '🯆', '🯇', '🯈', '🯉', '🯊', '🯋', '🯌', '🯍', '🯎', '🯏', '🯐', '🯑', '🯒', '🯓', '🯔', '🯕', '🯖', '🯗', '🯘', '🯙', '🯚', '🯛', '🯜', '🯝', '🯞', '🯟', '🯠', '🯡', '🯢', '🯣', '🯤', '🯥', '🯦', '🯧', '🯨', '🯩', '🯪', '🯫', '🯭', '🯮', '🯯', '🯰', '🯱', '🯲', '🯳', '🯴', '🯵', '🯶', '🯷', '🯸', '🯹', '🯺', '🯻', '🯼', '🯽', '🯾', '🯿'];
    
    // Create simple emoji picker
    const emojiPicker = document.createElement('div');
    emojiPicker.className = 'emoji-picker';
    emojiPicker.innerHTML = `
        <div class="emoji-picker-header">
            <span>Selecciona un emoji</span>
            <button class="close-picker" onclick="this.parentElement.remove()">×</button>
        </div>
        <div class="emoji-grid">${commonEmojis.map(emoji => 
            `<span class="emoji-item" onclick="insertEmoji('${emoji}')">${emoji}</span>`
        ).join('')}</div>
    `;
    
    document.body.appendChild(emojiPicker);
    
    // Close when clicking outside
    setTimeout(() => {
        document.addEventListener('click', (e) => {
            if (!emojiPicker.contains(e.target)) {
                emojiPicker.remove();
            }
        });
    }, 100);
}

// Insert emoji into input
function insertEmoji(emoji) {
    const messageInput = document.getElementById('message-input');
    if (messageInput) {
        messageInput.value += emoji;
        messageInput.focus();
    }
}
</script>

<?php
// Helper function for rendering media messages
function renderMediaMessage($message) {
    $content = '';
    $mediaUrl = $message['local_path'] ?: $message['media_url'];
    $safeUrl = $mediaUrl ? htmlspecialchars($mediaUrl, ENT_QUOTES, 'UTF-8') : null;
    $mediaName = htmlspecialchars($message['body_text'] ?: 'Archivo', ENT_QUOTES, 'UTF-8');
    $dataAttributes = $safeUrl ? ' data-media-url="' . $safeUrl . '" data-media-name="' . $mediaName . '"' : '';
    $extension = $safeUrl ? strtolower(pathinfo(parse_url($safeUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)) : '';
    $isImageDoc = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    $isPdf = $extension === 'pdf';
    
    switch ($message['msg_type']) {
        case 'image':
            if ($safeUrl) {
                $content = '<div class="message-media-trigger" data-media-type="image"' . $dataAttributes . '>'
                    . '<img src="' . $safeUrl . '" style="max-width: 200px; border-radius: 0.5rem;" alt="Image"></div>';
            } else {
                $content = '<div class="message-text">📷 Image' . ($message['body_text'] ? ': ' . htmlspecialchars($message['body_text'], ENT_QUOTES, 'UTF-8') : '') . '</div>';
            }
            break;
            
        case 'video':
            if ($safeUrl) {
                $content = '<div class="message-media-trigger" data-media-type="video"' . $dataAttributes . '>'
                    . '<video controls style="max-width: 200px; border-radius: 0.5rem;"><source src="' . $safeUrl . '"></video></div>';
            } else {
                $content = '<div class="message-text">🎥 Video' . ($message['body_text'] ? ': ' . htmlspecialchars($message['body_text'], ENT_QUOTES, 'UTF-8') : '') . '</div>';
            }
            break;
            
        case 'audio':
            if ($safeUrl) {
                $content = '<div class="message-media-trigger" data-media-type="audio"' . $dataAttributes . '>'
                    . '<audio controls style="max-width: 200px;"><source src="' . $safeUrl . '"></audio></div>';
            } else {
                $content = '<div class="message-text">🎵 Audio' . ($message['body_text'] ? ': ' . htmlspecialchars($message['body_text'], ENT_QUOTES, 'UTF-8') : '') . '</div>';
            }
            break;
            
        case 'document':
            if ($safeUrl) {
                $type = $isImageDoc ? 'image' : ($isPdf ? 'pdf' : 'document');
                $content = '<div class="message-media-trigger" data-media-type="' . $type . '"' . $dataAttributes . '>';
                if ($isImageDoc) {
                    $content .= '<img src="' . $safeUrl . '" style="max-width: 200px; border-radius: 0.5rem;" alt="Document">';
                } else {
                    $content .= '<div class="message-text">📄 <a href="' . $safeUrl . '" target="_blank">' . $mediaName . '</a></div>';
                }
                $content .= '</div>';
            } else {
                $content = '<div class="message-text">📄 ' . $mediaName . '</div>';
            }
            break;

        case 'sticker':
            if ($safeUrl) {
                $content = '<div class="message-media-trigger" data-media-type="image"' . $dataAttributes . '>'
                    . '<img src="' . $safeUrl . '" style="max-width: 200px; border-radius: 0.5rem;" alt="Sticker"></div>';
            } else {
                $content = '<div class="message-text">😀 Sticker</div>';
            }
            break;
            
        default:
            if ($safeUrl) {
                $content = '<div class="message-media-trigger" data-media-type="document"' . $dataAttributes . '>'
                    . '<div class="message-text">📎 <a href="' . $safeUrl . '" target="_blank">' . $mediaName . '</a></div>'
                    . '</div>';
            } else {
                $content = '<div class="message-text">📎 ' . $mediaName . '</div>';
            }
    }
    
    return $content;
}
?>
