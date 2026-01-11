<?php
// Test Manual COMPLETO de Webhook - TODOS los 26 eventos Evolution API
?>
<!DOCTYPE html>
<html>
<head>
    <title>Webhook Manual Test - Complete 26 Events</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .instance-selector { background: #e9ecef; padding: 15px; border-radius: 8px; margin: 20px 0; text-align: center; }
        .instance-selector select { padding: 10px; font-size: 16px; border-radius: 5px; border: 1px solid #ccc; min-width: 200px; }
        .events-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin: 20px 0; }
        .event-card { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff; transition: transform 0.2s; }
        .event-card:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .event-card.system { border-left-color: #28a745; }
        .event-card.messages { border-left-color: #007bff; }
        .event-card.contacts { border-left-color: #fd7e14; }
        .event-card.chats { border-left-color: #20c997; }
        .event-card.groups { border-left-color: #e83e8c; }
        .event-card.presence { border-left-color: #6f42c1; }
        .event-card.participants { border-left-color: #6610f2; }
        .event-card.typebot { border-left-color: #17a2b8; }
        .event-card.status { border-left-color: #343a40; }
        .event-card.labels { border-left-color: #ffc107; }
        .event-card.calls { border-left-color: #dc3545; }
        .event-card h4 { margin: 0 0 10px 0; color: #495057; font-size: 14px; }
        .event-card p { margin: 5px 0; font-size: 12px; color: #6c757d; }
        .test-button { background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; margin: 5px 5px 5px 0; transition: all 0.2s; }
        .test-button:hover { background: #218838; transform: scale(1.05); }
        .bulk-test { background: #dc3545; color: white; padding: 15px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; margin: 10px 5px; transition: all 0.2s; }
        .bulk-test:hover { background: #c82333; transform: scale(1.05); }
        .response { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; white-space: pre-wrap; font-family: monospace; max-height: 500px; overflow-y: auto; }
        .success { border-left: 4px solid #28a745; background: #d4edda; }
        .error { border-left: 4px solid #dc3545; background: #f8d7da; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: white; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #007bff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-number { font-size: 24px; font-weight: bold; color: #007bff; }
        .stat-label { font-size: 14px; color: #6c757d; margin-top: 5px; }
        .progress { width: 100%; background: #e9ecef; border-radius: 4px; margin: 10px 0; height: 20px; position: relative; }
        .progress-bar { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); border-radius: 4px; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px; }
        .category-header { background: #007bff; color: white; padding: 10px; margin: 20px 0 10px 0; border-radius: 5px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Webhook Manual Test - Complete 26 Events</h1>
            <p>Prueba manual de TODOS los 26 eventos Evolution API con payloads realistas</p>
        </div>
        
        <div class="instance-selector">
            <h3>🏢 Seleccionar Instancia</h3>
            <select id="instance-select">
                <option value="SCAMMARANO">SCAMMARANO</option>
                <option value="2CAMCARGO">2CAMCARGO</option>
                <option value="CASAMIA">CASAMIA</option>
                <option value="2CAMSERVICES">2CAMSERVICES</option>
            </select>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number">26</div>
                <div class="stat-label">Total Eventos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="success-count">0</div>
                <div class="stat-label">Exitosos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="error-count">0</div>
                <div class="stat-label">Fallidos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="tested-count">0</div>
                <div class="stat-label">Probados</div>
            </div>
        </div>
        
        <div style="text-align: center; margin: 20px 0;">
            <button class="bulk-test" onclick="testAllEvents()">🚀 TEST ALL 26 EVENTS</button>
            <button class="bulk-test" onclick="testSystemEvents()" style="background: #28a745;">🔧 TEST SYSTEM (4)</button>
            <button class="bulk-test" onclick="testMessageEvents()" style="background: #007bff;">📨 TEST MESSAGES (5)</button>
            <button class="bulk-test" onclick="testContactEvents()" style="background: #fd7e14;">👥 TEST CONTACTS (3)</button>
            <button class="bulk-test" onclick="testChatEvents()" style="background: #20c997;">💬 TEST CHATS (4)</button>
            <button class="bulk-test" onclick="testGroupEvents()" style="background: #e83e8c;">👨‍👩‍👧‍👦 TEST GROUPS (2)</button>
            <button class="bulk-test" onclick="testTypebotEvents()" style="background: #17a2b8;">🤖 TEST TYPEBOT (2)</button>
            <button class="bulk-test" onclick="clearResponse()" style="background: #6c757d;">🗑️ CLEAR RESPONSE</button>
        </div>
        
        <div class="progress" id="progress-container" style="display: none;">
            <div class="progress-bar" id="progress-bar" style="width: 0%">0%</div>
        </div>
        
        <!-- System Events -->
        <div class="category-header">🔧 SYSTEM EVENTS (4)</div>
        <div class="events-grid">
            <div class="event-card system">
                <h4>🔧 QRCODE_UPDATED</h4>
                <p>Actualización de código QR</p>
                <button class="test-button" onclick="testEvent('QRCODE_UPDATED')">Test</button>
            </div>
            
            <div class="event-card system">
                <h4>🔗 CONNECTION_UPDATE</h4>
                <p>Estado de conexión WhatsApp</p>
                <button class="test-button" onclick="testEvent('CONNECTION_UPDATE')">Test</button>
            </div>
            
            <div class="event-card system">
                <h4>🔑 NEW_TOKEN</h4>
                <p>Actualización de token JWT</p>
                <button class="test-button" onclick="testEvent('NEW_TOKEN')">Test</button>
            </div>
            
            <div class="event-card system">
                <h4>🚀 APPLICATION_STARTUP</h4>
                <p>Inicio de aplicación</p>
                <button class="test-button" onclick="testEvent('APPLICATION_STARTUP')">Test</button>
            </div>
        </div>
        
        <!-- Message Events -->
        <div class="category-header">📨 MESSAGE EVENTS (5)</div>
        <div class="events-grid">
            <div class="event-card messages">
                <h4>📨 MESSAGES_SET</h4>
                <p>Carga inicial de mensajes</p>
                <button class="test-button" onclick="testEvent('MESSAGES_SET')">Test</button>
            </div>
            
            <div class="event-card messages">
                <h4>📤 MESSAGES_UPSERT</h4>
                <p>Nuevos mensajes recibidos</p>
                <button class="test-button" onclick="testEvent('MESSAGES_UPSERT')">Test</button>
            </div>
            
            <div class="event-card messages">
                <h4>📝 MESSAGES_UPDATE</h4>
                <p>Actualización de estado mensajes</p>
                <button class="test-button" onclick="testEvent('MESSAGES_UPDATE')">Test</button>
            </div>
            
            <div class="event-card messages">
                <h4>🗑️ MESSAGES_DELETE</h4>
                <p>Eliminación de mensajes</p>
                <button class="test-button" onclick="testEvent('MESSAGES_DELETE')">Test</button>
            </div>
            
            <div class="event-card messages">
                <h4>📤 SEND_MESSAGE</h4>
                <p>Mensajes enviados</p>
                <button class="test-button" onclick="testEvent('SEND_MESSAGE')">Test</button>
            </div>
        </div>
        
        <!-- Contact Events -->
        <div class="category-header">👥 CONTACT EVENTS (3)</div>
        <div class="events-grid">
            <div class="event-card contacts">
                <h4>👥 CONTACTS_SET</h4>
                <p>Carga inicial de contactos</p>
                <button class="test-button" onclick="testEvent('CONTACTS_SET')">Test</button>
            </div>
            
            <div class="event-card contacts">
                <h4>📝 CONTACTS_UPSERT</h4>
                <p>Actualización de contactos</p>
                <button class="test-button" onclick="testEvent('CONTACTS_UPSERT')">Test</button>
            </div>
            
            <div class="event-card contacts">
                <h4>👤 CONTACTS_UPDATE</h4>
                <p>Actualización individual</p>
                <button class="test-button" onclick="testEvent('CONTACTS_UPDATE')">Test</button>
            </div>
        </div>
        
        <!-- Chat Events -->
        <div class="category-header">💬 CHAT EVENTS (4)</div>
        <div class="events-grid">
            <div class="event-card chats">
                <h4>💬 CHATS_SET</h4>
                <p>Carga inicial de chats</p>
                <button class="test-button" onclick="testEvent('CHATS_SET')">Test</button>
            </div>
            
            <div class="event-card chats">
                <h4>🆕 CHATS_UPSERT</h4>
                <p>Nuevos chats creados</p>
                <button class="test-button" onclick="testEvent('CHATS_UPSERT')">Test</button>
            </div>
            
            <div class="event-card chats">
                <h4>📝 CHATS_UPDATE</h4>
                <p>Actualización de chats</p>
                <button class="test-button" onclick="testEvent('CHATS_UPDATE')">Test</button>
            </div>
            
            <div class="event-card chats">
                <h4>🗑️ CHATS_DELETE</h4>
                <p>Eliminación de chats</p>
                <button class="test-button" onclick="testEvent('CHATS_DELETE')">Test</button>
            </div>
        </div>
        
        <!-- Group Events -->
        <div class="category-header">👨‍👩‍👧‍👦 GROUP EVENTS (2)</div>
        <div class="events-grid">
            <div class="event-card groups">
                <h4>👨‍👩‍👧‍👦 GROUPS_UPSERT</h4>
                <p>Creación de grupos</p>
                <button class="test-button" onclick="testEvent('GROUPS_UPSERT')">Test</button>
            </div>
            
            <div class="event-card groups">
                <h4>📝 GROUPS_UPDATE</h4>
                <p>Actualización de grupos</p>
                <button class="test-button" onclick="testEvent('GROUPS_UPDATE')">Test</button>
            </div>
        </div>
        
        <!-- Typebot Events -->
        <div class="category-header">🤖 TYPEBOT EVENTS (2)</div>
        <div class="events-grid">
            <div class="event-card typebot">
                <h4>🤖 TYPEBOT_START</h4>
                <p>Inicio de Typebot</p>
                <button class="test-button" onclick="testEvent('TYPEBOT_START')">Test</button>
            </div>
            
            <div class="event-card typebot">
                <h4>🔄 TYPEBOT_CHANGE_STATUS</h4>
                <p>Cambio de estado Typebot</p>
                <button class="test-button" onclick="testEvent('TYPEBOT_CHANGE_STATUS')">Test</button>
            </div>
        </div>
        
        <!-- Additional Events -->
        <div class="category-header">📱 ADDITIONAL EVENTS (6)</div>
        <div class="events-grid">
            <div class="event-card presence">
                <h4>📱 PRESENCE_UPDATE</h4>
                <p>Estado de presencia</p>
                <button class="test-button" onclick="testEvent('PRESENCE_UPDATE')">Test</button>
            </div>
            
            <div class="event-card participants">
                <h4>👥 GROUP_PARTICIPANTS_UPDATE</h4>
                <p>Cambios en participantes</p>
                <button class="test-button" onclick="testEvent('GROUP_PARTICIPANTS_UPDATE')">Test</button>
            </div>
            
            <div class="event-card status">
                <h4>📝 STATUS_MESSAGE</h4>
                <p>Mensaje de estado</p>
                <button class="test-button" onclick="testEvent('STATUS_MESSAGE')">Test</button>
            </div>
            
            <div class="event-card labels">
                <h4>🏷️ LABELS_EDIT</h4>
                <p>Edición de etiquetas</p>
                <button class="test-button" onclick="testEvent('LABELS_EDIT')">Test</button>
            </div>
            
            <div class="event-card labels">
                <h4>🔗 LABELS_ASSOCIATION</h4>
                <p>Asociación de etiquetas</p>
                <button class="test-button" onclick="testEvent('LABELS_ASSOCIATION')">Test</button>
            </div>
            
            <div class="event-card calls">
                <h4>📞 CALL_WEBHOOK</h4>
                <p>Webhook de llamadas</p>
                <button class="test-button" onclick="testEvent('CALL_WEBHOOK')">Test</button>
            </div>
        </div>
        
        <div id="response" class="response" style="display: none;"></div>
        
        <div style="margin-top: 30px; padding: 15px; background: #e2e3e5; border-radius: 4px;">
            <h3>📊 Estadísticas Completas:</h3>
            <p>✅ <strong>Total eventos:</strong> 26 eventos Evolution API</p>
            <p>🔧 <strong>System events:</strong> 4 (QR, Connection, Token, Application)</p>
            <p>📨 <strong>Message events:</strong> 5 (Set, Upsert, Update, Delete, Send)</p>
            <p>👥 <strong>Contact events:</strong> 3 (Set, Upsert, Update)</p>
            <p>💬 <strong>Chat events:</strong> 4 (Set, Upsert, Update, Delete)</p>
            <p>👨‍👩‍👧‍👦 <strong>Group events:</strong> 2 (Upsert, Update)</p>
            <p>🤖 <strong>Typebot events:</strong> 2 (Start, Change Status)</p>
            <p>📱 <strong>Presence events:</strong> 1 (Update)</p>
            <p>👥 <strong>Group participants:</strong> 1 (Update)</p>
            <p>📝 <strong>Status events:</strong> 1 (Message)</p>
            <p>🏷️ <strong>Labels events:</strong> 2 (Edit, Association)</p>
            <p>📞 <strong>Call events:</strong> 1 (Webhook)</p>
        </div>
    </div>

    <script>
        let testedCount = 0;
        let successCount = 0;
        let errorCount = 0;
        
        function updateStats() {
            document.getElementById('tested-count').textContent = testedCount;
            document.getElementById('success-count').textContent = successCount;
            document.getElementById('error-count').textContent = errorCount;
        }
        
        function updateProgress(current, total) {
            const percentage = Math.round((current / total) * 100);
            const progressBar = document.getElementById('progress-bar');
            const progressContainer = document.getElementById('progress-container');
            
            progressContainer.style.display = 'block';
            progressBar.style.width = percentage + '%';
            progressBar.textContent = percentage + '% (' + current + '/' + total + ')';
            
            if (current === total) {
                setTimeout(() => {
                    progressContainer.style.display = 'none';
                }, 2000);
            }
        }
        
        function showResponse(data, type, eventName = '') {
            const responseDiv = document.getElementById('response');
            responseDiv.style.display = 'block';
            responseDiv.className = 'response ' + type;
            
            const timestamp = new Date().toLocaleString();
            const responseText = '[' + timestamp + '] ' + (eventName ? 'EVENT: ' + eventName + '\n' : '') + JSON.stringify(data, null, 2);
            
            responseDiv.textContent = responseText + '\n\n' + (responseDiv.textContent || '');
            responseDiv.scrollTop = responseDiv.scrollHeight;
            
            testedCount++;
            if (type === 'success') {
                successCount++;
            } else {
                errorCount++;
            }
            updateStats();
        }
        
        async function testEvent(eventName) {
            const instance = document.getElementById('instance-select').value;
            const payload = createPayloadForEvent(eventName, instance);
            
            try {
                const response = await fetch('webhook_test_simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Webhook-Token': 'manual-test-token'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                showResponse(data, response.ok ? 'success' : 'error', eventName);
                
            } catch (error) {
                showResponse({error: 'Network error: ' + error.message}, 'error', eventName);
            }
        }
        
        function createPayloadForEvent(eventName, instance) {
            const timestamp = Date.now();
            const basePayload = {
                date_time: new Date().toISOString(),
                instance: instance
            };
            
            switch (eventName) {
                case 'QRCODE_UPDATED':
                    return {...basePayload, event: 'QRCODE_UPDATED', data: {qrcode: {base64: 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='}}};
                case 'CONNECTION_UPDATE':
                    return {...basePayload, event: 'CONNECTION_UPDATE', data: {state: 'open', instance: instance}};
                case 'NEW_TOKEN':
                    return {...basePayload, event: 'NEW_TOKEN', data: {token: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.test.token.' + timestamp}};
                case 'APPLICATION_STARTUP':
                    return {...basePayload, event: 'APPLICATION_STARTUP', data: {application: 'Evolution API', version: '1.0.0', instance: instance}};
                case 'MESSAGES_SET':
                    return {...basePayload, event: 'MESSAGES_SET', data: [{key: {remoteJid: "584142024787@s.whatsapp.net", id: "MSG_SET_1", fromMe: false}, message: {conversation: "Initial load message 1"}, messageTimestamp: timestamp * 1000}]};
                case 'MESSAGES_UPSERT':
                    return {...basePayload, event: 'MESSAGES_UPSERT', data: {key: {remoteJid: "584142024787@s.whatsapp.net", id: "MSG_NEW_" + timestamp, fromMe: false}, message: {conversation: "New message from manual test"}, messageTimestamp: timestamp * 1000, pushname: "Test Contact"}};
                case 'MESSAGES_UPDATE':
                    return {...basePayload, event: 'MESSAGES_UPDATE', data: {key: {remoteJid: "584142024787@s.whatsapp.net", id: "MSG_UPDATE_" + timestamp, fromMe: true}, status: 'read', messageTimestamp: timestamp * 1000}};
                case 'MESSAGES_DELETE':
                    return {...basePayload, event: 'MESSAGES_DELETE', data: {key: {remoteJid: "584142024787@s.whatsapp.net", id: "MSG_DELETE_" + timestamp, fromMe: false}, messageTimestamp: timestamp * 1000}};
                case 'SEND_MESSAGE':
                    return {...basePayload, event: 'SEND_MESSAGE', data: {key: {remoteJid: "584142024787@s.whatsapp.net", id: "MSG_SENT_" + timestamp, fromMe: true}, message: {conversation: "Sent message from manual test"}, messageTimestamp: timestamp * 1000, status: 'sent'}};
                case 'CONTACTS_SET':
                    return {...basePayload, event: 'CONTACTS_SET', data: [{remoteJid: "584142024787@s.whatsapp.net", pushname: "Contact 1", profilePicUrl: "https://example.com/pic1.jpg"}, {remoteJid: "584143238051@s.whatsapp.net", pushname: "Contact 2", profilePicUrl: "https://example.com/pic2.jpg"}]};
                case 'CONTACTS_UPSERT':
                    return {...basePayload, event: 'CONTACTS_UPSERT', data: [{remoteJid: "584142024787@s.whatsapp.net", pushname: "Updated Contact", profilePicUrl: "https://example.com/updated.jpg"}]};
                case 'CONTACTS_UPDATE':
                    return {...basePayload, event: 'CONTACTS_UPDATE', data: {remoteJid: "584142024787@s.whatsapp.net", pushname: "Single Updated Contact", profilePicUrl: "https://example.com/single.jpg"}};
                case 'CHATS_SET':
                    return {...basePayload, event: 'CHATS_SET', data: [{id: "584142024787@s.whatsapp.net", name: "Chat 1", unreadMessages: 2}, {id: "584143238051@s.whatsapp.net", name: "Chat 2", unreadMessages: 0}]};
                case 'CHATS_UPSERT':
                    return {...basePayload, event: 'CHATS_UPSERT', data: {id: "584142024787@s.whatsapp.net", name: "New Chat", unreadMessages: 1}};
                case 'CHATS_UPDATE':
                    return {...basePayload, event: 'CHATS_UPDATE', data: {remoteJid: "584142024787@s.whatsapp.net", lastMessageTimestamp: timestamp * 1000}};
                case 'CHATS_DELETE':
                    return {...basePayload, event: 'CHATS_DELETE', data: {id: "584142024787@s.whatsapp.net"}};
                case 'GROUPS_UPSERT':
                    return {...basePayload, event: 'GROUPS_UPSERT', data: {id: "584142404172-1496408994@g.us", subject: "Test Group", desc: "Test group description", owner: "584142024787@s.whatsapp.net"}};
                case 'GROUPS_UPDATE':
                    return {...basePayload, event: 'GROUPS_UPDATE', data: {id: "584142404172-1496408994@g.us", subject: "Updated Group", desc: "Updated description"}};
                case 'TYPEBOT_START':
                    return {...basePayload, event: 'TYPEBOT_START', data: {typebot_id: "typebot_" + timestamp, remoteJid: "584142024787@s.whatsapp.net", typebot_name: "Test Typebot"}};
                case 'TYPEBOT_CHANGE_STATUS':
                    return {...basePayload, event: 'TYPEBOT_CHANGE_STATUS', data: {typebot_id: "typebot_" + timestamp, status: "active", remoteJid: "584142024787@s.whatsapp.net"}};
                case 'PRESENCE_UPDATE':
                    return {...basePayload, event: 'PRESENCE_UPDATE', data: {id: "584142024787@s.whatsapp.net", presences: {"584142024787@s.whatsapp.net": {lastKnownPresence: "available", lastSeen: timestamp * 1000}}}};
                case 'GROUP_PARTICIPANTS_UPDATE':
                    return {...basePayload, event: 'GROUP_PARTICIPANTS_UPDATE', data: {id: "584142404172-1496408994@g.us", action: "add", participants: [{userJid: "584142024787@s.whatsapp.net", name: "Added Participant", isAdmin: false}]}};
                case 'STATUS_MESSAGE':
                    return {...basePayload, event: 'STATUS_MESSAGE', data: {id: "status_" + timestamp, remoteJid: "584142024787@s.whatsapp.net", message: "Test status message", type: "text"}};
                case 'LABELS_EDIT':
                    return {...basePayload, event: 'LABELS_EDIT', data: {labelId: "label_" + timestamp, name: "Test Label", color: "#ff0000", remoteJid: "584142024787@s.whatsapp.net"}};
                case 'LABELS_ASSOCIATION':
                    return {...basePayload, event: 'LABELS_ASSOCIATION', data: {labelId: "label_" + timestamp, remoteJid: "584142024787@s.whatsapp.net", action: "add", instance: instance}};
                case 'CALL_WEBHOOK':
                    return {...basePayload, event: 'CALL_WEBHOOK', data: {callId: "call_" + timestamp, remoteJid: "584142024787@s.whatsapp.net", status: "ringing", type: "audio", duration: 0}};
                default:
                    return {...basePayload, event: eventName, data: {message: "Test payload for " + eventName}};
            }
        }
        
        async function testAllEvents() {
            const allEvents = [
                'QRCODE_UPDATED', 'CONNECTION_UPDATE', 'NEW_TOKEN', 'APPLICATION_STARTUP',
                'MESSAGES_SET', 'MESSAGES_UPSERT', 'MESSAGES_UPDATE', 'MESSAGES_DELETE', 'SEND_MESSAGE',
                'CONTACTS_SET', 'CONTACTS_UPSERT', 'CONTACTS_UPDATE',
                'CHATS_SET', 'CHATS_UPSERT', 'CHATS_UPDATE', 'CHATS_DELETE',
                'GROUPS_UPSERT', 'GROUPS_UPDATE',
                'TYPEBOT_START', 'TYPEBOT_CHANGE_STATUS',
                'PRESENCE_UPDATE', 'GROUP_PARTICIPANTS_UPDATE',
                'STATUS_MESSAGE', 'LABELS_EDIT', 'LABELS_ASSOCIATION', 'CALL_WEBHOOK'
            ];
            
            testedCount = 0;
            successCount = 0;
            errorCount = 0;
            updateStats();
            
            for (let i = 0; i < allEvents.length; i++) {
                updateProgress(i + 1, allEvents.length);
                await testEvent(allEvents[i]);
                await new Promise(resolve => setTimeout(resolve, 200));
            }
        }
        
        async function testSystemEvents() {
            const events = ['QRCODE_UPDATED', 'CONNECTION_UPDATE', 'NEW_TOKEN', 'APPLICATION_STARTUP'];
            for (const event of events) {
                await testEvent(event);
                await new Promise(resolve => setTimeout(resolve, 200));
            }
        }
        
        async function testMessageEvents() {
            const events = ['MESSAGES_SET', 'MESSAGES_UPSERT', 'MESSAGES_UPDATE', 'MESSAGES_DELETE', 'SEND_MESSAGE'];
            for (const event of events) {
                await testEvent(event);
                await new Promise(resolve => setTimeout(resolve, 200));
            }
        }
        
        async function testContactEvents() {
            const events = ['CONTACTS_SET', 'CONTACTS_UPSERT', 'CONTACTS_UPDATE'];
            for (const event of events) {
                await testEvent(event);
                await new Promise(resolve => setTimeout(resolve, 200));
            }
        }
        
        async function testChatEvents() {
            const events = ['CHATS_SET', 'CHATS_UPSERT', 'CHATS_UPDATE', 'CHATS_DELETE'];
            for (const event of events) {
                await testEvent(event);
                await new Promise(resolve => setTimeout(resolve, 200));
            }
        }
        
        async function testGroupEvents() {
            const events = ['GROUPS_UPSERT', 'GROUPS_UPDATE'];
            for (const event of events) {
                await testEvent(event);
                await new Promise(resolve => setTimeout(resolve, 200));
            }
        }
        
        async function testTypebotEvents() {
            const events = ['TYPEBOT_START', 'TYPEBOT_CHANGE_STATUS'];
            for (const event of events) {
                await testEvent(event);
                await new Promise(resolve => setTimeout(resolve, 200));
            }
        }
        
        function clearResponse() {
            const responseDiv = document.getElementById('response');
            responseDiv.style.display = 'none';
            responseDiv.textContent = '';
            testedCount = 0;
            successCount = 0;
            errorCount = 0;
            updateStats();
            document.getElementById('progress-container').style.display = 'none';
        }
    </script>
</body>
</html>
