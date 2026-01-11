<?php
// Script para arreglar estructura de tabla chats y sincronizar contactos
// Ejecutar: https://camcam.com.ve/evoappws/fix_chats_structure.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔧 Arreglar Estructura y Sincronizar Contactos</h2>";
    
    // 1. Verificar estructura actual de chats
    echo "<h3>📋 Estructura Actual de Chats</h3>";
    $stmt = $pdo->query("DESCRIBE chats");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasName = false;
    $hasRemoteJid = false;
    $hasIsGroup = false;
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Status</th></tr>";
    foreach ($columns as $col) {
        $status = "✅";
        if ($col['Field'] === 'name') $hasName = true;
        if ($col['Field'] === 'remote_jid') $hasRemoteJid = true;
        if ($col['Field'] === 'is_group') $hasIsGroup = true;
        
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Agregar columnas faltantes
    echo "<h3>🔨 Agregar Columnas Faltantes</h3>";
    
    if (!$hasName) {
        try {
            $pdo->query("ALTER TABLE chats ADD COLUMN name VARCHAR(255) DEFAULT NULL AFTER remote_jid");
            echo "<p style='color: green;'>✅ Columna 'name' agregada a chats</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Error agregando 'name': " . $e->getMessage() . "</p>";
        }
    }
    
    if (!$hasRemoteJid) {
        try {
            $pdo->query("ALTER TABLE chats ADD COLUMN remote_jid VARCHAR(255) DEFAULT NULL AFTER id");
            echo "<p style='color: green;'>✅ Columna 'remote_jid' agregada a chats</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Error agregando 'remote_jid': " . $e->getMessage() . "</p>";
        }
    }
    
    if (!$hasIsGroup) {
        try {
            $pdo->query("ALTER TABLE chats ADD COLUMN is_group TINYINT(1) DEFAULT 0 AFTER name");
            echo "<p style='color: green;'>✅ Columna 'is_group' agregada a chats</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Error agregando 'is_group': " . $e->getMessage() . "</p>";
        }
    }
    
    // 3. Verificar tabla contacts
    echo "<h3>👥 Verificar Tabla Contacts</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'contacts'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<p style='color: red;'>❌ Tabla 'contacts' no existe - Creando...</p>";
        
        $createTable = "
        CREATE TABLE contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) DEFAULT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            remote_jid VARCHAR(255) DEFAULT NULL,
            instance_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_instance (instance_id),
            INDEX idx_phone (phone),
            INDEX idx_remote_jid (remote_jid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        try {
            $pdo->query($createTable);
            echo "<p style='color: green;'>✅ Tabla 'contacts' creada</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error creando 'contacts': " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Tabla 'contacts' existe</p>";
        
        // Verificar estructura
        $stmt = $pdo->query("DESCRIBE contacts");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Column</th><th>Type</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Sincronizar desde webhook_events
    echo "<h3>🔄 Sincronizar desde Webhook Events</h3>";
    
    // Extraer contactos desde webhook_events
    $stmt = $pdo->query("
        SELECT 
            instance_id,
            JSON_UNQUOTE(JSON_EXTRACT(payload_json, '$.data.remoteJid')) as remote_jid,
            JSON_UNQUOTE(JSON_EXTRACT(payload_json, '$.data.pushName')) as push_name,
            created_at
        FROM webhook_events 
        WHERE event_type IN ('contacts.update', 'contact_update')
        AND JSON_EXTRACT(payload_json, '$.data.remoteJid') IS NOT NULL
        AND status = 'processed'
        ORDER BY created_at DESC
        LIMIT 100
    ");
    
    $contactEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $contactsInserted = 0;
    
    foreach ($contactEvents as $event) {
        if ($event['remote_jid'] && $event['push_name']) {
            // Extraer número de teléfono del remote_jid
            $phone = str_replace(['@s.whatsapp.net', '@g.us', '@lid'], '', $event['remote_jid']);
            
            // Verificar si ya existe
            $stmt = $pdo->prepare("SELECT id FROM contacts WHERE remote_jid = ?");
            $stmt->execute([$event['remote_jid']]);
            
            if ($stmt->rowCount() == 0) {
                // Insertar nuevo contacto
                $stmt = $pdo->prepare("
                    INSERT INTO contacts (name, phone, remote_jid, instance_id, created_at) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $event['push_name'],
                    $phone,
                    $event['remote_jid'],
                    $event['instance_id'],
                    $event['created_at']
                ]);
                $contactsInserted++;
            }
        }
    }
    
    echo "<p style='color: green;'>✅ Contactos insertados: <strong>$contactsInserted</strong></p>";
    
    // Extraer chats desde webhook_events
    $stmt = $pdo->query("
        SELECT 
            instance_id,
            JSON_UNQUOTE(JSON_EXTRACT(payload_json, '$.data.id')) as chat_id,
            JSON_UNQUOTE(JSON_EXTRACT(payload_json, '$.data.name')) as chat_name,
            JSON_UNQUOTE(JSON_EXTRACT(payload_json, '$.data.remoteJid')) as remote_jid,
            created_at
        FROM webhook_events 
        WHERE event_type IN ('chats.upsert', 'chat_update')
        AND JSON_EXTRACT(payload_json, '$.data.id') IS NOT NULL
        AND status = 'processed'
        ORDER BY created_at DESC
        LIMIT 100
    ");
    
    $chatEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $chatsUpdated = 0;
    
    foreach ($chatEvents as $event) {
        if ($event['chat_id']) {
            // Determinar si es grupo
            $isGroup = strpos($event['chat_id'], '@g.us') !== false;
            
            // Verificar si ya existe
            $stmt = $pdo->prepare("SELECT id FROM chats WHERE id = ?");
            $stmt->execute([$event['chat_id']]);
            
            if ($stmt->rowCount() == 0) {
                // Insertar nuevo chat
                $stmt = $pdo->prepare("
                    INSERT INTO chats (id, name, remote_jid, is_group, instance_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $event['chat_id'],
                    $event['chat_name'],
                    $event['remote_jid'],
                    $isGroup ? 1 : 0,
                    $event['instance_id'],
                    $event['created_at']
                ]);
                $chatsUpdated++;
            } else {
                // Actualizar nombre si existe
                $stmt = $pdo->prepare("
                    UPDATE chats SET name = ?, remote_jid = ?, is_group = ? 
                    WHERE id = ?
                ");
                $stmt->execute([
                    $event['chat_name'],
                    $event['remote_jid'],
                    $isGroup ? 1 : 0,
                    $event['chat_id']
                ]);
                $chatsUpdated++;
            }
        }
    }
    
    echo "<p style='color: green;'>✅ Chats actualizados: <strong>$chatsUpdated</strong></p>";
    
    // 5. Mostrar resultados finales
    echo "<h3>📊 Resultados Finales</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contacts");
    $contactsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats");
    $chatsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Tabla</th><th>Registros</th><th>Status</th></tr>";
    echo "<tr><td>Contacts</td><td>$contactsCount</td><td>" . ($contactsCount > 0 ? '✅' : '❌') . "</td></tr>";
    echo "<tr><td>Chats</td><td>$chatsCount</td><td>" . ($chatsCount > 0 ? '✅' : '❌') . "</td></tr>";
    echo "</table>";
    
    // 6. Mostrar muestra de datos
    if ($contactsCount > 0) {
        echo "<h4>📇 Contactos Recientes:</h4>";
        $stmt = $pdo->query("SELECT name, phone, remote_jid FROM contacts ORDER BY created_at DESC LIMIT 5");
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Name</th><th>Phone</th><th>Remote JID</th></tr>";
        foreach ($contacts as $contact) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($contact['name']) . "</td>";
            echo "<td>{$contact['phone']}</td>";
            echo "<td>{$contact['remote_jid']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    if ($chatsCount > 0) {
        echo "<h4>💬 Chats Recientes:</h4>";
        $stmt = $pdo->query("SELECT id, name, remote_jid, is_group FROM chats ORDER BY created_at DESC LIMIT 5");
        $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Remote JID</th><th>Group</th></tr>";
        foreach ($chats as $chat) {
            echo "<tr>";
            echo "<td>{$chat['id']}</td>";
            echo "<td>" . htmlspecialchars($chat['name'] ?? 'SIN NOMBRE') . "</td>";
            echo "<td>{$chat['remote_jid']}</td>";
            echo "<td>" . ($chat['is_group'] ? 'Sí' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>🎯 Próximos Pasos</h3>";
    echo "<ul>";
    echo "<li>✅ Estructura arreglada</li>";
    echo "<li>✅ Contactos sincronizados</li>";
    echo "<li>✅ Chats actualizados</li>";
    echo "<li>🔄 Refrescar el catálogo para ver los nombres</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error de Conexión</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
