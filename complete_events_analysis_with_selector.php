<?php
// Análisis completo con selector de instancia y reporte copiable
echo "<h1>📊 Análisis Completo de Eventos Evolution API - Con Selector</h1>";

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['instance'])) {
    $selectedInstance = $_POST['instance'];
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>✅ Instancia Seleccionada: <strong>{$selectedInstance}</strong></h2>";
    echo "<p>Procesando análisis para la instancia {$selectedInstance}...</p>";
    echo "</div>";
    
    // Generar reporte completo para la instancia seleccionada
    generateCompleteReport($selectedInstance);
} else {
    // Mostrar formulario de selección
    showInstanceSelector();
}

function showInstanceSelector() {
    echo "<div style='background: #e9ecef; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;'>";
    echo "<h2>🏢 Seleccionar Instancia para Análisis</h2>";
    echo "<form method='POST' style='margin: 20px 0;'>";
    echo "<label for='instance' style='font-size: 18px; margin-right: 10px;'>Instancia:</label>";
    echo "<select name='instance' id='instance' style='padding: 10px; font-size: 16px; border-radius: 5px; border: 1px solid #ccc; margin-right: 10px;'>";
    echo "<option value=''>-- Seleccionar Instancia --</option>";
    echo "<option value='SCAMMARANO'>SCAMMARANO</option>";
    echo "<option value='2CAMCARGO'>2CAMCARGO</option>";
    echo "<option value='CASAMIA'>CASAMIA</option>";
    echo "<option value='2CAMSERVICES'>2CAMSERVICES</option>";
    echo "</select>";
    echo "<button type='submit' style='background: #28a745; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>🚀 ANALIZAR EVENTOS</button>";
    echo "</form>";
    echo "</div>";
    
    // Mostrar información general
    showGeneralInfo();
}

function showGeneralInfo() {
    // Lista completa de 26 eventos Evolution API
    $allEvolutionEvents = [
        // System Events (4)
        'QRCODE_UPDATED' => ['category' => 'system', 'description' => 'Actualización de código QR'],
        'CONNECTION_UPDATE' => ['category' => 'system', 'description' => 'Estado de conexión WhatsApp'],
        'NEW_TOKEN' => ['category' => 'system', 'description' => 'Actualización de token JWT'],
        'APPLICATION_STARTUP' => ['category' => 'system', 'description' => 'Inicio de aplicación'],
        
        // Message Events (5)
        'MESSAGES_SET' => ['category' => 'messages', 'description' => 'Carga inicial de mensajes'],
        'MESSAGES_UPSERT' => ['category' => 'messages', 'description' => 'Nuevos mensajes recibidos'],
        'MESSAGES_UPDATE' => ['category' => 'messages', 'description' => 'Actualización de estado mensajes'],
        'MESSAGES_DELETE' => ['category' => 'messages', 'description' => 'Eliminación de mensajes'],
        'SEND_MESSAGE' => ['category' => 'messages', 'description' => 'Mensajes enviados'],
        
        // Contact Events (3)
        'CONTACTS_SET' => ['category' => 'contacts', 'description' => 'Carga inicial de contactos'],
        'CONTACTS_UPSERT' => ['category' => 'contacts', 'description' => 'Actualización de contactos'],
        'CONTACTS_UPDATE' => ['category' => 'contacts', 'description' => 'Actualización individual'],
        
        // Chat Events (4)
        'CHATS_SET' => ['category' => 'chats', 'description' => 'Carga inicial de chats'],
        'CHATS_UPSERT' => ['category' => 'chats', 'description' => 'Nuevos chats creados'],
        'CHATS_UPDATE' => ['category' => 'chats', 'description' => 'Actualización de chats'],
        'CHATS_DELETE' => ['category' => 'chats', 'description' => 'Eliminación de chats'],
        
        // Group Events (2)
        'GROUPS_UPSERT' => ['category' => 'groups', 'description' => 'Creación de grupos'],
        'GROUPS_UPDATE' => ['category' => 'groups', 'description' => 'Actualización de grupos'],
        
        // Typebot Events (2)
        'TYPEBOT_START' => ['category' => 'typebot', 'description' => 'Inicio de Typebot'],
        'TYPEBOT_CHANGE_STATUS' => ['category' => 'typebot', 'description' => 'Cambio de estado Typebot'],
        
        // Additional Events (8)
        'PRESENCE_UPDATE' => ['category' => 'presence', 'description' => 'Estado de presencia'],
        'GROUP_PARTICIPANTS_UPDATE' => ['category' => 'participants', 'description' => 'Cambios en participantes'],
        'STATUS_MESSAGE' => ['category' => 'status', 'description' => 'Mensaje de estado'],
        'LABELS_EDIT' => ['category' => 'labels', 'description' => 'Edición de etiquetas'],
        'LABELS_ASSOCIATION' => ['category' => 'labels', 'description' => 'Asociación de etiquetas'],
        'CALL_WEBHOOK' => ['category' => 'calls', 'description' => 'Webhook de llamadas']
    ];

    echo "<h2>📋 Total de Eventos: " . count($allEvolutionEvents) . "</h2>";

    echo "<h3>📊 Distribución por Categoría:</h3>";
    $categories = [];
    foreach ($allEvolutionEvents as $event => $info) {
        $categories[$info['category']][] = $event;
    }

    foreach ($categories as $category => $events) {
        echo "<div style='background: #f8f9fa; padding: 10px; margin: 5px 0; border-radius: 5px; border-left: 4px solid #007bff;'>";
        echo "<h4>📂 " . strtoupper($category) . " (" . count($events) . " eventos)</h4>";
        echo "<ul>";
        foreach ($events as $event) {
            echo "<li><strong>{$event}</strong> - {$allEvolutionEvents[$event]['description']}</li>";
        }
        echo "</ul>";
        echo "</div>";
    }

    echo "<h3>🎯 Próximos Pasos:</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
    echo "<h4>✅ Para continuar:</h4>";
    echo "<ol>";
    echo "<li>🏢 Selecciona una instancia del dropdown</li>";
    echo "<li>🚀 Click en 'ANALIZAR EVENTOS'</li>";
    echo "<li>📋 Obtendrás el reporte completo para esa instancia</li>";
    echo "<li>📄 Podrás copiar y pegar el resultado</li>";
    echo "</ol>";
    echo "</div>";
}

function generateCompleteReport($instance) {
    $timestamp = date('Y-m-d H:i:s');
    $allEvents = [
        'QRCODE_UPDATED', 'CONNECTION_UPDATE', 'NEW_TOKEN', 'APPLICATION_STARTUP',
        'MESSAGES_SET', 'MESSAGES_UPSERT', 'MESSAGES_UPDATE', 'MESSAGES_DELETE', 'SEND_MESSAGE',
        'CONTACTS_SET', 'CONTACTS_UPSERT', 'CONTACTS_UPDATE',
        'CHATS_SET', 'CHATS_UPSERT', 'CHATS_UPDATE', 'CHATS_DELETE',
        'GROUPS_UPSERT', 'GROUPS_UPDATE',
        'TYPEBOT_START', 'TYPEBOT_CHANGE_STATUS',
        'PRESENCE_UPDATE', 'GROUP_PARTICIPANTS_UPDATE',
        'STATUS_MESSAGE', 'LABELS_EDIT', 'LABELS_ASSOCIATION', 'CALL_WEBHOOK'
    ];
    
    $report = "╔══════════════════════════════════════════════════════════════╗
║                    🚀 WEBHOOK EVOLUTION API ANALYSIS REPORT          ║
╚══════════════════════════════════════════════════════════════╝

📅 Fecha y Hora: {$timestamp}
🌐 Instance: {$instance}
🔗 Webhook URL: https://camcam.com.ve/evoappws/webhook_test_simple.php
📊 Total Eventos: " . count($allEvents) . "
🎯 Tipo de Análisis: Completo de Eventos Evolution API

────────────────────────────────────────────────────────────────────
📋 LISTA COMPLETA DE EVENTOS PARA CONFIGURAR:
────────────────────────────────────────────────────────────────────
";
    
    $categories = [
        'system' => ['QRCODE_UPDATED', 'CONNECTION_UPDATE', 'NEW_TOKEN', 'APPLICATION_STARTUP'],
        'messages' => ['MESSAGES_SET', 'MESSAGES_UPSERT', 'MESSAGES_UPDATE', 'MESSAGES_DELETE', 'SEND_MESSAGE'],
        'contacts' => ['CONTACTS_SET', 'CONTACTS_UPSERT', 'CONTACTS_UPDATE'],
        'chats' => ['CHATS_SET', 'CHATS_UPSERT', 'CHATS_UPDATE', 'CHATS_DELETE'],
        'groups' => ['GROUPS_UPSERT', 'GROUPS_UPDATE'],
        'typebot' => ['TYPEBOT_START', 'TYPEBOT_CHANGE_STATUS'],
        'presence' => ['PRESENCE_UPDATE'],
        'participants' => ['GROUP_PARTICIPANTS_UPDATE'],
        'status' => ['STATUS_MESSAGE'],
        'labels' => ['LABELS_EDIT', 'LABELS_ASSOCIATION'],
        'calls' => ['CALL_WEBHOOK']
    ];
    
    foreach ($categories as $category => $events) {
        $report .= "\n📂 " . strtoupper($category) . " (" . count($events) . " eventos):";
        foreach ($events as $index => $event) {
            $report .= "\n" . ($index + 1) . ". " . $event;
        }
    }
    
    $report .= "\n
────────────────────────────────────────────────────────────────────
🔧 CONFIGURACIÓN WEBHOOK EVOLUTION API:
────────────────────────────────────────────────────────────────────
📡 URL del Webhook: https://camcam.com.ve/evoappws/webhook_test_simple.php
🏢 Instance: {$instance}
🔧 Método: POST
📄 Content-Type: application/json
🛡️ Token: X-Webhook-Token (opcional)

📋 Eventos a Activar (JSON para Evolution API):
{
  \"url\": \"https://camcam.com.ve/evoappws/webhook_test_simple.php\",
  \"webhook_by_events\": false,
  \"events\": [
    \"QRCODE_UPDATED\",
    \"CONNECTION_UPDATE\",
    \"NEW_TOKEN\",
    \"APPLICATION_STARTUP\",
    \"MESSAGES_SET\",
    \"MESSAGES_UPSERT\",
    \"MESSAGES_UPDATE\",
    \"MESSAGES_DELETE\",
    \"SEND_MESSAGE\",
    \"CONTACTS_SET\",
    \"CONTACTS_UPSERT\",
    \"CONTACTS_UPDATE\",
    \"CHATS_SET\",
    \"CHATS_UPSERT\",
    \"CHATS_UPDATE\",
    \"CHATS_DELETE\",
    \"GROUPS_UPSERT\",
    \"GROUPS_UPDATE\",
    \"TYPEBOT_START\",
    \"TYPEBOT_CHANGE_STATUS\",
    \"PRESENCE_UPDATE\",
    \"GROUP_PARTICIPANTS_UPDATE\",
    \"STATUS_MESSAGE\",
    \"LABELS_EDIT\",
    \"LABELS_ASSOCIATION\",
    \"CALL_WEBHOOK\"
  ]
}

────────────────────────────────────────────────────────────────────
🧪 PLAN DE PRUEBAS RECOMENDADO:
────────────────────────────────────────────────────────────────────
1. 📋 Probar eventos individualmente con webhook_manual_test_complete.php
2. 🚀 Ejecutar test secuencial completo con webhook_sequential_test.php
3. 📊 Verificar que todos los 26 eventos funcionen correctamente
4. 🚀 Configurar webhooks reales en Evolution API
5. 📈 Monitorear funcionamiento en producción

────────────────────────────────────────────────────────────────────
🎯 ENDPOINTS DE PRUEBA DISPONIBLES:
────────────────────────────────────────────────────────────────────
🧪 Test Manual Completo: https://camcam.com.ve/evoappws/webhook_manual_test_complete.php
📊 Test Simple: https://camcam.com.ve/evoappws/webhook_test_simple.php
🔍 Debug: https://camcam.com.ve/evoappws/webhook_debug.php
🚀 Test POST: https://camcam.com.ve/evoappws/webhook_post_test.php

────────────────────────────────────────────────────────────────────
🎯 CONCLUSIÓN:
────────────────────────────────────────────────────────────────────
🎉 Análisis completo generado para instancia {$instance}
✅ Todos los 26 eventos Evolution API identificados
🚀 Sistema listo para configuración completa
📋 Reporte copiable para configuración Evolution API

────────────────────────────────────────────────────────────────────
🔚 FIN DEL ANÁLISIS
────────────────────────────────────────────────────────────────────
Generado por: Evolution API Events Analyzer v1.0
URL: https://camcam.com.ve/evoappws/complete_events_analysis_with_selector.php
";
    
    // Mostrar el reporte con opción de copiar
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>📋 Reporte Completo para Instancia: {$instance}</h3>";
    echo "<button onclick='copyReport()' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-bottom: 15px;'>📋 COPIAR REPORTE COMPLETO</button>";
    echo "<textarea id='report-content' style='width: 100%; height: 600px; font-family: \"Courier New\", monospace; font-size: 12px; padding: 10px; border: 1px solid #ccc; border-radius: 5px;'>" . htmlspecialchars($report) . "</textarea>";
    echo "</div>";
    
    echo "<script>
function copyReport() {
    const reportContent = document.getElementById('report-content').value;
    navigator.clipboard.writeText(reportContent).then(() => {
        alert('📋 ¡Reporte copiado al portapapeles!');
    }).catch(err => {
        console.error('Error al copiar:', err);
        const textArea = document.createElement('textarea');
        textArea.value = reportContent;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('📋 ¡Reporte copiado al portapapeles!');
    });
}
</script>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Análisis Completado</h3>";
    echo "<p><strong>📊 Resumen:</strong></p>";
    echo "<ul>";
    echo "<li>🏢 Instancia analizada: <strong>{$instance}</strong></li>";
    echo "<li>📋 Total eventos: <strong>26</strong></li>";
    echo "<li>📂 Categorías: <strong>11</strong> (system, messages, contacts, chats, groups, typebot, presence, participants, status, labels, calls)</li>";
    echo "<li>🔧 Configuración JSON lista para Evolution API</li>";
    echo "<li>📋 Reporte copiable para documentación</li>";
    echo "</ul>";
    echo "</div>";
}
?>
