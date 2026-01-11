<?php
// Script corregido para sincronizar contactos desde webhook events
// Ejecutar: https://camcam.com.ve/evoappws/sync_contacts_fixed.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔄 Sincronizar Contactos (Versión Corregida)</h2>";
    
    // 1. Extraer contactos desde webhook events
    echo "<h3>📇 Sincronizar Contactos Completos</h3>";
    
    $stmt = $pdo->query("
        SELECT 
            instance_id,
            payload_json,
            created_at
        FROM webhook_events 
        WHERE event_type IN ('contacts.update', 'contact_update')
        AND status = 'processed'
        ORDER BY created_at ASC
        LIMIT 500
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
                    $pushName = $contact['pushName'];
                    
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
                        // Actualizar si el nombre es diferente
                        if ($existing['name'] !== $pushName) {
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
    
    // 2. Actualizar nombres en chats usando todos los contactos
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
    
    // También actualizar por phone_e164 si remote_jid no coincide
    $stmt = $pdo->query("
        UPDATE chats c
        INNER JOIN contacts co ON REPLACE(c.remote_jid, '@s.whatsapp.net', '') = co.phone_e164
        SET c.name = co.name
        WHERE c.name IS NULL OR c.name != co.name
    ");
    $updatedByPhone = $stmt->rowCount();
    
    echo "<p style='color: green;'>✅ Chats actualizados por teléfono: <strong>$updatedByPhone</strong></p>";
    
    // 3. Extraer nombres de webhooks de chats que no tienen pushName
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
        ORDER BY created_at ASC
        LIMIT 200
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
    
    // 4. Mostrar resultados finales
    echo "<h3>📊 Resultados Finales</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contacts");
    $contactsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats");
    $chatsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats WHERE name IS NOT NULL AND name != ''");
    $chatsWithNames = $stmt->rowCount();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Tabla</th><th>Total</th><th>Con Nombres</th><th>Status</th></tr>";
    echo "<tr><td>Contacts</td><td>$contactsCount</td><td>-</td><td>" . ($contactsCount > 0 ? '✅' : '❌') . "</td></tr>";
    echo "<tr><td>Chats</td><td>$chatsCount</td><td>$chatsWithNames</td><td>" . ($chatsWithNames > 0 ? '✅' : '❌') . "</td></tr>";
    echo "</table>";
    
    // 5. Mostrar todos los contactos disponibles
    echo "<h4>📇 Todos los Contactos Disponibles:</h4>";
    $stmt = $pdo->query("SELECT name, phone_e164, remote_jid FROM contacts ORDER BY name ASC");
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
    
    // 6. Mostrar chats con nombres
    echo "<h4>💬 Todos los Chats con Nombres:</h4>";
    $stmt = $pdo->query("SELECT id, name, remote_jid, is_group FROM chats WHERE name IS NOT NULL AND name != '' ORDER BY name ASC");
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
    
    echo "<h3>🎯 Resumen Final</h3>";
    echo "<ul>";
    echo "<li>✅ Contactos totales: $contactsCount</li>";
    echo "<li>✅ Chats totales: $chatsCount</li>";
    echo "<li>✅ Chats con nombres: $chatsWithNames</li>";
    echo "<li>🔄 Refresca el catálogo para ver los cambios</li>";
    echo "</ul>";
    
    if ($chatsWithNames >= 10) {
        echo "<h3 style='color: green;'>🎉 ¡Excelente! Hay $chatsWithNames chats con nombres</h3>";
        echo "<p>El catálogo debería mostrar muchos más nombres ahora.</p>";
    } elseif ($chatsWithNames >= 5) {
        echo "<h3 style='color: blue;'>👍 ¡Buen Progreso! Hay $chatsWithNames chats con nombres</h3>";
        echo "<p>Los nombres deberían ser visibles en el catálogo.</p>";
    } else {
        echo "<h3 style='color: orange;'>⚠️ Progreso Parcial: Solo $chatsWithNames chats con nombres</h3>";
        echo "<p>Puede que necesitemos procesar más webhooks o verificar la estructura.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>❌ Error de Conexión</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2>❌ Error General</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
