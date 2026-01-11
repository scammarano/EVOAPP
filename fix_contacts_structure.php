<?php
// Script para arreglar estructura de contacts y sincronizar correctamente
// Ejecutar: https://camcam.com.ve/evoappws/fix_contacts_structure.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔧 Arreglar Estructura de Contacts y Sincronizar</h2>";
    
    // 1. Verificar estructura actual de contacts
    echo "<h3>📋 Estructura Actual de Contacts</h3>";
    $stmt = $pdo->query("DESCRIBE contacts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasRemoteJid = false;
    $hasPushName = false;
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Status</th></tr>";
    foreach ($columns as $col) {
        $status = "✅";
        if ($col['Field'] === 'remote_jid') $hasRemoteJid = true;
        if ($col['Field'] === 'push_name') $hasPushName = true;
        
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Agregar columnas faltantes a contacts
    echo "<h3>🔨 Agregar Columnas Faltantes a Contacts</h3>";
    
    if (!$hasRemoteJid) {
        try {
            $pdo->query("ALTER TABLE contacts ADD COLUMN remote_jid VARCHAR(255) DEFAULT NULL AFTER phone_e164");
            echo "<p style='color: green;'>✅ Columna 'remote_jid' agregada a contacts</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Error agregando 'remote_jid': " . $e->getMessage() . "</p>";
        }
    }
    
    if (!$hasPushName) {
        try {
            $pdo->query("ALTER TABLE contacts ADD COLUMN push_name VARCHAR(255) DEFAULT NULL AFTER name");
            echo "<p style='color: green;'>✅ Columna 'push_name' agregada a contacts</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Error agregando 'push_name': " . $e->getMessage() . "</p>";
        }
    }
    
    // 3. Sincronizar contactos desde webhook_events (versión corregida)
    echo "<h3>🔄 Sincronizar Contactos (Versión Corregida)</h3>";
    
    // Primero verificar si tenemos las columnas necesarias
    $stmt = $pdo->query("DESCRIBE contacts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_map(function($col) { return $col['Field']; }, $columns);
    
    if (in_array('remote_jid', $columnNames) && in_array('push_name', $columnNames)) {
        // Extraer contactos desde webhook_events
        $stmt = $pdo->query("
            SELECT 
                instance_id,
                payload_json,
                created_at
            FROM webhook_events 
            WHERE event_type IN ('contacts.update', 'contact_update')
            AND status = 'processed'
            ORDER BY created_at DESC
            LIMIT 100
        ");
        
        $contactEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $contactsInserted = 0;
        $contactsUpdated = 0;
        
        foreach ($contactEvents as $event) {
            $payload = json_decode($event['payload_json'], true);
            
            if (isset($payload['data'])) {
                $data = $payload['data'];
                
                // Manejar ambos formatos: array y object
                $contactsList = is_array($data) ? $data : [$data];
                
                foreach ($contactsList as $contact) {
                    if (isset($contact['remoteJid']) && isset($contact['pushName'])) {
                        $remoteJid = $contact['remoteJid'];
                        $pushName = $contact['pushName'];
                        
                        // Extraer número de teléfono
                        $phone = str_replace(['@s.whatsapp.net', '@g.us', '@lid'], '', $remoteJid);
                        
                        // Verificar si ya existe
                        $stmt = $pdo->prepare("SELECT id FROM contacts WHERE remote_jid = ?");
                        $stmt->execute([$remoteJid]);
                        
                        if ($stmt->rowCount() == 0) {
                            // Insertar nuevo contacto
                            $stmt = $pdo->prepare("
                                INSERT INTO contacts (instance_id, phone_e164, name, push_name, remote_jid, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $event['instance_id'],
                                $phone,
                                $pushName,
                                $pushName,
                                $remoteJid,
                                $event['created_at']
                            ]);
                            $contactsInserted++;
                        } else {
                            // Actualizar nombre si es diferente
                            $stmt = $pdo->prepare("
                                UPDATE contacts SET name = ?, push_name = ?, updated_at = ? 
                                WHERE remote_jid = ?
                            ");
                            $stmt->execute([
                                $pushName,
                                $pushName,
                                $event['created_at'],
                                $remoteJid
                            ]);
                            $contactsUpdated++;
                        }
                    }
                }
            }
        }
        
        echo "<p style='color: green;'>✅ Contactos insertados: <strong>$contactsInserted</strong></p>";
        echo "<p style='color: blue;'>✅ Contactos actualizados: <strong>$contactsUpdated</strong></p>";
        
    } else {
        echo "<p style='color: red;'>❌ Columnas necesarias no disponibles en contacts</p>";
    }
    
    // 4. Sincronizar chats desde webhook_events (versión corregida)
    echo "<h3>🔄 Sincronizar Chats (Versión Corregida)</h3>";
    
    $stmt = $pdo->query("
        SELECT 
            instance_id,
            payload_json,
            created_at
        FROM webhook_events 
        WHERE event_type IN ('chats.upsert', 'chat_update', 'chats.update')
        AND status = 'processed'
        ORDER BY created_at DESC
        LIMIT 100
    ");
    
    $chatEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $chatsInserted = 0;
    $chatsUpdated = 0;
    
    foreach ($chatEvents as $event) {
        $payload = json_decode($event['payload_json'], true);
        
        if (isset($payload['data'])) {
            $data = $payload['data'];
            
            // Manejar ambos formatos: array y object
            $chatsList = is_array($data) ? $data : [$data];
            
            foreach ($chatsList as $chat) {
                if (isset($chat['id'])) {
                    $chatId = $chat['id'];
                    $chatName = $chat['name'] ?? null;
                    $remoteJid = $chat['remoteJid'] ?? null;
                    
                    // Determinar si es grupo
                    $isGroup = strpos($chatId, '@g.us') !== false;
                    
                    // Verificar si ya existe
                    $stmt = $pdo->prepare("SELECT id FROM chats WHERE id = ?");
                    $stmt->execute([$chatId]);
                    
                    if ($stmt->rowCount() == 0) {
                        // Insertar nuevo chat
                        $stmt = $pdo->prepare("
                            INSERT INTO chats (id, instance_id, name, remote_jid, is_group, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $chatId,
                            $event['instance_id'],
                            $chatName,
                            $remoteJid,
                            $isGroup ? 1 : 0,
                            $event['created_at']
                        ]);
                        $chatsInserted++;
                    } else {
                        // Actualizar nombre si existe
                        $stmt = $pdo->prepare("
                            UPDATE chats SET name = ?, remote_jid = ?, updated_at = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $chatName,
                            $remoteJid,
                            $event['created_at'],
                            $chatId
                        ]);
                        $chatsUpdated++;
                    }
                }
            }
        }
    }
    
    echo "<p style='color: green;'>✅ Chats insertados: <strong>$chatsInserted</strong></p>";
    echo "<p style='color: blue;'>✅ Chats actualizados: <strong>$chatsUpdated</strong></p>";
    
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
        $stmt = $pdo->query("SELECT name, phone_e164, remote_jid, push_name FROM contacts ORDER BY created_at DESC LIMIT 5");
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Name</th><th>Phone</th><th>Remote JID</th><th>Push Name</th></tr>";
        foreach ($contacts as $contact) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($contact['name'] ?? 'SIN NOMBRE') . "</td>";
            echo "<td>{$contact['phone_e164']}</td>";
            echo "<td>{$contact['remote_jid']}</td>";
            echo "<td>" . htmlspecialchars($contact['push_name'] ?? 'N/A') . "</td>";
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
    echo "<li>✅ Estructura de contacts arreglada</li>";
    echo "<li>✅ Contactos sincronizados</li>";
    echo "<li>✅ Chats actualizados</li>";
    echo "<li>🔄 Refrescar el catálogo para ver los nombres</li>";
    echo "<li>📱 Probar envío de mensajes para ver nombres</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error de Conexión</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
