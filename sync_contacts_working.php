<?php
// Script corregido para sincronizar contactos - SQL Syntax Fixed
// Ejecutar: https://camcam.com.ve/evoappws/sync_contacts_working.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔄 Sincronizar Contactos (SQL Corregido)</h2>";
    
    // 1. Verificar estado actual
    echo "<h3>📊 Estado Actual</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contacts");
    $contactsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats");
    $chatsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats WHERE name IS NOT NULL AND name != ''");
    $chatsWithNames = $stmt->rowCount();
    
    echo "<p>📇 Contactos actuales: <strong>$contactsCount</strong></p>";
    echo "<p>💬 Chats totales: <strong>$chatsCount</strong></p>";
    echo "<p>📝 Chats con nombres: <strong>$chatsWithNames</strong></p>";
    
    // 2. Extraer contactos de forma simple (sin LIMIT/OFFSET problemático)
    echo "<h3>📇 Extrayendo Contactos (Método Simple)</h3>";
    
    $stmt = $pdo->query("
        SELECT 
            instance_id,
            payload_json,
            created_at
        FROM webhook_events 
        WHERE event_type IN ('contacts.update', 'contact_update')
        AND status = 'processed'
        ORDER BY created_at DESC
        LIMIT 200
    ");
    
    $contactEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $contactsInserted = 0;
    $contactsUpdated = 0;
    $contactsSkipped = 0;
    
    echo "<p>📊 Procesando " . count($contactEvents) . " eventos de contactos...</p>";
    
    foreach ($contactEvents as $event) {
        $payload = json_decode($event['payload_json'], true);
        
        if (isset($payload['data'])) {
            $data = $payload['data'];
            
            // Manejar ambos formatos: array y object
            $contactsList = is_array($data) ? $data : [$data];
            
            foreach ($contactsList as $contact) {
                if (isset($contact['remoteJid']) && isset($contact['pushName'])) {
                    $remoteJid = $contact['remoteJid'];
                    $pushName = trim($contact['pushName']);
                    
                    // Ignorar nombres vacíos
                    if (empty($pushName)) {
                        $contactsSkipped++;
                        continue;
                    }
                    
                    // Extraer número de teléfono
                    $phone = str_replace(['@s.whatsapp.net', '@g.us', '@lid'], '', $remoteJid);
                    
                    // Verificar si ya existe
                    $stmt = $pdo->prepare("SELECT id, name FROM contacts WHERE remote_jid = ?");
                    $stmt->execute([$remoteJid]);
                    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$existing) {
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
                        // Actualizar si el nombre es diferente y no está vacío
                        if ($existing['name'] !== $pushName && !empty($pushName)) {
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
                } else {
                    $contactsSkipped++;
                }
            }
        }
    }
    
    echo "<p style='color: green;'>✅ Contactos insertados: <strong>$contactsInserted</strong></p>";
    echo "<p style='color: blue;'>✅ Contactos actualizados: <strong>$contactsUpdated</strong></p>";
    echo "<p style='color: orange;'>⚠️ Contactos omitidos: <strong>$contactsSkipped</strong></p>";
    
    // 3. Actualizar nombres en chats
    echo "<h3>💬 Actualizar Nombres en Chats</h3>";
    
    // Actualizar chats donde remote_jid coincide con contact.remote_jid
    $stmt = $pdo->query("
        UPDATE chats c
        INNER JOIN contacts co ON c.remote_jid = co.remote_jid
        SET c.name = co.name
        WHERE c.name IS NULL OR c.name != co.name
    ");
    $updatedFromContacts = $stmt->rowCount();
    
    echo "<p style='color: green;'>✅ Chats actualizados desde contacts: <strong>$updatedFromContacts</strong></p>";
    
    // También actualizar por phone_e164
    $stmt = $pdo->query("
        UPDATE chats c
        INNER JOIN contacts co ON REPLACE(c.remote_jid, '@s.whatsapp.net', '') = co.phone_e164
        SET c.name = co.name
        WHERE c.name IS NULL OR c.name != co.name
    ");
    $updatedByPhone = $stmt->rowCount();
    
    echo "<p style='color: green;'>✅ Chats actualizados por teléfono: <strong>$updatedByPhone</strong></p>";
    
    // 4. Extraer nombres directamente de webhooks de chats
    echo "<h3>🔄 Extraer Nombres de Chats Directamente</h3>";
    
    $stmt = $pdo->query("
        SELECT 
            instance_id,
            payload_json,
            created_at
        FROM webhook_events 
        WHERE event_type IN ('chats.upsert', 'chat_update')
        AND status = 'processed'
        AND payload_json LIKE '%name%'
        ORDER BY created_at DESC
        LIMIT 100
    ");
    
    $chatEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $chatsUpdated = 0;
    
    echo "<p>📊 Procesando " . count($chatEvents) . " eventos de chats con nombres...</p>";
    
    foreach ($chatEvents as $event) {
        $payload = json_decode($event['payload_json'], true);
        
        if (isset($payload['data'])) {
            $data = $payload['data'];
            
            // Manejar ambos formatos: array y object
            $chatsList = is_array($data) ? $data : [$data];
            
            foreach ($chatsList as $chat) {
                if (isset($chat['id']) && isset($chat['name']) && !empty($chat['name'])) {
                    $chatId = $chat['id'];
                    $chatName = trim($chat['name']);
                    
                    if (!empty($chatName)) {
                        // Actualizar directamente el nombre del chat
                        $stmt = $pdo->prepare("
                            UPDATE chats SET name = ?, updated_at = ? 
                            WHERE id = ? AND (name IS NULL OR name != ?)
                        ");
                        $result = $stmt->execute([$chatName, $event['created_at'], $chatId, $chatName]);
                        
                        if ($result && $stmt->rowCount() > 0) {
                            $chatsUpdated++;
                        }
                    }
                }
            }
        }
    }
    
    echo "<p style='color: green;'>✅ Chats actualizados con nombres directos: <strong>$chatsUpdated</strong></p>";
    
    // 5. Mostrar resultados finales
    echo "<h3>📊 Resultados Finales</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contacts");
    $finalContactsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats");
    $finalChatsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats WHERE name IS NOT NULL AND name != ''");
    $finalChatsWithNames = $stmt->rowCount();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Tabla</th><th>Antes</th><th>Después</th><th>Cambio</th><th>Status</th></tr>";
    echo "<tr><td>Contacts</td><td>$contactsCount</td><td>$finalContactsCount</td><td>+" . ($finalContactsCount - $contactsCount) . "</td><td>" . ($finalContactsCount > $contactsCount ? '✅' : '➡️') . "</td></tr>";
    echo "<tr><td>Chats</td><td>$chatsCount</td><td>$finalChatsCount</td><td>" . ($finalChatsCount - $chatsCount) . "</td><td>" . ($finalChatsCount >= $chatsCount ? '✅' : '➡️') . "</td></tr>";
    echo "<tr><td>Chats con nombres</td><td>$chatsWithNames</td><td>$finalChatsWithNames</td><td>+" . ($finalChatsWithNames - $chatsWithNames) . "</td><td>" . ($finalChatsWithNames > $chatsWithNames ? '✅' : '➡️') . "</td></tr>";
    echo "</table>";
    
    // 6. Mostrar contactos sincronizados
    if ($finalContactsCount > 0) {
        echo "<h4>📇 Contactos Sincronizados:</h4>";
        $stmt = $pdo->query("SELECT name, phone_e164, remote_jid FROM contacts ORDER BY name ASC LIMIT 10");
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Name</th><th>Phone</th><th>Remote JID</th></tr>";
        foreach ($contacts as $contact) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($contact['name']) . "</td>";
            echo "<td>{$contact['phone_e164']}</td>";
            echo "<td>{$contact['remote_jid']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 7. Mostrar chats con nombres
    if ($finalChatsWithNames > 0) {
        echo "<h4>💬 Chats con Nombres:</h4>";
        $stmt = $pdo->query("SELECT id, name, remote_jid, is_group FROM chats WHERE name IS NOT NULL AND name != '' ORDER BY name ASC LIMIT 10");
        $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Remote JID</th><th>Group</th></tr>";
        foreach ($chats as $chat) {
            echo "<tr>";
            echo "<td>{$chat['id']}</td>";
            echo "<td>" . htmlspecialchars($chat['name']) . "</td>";
            echo "<td>{$chat['remote_jid']}</td>";
            echo "<td>" . ($chat['is_group'] ? 'Sí' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>🎯 Resumen de la Sincronización</h3>";
    echo "<ul>";
    echo "<li>✅ Contactos nuevos: $contactsInserted</li>";
    echo "<li>✅ Contactos actualizados: $contactsUpdated</li>";
    echo "<li>✅ Chats actualizados: " . ($updatedFromContacts + $updatedByPhone + $chatsUpdated) . "</li>";
    echo "<li>🔄 Refresca el catálogo para ver los cambios</li>";
    echo "</ul>";
    
    if ($finalChatsWithNames >= 10) {
        echo "<h3 style='color: green;'>🎉 ¡Excelente! Hay $finalChatsWithNames chats con nombres</h3>";
        echo "<p>El catálogo debería mostrar muchos más nombres ahora.</p>";
    } elseif ($finalChatsWithNames >= 5) {
        echo "<h3 style='color: blue;'>👍 ¡Buen Progreso! Hay $finalChatsWithNames chats con nombres</h3>";
        echo "<p>Los nombres deberían ser visibles en el catálogo.</p>";
    } else {
        echo "<h3 style='color: orange;'>⚠️ Progreso Parcial: $finalChatsWithNames chats con nombres</h3>";
        echo "<p>Se necesitarán más webhooks para obtener más nombres.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>❌ Error de Conexión</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Verifica la configuración de la base de datos.</p>";
} catch (Exception $e) {
    echo "<h2>❌ Error General</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
