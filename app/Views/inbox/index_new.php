<?php
// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .chat-item {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .chat-item:hover {
            background-color: #f8f9fa;
        }
        .chat-item.active {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        .message-bubble {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 15px;
            margin-bottom: 10px;
            word-wrap: break-word;
        }
        .message-sent {
            background-color: #dcf8c6;
            margin-left: auto;
            text-align: right;
        }
        .message-received {
            background-color: #ffffff;
            border: 1px solid #e3e3e3;
        }
        .send-options {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .media-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        .typing-indicator {
            display: none;
            color: #666;
            font-style: italic;
            padding: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard">
                <i class="fas fa-comments mr-2"></i><?= APP_NAME ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard">
                            <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/instances">
                            <i class="fas fa-server mr-1"></i> Instancias
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/inbox">
                            <i class="fas fa-comments mr-1"></i> Inbox
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/campaigns">
                            <i class="fas fa-bullhorn mr-1"></i> Campañas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/contacts">
                            <i class="fas fa-address-book mr-1"></i> Contactos
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user mr-1"></i> <?= $_SESSION['user_name'] ?? 'Usuario' ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/profile">Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-3">
        <div class="row">
            <!-- Chat List -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-comments mr-2"></i>Chats</h5>
                    </div>
                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                        <div id="chatList">
                            <!-- Chats se cargarán dinámicamente -->
                            <div class="text-center p-3">
                                <i class="fas fa-spinner fa-spin"></i> Cargando chats...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header" id="chatHeader">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0" id="chatTitle">
                                <i class="fas fa-comments mr-2"></i>Selecciona un chat
                            </h5>
                            <div id="chatActions" style="display: none;">
                                <button class="btn btn-sm btn-outline-primary" onclick="refreshMessages()">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="height: 500px; overflow-y: auto;" id="messagesContainer">
                        <div class="text-center p-5">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Selecciona un chat para comenzar</h5>
                        </div>
                    </div>
                    <div class="card-footer">
                        <!-- Send Options -->
                        <div id="sendOptions" style="display: none;">
                            <div class="send-options mb-3">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <label class="form-label">Modo de Envío:</label>
                                        <select class="form-select" id="sendMode">
                                            <option value="media_only">Solo imagen + caption</option>
                                            <option value="media_with_text">Imagen + caption + texto</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Archivo:</label>
                                        <input type="file" class="form-control" id="mediaFile" accept="image/*,video/*,.pdf">
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <label class="form-label">Caption (opcional):</label>
                                        <textarea class="form-control" id="mediaCaption" rows="2" placeholder="Caption para el adjunto..."></textarea>
                                    </div>
                                </div>
                                <div class="row mt-2" id="textRow" style="display: none;">
                                    <div class="col-md-12">
                                        <label class="form-label">Mensaje de texto:</label>
                                        <textarea class="form-control" id="messageText" rows="2" placeholder="Tu mensaje de texto..."></textarea>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-primary" onclick="sendMedia()">
                                                <i class="fas fa-paper-plane mr-1"></i>Enviar
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="clearMediaForm()">
                                                <i class="fas fa-times mr-1"></i>Limpiar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Message Input -->
                        <div id="messageInput" style="display: none;">
                            <div class="input-group">
                                <input type="text" class="form-control" id="messageInputField" placeholder="Escribe un mensaje...">
                                <button class="btn btn-primary" onclick="sendMessage()">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Typing Indicator -->
                        <div class="typing-indicator" id="typingIndicator">
                            <i class="fas fa-ellipsis-h"></i> Escribiendo...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentChatId = null;
        let currentInstance = null;

        // Cargar chats al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadChats();
            
            // Event listeners
            document.getElementById('sendMode').addEventListener('change', function() {
                const textRow = document.getElementById('textRow');
                if (this.value === 'media_with_text') {
                    textRow.style.display = 'block';
                } else {
                    textRow.style.display = 'none';
                }
            });
        });

        function loadChats() {
            fetch('/api/chats?instance=<?= $_GET['instance'] ?? '' ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayChats(data.chats);
                    }
                })
                .catch(error => {
                    console.error('Error loading chats:', error);
                });
        }

        function displayChats(chats) {
            const chatList = document.getElementById('chatList');
            chatList.innerHTML = '';
            
            if (chats.length === 0) {
                chatList.innerHTML = '<div class="text-center p-3">No hay chats</div>';
                return;
            }
            
            chats.forEach(chat => {
                const chatItem = document.createElement('div');
                chatItem.className = 'chat-item p-3 border-bottom';
                chatItem.onclick = () => selectChat(chat.id);
                
                const lastMessage = chat.last_message || '';
                const time = chat.last_message_time ? new Date(chat.last_message_time).toLocaleTimeString() : '';
                
                chatItem.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            ${chat.name ? chat.name.charAt(0).toUpperCase() : chat.phone.charAt(0)}
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">${chat.name || chat.phone}</div>
                            <div class="text-muted small">${lastMessage}</div>
                        </div>
                        <div class="text-muted small">${time}</div>
                    </div>
                `;
                
                chatList.appendChild(chatItem);
            });
        }

        function selectChat(chatId) {
            currentChatId = chatId;
            
            // Actualizar UI
            document.querySelectorAll('.chat-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Mostrar opciones de envío
            document.getElementById('sendOptions').style.display = 'block';
            document.getElementById('messageInput').style.display = 'block';
            document.getElementById('chatActions').style.display = 'block';
            
            // Cargar mensajes
            loadMessages(chatId);
        }

        function loadMessages(chatId) {
            document.getElementById('messagesContainer').innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Cargando mensajes...</div>';
            
            fetch(`/api/messages?instance=<?= $_GET['instance'] ?? '' ?>&chat_id=${chatId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayMessages(data.messages);
                    }
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                });
        }

        function displayMessages(messages) {
            const container = document.getElementById('messagesContainer');
            container.innerHTML = '';
            
            messages.forEach(message => {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message-bubble ${message.direction === 'sent' ? 'message-sent' : 'message-received'}`;
                
                if (message.media) {
                    messageDiv.innerHTML = `
                        <div class="media-preview">
                            <img src="${message.media.url}" alt="Media" style="max-width: 100%; border-radius: 10px;">
                        </div>
                        <div>${message.text || ''}</div>
                        <div class="text-muted small">${formatTime(message.timestamp)}</div>
                    `;
                } else {
                    messageDiv.innerHTML = `
                        <div>${message.text}</div>
                        <div class="text-muted small">${formatTime(message.timestamp)}</div>
                    `;
                }
                
                container.appendChild(messageDiv);
            });
            
            // Scroll al final
            container.scrollTop = container.scrollHeight;
        }

        function sendMessage() {
            const input = document.getElementById('messageInputField');
            const text = input.value.trim();
            
            if (!text || !currentChatId) return;
            
            const formData = new FormData();
            formData.append('instance', '<?= $_GET['instance'] ?? '' ?>');
            formData.append('chat_id', currentChatId);
            formData.append('text', text);
            
            fetch('/api/send', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadMessages(currentChatId);
                } else {
                    alert('Error al enviar mensaje: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Error al enviar mensaje');
            });
        }

        function sendMedia() {
            const fileInput = document.getElementById('mediaFile');
            const caption = document.getElementById('mediaCaption').value;
            const text = document.getElementById('messageText').value;
            const sendMode = document.getElementById('sendMode').value;
            
            if (!fileInput.files[0] || !currentChatId) return;
            
            const formData = new FormData();
            formData.append('instance', '<?= $_GET['instance'] ?? '' ?>');
            formData.append('chat_id', currentChatId);
            formData.append('media', fileInput.files[0]);
            formData.append('caption', caption);
            formData.append('text', text);
            formData.append('send_mode', sendMode);
            
            fetch('/api/send-media', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    clearMediaForm();
                    loadMessages(currentChatId);
                    
                    // Mostrar resultado
                    let message = 'Archivo enviado correctamente';
                    if (data.send_mode === 'media_with_text') {
                        message = 'Texto y archivo enviados correctamente';
                    }
                    
                    // Mostrar notificación
                    showNotification(message, 'success');
                } else {
                    alert('Error al enviar archivo: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error sending media:', error);
                alert('Error al enviar archivo');
            });
        }

        function clearMediaForm() {
            document.getElementById('mediaFile').value = '';
            document.getElementById('mediaCaption').value = '';
            document.getElementById('messageText').value = '';
            document.getElementById('sendMode').value = 'media_only';
            document.getElementById('textRow').style.display = 'none';
        }

        function refreshMessages() {
            if (currentChatId) {
                loadMessages(currentChatId);
            }
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        function formatTime(timestamp) {
            return new Date(timestamp).toLocaleTimeString();
        }

        // Prevenir refresh automático
        document.addEventListener('DOMContentLoaded', function() {
            if (window.refreshInterval) {
                clearInterval(window.refreshInterval);
                window.refreshInterval = null;
            }
        });
    </script>
</body>
</html>
