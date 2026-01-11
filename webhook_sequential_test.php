<?php
// Test Secuencial COMPLETO con Reporte Copiable
?>
<!DOCTYPE html>
<html>
<head>
    <title>Webhook Sequential Test - Full Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .test-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .progress-section { text-align: center; padding: 20px; background: #e9ecef; border-radius: 8px; margin: 20px 0; }
        .progress { width: 100%; background: #e9ecef; border-radius: 4px; margin: 10px 0; }
        .progress-bar { height: 25px; background: linear-gradient(90deg, #28a745, #20c997); border-radius: 4px; transition: width 0.5s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .results-section { background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .report-box { background: #f8f9fa; padding: 15px; border-radius: 5px; border: 2px solid #28a745; margin: 15px 0; }
        .report-content { font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.4; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
        .copy-button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 10px 5px; }
        .copy-button:hover { background: #0056b3; }
        .start-button { background: #28a745; color: white; padding: 20px 40px; border: none; border-radius: 8px; cursor: pointer; font-size: 18px; font-weight: bold; }
        .start-button:hover { background: #218838; }
        .start-button:disabled { background: #6c757d; cursor: not-allowed; }
        .event-status { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; margin: 2px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .pending { background: #fff3cd; color: #856404; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: white; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #007bff; }
        .stat-number { font-size: 24px; font-weight: bold; color: #007bff; }
        .stat-label { font-size: 14px; color: #6c757d; }
        .log-entry { margin: 2px 0; padding: 2px 5px; border-radius: 3px; font-size: 11px; }
        .log-success { background: #d4edda; }
        .log-error { background: #f8d7da; }
        .log-info { background: #d1ecf1; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Webhook Sequential Test - Full Report</h1>
            <p>Prueba secuencial de TODOS los 18 eventos Evolution API con reporte copiable</p>
        </div>
        
        <div class="progress-section">
            <h2 id="status-title">🎯 Listo para test secuencial completo</h2>
            <div class="progress">
                <div class="progress-bar" id="progress-bar" style="width: 0%">0%</div>
            </div>
            <p id="status-text">18 eventos listos para probar</p>
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number" id="total-events">18</div>
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
                    <div class="stat-number" id="time-elapsed">0s</div>
                    <div class="stat-label">Tiempo</div>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <button id="start-button" class="start-button" onclick="startSequentialTest()">
                🚀 INICIAR TEST SECUENCIAL COMPLETO
            </button>
            <button class="copy-button" onclick="copyReport()" id="copy-button" style="display: none;">
                📋 COPIAR REPORTE COMPLETO
            </button>
            <button class="copy-button" onclick="copyJSONReport()" id="copy-json-button" style="display: none;">
                📄 COPIAR REPORTE JSON
            </button>
            <button class="copy-button" onclick="downloadReport()" id="download-button" style="display: none;">
                💾 DESCARGAR REPORTE
            </button>
        </div>
        
        <div class="results-section" id="results-section" style="display: none;">
            <h2>📊 Resultados del Test Secuencial</h2>
            <div class="report-box">
                <h3>📋 Reporte Completo (Copiar y Pegar)</h3>
                <div class="report-content" id="report-content"></div>
            </div>
            
            <div class="report-box">
                <h3>📄 Reporte JSON (Para integraciones)</h3>
                <div class="report-content" id="json-report-content"></div>
            </div>
            
            <div class="report-box">
                <h3>📝 Log Detallado</h3>
                <div id="detailed-log"></div>
            </div>
        </div>
    </div>

    <script>
        let testResults = [];
        let startTime = null;
        let isRunning = false;
        
        const allEvents = [
            {name: 'QRCODE_UPDATED', category: 'system', description: 'Actualización de código QR'},
            {name: 'CONNECTION_UPDATE', category: 'system', description: 'Estado de conexión WhatsApp'},
            {name: 'NEW_TOKEN', category: 'system', description: 'Actualización de token JWT'},
            {name: 'MESSAGES_SET', category: 'messages', description: 'Carga inicial de mensajes'},
            {name: 'MESSAGES_UPSERT', category: 'messages', description: 'Nuevos mensajes recibidos'},
            {name: 'MESSAGES_UPDATE', category: 'messages', description: 'Actualización de estado mensajes'},
            {name: 'MESSAGES_DELETE', category: 'messages', description: 'Eliminación de mensajes'},
            {name: 'SEND_MESSAGE', category: 'messages', description: 'Mensajes enviados'},
            {name: 'CONTACTS_SET', category: 'contacts', description: 'Carga inicial de contactos'},
            {name: 'CONTACTS_UPSERT', category: 'contacts', description: 'Actualización de contactos'},
            {name: 'CONTACTS_UPDATE', category: 'contacts', description: 'Actualización individual'},
            {name: 'CHATS_SET', category: 'chats', description: 'Carga inicial de chats'},
            {name: 'CHATS_UPSERT', category: 'chats', description: 'Nuevos chats creados'},
            {name: 'CHATS_UPDATE', category: 'chats', description: 'Actualización de chats'},
            {name: 'CHATS_DELETE', category: 'chats', description: 'Eliminación de chats'},
            {name: 'GROUPS_UPSERT', category: 'groups', description: 'Creación de grupos'},
            {name: 'GROUPS_UPDATE', category: 'groups', description: 'Actualización de grupos'},
            {name: 'PRESENCE_UPDATE', category: 'presence', description: 'Estado de presencia'},
            {name: 'GROUP_PARTICIPANTS_UPDATE', category: 'participants', description: 'Cambios en participantes'}
        ];
        
        function updateProgress(current, total, eventName = '') {
            const percentage = Math.round((current / total) * 100);
            const progressBar = document.getElementById('progress-bar');
            progressBar.style.width = percentage + '%';
            progressBar.textContent = `${percentage}% (${current}/${total})`;
            
            const successCount = testResults.filter(r => r.success).length;
            const errorCount = testResults.filter(r => !r.success).length;
            
            document.getElementById('success-count').textContent = successCount;
            document.getElementById('error-count').textContent = errorCount;
            
            if (startTime) {
                const elapsed = Math.round((Date.now() - startTime) / 1000);
                document.getElementById('time-elapsed').textContent = elapsed + 's';
            }
            
            if (eventName) {
                document.getElementById('status-title').textContent = `🧪 Probando: ${eventName}`;
                document.getElementById('status-text').textContent = `Evento ${current}/${total} - ${successCount} exitosos, ${errorCount} fallidos`;
            }
        }
        
        function addLogEntry(message, type = 'info') {
            const logDiv = document.getElementById('detailed-log');
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = `log-entry log-${type}`;
            logEntry.textContent = `[${timestamp}] ${message}`;
            logDiv.appendChild(logEntry);
            logDiv.scrollTop = logDiv.scrollHeight;
        }
        
        async function testSingleEvent(event, index) {
            const eventName = event.name;
            addLogEntry(`Iniciando test de ${eventName}...`, 'info');
            
            try {
                const payload = createPayloadForEvent(eventName);
                
                const response = await fetch('webhook_test_simple.php?instance=SCAMMARANO', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Webhook-Token': 'sequential-test-token'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                const success = response.ok && data.success;
                
                const result = {
                    event: eventName,
                    category: event.category,
                    description: event.description,
                    success: success,
                    response: data,
                    timestamp: new Date().toISOString(),
                    duration: Date.now() - startTime,
                    payload: payload
                };
                
                testResults.push(result);
                
                if (success) {
                    addLogEntry(`✅ ${eventName} - Exitoso`, 'success');
                } else {
                    addLogEntry(`❌ ${eventName} - Fallido: ${data.error || 'Unknown error'}`, 'error');
                }
                
                return result;
                
            } catch (error) {
                const result = {
                    event: eventName,
                    category: event.category,
                    description: event.description,
                    success: false,
                    error: error.message,
                    timestamp: new Date().toISOString(),
                    duration: Date.now() - startTime
                };
                
                testResults.push(result);
                addLogEntry(`❌ ${eventName} - Error de red: ${error.message}`, 'error');
                
                return result;
            }
        }
        
        function createPayloadForEvent(eventName) {
            const timestamp = Date.now();
            const basePayload = {
                date_time: new Date().toISOString(),
                instance: "SCAMMARANO"
            };
            
            switch (eventName) {
                case 'QRCODE_UPDATED':
                    return {...basePayload, event: 'QRCODE_UPDATED', data: {qrcode: {base64: 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='}}};
                case 'CONNECTION_UPDATE':
                    return {...basePayload, event: 'CONNECTION_UPDATE', data: {state: 'open', instance: 'SCAMMARANO'}};
                case 'NEW_TOKEN':
                    return {...basePayload, event: 'NEW_TOKEN', data: {token: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.test.token'}};
                case 'MESSAGES_SET':
                    return {...basePayload, event: 'MESSAGES_SET', data: [{key: {remoteJid: "584142024787@s.whatsapp.net", id: "MSG_SET_1", fromMe: false}, message: {conversation: "Initial load message 1"}, messageTimestamp: timestamp * 1000}]};
                case 'MESSAGES_UPSERT':
                    return {...basePayload, event: 'MESSAGES_UPSERT', data: {key: {remoteJid: "584142024787@s.whatsapp.net", id: "MSG_NEW_" + timestamp, fromMe: false}, message: {conversation: "New message"}, messageTimestamp: timestamp * 1000, pushname: "Test Contact"}};
                case 'MESSAGES_UPDATE':
                    return {...basePayload, event: 'MESSAGES_UPDATE', data: {key: {remoteJid: "584142024787@s.whatsapp.net", id: "MSG_UPDATE_" + timestamp, fromMe: true}, status: 'read', messageTimestamp: timestamp * 1000}};
                case 'MESSAGES_DELETE':
                    return {...basePayload, event: 'MESSAGES_DELETE', data: {key: {remoteJid: "584142024787@s.whatsapp.net", id: "MSG_DELETE_" + timestamp, fromMe: false}, messageTimestamp: timestamp * 1000}};
                case 'SEND_MESSAGE':
                    return {...basePayload, event: 'SEND_MESSAGE', data: {key: {remoteJid: "584142024787@s.whatsapp.net", id: "MSG_SENT_" + timestamp, fromMe: true}, message: {conversation: "Sent message"}, messageTimestamp: timestamp * 1000, status: 'sent'}};
                case 'CONTACTS_SET':
                    return {...basePayload, event: 'CONTACTS_SET', data: [{remoteJid: "584142024787@s.whatsapp.net", pushname: "Contact 1", profilePicUrl: "https://example.com/pic1.jpg"}]};
                case 'CONTACTS_UPSERT':
                    return {...basePayload, event: 'CONTACTS_UPSERT', data: [{remoteJid: "584142024787@s.whatsapp.net", pushname: "Updated Contact", profilePicUrl: "https://example.com/updated.jpg"}]};
                case 'CONTACTS_UPDATE':
                    return {...basePayload, event: 'CONTACTS_UPDATE', data: {remoteJid: "584142024787@s.whatsapp.net", pushname: "Single Updated Contact", profilePicUrl: "https://example.com/single.jpg"}};
                case 'CHATS_SET':
                    return {...basePayload, event: 'CHATS_SET', data: [{id: "584142024787@s.whatsapp.net", name: "Chat 1", unreadMessages: 2}]};
                case 'CHATS_UPSERT':
                    return {...basePayload, event: 'CHATS_UPSERT', data: {id: "584142024787@s.whatsapp.net", name: "New Chat", unreadMessages: 1}};
                case 'CHATS_UPDATE':
                    return {...basePayload, event: 'CHATS_UPDATE', data: {remoteJid: "584142024787@s.whatsapp.net", lastMessageTimestamp: timestamp * 1000}};
                case 'CHATS_DELETE':
                    return {...basePayload, event: 'CHATS_DELETE', data: {id: "584142024787@s.whatsapp.net"}};
                case 'GROUPS_UPSERT':
                    return {...basePayload, event: 'GROUPS_UPSERT', data: {id: "584142404172-1496408994@g.us", subject: "Test Group", desc: "Test group description"}};
                case 'GROUPS_UPDATE':
                    return {...basePayload, event: 'GROUPS_UPDATE', data: {id: "584142404172-1496408994@g.us", subject: "Updated Group"}};
                case 'PRESENCE_UPDATE':
                    return {...basePayload, event: 'PRESENCE_UPDATE', data: {id: "584142024787@s.whatsapp.net", presences: {"584142024787@s.whatsapp.net": {lastKnownPresence: "available"}}}};
                case 'GROUP_PARTICIPANTS_UPDATE':
                    return {...basePayload, event: 'GROUP_PARTICIPANTS_UPDATE', data: {id: "584142404172-1496408994@g.us", action: "add", participants: [{userJid: "584142024787@s.whatsapp.net", name: "Added Participant"}]}};
                default:
                    return {...basePayload, event: eventName, data: {message: "Test payload for " + eventName}};
            }
        }
        
        async function startSequentialTest() {
            if (isRunning) return;
            
            isRunning = true;
            startTime = Date.now();
            testResults = [];
            
            const startButton = document.getElementById('start-button');
            startButton.disabled = true;
            startButton.textContent = '⏳ TEST EN PROGRESO...';
            
            // Limpiar logs anteriores
            document.getElementById('detailed-log').innerHTML = '';
            document.getElementById('results-section').style.display = 'none';
            
            addLogEntry('🚀 Iniciando test secuencial completo de 18 eventos Evolution API', 'info');
            addLogEntry(`📊 Fecha y hora: ${new Date().toLocaleString()}`, 'info');
            addLogEntry(`🌐 Instance: SCAMMARANO`, 'info');
            addLogEntry(`🔗 Webhook: webhook_test_simple.php`, 'info');
            
            for (let i = 0; i < allEvents.length; i++) {
                const event = allEvents[i];
                updateProgress(i + 1, allEvents.length, event.name);
                
                await testSingleEvent(event, i);
                
                // Pequeña pausa entre eventos para no sobrecargar
                await new Promise(resolve => setTimeout(resolve, 300));
            }
            
            // Finalizar test
            const totalTime = Math.round((Date.now() - startTime) / 1000);
            const successCount = testResults.filter(r => r.success).length;
            const errorCount = testResults.filter(r => !r.success).length;
            
            updateProgress(allEvents.length, allEvents.length);
            
            addLogEntry(`🎉 Test completado en ${totalTime}s`, 'success');
            addLogEntry(`📊 Resultados: ${successCount} exitosos, ${errorCount} fallidos`, successCount === allEvents.length ? 'success' : 'error');
            
            // Generar reportes
            generateReports();
            
            // Mostrar resultados
            document.getElementById('results-section').style.display = 'block';
            document.getElementById('copy-button').style.display = 'inline-block';
            document.getElementById('copy-json-button').style.display = 'inline-block';
            document.getElementById('download-button').style.display = 'inline-block';
            
            startButton.disabled = false;
            startButton.textContent = '🔄 REPETIR TEST SECUENCIAL';
            
            document.getElementById('status-title').textContent = `🎉 Test Completado`;
            document.getElementById('status-text').textContent = `${allEvents.length} eventos probados en ${totalTime}s: ${successCount} exitosos, ${errorCount} fallidos`;
            
            isRunning = false;
        }
        
        function generateReports() {
            const successCount = testResults.filter(r => r.success).length;
            const errorCount = testResults.filter(r => !r.success).length;
            const totalTime = Math.round((Date.now() - startTime) / 1000);
            
            // Reporte de texto formateado
            const textReport = generateTextReport();
            document.getElementById('report-content').textContent = textReport;
            
            // Reporte JSON
            const jsonReport = generateJSONReport();
            document.getElementById('json-report-content').textContent = JSON.stringify(jsonReport, null, 2);
        }
        
        function generateTextReport() {
            const successCount = testResults.filter(r => r.success).length;
            const errorCount = testResults.filter(r => !r.success).length;
            const totalTime = Math.round((Date.now() - startTime) / 1000);
            const timestamp = new Date().toLocaleString();
            
            let report = `╔══════════════════════════════════════════════════════════════╗
║                    🚀 WEBHOOK EVOLUTION API TEST REPORT              ║
╚══════════════════════════════════════════════════════════════╝

📅 Fecha y Hora: ${timestamp}
🌐 Instance: SCAMMARANO
🔗 Webhook URL: https://camcam.com.ve/evoappws/webhook_test_simple.php
⏱️  Duración Total: ${totalTime} segundos
📊 Total Eventos: ${allEvents.length}
✅ Exitosos: ${successCount}
❌ Fallidos: ${errorCount}
📈 Tasa Éxito: ${Math.round((successCount / allEvents.length) * 100)}%

────────────────────────────────────────────────────────────────────
📋 RESULTADOS POR EVENTO:
────────────────────────────────────────────────────────────────────
`;
            
            testResults.forEach((result, index) => {
                const status = result.success ? '✅' : '❌';
                const time = Math.round(result.duration / 1000);
                report += `\n${index + 1:2}. ${status} ${result.event} (${result.category}) - ${time}s`;
                report += `\n    📝 ${result.description}`;
                
                if (!result.success) {
                    report += `\n    ❌ Error: ${result.error || result.response?.error || 'Unknown error'}`;
                } else {
                    report += `\n    ✅ Procesado exitosamente`;
                }
                report += `\n    🕐 ${new Date(result.timestamp).toLocaleTimeString()}`;
            });
            
            report += `\n
────────────────────────────────────────────────────────────────────
📊 RESUMEN POR CATEGORÍA:
────────────────────────────────────────────────────────────────────
`;
            
            const categories = ['system', 'messages', 'contacts', 'chats', 'groups', 'presence', 'participants'];
            categories.forEach(category => {
                const categoryEvents = testResults.filter(r => r.category === category);
                const categorySuccess = categoryEvents.filter(r => r.success).length;
                const categoryTotal = categoryEvents.length;
                report += `\n📂 ${category.toUpperCase()}: ${categorySuccess}/${categoryTotal} (${Math.round((categorySuccess / categoryTotal) * 100)}%)`;
            });
            
            report += `\n
────────────────────────────────────────────────────────────────────
🎯 CONCLUSIÓN:
────────────────────────────────────────────────────────────────────
`;
            
            if (successCount === allEvents.length) {
                report += `\n🎉 ¡TODOS LOS EVENTOS FUNCIONAN CORRECTAMENTE!
✅ El webhook está 100% operativo para Evolution API
🚀 Listo para configurar webhooks reales en producción
📈 Sistema preparado para sincronización completa de WhatsApp`;
            } else {
                report += `\n⚠️  ${errorCount} eventos necesitan atención
🔧 Revisar los eventos fallidos y corregir problemas
📊 ${successCount}/${allEvents.length} eventos funcionan correctamente
🛠️  Sistema parcialmente operativo`;
            }
            
            report += `\n
────────────────────────────────────────────────────────────────────
📝 NOTAS:
────────────────────────────────────────────────────────────────────
• Test ejecutado con payloads realistas de Evolution API
• Cada evento probado con método POST y JSON válido
• Tiempo de respuesta promedio: ${Math.round(testResults.reduce((sum, r) => sum + (r.duration || 0), 0) / testResults.length)}ms
• Webhook endpoint: webhook_test_simple.php?instance=SCAMMARANO
• Todos los timestamps y IDs son únicos para cada test

────────────────────────────────────────────────────────────────────
🔚 FIN DEL REPORTE
────────────────────────────────────────────────────────────────────
Generado por: Webhook Sequential Test v1.0
URL: https://camcam.com.ve/evoappws/webhook_sequential_test.php`;
            
            return report;
        }
        
        function generateJSONReport() {
            const successCount = testResults.filter(r => r.success).length;
            const errorCount = testResults.filter(r => !r.success).length;
            const totalTime = Math.round((Date.now() - startTime) / 1000);
            
            return {
                test_info: {
                    timestamp: new Date().toISOString(),
                    instance: "SCAMMARANO",
                    webhook_url: "https://camcam.com.ve/evoappws/webhook_test_simple.php",
                    total_duration_seconds: totalTime,
                    total_events: allEvents.length,
                    successful_events: successCount,
                    failed_events: errorCount,
                    success_rate: Math.round((successCount / allEvents.length) * 100),
                    test_type: "sequential_complete",
                    api_version: "Evolution API v1.0"
                },
                results: testResults,
                summary_by_category: (() => {
                    const categories = {};
                    testResults.forEach(result => {
                        if (!categories[result.category]) {
                            categories[result.category] = {total: 0, success: 0};
                        }
                        categories[result.category].total++;
                        if (result.success) categories[result.category].success++;
                    });
                    
                    Object.keys(categories).forEach(cat => {
                        categories[cat].success_rate = Math.round((categories[cat].success / categories[cat].total) * 100);
                    });
                    
                    return categories;
                })(),
                conclusion: {
                    all_events_successful: successCount === allEvents.length,
                    webhook_ready: successCount === allEvents.length,
                    needs_attention: errorCount > 0,
                    recommended_action: successCount === allEvents.length ? 
                        "Configure Evolution API webhooks in production" : 
                        "Fix failed events before production deployment"
                }
            };
        }
        
        function copyReport() {
            const reportContent = document.getElementById('report-content').textContent;
            navigator.clipboard.writeText(reportContent).then(() => {
                alert('📋 ¡Reporte copiado al portapapeles!');
            }).catch(err => {
                console.error('Error al copiar:', err);
                // Fallback: seleccionar texto
                const textArea = document.createElement('textarea');
                textArea.value = reportContent;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('📋 ¡Reporte copiado al portapapeles!');
            });
        }
        
        function copyJSONReport() {
            const jsonContent = document.getElementById('json-report-content').textContent;
            navigator.clipboard.writeText(jsonContent).then(() => {
                alert('📄 ¡Reporte JSON copiado al portapapeles!');
            }).catch(err => {
                console.error('Error al copiar JSON:', err);
                const textArea = document.createElement('textarea');
                textArea.value = jsonContent;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('📄 ¡Reporte JSON copiado al portapapeles!');
            });
        }
        
        function downloadReport() {
            const reportContent = document.getElementById('report-content').textContent;
            const blob = new Blob([reportContent], {type: 'text/plain'});
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `webhook_test_report_${new Date().toISOString().replace(/[:.]/g, '-')}.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
