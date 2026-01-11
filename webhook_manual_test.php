<?php
// Test Manual de Webhook - Simulación de petición Evolution API
?>
<!DOCTYPE html>
<html>
<head>
    <title>Webhook Manual Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
        .response { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 15px 0; white-space: pre-wrap; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Webhook Manual Test - Evolution API</h1>
        
        <form id="webhookForm">
            <div class="form-group">
                <label for="instance">Instance:</label>
                <select id="instance" name="instance">
                    <option value="SCAMMARANO">SCAMMARANO</option>
                    <option value="2CAMCARGO">2CAMCARGO</option>
                    <option value="CASAMIA">CASAMIA</option>
                    <option value="2CAMSERVICES">2CAMSERVICES</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="event">Event Type:</label>
                <select id="event" name="event">
                    <option value="messages.upsert">messages.upsert</option>
                    <option value="contacts.update">contacts.update</option>
                    <option value="chats.upsert">chats.upsert</option>
                    <option value="presence.update">presence.update</option>
                    <option value="connection-update">connection-update</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="webhookUrl">Webhook URL:</label>
                <input type="text" id="webhookUrl" name="webhookUrl" value="webhook_test_simple.php" readonly>
            </div>
            
            <div class="form-group">
                <label for="payload">Payload JSON:</label>
                <textarea id="payload" name="payload" rows="10">{
  "event": "messages.upsert",
  "data": {
    "key": {
      "remoteJid": "584142024787@s.whatsapp.net",
      "id": "3EB0D1FDEBFC756912461A",
      "fromMe": false
    },
    "message": {
      "conversation": "Test message from webhook"
    },
    "messageTimestamp": 1642678800000,
    "pushname": "Test Contact"
  },
  "date_time": "2026-01-11T00:30:00.000Z"
}</textarea>
            </div>
            
            <button type="button" onclick="testWebhook()">🧪 Test Webhook</button>
            <button type="button" onclick="testDebug()">🔍 Test Debug</button>
            <button type="button" onclick="clearResponse()">🗑️ Clear Response</button>
        </form>
        
        <div id="response" class="response" style="display: none;"></div>
        
        <div style="margin-top: 30px; padding: 15px; background: #e2e3e5; border-radius: 4px;">
            <h3>📋 Available Test Endpoints:</h3>
            <ul>
                <li><strong>webhook_test_simple.php</strong> - Test básico con validaciones mínimas</li>
                <li><strong>webhook_debug.php</strong> - Debug completo de la petición</li>
                <li><strong>index.php?r=webhook/evolution</strong> - Webhook real del sistema</li>
            </ul>
            
            <h3>🔧 Troubleshooting:</h3>
            <ul>
                <li>Si "Invalid request" → Verificar que se envíe instance y JSON</li>
                <li>Si no hay respuesta → Verificar URL y método POST</li>
                <li>Si error 500 → Revisar logs de errores del servidor</li>
            </ul>
        </div>
    </div>

    <script>
        function testWebhook() {
            const form = document.getElementById('webhookForm');
            const formData = new FormData(form);
            
            const webhookUrl = formData.get('webhookUrl');
            const instance = formData.get('instance');
            const payload = formData.get('payload');
            
            try {
                const payloadObj = JSON.parse(payload);
                payloadObj.instance = instance; // Agregar instance al payload
                
                fetch(webhookUrl + '?instance=' + instance, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Webhook-Token': 'test-token'
                    },
                    body: JSON.stringify(payloadObj)
                })
                .then(response => response.json())
                .then(data => {
                    showResponse(data, 'success');
                })
                .catch(error => {
                    showResponse({error: 'Network error: ' + error.message}, 'error');
                });
            } catch (e) {
                showResponse({error: 'JSON parse error: ' + e.message}, 'error');
            }
        }
        
        function testDebug() {
            const instance = document.getElementById('instance').value;
            
            fetch('webhook_debug.php?instance=' + instance, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({test: 'debug'})
            })
            .then(response => response.json())
            .then(data => {
                showResponse(data, 'success');
            })
            .catch(error => {
                showResponse({error: 'Debug error: ' + error.message}, 'error');
            });
        }
        
        function showResponse(data, type) {
            const responseDiv = document.getElementById('response');
            responseDiv.style.display = 'block';
            responseDiv.className = 'response ' + type;
            responseDiv.textContent = JSON.stringify(data, null, 2);
        }
        
        function clearResponse() {
            const responseDiv = document.getElementById('response');
            responseDiv.style.display = 'none';
        }
        
        // Actualizar payload cuando cambia el evento
        document.getElementById('event').addEventListener('change', function() {
            const event = this.value;
            const payloadTextarea = document.getElementById('payload');
            
            const payloads = {
                'messages.upsert': {
                    "event": "messages.upsert",
                    "data": {
                        "key": {"remoteJid": "584142024787@s.whatsapp.net", "id": "3EB0D1FDEBFC756912461A", "fromMe": false},
                        "message": {"conversation": "Test message"},
                        "messageTimestamp": 1642678800000,
                        "pushname": "Test Contact"
                    }
                },
                'contacts.update': {
                    "event": "contacts.update",
                    "data": {
                        "remoteJid": "584142024787@s.whatsapp.net",
                        "pushname": "Updated Contact",
                        "profilePicUrl": "https://example.com/pic.jpg"
                    }
                },
                'chats.upsert': {
                    "event": "chats.upsert",
                    "data": {
                        "id": "584142024787@s.whatsapp.net",
                        "name": "Test Chat",
                        "unreadMessages": 1
                    }
                },
                'presence.update': {
                    "event": "presence.update",
                    "data": {
                        "id": "584142024787@s.whatsapp.net",
                        "presences": {
                            "584142024787@s.whatsapp.net": {"lastKnownPresence": "available"}
                        }
                    }
                },
                'connection-update': {
                    "event": "connection-update",
                    "data": {
                        "state": "open",
                        "instance": "SCAMMARANO"
                    }
                }
            };
            
            if (payloads[event]) {
                payloadTextarea.value = JSON.stringify(payloads[event], null, 2);
            }
        });
    </script>
</body>
</html>
