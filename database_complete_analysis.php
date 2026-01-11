<?php
// Análisis completo del dump de la base de datos
require_once 'app/Core/DB.php';

// Inicializar conexión
App\Core\DB::init();

echo "<h1>🔍 ANÁLISIS COMPLETO DE BASE DE DATOS</h1>";
echo "<h2>📊 Database: grupoecc_evoappWS</h2>";

// Obtener todas las tablas
$tables = App\Core\DB::fetchAll("SHOW TABLES");
echo "<h3>📋 Total de Tablas: " . count($tables) . "</h3>";

// Análisis detallado de cada tabla
echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px;'>";

// Tablas principales identificadas
$mainTables = [
    'contacts', 'chats', 'messages', 'instances', 'evo_instances',
    'webhook_events', 'webhook_logs', 'users', 'audit_log'
];

$duplicateTables = [];
$schemaIssues = [];

foreach ($tables as $table) {
    $tableName = array_values($table)[0];
    
    echo "<div style='margin: 15px 0; padding: 10px; border-left: 4px solid #007bff; background: white;'>";
    echo "<h4>🗂️ Tabla: <strong>$tableName</strong></h4>";
    
    // Obtener estructura
    $columns = App\Core\DB::fetchAll("DESCRIBE `$tableName`");
    $count = App\Core\DB::fetch("SELECT COUNT(*) as count FROM `$tableName`");
    
    echo "<p><strong>📊 Registros:</strong> " . number_format($count['count']) . "</p>";
    echo "<p><strong>🏗️ Columnas:</strong></p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Columna</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Identificar tablas duplicadas
    if (strpos($tableName, 'instances') !== false) {
        $duplicateTables[] = $tableName;
    }
    
    // Verificar problemas específicos
    if ($tableName === 'contacts') {
        $hasStatus = false;
        foreach ($columns as $col) {
            if ($col['Field'] === 'status') {
                $hasStatus = true;
                break;
            }
        }
        if (!$hasStatus) {
            $schemaIssues[] = "Tabla 'contacts' NO tiene columna 'status'";
        }
    }
    
    if ($tableName === 'chats') {
        foreach ($columns as $col) {
            if ($col['Field'] === 'id' && strpos($col['Type'], 'int') !== false) {
                $schemaIssues[] = "Tabla 'chats.id' es tipo {$col['Type']} (debería ser BIGINT para WhatsApp IDs)";
            }
        }
    }
    
    echo "</div>";
}

echo "</div>";

// Análisis de tablas duplicadas
echo "<h2>⚠️ TABLAS DUPLICADAS IDENTIFICADAS</h2>";
if (!empty($duplicateTables)) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
    foreach ($duplicateTables as $table) {
        echo "<p>🔄 <strong>$table</strong> - Posible duplicación</p>";
    }
    echo "</div>";
} else {
    echo "<p>✅ No se encontraron tablas duplicadas obvias</p>";
}

// Análisis de problemas de schema
echo "<h2>🔧 PROBLEMAS DE SCHEMA IDENTIFICADOS</h2>";
if (!empty($schemaIssues)) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
    foreach ($schemaIssues as $issue) {
        echo "<p>❌ $issue</p>";
    }
    echo "</div>";
} else {
    echo "<p>✅ No se encontraron problemas de schema críticos</p>";
}

// Análisis de datos de webhook_events
echo "<h2>📡 ANÁLISIS DE WEBHOOK_EVENTS</h2>";
$webhookStats = App\Core\DB::fetch("
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'processed' THEN 1 END) as processed,
        COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as last_24h
    FROM webhook_events
");

echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
echo "<p>📊 <strong>Total Webhooks:</strong> " . number_format($webhookStats['total']) . "</p>";
echo "<p>✅ <strong>Procesados:</strong> " . number_format($webhookStats['processed']) . "</p>";
echo "<p>❌ <strong>Fallidos:</strong> " . number_format($webhookStats['failed']) . "</p>";
echo "<p>⏳ <strong>Pendientes:</strong> " . number_format($webhookStats['pending']) . "</p>";
echo "<p>🕐 <strong>Últimas 24h:</strong> " . number_format($webhookStats['last_24h']) . "</p>";
echo "</div>";

// Análisis de instancias
echo "<h2>🏢 ANÁLISIS DE INSTANCIAS</h2>";

$instances = App\Core\DB::fetchAll("SELECT * FROM evo_instances ORDER BY id");
echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px;'>";

foreach ($instances as $instance) {
    echo "<div style='margin: 10px 0; padding: 10px; background: white; border-radius: 3px;'>";
    echo "<h5>📱 <strong>{$instance['slug']}</strong> (ID: {$instance['id']})</h5>";
    echo "<p>🔗 API Key: " . substr($instance['api_key'], 0, 20) . "...</p>";
    echo "<p>🌐 Base URL: {$instance['base_url']}</p>";
    echo "<p>📡 Webhook Enabled: " . ($instance['webhook_enabled'] ? '✅ Sí' : '❌ No') . "</p>";
    echo "<p>⏰ Last Webhook: {$instance['last_webhook_at']}</p>";
    echo "</div>";
}
echo "</div>";

// Análisis de contacts y chats
echo "<h2>👥 ANÁLISIS DE CONTACTS Y CHATS</h2>";

$contactStats = App\Core\DB::fetch("SELECT COUNT(*) as total FROM contacts");
$chatStats = App\Core\DB::fetch("SELECT COUNT(*) as total, COUNT(CASE WHEN is_group = 1 THEN 1 END) as groups FROM chats");
$messageStats = App\Core\DB::fetch("SELECT COUNT(*) as total FROM messages");

echo "<div style='background: #cce5ff; padding: 15px; border-radius: 5px; border-left: 4px solid #004085;'>";
echo "<p>👥 <strong>Contactos:</strong> " . number_format($contactStats['total']) . "</p>";
echo "<p>💬 <strong>Chats Totales:</strong> " . number_format($chatStats['total']) . "</p>";
echo "<p>👨‍👩‍👧‍👦 <strong>Grupos:</strong> " . number_format($chatStats['groups']) . "</p>";
echo "<p>📨 <strong>Mensajes:</strong> " . number_format($messageStats['total']) . "</p>";
echo "</div>";

// Recomendaciones
echo "<h2>💡 RECOMENDACIONES</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo "<h3>🔧 Acciones Inmediatas:</h3>";
echo "<ol>";
echo "<li><strong>Corregir Contact.php:</strong> Usar App\Core\DB en lugar de App\Core\Database</li>";
echo "<li><strong>Agregar getInstance():</strong> Método faltante en clase DB</li>";
echo "<li><strong>Eliminar scripts problemáticos:</strong> sync_more_contacts.php y otros con errores</li>";
echo "<li><strong>Verificar webhooks:</strong> " . $webhookStats['failed'] . " webhooks fallidos necesitan revisión</li>";
echo "</ol>";

echo "<h3>🏗️ Optimizaciones de Schema:</h3>";
echo "<ul>";
echo "<li>Considerar unificar tablas de instancias (instances vs evo_instances)</li>";
echo "<li>Verificar tipos de datos en chats.id para WhatsApp IDs</li>";
echo "<li>Agregar índices faltantes para mejorar rendimiento</li>";
echo "</ul>";

echo "<h3>📊 Mantenimiento:</h3>";
echo "<ul>";
echo "<li>Limpiar webhook_events antiguos (actualmente " . number_format($webhookStats['total']) . " registros)</li>";
echo "<li>Revisar cron_logs vs cron_log (posible duplicación)</li>";
echo "<li>Monitorear crecimiento de messages table</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><em>Análisis completado: " . date('Y-m-d H:i:s') . "</em></p>";
?>
