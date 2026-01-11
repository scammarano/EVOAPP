<?php
// Test POST Real - Auto-ejecuta POST cuando se accede por GET
?>
<!DOCTYPE html>
<html>
<head>
    <title>Webhook POST Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .auto-test { background: #28a745; color: white; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
        .response { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; white-space: pre-wrap; font-family: monospace; }
        .success { border-left: 4px solid #28a745; background: #d4edda; }
        .error { border-left: 4px solid #dc3545; background: #f8d7da; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 15px 0; border-radius: 5px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Webhook POST Test - Solución Definitiva</h1>
        
        <div class="auto-test">
            <h2>🚀 Auto-ejecutando Test POST</h2>
            <p>Esta página automáticamente enviará una petición POST al webhook para probarlo correctamente.</p>
            <div id="auto-status">⏳ Iniciando test POST...</div>
        </div>
        
        <div id="response" class="response" style="display: none;"></div>
        
        <div class="info">
            <h3>📋 ¿Por qué GET no funciona?</h3>
            <ul>
                <li>📡 <strong>GET</strong> es para navegar/páginas web</li>
                <li>📤 <strong>POST</strong> es para enviar datos (webhooks)</li>
                <li>🌐 Los navegadores siempre usan GET al escribir URLs</li>
                <li>🤖 Evolution API envía POST con JSON body</li>
            </ul>
        </div>
        
        <div style="margin-top: 30px;">
            <h3>🔧 Opciones de Prueba:</h3>
            <button onclick="testMessages()">📨 Test Messages</button>
            <button onclick="testContacts()">👥 Test Contacts</button>
            <button onclick="testChats()">💬 Test Chats</button>
            <button onclick="clearResponse()">🗑️ Clear</button>
        </div>
    </div>

    <script>
        // Auto-ejecutar test POST cuando carga la página
        window.onload = function() {
            setTimeout(() => {
                testMessages(); // Test automático
            }, 1000);
        };
        
        function showResponse(data, type) {
            const responseDiv = document.getElementById('response');
            const statusDiv = document.getElementById('auto-status');
            
            responseDiv.style.display = 'block';
            responseDiv.className = 'response ' + type;
            responseDiv.textContent = JSON.stringify(data, null, 2);
            
            if (type === 'success') {
                statusDiv.innerHTML = '✅ Test POST completado exitosamente';
            } else {
                statusDiv.innerHTML = '❌ Error en test POST';
            }
        }
        
        async function testMessages() {
            const payload = {
                event: "messages.upsert",
                data: {
                    key: {
                        remoteJid: "584142024787@s.whatsapp.net",
                        id: "AUTO_TEST_" + Date.now(),
                        fromMe: false
                    },
                    message: {
                        conversation: "Auto test message from POST"
                    },
                    messageTimestamp: Date.now() * 1000,
                    pushname: "Auto Test Contact"
                }
            };
            
            await sendPostRequest(payload);
        }
        
        async function testContacts() {
            const payload = {
                event: "contacts.update",
                data: {
                    remoteJid: "584142024787@s.whatsapp.net",
                    pushname: "Auto Updated Contact",
                    profilePicUrl: "https://example.com/auto-pic.jpg"
                }
            };
            
            await sendPostRequest(payload);
        }
        
        async function testChats() {
            const payload = {
                event: "chats.upsert",
                data: {
                    id: "584142024787@s.whatsapp.net",
                    name: "Auto Test Chat",
                    unreadMessages: 1
                }
            };
            
            await sendPostRequest(payload);
        }
        
        async function sendPostRequest(payload) {
            try {
                const response = await fetch('webhook_test_simple.php?instance=SCAMMARANO', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Webhook-Token': 'auto-test-token'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                showResponse(data, response.ok ? 'success' : 'error');
                
            } catch (error) {
                showResponse({error: 'Network error: ' + error.message}, 'error');
            }
        }
        
        function clearResponse() {
            const responseDiv = document.getElementById('response');
            const statusDiv = document.getElementById('auto-status');
            
            responseDiv.style.display = 'none';
            statusDiv.innerHTML = '⏳ Listo para nuevo test';
        }
    </script>
</body>
</html>
