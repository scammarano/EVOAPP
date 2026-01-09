/**
 * EVOAPP - Main JavaScript
 * WhatsApp-like functionality
 */

class EVOAPP {
    constructor() {
        this.currentInstance = null;
        this.currentChat = null;
        this.lastEventId = 0;
        this.pollingInterval = null;
        this.init();
    }

    init() {
        this.initCurrentInstance();
        if (window.evoappInitialChat && window.evoappInitialChat.id && window.evoappInitialChat.remoteJid) {
            this.currentChat = {
                id: String(window.evoappInitialChat.id),
                remoteJid: String(window.evoappInitialChat.remoteJid)
            };
        }
        this.setupEventListeners();
        if (!window.evoappDisablePolling) {
            this.startPolling();
        }
    }

    initCurrentInstance() {
        try {
            if (window.evoappInitialInstance) {
                this.currentInstance = window.evoappInitialInstance;
                return;
            }

            const params = new URLSearchParams(window.location.search);
            const instanceFromQuery = params.get('instance');
            if (instanceFromQuery) {
                this.currentInstance = instanceFromQuery;
                return;
            }

            const instanceFromBody = document.body ? document.body.dataset.instance : '';
            if (instanceFromBody) {
                this.currentInstance = instanceFromBody;
            }
        } catch (e) {
            // ignore
        }
    }

    setupEventListeners() {
        // Chat selection
        document.addEventListener('click', (e) => {
            if (e.target.closest('.chat-item')) {
                this.selectChat(e.target.closest('.chat-item'));
            }
        });

        // Message sending
        const messageForm = document.getElementById('message-form');
        if (messageForm) {
            messageForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.sendMessage();
            });
        }

        // File attachment
        const fileInput = document.getElementById('file-input');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                this.handleFileSelect(e);
            });
        }

        // Infinite scroll for chats
        const chatList = document.querySelector('.chat-list');
        if (chatList) {
            chatList.addEventListener('scroll', () => {
                if (chatList.scrollTop + chatList.clientHeight >= chatList.scrollHeight - 100) {
                    this.loadMoreChats();
                }
            });
        }

        // Infinite scroll for messages
        const messagesContainer = document.querySelector('.messages-container');
        if (messagesContainer) {
            messagesContainer.addEventListener('scroll', () => {
                if (messagesContainer.scrollTop <= 100) {
                    this.loadMoreMessages();
                }
            });
        }

        // Tab switching
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('instance-tab')) {
                this.switchTab(e.target);
            }
        });
    }

    selectChat(chatElement) {
        // Remove active class from previous chat
        document.querySelectorAll('.chat-item').forEach(item => {
            item.classList.remove('active');
        });

        // Add active class to selected chat
        chatElement.classList.add('active');

        // Get chat data
        const chatId = chatElement.dataset.chatId;
        const remoteJid = chatElement.dataset.remoteJid;

        if (!chatId || !remoteJid) return;

        this.currentChat = {
            id: chatId,
            remoteJid: remoteJid
        };

        // Load messages
        this.loadMessages(chatId);

        // Mark as read
        this.markAsRead(chatId);

        // Update conversation header
        const chatName = chatElement.querySelector('.chat-name').textContent;
        document.querySelector('.conversation-title').textContent = chatName;
    }

    async loadMessages(chatId, page = 1) {
        try {
            const response = await fetch(`index.php?r=inbox/messages&instance=${this.currentInstance}&chat_id=${chatId}&page=${page}`);
            const data = await response.json();

            if (data.success) {
                this.renderMessages(data.messages, page === 1);
            }
        } catch (error) {
            console.error('Error loading messages:', error);
            this.showError('Error loading messages');
        }
    }

    async loadMoreMessages() {
        if (!this.currentChat) return;

        const firstMessage = document.querySelector('.message');
        if (!firstMessage) return;

        const beforeTs = firstMessage.dataset.ts;

        try {
            const response = await fetch(`index.php?r=inbox/messages&instance=${this.currentInstance}&chat_id=${this.currentChat.id}&before_ts=${beforeTs}`);
            const data = await response.json();

            if (data.success && data.messages.length > 0) {
                const messagesContainer = document.querySelector('.messages-container');
                const scrollHeight = messagesContainer.scrollHeight;

                data.messages.forEach(message => {
                    const messageElement = this.createMessageElement(message);
                    messagesContainer.insertBefore(messageElement, messagesContainer.firstChild);
                });

                // Maintain scroll position
                messagesContainer.scrollTop = messagesContainer.scrollHeight - scrollHeight;
            }
        } catch (error) {
            console.error('Error loading more messages:', error);
        }
    }

    renderMessages(messages, clear = true) {
        const container = document.querySelector('.messages-container');
        if (!container) return;

        if (clear) {
            container.innerHTML = '';
        }

        messages.forEach(message => {
            const messageElement = this.createMessageElement(message);
            container.appendChild(messageElement);
        });

        // Scroll to bottom
        container.scrollTop = container.scrollHeight;
    }

    createMessageElement(message) {
        const div = document.createElement('div');
        div.className = `message ${message.from_me ? 'sent' : 'received'}`;
        div.dataset.ts = message.ts;

        let bubbleContent = '';

        // Add author for group messages
        if (message.participant_jid && !message.from_me) {
            bubbleContent += `<div class="message-author">${message.display_name}</div>`;
        }

        // Add message content
        if (message.msg_type === 'text') {
            bubbleContent += `<div class="message-text">${this.escapeHtml(message.body_text)}</div>`;
        } else {
            bubbleContent += this.renderMediaMessage(message);
        }

        // Add metadata
        bubbleContent += `
            <div class="message-meta">
                <span>${this.formatTime(message.ts)}</span>
                ${message.from_me ? '<span>✓✓</span>' : ''}
            </div>
        `;

        div.innerHTML = `<div class="message-bubble">${bubbleContent}</div>`;

        return div;
    }

    renderMediaMessage(message) {
        let content = '';

        switch (message.msg_type) {
            case 'image':
                if (message.media_url) {
                    content = `<img src="${message.media_url}" style="max-width: 200px; border-radius: 0.5rem;" alt="Image">`;
                } else {
                    content = `<div class="message-text">📷 Image${message.body_text ? ': ' + message.body_text : ''}</div>`;
                }
                break;

            case 'video':
                if (message.media_url) {
                    content = `<video controls style="max-width: 200px; border-radius: 0.5rem;"><source src="${message.media_url}"></video>`;
                } else {
                    content = `<div class="message-text">🎥 Video${message.body_text ? ': ' + message.body_text : ''}</div>`;
                }
                break;

            case 'audio':
                if (message.media_url) {
                    content = `<audio controls style="max-width: 200px;"><source src="${message.media_url}"></audio>`;
                } else {
                    content = `<div class="message-text">🎵 Audio${message.body_text ? ': ' + message.body_text : ''}</div>`;
                }
                break;

            case 'document':
                if (message.media_url) {
                    content = `<div class="message-text">📄 <a href="${message.media_url}" target="_blank">${message.body_text || 'Document'}</a></div>`;
                } else {
                    content = `<div class="message-text">📄 ${message.body_text || 'Document'}</div>`;
                }
                break;

            default:
                content = `<div class="message-text">📎 ${message.body_text || 'Media'}</div>`;
        }

        return content;
    }

    async sendMessage() {
        const input = document.getElementById('message-input');
        const text = input.value.trim();

        if (!text || !this.currentChat) return;

        try {
            const formData = new FormData();
            formData.append('instance', this.currentInstance);
            formData.append('chat_id', this.currentChat.id);
            formData.append('text', text);

            const response = await fetch('index.php?r=inbox/send', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                input.value = '';
                // Message will appear via webhook polling
            } else {
                this.showError(data.error || 'Error sending message');
            }
        } catch (error) {
            console.error('Error sending message:', error);
            this.showError('Error sending message');
        }
    }

    async sendMedia(file, caption = '') {
        if (!this.currentChat) return;

        try {
            const formData = new FormData();
            formData.append('instance', this.currentInstance);
            formData.append('chat_id', this.currentChat.id);
            formData.append('media', file);
            formData.append('caption', caption);

            const response = await fetch('index.php?r=inbox/sendMedia', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Message will appear via webhook polling
            } else {
                this.showError(data.error || 'Error sending media');
            }
        } catch (error) {
            console.error('Error sending media:', error);
            this.showError('Error sending media');
        }
    }

    handleFileSelect(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Check file size
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (file.size > maxSize) {
            this.showError('File too large. Maximum size is 10MB.');
            return;
        }

        // Send media
        this.sendMedia(file);

        // Clear input
        event.target.value = '';
    }

    async markAsRead(chatId) {
        try {
            const formData = new FormData();
            formData.append('chat_id', chatId);

            await fetch('index.php?r=inbox/markRead', {
                method: 'POST',
                body: formData
            });
        } catch (error) {
            console.error('Error marking as read:', error);
        }
    }

    async loadMoreChats() {
        const chatList = document.querySelector('.chat-list');
        if (!chatList) return;

        const currentPage = parseInt(chatList.dataset.page || '1');
        const nextPage = currentPage + 1;

        try {
            const response = await fetch(`index.php?r=inbox/chats&instance=${this.currentInstance}&page=${nextPage}`);
            const data = await response.json();

            if (data.success && data.chats.length > 0) {
                data.chats.forEach(chat => {
                    const chatElement = this.createChatElement(chat);
                    chatList.appendChild(chatElement);
                });

                chatList.dataset.page = nextPage;
            }
        } catch (error) {
            console.error('Error loading more chats:', error);
        }
    }

    createChatElement(chat) {
        const div = document.createElement('div');
        div.className = 'chat-item';
        div.dataset.chatId = chat.id;
        div.dataset.remoteJid = chat.remote_jid;

        const unreadBadge = chat.has_unread ? '<div class="chat-unread">!</div>' : '';

        div.innerHTML = `
            <div class="chat-avatar ${chat.is_group ? 'group' : ''}">
                <span class="icon-${chat.is_group ? 'group' : 'person'}"></span>
            </div>
            <div class="chat-info">
                <div class="chat-name">${this.escapeHtml(chat.title || chat.remote_jid)}</div>
                <div class="chat-last-message">${this.escapeHtml(chat.last_snippet || '')}</div>
            </div>
            <div class="chat-meta">
                <div class="chat-time">${this.formatTime(chat.last_message_at)}</div>
                ${unreadBadge}
            </div>
        `;

        return div;
    }

    switchTab(tabElement) {
        // Remove active class from all tabs
        document.querySelectorAll('.instance-tab').forEach(tab => {
            tab.classList.remove('active');
        });

        // Add active class to clicked tab
        tabElement.classList.add('active');

        // Get tab route
        const route = tabElement.dataset.route;
        if (route) {
            window.location.href = `index.php?r=${route}&instance=${this.currentInstance}`;
        }
    }

    startPolling() {
        // Poll for new events every 3 seconds
        this.pollingInterval = setInterval(() => {
            this.pollEvents();
        }, 3000);
    }

    async pollEvents() {
        if (!this.currentInstance) return;

        try {
            const response = await fetch(`index.php?r=webhook/events&instance=${this.currentInstance}&since=${this.lastEventId}`);
            const data = await response.json();

            if (data.success && data.events.length > 0) {
                this.processEvents(data.events);
                this.lastEventId = Math.max(...data.events.map(e => e.id));
            }
        } catch (error) {
            console.error('Error polling events:', error);
        }
    }

    processEvents(events) {
        events.forEach(event => {
            // Update chat list if needed
            if (event.event_type === 'messages.upsert') {
                this.updateChatList(event);
                
                // If it's the current chat, add message
                if (event.remote_jid === this.currentChat?.remoteJid) {
                    this.addNewMessage(event);
                }
            }
        });
    }

    updateChatList(event) {
        // Find and update chat in list
        const chatElements = document.querySelectorAll('.chat-item');
        let foundChat = null;

        chatElements.forEach(element => {
            if (element.dataset.remoteJid === event.remote_jid) {
                foundChat = element;
            }
        });

        if (foundChat) {
            // Update last message and move to top
            const lastMessageElement = foundChat.querySelector('.chat-last-message');
            const timeElement = foundChat.querySelector('.chat-time');
            
            if (lastMessageElement) {
                lastMessageElement.textContent = this.getMessageSnippet(event.payload);
            }
            
            if (timeElement) {
                timeElement.textContent = this.formatTime(new Date());
            }

            // Move to top
            const chatList = document.querySelector('.chat-list');
            chatList.insertBefore(foundChat, chatList.firstChild);
        } else {
            // Add new chat to top
            // This would require fetching the full chat data
            this.refreshChatList();
        }
    }

    addNewMessage(event) {
        const message = {
            from_me: event.payload.data.key.fromMe,
            msg_type: this.getMessageType(event.payload.data.message),
            body_text: this.getMessageBody(event.payload.data.message),
            ts: new Date(event.payload.data.messageTimestamp).toISOString(),
            participant_jid: event.payload.data.participant
        };

        const messageElement = this.createMessageElement(message);
        const container = document.querySelector('.messages-container');
        container.appendChild(messageElement);
        container.scrollTop = container.scrollHeight;
    }

    refreshChatList() {
        // Reload the entire chat list
        window.location.href = window.location.pathname + window.location.search;
    }

    getMessageType(message) {
        if (message.conversation) return 'text';
        if (message.imageMessage) return 'image';
        if (message.videoMessage) return 'video';
        if (message.audioMessage) return 'audio';
        if (message.documentMessage) return 'document';
        return 'unknown';
    }

    getMessageBody(message) {
        if (message.conversation) return message.conversation;
        if (message.imageMessage?.caption) return message.imageMessage.caption;
        if (message.videoMessage?.caption) return message.videoMessage.caption;
        if (message.documentMessage?.caption) return message.documentMessage.caption;
        return '';
    }

    getMessageSnippet(event) {
        const message = event.payload.data.message;
        const body = this.getMessageBody(message);
        if (body) return body.substring(0, 50);

        const type = this.getMessageType(message);
        switch (type) {
            case 'image': return '📷 Image';
            case 'video': return '🎥 Video';
            case 'audio': return '🎵 Audio';
            case 'document': return '📄 Document';
            default: return '📎 Media';
        }
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        if (diff < 60000) return 'now';
        if (diff < 3600000) return Math.floor(diff / 60000) + ' min';
        if (diff < 86400000) return Math.floor(diff / 3600000) + ' h';
        
        return date.toLocaleDateString();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showError(message) {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = 'alert alert-error';
        toast.textContent = message;
        toast.style.position = 'fixed';
        toast.style.top = '20px';
        toast.style.right = '20px';
        toast.style.zIndex = '9999';
        toast.style.maxWidth = '300px';

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 5000);
    }
}

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.evoapp = new EVOAPP();
});

// Utility functions
function showLoading(element) {
    element.innerHTML = '<div class="loading"><div class="spinner"></div> Loading...</div>';
}

function hideLoading(element, content) {
    element.innerHTML = content;
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
