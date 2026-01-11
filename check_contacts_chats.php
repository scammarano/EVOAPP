<?php
// Script para verificar contactos y chats en la base de datos
// Ejecutar: https://camcam.com.ve/evoappws/check_contacts_chats.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>📇 Diagnóstico de Contactos y Chats</h2>";
    
    // Verificar tabla contacts
    echo "<h3>👥 Tabla Contacts</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contacts");
    $contactsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>Total contactos: <strong>$contactsCount</strong></p>";
    
    if ($contactsCount > 0) {
        $stmt = $pdo->query("SELECT id, name, phone, instance_id, created_at FROM contacts ORDER BY created_at DESC LIMIT 10");
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Phone</th><th>Instance</th><th>Created</th></tr>";
        foreach ($contacts as $contact) {
            echo "<tr>";
            echo "<td>{$contact['id']}</td>";
            echo "<td>" . htmlspecialchars($contact['name'] ?? 'SIN NOMBRE') . "</td>";
            echo "<td>{$contact['phone']}</td>";
            echo "<td>{$contact['instance_id']}</td>";
            echo "<td>{$contact['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>⚠️ No hay contactos en la base de datos</p>";
    }
    
    // Verificar tabla chats
    echo "<h3>💬 Tabla Chats</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats");
    $chatsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>Total chats: <strong>$chatsCount</strong></p>";
    
    if ($chatsCount > 0) {
        $stmt = $pdo->query("SELECT id, name, remote_jid, is_group, instance_id, created_at FROM chats ORDER BY created_at DESC LIMIT 10");
        $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Remote JID</th><th>Group</th><th>Instance</th><th>Created</th></tr>";
        foreach ($chats as $chat) {
            echo "<tr>";
            echo "<td>{$chat['id']}</td>";
            echo "<td>" . htmlspecialchars($chat['name'] ?? 'SIN NOMBRE') . "</td>";
            echo "<td>{$chat['remote_jid']}</td>";
            echo "<td>" . ($chat['is_group'] ? 'Sí' : 'No') . "</td>";
            echo "<td>{$chat['instance_id']}</td>";
            echo "<td>{$chat['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>⚠️ No hay chats en la base de datos</p>";
    }
    
    // Verificar estructura de tablas
    echo "<h3>🔍 Estructura de Tablas</h3>";
    
    // Estructura contacts
    echo "<h4>Tabla Contacts:</h4>";
    $stmt = $pdo->query("DESCRIBE contacts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Estructura chats
    echo "<h4>Tabla Chats:</h4>";
    $stmt = $pdo->query("DESCRIBE chats");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar webhook events recientes
    echo "<h3>📡 Webhook Events Recientes (Contactos/Chats)</h3>";
    $stmt = $pdo->query("
        SELECT event_type, instance_id, status, created_at, 
               JSON_EXTRACT(payload_json, '$.data.remoteJid') as remote_jid,
               JSON_EXTRACT(payload_json, '$.data.pushName') as push_name
        FROM webhook_events 
        WHERE event_type IN ('contact_update', 'chat_update', 'chats.upsert', 'contacts.update')
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($events) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Event Type</th><th>Remote JID</th><th>Push Name</th><th>Status</th><th>Created</th></tr>";
        foreach ($events as $event) {
            echo "<tr>";
            echo "<td>{$event['event_type']}</td>";
            echo "<td>" . htmlspecialchars($event['remote_jid'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($event['push_name'] ?? 'N/A') . "</td>";
            echo "<td>{$event['status']}</td>";
            echo "<td>{$event['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No hay webhook events de contactos/chats recientes</p>";
    }
    
    echo "<h3>🔧 Recomendaciones</h3>";
    echo "<ul>";
    if ($contactsCount == 0) {
        echo "<li style='color: red;'>❌ No hay contactos - Verificar que los webhooks de contactos se procesen</li>";
    }
    if ($chatsCount == 0) {
        echo "<li style='color: red;'>❌ No hay chats - Verificar que los webhooks de chats se procesen</li>";
    }
    if ($contactsCount > 0 && $chatsCount > 0) {
        echo "<li style='color: green;'>✅ Contactos y chats existen - Verificar la vista del catálogo</li>";
    }
    echo "<li>📝 Revisar los logs para ver si los contactos/chats se están guardando correctamente</li>";
    echo "<li>🔄 Ejecutar script de sincronización si es necesario</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error de Conexión</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
