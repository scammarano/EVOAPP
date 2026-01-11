<?php
// Test COMPLETO de Webhooks - TODOS los eventos Evolution API
?>
<!DOCTYPE html>
<html>
<head>
    <title>Webhook Complete Test - All Events</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .events-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0; }
        .event-card { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff; }
        .event-card h4 { margin: 0 0 10px 0; color: #495057; }
        .event-card p { margin: 5px 0; font-size: 14px; color: #6c757d; }
        .test-button { background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; margin: 5px 5px 5px 0; }
        .test-button:hover { background: #218838; }
        .test-all { background: #dc3545; color: white; padding: 15px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; margin: 20px 0; }
        .test-all:hover { background: #c82333; }
        .response { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; white-space: pre-wrap; font-family: monospace; max-height: 400px; overflow-y: auto; }
        .success { border-left: 4px solid #28a745; background: #d4edda; }
        .error { border-left: 4px solid #dc3545; background: #f8d7da; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .status { text-align: center; padding: 20px; background: #e9ecef; border-radius: 8px; margin: 20px 0; }
        .progress { width: 100%; background: #e9ecef; border-radius: 4px; margin: 10px 0; }
        .progress-bar { height: 20px; background: #28a745; border-radius: 4px; transition: width 0.3s; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Webhook Complete Test - All Evolution API Events</h1>
            <p>Prueba TODOS los 18 eventos Evolution API con POST real</p>
        </div>
        
        <div class="status">
            <h3 id="status-title">🎯 Listo para probar todos los eventos</h3>
            <div class="progress">
                <div class="progress-bar" id="progress-bar" style="width: 0%"></div>
            </div>
            <p id="status-text">18 eventos listos para test</p>
        </div>
        
        <button class="test-all" onclick="testAllEvents()">🚀 TEST ALL EVENTS (18 eventos)</button>
        <button class="test-all" onclick="testSystemEvents()" style="background: #007bff;">🔧 TEST SYSTEM EVENTS (3)</button>
        <button class="test-all" onclick="testMessageEvents()" style="background: #6f42c1;">📨 TEST MESSAGE EVENTS (5)</button>
        <button class="test-all" onclick="testContactEvents()" style="background: #fd7e14;">👥 TEST CONTACT EVENTS (3)</button>
        <button class="test-all" onclick="testChatEvents()" style="background: #20c997;">💬 TEST CHAT EVENTS (4)</button>
        <button class="test-all" onclick="testGroupEvents()" style="background: #e83e8c;">👨‍👩‍👧‍👦 TEST GROUP EVENTS (2)</button>
        <button class="test-all" onclick="clearResponse()" style="background: #6c757d;">🗑️ CLEAR RESPONSE</button>
        
        <div class="events-grid">
            <!-- System Events -->
            <div class="event-card">
                <h4>🔧 QRCODE_UPDATED</h4>
                <p>Actualización de código QR</p>
                <button class="test-button" onclick="testEvent('qrcode-updated')">Test</button>
            </div>
            
            <div class="event-card">
                <h4>🔗 CONNECTION_UPDATE</h4>
                <p>Estado de conexión WhatsApp</p>
                <button class="test-button" onclick="testEvent('connection-update')">Test</button>
            </div>
            
            <div class="event-card">
                <h4>🔑 NEW_TOKEN</h4>
                <p>Actualización de token JWT</p>
                <button class="test-button" onclick="testEvent('new-jwt')">Test</button>
            </div>
            
            <!-- Message Events -->
            <div class="event-card">
                <h4>📨 MESSAGES_SET</h4>
                <p>Carga inicial de mensajes</p>
                <button class="test-button" onclick="testEvent('messages-set')">Test</button>
            </div>
            
            <div class="event-card">
                <h4>📤 MESSAGES_UPSERT</h4>
                <p>Nuevos mensajes recibidos</p>
                <button class="test-button" onclick="testEvent('messages-upsert')">Test</button>
            </div>
            
            <div class="event-card">
                <h4>📝 MESSAGES_UPDATE</h4>
                <p>Actualización de estado mensajes</p>
                <button class="test-button" onclick="testEvent('messages-update')">Test</button>
            </div>
            
            <div class="event-card">
                <h4>🗑️ MESSAGES_DELETE</h4>
                <p>Eliminación de mensajes</p>
                <button class="test-button" onclick="testEvent('messages-delete')">Test</button>
            </div>
            
            <div class="event-card">
                <h4>📤 SEND_MESSAGE</h4>
                <p>Mensajes enviados</p>
                <button class="test-button" onclick="testEvent('send-message')">Test</button>
            </div>
            
            <!-- Contact Events -->
            <div class="event-card">
                <h4>👥 CONTACTS_SET</h4>
                <p>Carga inicial de contactos</p>
                <button class="test-button" onclick="testEvent('contacts-set')">Test</button>
            </div>
            
            <div class="event-card">
                <h4>📝 CONTACTS_UPSERT</h4>
                <p>Actualización de contactos</p>
                <button class="test-button" onclick="testEvent('contacts-upsert')">Test</button>
            </div>
            
            <div class="event-card">
                <h4>👤 CONTACTS_UPDATE</h4>
                <p>Actualización individual</p>
                <button class="test-button" onclick="testEvent('contacts-update')">Test</button>
            </div>
            
            <!-- Chat Events -->
            <div class="event-card">
                <h4>💬 CHATS_SET</h4>
                <p>Carga inicial de chats</p>
                <button class="test-button" onclick="testEvent('chats-set')">Test</button>
            </div>
            
            <div class="event-card">
                <h4>🆕 CHATS_UPSERT</h4>
                <p>Nuevos chats creados</p>
                <button class="test-button" onclick="testEvent('chats-upsert')">Test</button>
            </div>
            
            <div class="event-card">
                <h4>📝 CHATS_UPDATE</h4>
                <p>Actualización de chats</p>
                <button class="test-button" onclick="testEvent('chats-update')">Test</button>
            </div>
            
            <div class="event-card">
                <h4>🗑️ CHATS_DELETE</h4>
                <p>Eliminación de chats</p>
                <button class="test-button" onclick="testEvent('chats-delete')">Test</button>
            </div>
            
            <!-- Group Events -->
            <div class="event-card">
                <h4>👨‍👩‍👧‍👦 GROUPS_UPSERT</h4>
                <p>Creación de grupos</p>
                <button class="test-button" onclick="testEvent('groups-upsert')">Test</button>
            </div>
            
            <div class="event-card">
                <h4>📝 GROUPS_UPDATE</h4>
                <p>Actualización de grupos</p>
                <button class="test-button" onclick="testEvent('groups-update')">Test</button>
            </div>
            
            <!-- Presence Event -->
            <div class="event-card">
                <h4>📱 PRESENCE_UPDATE</h4>
                <p>Estado de presencia</p>
                <button class="test-button" onclick="testEvent('presence-update')">Test</button>
            </div>
            
            <!-- Group Participants Event -->
            <div class="event-card">
                <h4>👥 GROUP_PARTICIPANTS_UPDATE</h4>
                <p>Cambios en participantes</p>
                <button class="test-button" onclick="testEvent('group-participants-update')">Test</button>
            </div>
        </div>
        
        <div id="response" class="response" style="display: none;"></div>
        
        <div class="info">
            <h3>📊 Estadísticas del Test:</h3>
            <p>✅ <strong>Total eventos:</strong> 18 eventos Evolution API</p>
            <p>🔧 <strong>System events:</strong> 3 (QR, Connection, Token)</p>
            <p>📨 <strong>Message events:</strong> 5 (Set, Upsert, Update, Delete, Send)</p>
            <p>👥 <strong>Contact events:</strong> 3 (Set, Upsert, Update)</p>
            <p>💬 <strong>Chat events:</strong> 4 (Set, Upsert, Update, Delete)</p>
            <p>👨‍👩‍👧‍👦 <strong>Group events:</strong> 2 (Upsert, Update)</p>
            <p>📱 <strong>Presence events:</strong> 1 (Update)</p>
            <p>👥 <strong>Group participants:</strong> 1 (Update)</p>
        </div>
    </div>

    <script>
        let currentTestIndex = 0;
        let totalTests = 0;
        let successfulTests = 0;
        let failedTests = 0;
        
        const allEvents = [
            'qrcode-updated',
            'connection-update', 
            'new-jwt',
            'messages-set',
            'messages-upsert',
            'messages-update',
            'messages-delete',
            'send-message',
            'contacts-set',
            'contacts-upsert',
            'contacts-update',
            'chats-set',
            'chats-upsert',
            'chats-update',
            'chats-delete',
            'groups-upsert',
            'groups-update',
            'presence-update',
            'group-participants-update'
        ];
        
        const eventCategories = {
            system: ['qrcode-updated', 'connection-update', 'new-jwt'],
            messages: ['messages-set', 'messages-upsert', 'messages-update', 'messages-delete', 'send-message'],
            contacts: ['contacts-set', 'contacts-upsert', 'contacts-update'],
            chats: ['chats-set', 'chats-upsert', 'chats-update', 'chats-delete'],
            groups: ['groups-upsert', 'groups-update'],
            presence: ['presence-update'],
            participants: ['group-participants-update']
        };
        
        function updateProgress(current, total) {
            const percentage = Math.round((current / total) * 100);
            document.getElementById('progress-bar').style.width = percentage + '%';
            document.getElementById('status-text').textContent = `${current}/${total} eventos probados (${successfulTests} exitosos, ${failedTests} fallidos)`;
        }
        
        function updateStatus(title, text) {
            document.getElementById('status-title').textContent = title;
            document.getElementById('status-text').textContent = text;
        }
        
        function showResponse(data, type, eventName = '') {
            const responseDiv = document.getElementById('response');
            responseDiv.style.display = 'block';
            responseDiv.className = 'response ' + type;
            
            const timestamp = new Date().toLocaleString();
            const responseText = `[${timestamp}] ${eventName ? 'EVENT: ' + eventName + '\n' : ''}${JSON.stringify(data, null, 2)}`;
            
            if (type === 'success') {
                successfulTests++;
            } else {
                failedTests++;
            }
            
            responseDiv.textContent = responseText + '\n\n' + (responseDiv.textContent || '');
            responseDiv.scrollTop = responseDiv.scrollHeight;
        }
        
        async function testEvent(eventName) {
            updateStatus(`🧪 Probando: ${eventName}`, `Enviando evento ${eventName}...`);
            
            const payload = createPayloadForEvent(eventName);
            
            try {
                const response = await fetch('webhook_test_simple.php?instance=SCAMMARANO', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Webhook-Token': 'complete-test-token'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                showResponse(data, response.ok ? 'success' : 'error', eventName);
                
                if (response.ok) {
                    updateStatus(`✅ ${eventName} probado`, `Evento ${eventName} procesado exitosamente`);
                } else {
                    updateStatus(`❌ Error en ${eventName}`, `Evento ${eventName} falló: ${data.error || 'Unknown error'}`);
                }
                
            } catch (error) {
                showResponse({error: 'Network error: ' + error.message}, 'error', eventName);
                updateStatus(`❌ Error de red en ${eventName}`, `No se pudo conectar: ${error.message}`);
            }
        }
        
        function createPayloadForEvent(eventName) {
            const timestamp = Date.now();
            const basePayload = {
                date_time: new Date().toISOString(),
                instance: "SCAMMARANO"
            };
            
            switch (eventName) {
                case 'qrcode-updated':
                    return {
                        ...basePayload,
                        event: 'QRCODE_UPDATED',
                        data: {
                            qrcode: {
                                base64: 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
                            }
                        }
                    };
                    
                case 'connection-update':
                    return {
                        ...basePayload,
                        event: 'CONNECTION_UPDATE',
                        data: {
                            state: 'open',
                            instance: 'SCAMMARANO'
                        }
                    };
                    
                case 'new-jwt':
                    return {
                        ...basePayload,
                        event: 'NEW_TOKEN',
                        data: {
                            token: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.test.token'
                        }
                    };
                    
                case 'messages-set':
                    return {
                        ...basePayload,
                        event: 'MESSAGES_SET',
                        data: [
                            {
                                key: {remoteJid: "584142024787@s.whatsapp.net", id: "MSG_SET_1", fromMe: false},
                                message: {conversation: "Initial load message 1"},
                                messageTimestamp: timestamp * 1000
                            },
                            {
                                key: {remoteJid: "584143238051@s.whatsapp.net", id: "MSG_SET_2", fromMe: true},
                                message: {conversation: "Initial load message 2"},
                                messageTimestamp: timestamp * 1000
                            }
                        ]
                    };
                    
                case 'messages-upsert':
                    return {
                        ...basePayload,
                        event: 'MESSAGES_UPSERT',
                        data: {
                            key: {remoteJid: "584142024787@s.whatsapp.net", id: "MSG_NEW_" + timestamp, fromMe: false},
                            message: {conversation: "New message from upsert"},
                            messageTimestamp: timestamp * 1000,
                            pushname: "Test Contact"
                        }
                    };
                    
                case 'messages-update':
                    return {
                        ...basePayload,
                        event: 'MESSAGES_UPDATE',
                        data: {
                            key: {remoteJid: "584142024787@s.whatsapp.net", id: "MSG_UPDATE_" + timestamp, fromMe: true},
                            status: 'read',
                            messageTimestamp: timestamp * 1000
                        }
                    };
                    
                case 'messages-delete':
                    return {
                        ...basePayload,
                        event: 'MESSAGES_DELETE',
                        data: {
                            key: {remoteJid: "584142024787@s.whatsapp.net", id: "MSG_DELETE_" + timestamp, fromMe: false},
                            messageTimestamp: timestamp * 1000
                        }
                    };
                    
                case 'send-message':
                    return {
                        ...basePayload,
                        event: 'SEND_MESSAGE',
                        data: {
                            key: {remoteJid: "584142024787@s.whatsapp.net", id: "MSG_SENT_" + timestamp, fromMe: true},
                            message: {conversation: "Sent message test"},
                            messageTimestamp: timestamp * 1000,
                            status: 'sent'
                        }
                    };
                    
                case 'contacts-set':
                    return {
                        ...basePayload,
                        event: 'CONTACTS_SET',
                        data: [
                            {
                                remoteJid: "584142024787@s.whatsapp.net",
                                pushname: "Contact 1",
                                profilePicUrl: "https://example.com/pic1.jpg"
                            },
                            {
                                remoteJid: "584143238051@s.whatsapp.net",
                                pushname: "Contact 2",
                                profilePicUrl: "https://example.com/pic2.jpg"
                            }
                        ]
                    };
                    
                case 'contacts-upsert':
                    return {
                        ...basePayload,
                        event: 'CONTACTS_UPSERT',
                        data: [
                            {
                                remoteJid: "584142024787@s.whatsapp.net",
                                pushname: "Updated Contact",
                                profilePicUrl: "https://example.com/updated.jpg"
                            }
                        ]
                    };
                    
                case 'contacts-update':
                    return {
                        ...basePayload,
                        event: 'CONTACTS_UPDATE',
                        data: {
                            remoteJid: "584142024787@s.whatsapp.net",
                            pushname: "Single Updated Contact",
                            profilePicUrl: "https://example.com/single.jpg"
                        }
                    };
                    
                case 'chats-set':
                    return {
                        ...basePayload,
                        event: 'CHATS_SET',
                        data: [
                            {
                                id: "584142024787@s.whatsapp.net",
                                name: "Chat 1",
                                unreadMessages: 2
                            },
                            {
                                id: "584143238051@s.whatsapp.net", 
                                name: "Chat 2",
                                unreadMessages: 0
                            }
                        ]
                    };
                    
                case 'chats-upsert':
                    return {
                        ...basePayload,
                        event: 'CHATS_UPSERT',
                        data: {
                            id: "584142024787@s.whatsapp.net",
                            name: "New Chat",
                            unreadMessages: 1
                        }
                    };
                    
                case 'chats-update':
                    return {
                        ...basePayload,
                        event: 'CHATS_UPDATE',
                        data: {
                            remoteJid: "584142024787@s.whatsapp.net",
                            lastMessageTimestamp: timestamp * 1000
                        }
                    };
                    
                case 'chats-delete':
                    return {
                        ...basePayload,
                        event: 'CHATS_DELETE',
                        data: {
                            id: "584142024787@s.whatsapp.net"
                        }
                    };
                    
                case 'groups-upsert':
                    return {
                        ...basePayload,
                        event: 'GROUPS_UPSERT',
                        data: {
                            id: "584142404172-1496408994@g.us",
                            subject: "Test Group",
                            desc: "Test group description"
                        }
                    };
                    
                case 'groups-update':
                    return {
                        ...basePayload,
                        event: 'GROUPS_UPDATE',
                        data: {
                            id: "584142404172-1496408994@g.us",
                            subject: "Updated Group"
                        }
                    };
                    
                case 'presence-update':
                    return {
                        ...basePayload,
                        event: 'PRESENCE_UPDATE',
                        data: {
                            id: "584142024787@s.whatsapp.net",
                            presences: {
                                "584142024787@s.whatsapp.net": {
                                    lastKnownPresence: "available"
                                }
                            }
                        }
                    };
                    
                case 'group-participants-update':
                    return {
                        ...basePayload,
                        event: 'GROUP_PARTICIPANTS_UPDATE',
                        data: {
                            id: "584142404172-1496408994@g.us",
                            action: "add",
                            participants: [
                                {
                                    userJid: "584142024787@s.whatsapp.net",
                                    name: "Added Participant"
                                }
                            ]
                        }
                    };
                    
                default:
                    return {
                        ...basePayload,
                        event: eventName.toUpperCase(),
                        data: {
                            message: "Test payload for " + eventName
                        }
                    };
            }
        }
        
        async function testAllEvents() {
            successfulTests = 0;
            failedTests = 0;
            currentTestIndex = 0;
            totalTests = allEvents.length;
            
            updateStatus('🚀 Probando TODOS los eventos', `Iniciando test de ${totalTests} eventos...`);
            
            for (let i = 0; i < allEvents.length; i++) {
                currentTestIndex = i + 1;
                updateProgress(currentTestIndex, totalTests);
                await testEvent(allEvents[i]);
                await new Promise(resolve => setTimeout(resolve, 500)); // Pequeña pausa entre tests
            }
            
            updateStatus(`🎉 Test completado`, `${totalTests} eventos probados: ${successfulTests} exitosos, ${failedTests} fallidos`);
        }
        
        async function testSystemEvents() {
            await testCategory('system', eventCategories.system);
        }
        
        async function testMessageEvents() {
            await testCategory('messages', eventCategories.messages);
        }
        
        async function testContactEvents() {
            await testCategory('contacts', eventCategories.contacts);
        }
        
        async function testChatEvents() {
            await testCategory('chats', eventCategories.chats);
        }
        
        async function testGroupEvents() {
            await testCategory('groups', eventCategories.groups);
        }
        
        async function testCategory(categoryName, events) {
            successfulTests = 0;
            failedTests = 0;
            currentTestIndex = 0;
            totalTests = events.length;
            
            updateStatus(`🧪 Probando eventos ${categoryName}`, `Probando ${totalTests} eventos de ${categoryName}...`);
            
            for (let i = 0; i < events.length; i++) {
                currentTestIndex = i + 1;
                updateProgress(currentTestIndex, totalTests);
                await testEvent(events[i]);
                await new Promise(resolve => setTimeout(resolve, 300));
            }
            
            updateStatus(`✅ Eventos ${categoryName} completados`, `${totalTests} eventos ${categoryName}: ${successfulTests} exitosos, ${failedTests} fallidos`);
        }
        
        function clearResponse() {
            const responseDiv = document.getElementById('response');
            responseDiv.style.display = 'none';
            responseDiv.textContent = '';
            successfulTests = 0;
            failedTests = 0;
            updateProgress(0, 0);
            updateStatus('🎯 Listo para probar todos los eventos', '18 eventos listos para test');
        }
    </script>
</body>
</html>
