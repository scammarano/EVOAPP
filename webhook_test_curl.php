<?php
// Test de Webhook con cURL para simular Evolution API
?>
<!DOCTYPE html>
<html>
<head>
    <title>Webhook cURL Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .test-button { background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; margin: 10px; font-size: 16px; }
        .test-button:hover { background: #218838; }
        .response { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; white-space: pre-wrap; font-family: monospace; }
        .success { border-left: 4px solid #28a745; }
        .error { border-left: 4px solid #dc3545; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 15px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Webhook cURL Test - Simulación Evolution API</h1>
        
        <div class="info">
            <h3>📋 Información:</h3>
            <p>Esta página realizará peticiones POST reales al webhook para simular la Evolution API.</p>
            <p><strong>URL del webhook:</strong> <code>webhook_test_simple.php?instance=SCAMMARANO</code></p>
        </div>
        
        <button class="test-button" onclick="testMessagesUpsert()">📨 Test messages.upsert</button>
        <button class="test-button" onclick="testContactsUpdate()">👥 Test contacts.update</button>
        <button class="test-button" onclick="testChatsUpsert()">💬 Test chats.upsert</button>
        <button class="test-button" onclick="testPresenceUpdate()">📱 Test presence.update</button>
        <button class="test-button" onclick="testConnectionUpdate()">🔗 Test connection-update</button>
        
        <div id="response" class="response" style="display: none;"></div>
        
        <div style="margin-top: 30px;">
            <h3>🔧 Para probar manualmente con cURL:</h3>
            <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
curl -X POST "https://camcam.com.ve/evoappws/webhook_test_simple.php?instance=SCAMMARANO" \
     -H "Content-Type: application/json" \
     -d '{
       "event": "messages.upsert",
       "data": {
         "key": {
           "remoteJid": "584142024787@s.whatsapp.net",
           "id": "3EB0D1FDEBFC756912461A",
           "fromMe": false
         },
         "message": {
           "conversation": "Test message from cURL"
         },
         "messageTimestamp": 1642678800000,
         "pushname": "Test Contact"
       }
     }'
            </pre>
        </div>
    </div>

    <script>
        function showResponse(data, type) {
            const responseDiv = document.getElementById('response');
            responseDiv.style.display = 'block';
            responseDiv.className = 'response ' + type;
            responseDiv.textContent = JSON.stringify(data, null, 2);
        }
        
        async function testMessagesUpsert() {
            const payload = {
                event: "messages.upsert",
                data: {
                    key: {
                        remoteJid: "584142024787@s.whatsapp.net",
                        id: "3EB0D1FDEBFC756912461A",
                        fromMe: false
                    },
                    message: {
                        conversation: "Test message from cURL"
                    },
                    messageTimestamp: 1642678800000,
                    pushname: "Test Contact"
                }
            };
            
            await sendWebhookTest(payload);
        }
        
        async function testContactsUpdate() {
            const payload = {
                event: "contacts.update",
                data: {
                    remoteJid: "584142024787@s.whatsapp.net",
                    pushname: "Updated Contact",
                    profilePicUrl: "https://example.com/pic.jpg"
                }
            };
            
            await sendWebhookTest(payload);
        }
        
        async function testChatsUpsert() {
            const payload = {
                event: "chats.upsert",
                data: {
                    id: "584142024787@s.whatsapp.net",
                    name: "Test Chat",
                    unreadMessages: 1
                }
            };
            
            await sendWebhookTest(payload);
        }
        
        async function testPresenceUpdate() {
            const payload = {
                event: "presence.update",
                data: {
                    id: "584142024787@s.whatsapp.net",
                    presences: {
                        "584142024787@s.whatsapp.net": {
                            lastKnownPresence: "available"
                        }
                    }
                }
            };
            
            await sendWebhookTest(payload);
        }
        
        async function testConnectionUpdate() {
            const payload = {
                event: "connection-update",
                data: {
                    state: "open",
                    instance: "SCAMMARANO"
                }
            };
            
            await sendWebhookTest(payload);
        }
        
        async function sendWebhookTest(payload) {
            try {
                showResponse({status: "Sending request...", payload: payload}, 'info');
                
                const response = await fetch('webhook_test_simple.php?instance=SCAMMARANO', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Webhook-Token': 'test-token'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                showResponse(data, response.ok ? 'success' : 'error');
                
            } catch (error) {
                showResponse({error: 'Network error: ' + error.message}, 'error');
            }
        }
    </script>
</body>
</html>
