<?php
// Análisis completo del dump de base de datos
// Basado en el dump proporcionado por el usuario

echo "<h2>📊 Análisis Completo del Dump de Base de Datos</h2>";

// Tablas identificadas en el dump
$tables = [
    'audit_log' => [
        'purpose' => 'Logs de auditoría del sistema',
        'columns' => ['id', 'user_id', 'action', 'entity_type', 'entity_id', 'before_json', 'after_json', 'created_at'],
        'records' => 23,
        'critical' => true
    ],
    'campaigns' => [
        'purpose' => 'Campañas de marketing',
        'columns' => ['id', 'instance_id', 'name', 'is_active', 'schedule_type', 'start_at', 'end_at', 'timezone', 'weekly_days', 'monthly_day', 'next_run_at', 'created_by', 'created_at', 'updated_at'],
        'records' => 0,
        'critical' => false
    ],
    'campaign_messages' => [
        'purpose' => 'Mensajes de campañas',
        'columns' => ['id', 'campaign_id', 'sort_order', 'text', 'media_path', 'media_type', 'caption'],
        'records' => 0,
        'critical' => false
    ],
    'campaign_runs' => [
        'purpose' => 'Ejecuciones de campañas',
        'columns' => ['id', 'campaign_id', 'run_at', 'status', 'total', 'ok_count', 'fail_count', 'raw_log'],
        'records' => 0,
        'critical' => false
    ],
    'campaign_run_items' => [
        'purpose' => 'Items de ejecución de campañas',
        'columns' => ['id', 'run_id', 'contact_id', 'status', 'response_json', 'error_text', 'sent_at'],
        'records' => 0,
        'critical' => false
    ],
    'campaign_targets' => [
        'purpose' => 'Objetivos de campañas',
        'columns' => ['id', 'campaign_id', 'target_type', 'target_id'],
        'records' => 0,
        'critical' => false
    ],
    'chats' => [
        'purpose' => 'Conversaciones y chats',
        'columns' => ['id', 'instance_id', 'remote_jid', 'name', 'is_group', 'title', 'last_snippet', 'last_message_at', 'unread_count', 'created_at', 'updated_at'],
        'records' => 42,
        'critical' => true
    ],
    'chat_reads' => [
        'purpose' => 'Estado de lectura de chats',
        'columns' => ['user_id', 'chat_id', 'last_read_ts'],
        'records' => 20,
        'critical' => false
    ],
    'contacts' => [
        'purpose' => 'Contactos del sistema',
        'columns' => ['id', 'instance_id', 'phone_e164', 'remote_jid', 'name', 'push_name', 'company', 'email', 'birthday', 'notes', 'created_at', 'updated_at'],
        'records' => 26,
        'critical' => true
    ],
    'contact_candidates' => [
        'purpose' => 'Candidatos a contactos',
        'columns' => ['id', 'instance_id', 'source_type', 'source_remote_jid', 'phone_e164', 'name_guess', 'raw_json', 'created_at', 'status'],
        'records' => 0,
        'critical' => false
    ],
    'contact_lists' => [
        'purpose' => 'Listas de contactos',
        'columns' => ['id', 'instance_id', 'name', 'created_at', 'updated_at'],
        'records' => 0,
        'critical' => false
    ],
    'contact_list_items' => [
        'purpose' => 'Items de listas de contactos',
        'columns' => ['list_id', 'contact_id'],
        'records' => 0,
        'critical' => false
    ],
    'contact_list_members' => [
        'purpose' => 'Miembros de listas de contactos',
        'columns' => ['id', 'list_id', 'contact_id', 'added_at'],
        'records' => 0,
        'critical' => false
    ],
    'cron_log' => [
        'purpose' => 'Logs de tareas programadas',
        'columns' => ['id', 'job_key', 'started_at', 'finished_at', 'ok', 'summary', 'error_text'],
        'records' => 0,
        'critical' => false
    ],
    'cron_logs' => [
        'purpose' => 'Logs de cron (DUPLICADA)',
        'columns' => ['id', 'job_name', 'status', 'message', 'duration_ms', 'created_at'],
        'records' => 0,
        'critical' => false,
        'duplicate' => true
    ],
    'evo_instances' => [
        'purpose' => 'Instancias de Evolution API',
        'columns' => ['id', 'slug', 'description', 'is_active', 'api_key', 'base_url', 'webhook_token', 'webhook_enabled', 'forward_webhook_url', 'forward_webhook_enabled', 'last_webhook_at', 'created_at', 'updated_at', 'webhook_timestamp'],
        'records' => 4,
        'critical' => true
    ],
    'instances' => [
        'purpose' => 'Instancias (DUPLICADA)',
        'columns' => ['id', 'slug', 'description', 'api_key', 'base_url', 'webhook_token', 'webhook_enabled', 'forward_webhook_url', 'forward_webhook_enabled', 'is_active', 'last_webhook_at', 'created_at', 'updated_at'],
        'records' => 3,
        'critical' => false,
        'duplicate' => true
    ],
    'instance_profiles' => [
        'purpose' => 'Perfiles de instancias',
        'columns' => ['id', 'instance_id', 'profile_image_url', 'description', 'created_at', 'updated_at'],
        'records' => 0,
        'critical' => false
    ],
    'messages' => [
        'purpose' => 'Mensajes del sistema',
        'columns' => ['id', 'instance_id', 'chat_id', 'message_id', 'from_me', 'ts', 'msg_type', 'body_text', 'participant_jid', 'media_url', 'local_path', 'status', 'raw_json', 'created_at'],
        'records' => 152,
        'critical' => true
    ],
    'group_participants' => [
        'purpose' => 'Participantes de grupos',
        'columns' => ['id', 'group_id', 'group_name', 'participant_jid', 'participant_name', 'participant_phone', 'is_admin', 'joined_at', 'instance_id'],
        'records' => 0,
        'critical' => false
    ],
    'users' => [
        'purpose' => 'Usuarios del sistema',
        'columns' => ['id', 'name', 'email', 'password_hash', 'is_active', 'created_at', 'updated_at'],
        'records' => 8,
        'critical' => true
    ],
    'permissions' => [
        'purpose' => 'Permisos del sistema',
        'columns' => ['id', 'key', 'name', 'description'],
        'records' => 31,
        'critical' => true
    ],
    'roles' => [
        'purpose' => 'Roles del sistema',
        'columns' => ['id', 'name', 'description', 'created_at', 'updated_at'],
        'records' => 6,
        'critical' => true
    ],
    'webhook_events' => [
        'purpose' => 'Eventos webhook recibidos',
        'columns' => ['id', 'created_at', 'instance_id', 'event_type', 'remote_jid', 'message_id', 'participant_jid', 'payload_json', 'received_at', 'processed_at', 'status', 'retry_count', 'error_message', 'error_text'],
        'records' => 6191,
        'critical' => true
    ],
    'webhook_logs' => [
        'purpose' => 'Logs de webhooks',
        'columns' => ['id', 'instance_id', 'event_type', 'data_json', 'processed', 'error_message', 'created_at'],
        'records' => 0,
        'critical' => false
    ]
];

echo "<h3>📊 Resumen de Tablas</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'><th>Tabla</th><th>Propósito</th><th>Registros</th><th>Crítica</th><th>Problemas</th></tr>";

foreach ($tables as $tableName => $info) {
    $criticalIcon = $info['critical'] ? '✅' : '⚠️';
    $duplicateIcon = isset($info['duplicate']) && $info['duplicate'] ? '🔄' : '';
    $problems = [];
    
    if (isset($info['duplicate']) && $info['duplicate']) {
        $problems[] = 'DUPLICADA';
    }
    
    if ($tableName === 'contacts') {
        $problems[] = 'Sin columna status';
    }
    
    if ($tableName === 'chats') {
        $problems[] = 'ID es int, no string';
    }
    
    echo "<tr>";
    echo "<td><strong>$tableName</strong> $duplicateIcon</td>";
    echo "<td>{$info['purpose']}</td>";
    echo "<td>{$info['records']}</td>";
    echo "<td>$criticalIcon</td>";
    echo "<td>" . implode(', ', $problems) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>🚨 Problemas Críticos Identificados</h3>";

echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;'>";
echo "<h4>❌ Tablas Duplicadas:</h4>";
echo "<ul>";
echo "<li><strong>instances vs evo_instances</strong> - Dos tablas para instancias con datos diferentes</li>";
echo "<li><strong>cron_log vs cron_logs</strong> - Dos tablas para logs de cron</li>";
echo "</ul>";

echo "<h4>❌ Schema Inconsistente:</h4>";
echo "<ul>";
echo "<li><strong>contacts</strong> - No tiene columna 'status' (causa errores en scripts)</li>";
echo "<li><strong>chats</strong> - ID es int(11) pero algunos scripts esperan string</li>";
echo "<li><strong>instances</strong> - Columnas diferentes entre versiones</li>";
echo "</ul>";

echo "<h4>❌ Datos Inconsistentes:</h4>";
echo "<ul>";
echo "<li><strong>evo_instances</strong> - 4 instancias activas</li>";
echo "<li><strong>instances</strong> - 3 instancias (diferentes datos)</li>";
echo "<li><strong>webhook_events</strong> - 6191 eventos procesados correctamente</li>";
echo "</ul>";
echo "</div>";

echo "<h3>🛠️ Recomendaciones de Normalización</h3>";

echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745;'>";
echo "<h4>✅ Acciones Inmediatas:</h4>";
echo "<ol>";
echo "<li><strong>Unificar instancias</strong> - Migrar datos de 'instances' a 'evo_instances' y eliminar duplicada</li>";
echo "<li><strong>Unificar cron logs</strong> - Consolidar 'cron_log' y 'cron_logs' en una sola tabla</li>";
echo "<li><strong>Agregar columna status</strong> - Añadir columna 'status' a 'contacts' si es necesaria</li>";
echo "<li><strong>Verificar tipos de datos</strong> - Asegurar consistencia en tipos de columnas</li>";
echo "<li><strong>Actualizar scripts</strong> - Corregir scripts para que usen tablas y columnas correctas</li>";
echo "</ol>";

echo "<h4>🔧 Para Contact.php:</h4>";
echo "<ul>";
echo "<li>Usar solo 'App\Core\DB' para consistencia</li>";
echo "<li>Verificar que todas las consultas usen columnas existentes</li>";
echo "<li>No usar columna 'status' en 'contacts' (no existe)</li>";
echo "<li>Usar 'evo_instances' en lugar de 'instances'</li>";
echo "</ul>";

echo "<h4>📊 Para Scripts:</h4>";
echo "<ul>";
echo "<li>Actualizar todas las consultas para usar 'evo_instances'</li>";
echo "<li>Eliminar referencias a columna 'status' en 'contacts'</li>";
echo "<li>Verificar que 'chats.id' es tratado como entero</li>";
echo "<li>Usar columnas correctas según el schema real</li>";
echo "</ul>";
echo "</div>";

echo "<h3>📈 Estadísticas del Sistema</h3>";

$totalTables = count($tables);
$criticalTables = count(array_filter($tables, function($t) { return $t['critical']; }));
$duplicateTables = count(array_filter($tables, function($t) { return isset($t['duplicate']) && $t['duplicate']; }));
$totalRecords = array_sum(array_column($tables, 'records'));

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'><th>Métrica</th><th>Valor</th></tr>";
echo "<tr><td>Total de Tablas</td><td>$totalTables</td></tr>";
echo "<tr><td>Tablas Críticas</td><td>$criticalTables</td></tr>";
echo "<tr><td>Tablas Duplicadas</td><td>$duplicateTables</td></tr>";
echo "<tr><td>Total de Registros</td><td>$totalRecords</td></tr>";
echo "<tr><td>Eventos Webhook</td><td>6191</td></tr>";
echo "<tr><td>Contactos</td><td>26</td></tr>";
echo "<tr><td>Chats</td><td>42</td></tr>";
echo "<tr><td>Mensajes</td><td>152</td></tr>";
echo "<tr><td>Instancias Activas</td><td>4</td></tr>";
echo "</table>";

echo "<h3>🎯 Próximos Pasos</h3>";

echo "<div style='background: #cce5ff; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #007bff;'>";
echo "<h4>📋 Para Normalizar el Sistema:</h4>";
echo "<ol>";
echo "<li><strong>Ejecutar auditoría completa</strong> - Usar database_audit.php para validar</li>";
echo "<li><strong>Corregir Contact.php</strong> - Usar 'App\Core\DB' y columnas correctas</li>";
echo "<li><strong>Unificar tablas duplicadas</strong> - Migrar datos y eliminar duplicados</li>";
echo "<li><strong>Actualizar todos los scripts</strong> - Para que usen schema correcto</li>";
echo "<li><strong>Probar sistema completo</strong> - Verificar que todo funcione</li>";
echo "</ol>";

echo "<h4>🔍 Para Verificar:</h4>";
echo "<ul>";
echo "<li>Que todos los archivos PHP usen 'App\Core\DB'</li>";
echo "<li>Que no haya referencias a columnas inexistentes</li>";
echo "<li>Que los scripts usen 'evo_instances' correctamente</li>";
echo "<li>Que el sistema procese webhooks sin errores</li>";
echo "</ul>";
echo "</div>";

echo "<p style='color: green; font-weight: bold;'>✅ Análisis completado. El sistema tiene 25 tablas con problemas específicos identificados y soluciones propuestas.</p>";
?>
