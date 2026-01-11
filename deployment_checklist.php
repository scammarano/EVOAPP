<?php
// Script para generar checklist de despliegue
// Ejecutar: php deployment_checklist.php

echo "<h2>🚀 Checklist de Despliegue - EVOAPP</h2>";

echo "<h3>📋 Archivos Modificados que Necesitan Subirse:</h3>";

$files = [
    'app/Controllers/WebhookController.php' => [
        'description' => 'Controlador de webhooks con eventos mapeados',
        'changes' => ['message_received', 'message_update', 'contact_update', 'chat_update', 'presence_update'],
        'critical' => true
    ],
    'app/Core/MessageSender.php' => [
        'description' => 'Clase para envío de mensajes',
        'changes' => ['Propiedades públicas: $instance, $client'],
        'critical' => true
    ],
    'app/Models/Instance.php' => [
        'description' => 'Modelo de instancias',
        'changes' => ['Método getById() alias', 'updateWebhookTimestamp() mejorado'],
        'critical' => true
    ],
    'app/Controllers/LogsController.php' => [
        'description' => 'Controlador de logs',
        'changes' => ['payload_json as data', 'error_message vs error_text'],
        'critical' => false
    ],
    'app/Controllers/DebugController.php' => [
        'description' => 'Controlador de debug',
        'changes' => ['error_message as error_text'],
        'critical' => false
    ],
    'app/Models/WebhookEvent.php' => [
        'description' => 'Modelo de eventos webhook',
        'changes' => ['error_message vs error_text'],
        'critical' => false
    ]
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Archivo</th><th>Descripción</th><th>Cambios</th><th>Prioridad</th></tr>";

foreach ($files as $file => $info) {
    $priority = $info['critical'] ? '🔴 CRÍTICO' : '🟡 IMPORTANTE';
    $changes = implode(', ', $info['changes']);
    
    echo "<tr>";
    echo "<td><code>$file</code></td>";
    echo "<td>{$info['description']}</td>";
    echo "<td>$changes</td>";
    echo "<td>$priority</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>🔧 Scripts SQL que Necesitan Ejecutarse:</h3>";

$sqlScripts = [
    'add_missing_columns.sql' => [
        'description' => 'Agregar columnas faltantes a evo_instances',
        'tables' => ['evo_instances'],
        'critical' => true
    ],
    'add_webhook_url.sql' => [
        'description' => 'Agregar webhook_url y columnas relacionadas',
        'tables' => ['evo_instances'],
        'critical' => true
    ],
    'add_created_at_column.sql' => [
        'description' => 'Agregar created_at a tabla chats',
        'tables' => ['chats'],
        'critical' => true
    ],
    'create_webhook_timestamp.sql' => [
        'description' => 'Agregar webhook_timestamp a evo_instances',
        'tables' => ['evo_instances'],
        'critical' => true
    ],
    'add_retry_count.sql' => [
        'description' => 'Agregar retry_count a webhook_events',
        'tables' => ['webhook_events'],
        'critical' => true
    ]
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Script SQL</th><th>Descripción</th><th>Tablas</th><th>Prioridad</th></tr>";

foreach ($sqlScripts as $script => $info) {
    $priority = $info['critical'] ? '🔴 CRÍTICO' : '🟡 IMPORTANTE';
    $tables = implode(', ', $info['tables']);
    
    echo "<tr>";
    echo "<td><code>$script</code></td>";
    echo "<td>{$info['description']}</td>";
    echo "<td>$tables</td>";
    echo "<td>$priority</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>📊 Problemas Resueltos:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Unknown webhook event type</strong> - Eventos mapeados en WebhookController</li>";
echo "<li>✅ <strong>Column not found: webhook_timestamp</strong> - Columna agregada</li>";
echo "<li>✅ <strong>Column not found: created_at</strong> - Columna agregada</li>";
echo "<li>✅ <strong>Column not found: retry_count</strong> - Columna agregada</li>";
echo "<li>✅ <strong>Cannot access private property</strong> - Propiedades públicas</li>";
echo "<li>✅ <strong>Call to undefined method getById</strong> - Método agregado</li>";
echo "<li>✅ <strong>Column not found: data</strong> - Alias payload_json as data</li>";
echo "<li>✅ <strong>Column not found: error_text</strong> - Usar error_message</li>";
echo "</ul>";

echo "<h3>🚀 Orden de Despliegue Recomendado:</h3>";
echo "<ol>";
echo "<li><strong>1. Ejecutar Scripts SQL</strong> (todos los críticos)</li>";
echo "<li><strong>2. Subir Archivos Críticos</strong> (WebhookController, MessageSender, Instance)</li>";
echo "<li><strong>3. Subir Archivos Importantes</strong> (LogsController, DebugController, WebhookEvent)</li>";
echo "<li><strong>4. Probar Sistema</strong> (Diagnostic, Inbox, System Logs)</li>";
echo "<li><strong>5. Procesar Webhooks Pendientes</strong> (process_pending_webhooks.php)</li>";
echo "</ol>";

echo "<h3>🔍 Verificación Post-Despliegue:</h3>";
echo "<ul>";
echo "<li>✅ System Logs debería mostrar menos errores</li>";
echo "<li>✅ Webhooks deberían procesarse correctamente</li>";
echo "<li>✅ Inbox debería mostrar mensajes nuevos</li>";
echo "<li>✅ Diagnostic debería funcionar</li>";
echo "<li>✅ Dashboard debería actualizarse</li>";
echo "</ul>";

echo "<h3>⚠️ Notas Importantes:</h3>";
echo "<ul>";
echo "<li>📁 <strong>Backup</strong> - Haz backup de los archivos antes de reemplazar</li>";
echo "<li>🔄 <strong>Cache</strong> - Limpia cache después de subir archivos</li>";
echo "<li>🧪 <strong>Pruebas</strong> - Prueba cada componente después del despliegue</li>";
echo "<li>📊 <strong>Monitoreo</strong> - Monitorea System Logs por 24 horas</li>";
echo "</ul>";

echo "<h3>🎯 Resultado Esperado:</h3>";
echo "<p>Después del despliegue correcto:</p>";
echo "<ul>";
echo "<li>🎉 <strong>0 errores</strong> de 'Unknown webhook event type'</li>";
echo "<li>🎉 <strong>0 errores</strong> de 'Column not found'</li>";
echo "<li>🎉 <strong>Webhooks procesados</strong> correctamente</li>";
echo "<li>🎉 <strong>Mensajes</strong> apareciendo en inbox</li>";
echo "<li>🎉 <strong>Diagnostic</strong> funcionando perfectamente</li>";
echo "</ul>";
?>
