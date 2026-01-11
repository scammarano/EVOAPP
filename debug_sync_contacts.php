<?php
// Script de depuración para sync_more_contacts.php
// Ejecutar: https://camcam.com.ve/evoappws/debug_sync_contacts.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

echo "<h2>🔍 Depuración de sync_more_contacts.php</h2>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Conexión a base de datos exitosa</p>";
    
    // 1. Verificar tabla contacts
    echo "<h3>📋 Verificar Tabla Contacts</h3>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM contacts");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>✅ Tabla contacts existe con $count registros</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error en tabla contacts: " . $e->getMessage() . "</p>";
    }
    
    // 2. Verificar tabla chats
    echo "<h3>💬 Verificar Tabla Chats</h3>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>✅ Tabla chats existe con $count registros</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error en tabla chats: " . $e->getMessage() . "</p>";
    }
    
    // 3. Verificar webhook_events
    echo "<h3>📡 Verificar Webhook Events</h3>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM webhook_events");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>✅ Tabla webhook_events existe con $count registros</p>";
        
        // Verificar eventos de contactos
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM webhook_events WHERE event_type IN ('contacts.update', 'contact_update')");
        $contactCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>📇 Eventos de contactos: $contactCount</p>";
        
        // Verificar eventos de chats
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM webhook_events WHERE event_type IN ('chats.upsert', 'chat_update')");
        $chatCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>💬 Eventos de chats: $chatCount</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error en webhook_events: " . $e->getMessage() . "</p>";
    }
    
    // 4. Verificar estructura de contacts
    echo "<h3>🔍 Estructura de Contacts</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE contacts");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error verificando estructura: " . $e->getMessage() . "</p>";
    }
    
    // 5. Probar consulta simple de contactos
    echo "<h3>🧪 Probar Consulta Simple</h3>";
    try {
        $stmt = $pdo->query("SELECT * FROM contacts LIMIT 5");
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>✅ Consulta simple exitosa</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Phone</th><th>Remote JID</th></tr>";
        foreach ($contacts as $contact) {
            echo "<tr>";
            echo "<td>{$contact['id']}</td>";
            echo "<td>" . htmlspecialchars($contact['name'] ?? 'N/A') . "</td>";
            echo "<td>{$contact['phone_e164']}</td>";
            echo "<td>{$contact['remote_jid'] ?? 'N/A'}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error en consulta simple: " . $e->getMessage() . "</p>";
    }
    
    // 6. Probar consulta de webhook_events
    echo "<h3>🧪 Probar Webhook Events</h3>";
    try {
        $stmt = $pdo->query("SELECT event_type, COUNT(*) as count FROM webhook_events GROUP BY event_type ORDER BY count DESC LIMIT 10");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>✅ Consulta de eventos exitosa</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Event Type</th><th>Count</th></tr>";
        foreach ($events as $event) {
            echo "<tr>";
            echo "<td>{$event['event_type']}</td>";
            echo "<td>{$event['count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error en consulta de eventos: " . $e->getMessage() . "</p>";
    }
    
    // 7. Verificar memoria y límites
    echo "<h3>💾 Configuración de PHP</h3>";
    echo "<p>Memory limit: " . ini_get('memory_limit') . "</p>";
    echo "<p>Max execution time: " . ini_get('max_execution_time') . "</p>";
    echo "<p>Post max size: " . ini_get('post_max_size') . "</p>";
    echo "<p>Upload max filesize: " . ini_get('upload_max_filesize') . "</p>";
    
    echo "<h3>🎯 Diagnóstico Completado</h3>";
    echo "<p>Si todo está en verde, el problema puede estar en:</p>";
    echo "<ul>";
    echo "<li>📄 Sintaxis del script sync_more_contacts.php</li>";
    echo "<li>🔄 Bucle infinito o demasiado grande</li>";
    echo "<li>💾 Límite de memoria excedido</li>";
    echo "<li>⏱️ Tiempo de ejecución excedido</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error de Conexión</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2>❌ Error General</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
