<?php
// Script rápido y seguro para sincronizar contactos
// Ejecutar: https://camcam.com.ve/evoappws/quick_sync_contacts.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>⚡ Sincronización Rápida de Contactos</h2>";
    
    // 1. Extraer contactos en lotes pequeños
    echo "<h3>📇 Extrayendo Contactos (Lotes de 50)</h3>";
    
    $offset = 0;
    $limit = 50;
    $totalProcessed = 0;
    $contactsInserted = 0;
    $contactsUpdated = 0;
    
    do {
        $stmt = $pdo->prepare("
            SELECT 
                instance_id,
                payload_json,
                created_at
            FROM webhook_events 
            WHERE event_type IN ('contacts.update', 'contact_update')
            AND status = 'processed'
            ORDER BY created_at ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $contactEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>📊 Procesando lote " . ($offset / $limit + 1) . " (" . count($contactEvents) . " eventos)...</p>";
        
        foreach ($contactEvents as $event) {
            $payload = json_decode($event['payload_json'], true);
            
            if (isset($payload['data'])) {
                $data = $payload['data'];
                $contactsList = is_array($data) ? $data : [$data];
                
                foreach ($contactsList as $contact) {
                    if (isset($contact['remoteJid']) && isset($contact['pushName'])) {
                        $remoteJid = $contact['remoteJid'];
                        $pushName = $contact['pushName'];
                        $phone = str_replace(['@s.whatsapp.net', '@g.us', '@lid'], '', $remoteJid);
                        
                        // Verificar si existe
                        $stmt = $pdo->prepare("SELECT id, name FROM contacts WHERE remote_jid = ?");
                        $stmt->execute([$remoteJid]);
                        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$existing) {
                            // Insertar
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
                        } elseif ($existing['name'] !== $pushName) {
                            // Actualizar
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
        
        $totalProcessed += count($contactEvents);
        $offset += $limit;
        
        // Pequeña pausa para no sobrecargar
        if ($totalProcessed % 200 == 0) {
            echo "<p>⏸️ Pausa breve para evitar sobrecarga...</p>";
            usleep(100000); // 0.1 segundos
        }
        
    } while (count($contactEvents) > 0 && $totalProcessed < 1000); // Límite de seguridad
    
    echo "<p style='color: green;'>✅ Contactos procesados: <strong>$totalProcessed</strong></p>";
    echo "<p style='color: green;'>✅ Contactos insertados: <strong>$contactsInserted</strong></p>";
    echo "<p style='color: blue;'>✅ Contactos actualizados: <strong>$contactsUpdated</strong></p>";
    
    // 2. Actualizar chats con nombres de contactos
    echo "<h3>💬 Actualizando Chats con Nombres</h3>";
    
    $stmt = $pdo->query("
        UPDATE chats c
        INNER JOIN contacts co ON c.remote_jid = co.remote_jid
        SET c.name = co.name
        WHERE c.name IS NULL OR c.name != co.name
    ");
    $updatedFromContacts = $stmt->rowCount();
    
    echo "<p style='color: green;'>✅ Chats actualizados desde contacts: <strong>$updatedFromContacts</strong></p>";
    
    // 3. Mostrar resultados finales
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
    
    // 4. Mostrar muestra de contactos
    if ($contactsCount > 0) {
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
    
    // 5. Mostrar chats con nombres
    if ($chatsWithNames > 0) {
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
    
    echo "<h3>🎯 ¡Sincronización Completada!</h3>";
    echo "<ul>";
    echo "<li>✅ Contactos totales: $contactsCount</li>";
    echo "<li>✅ Chats totales: $chatsCount</li>";
    echo "<li>✅ Chats con nombres: $chatsWithNames</li>";
    echo "<li>🔄 Refresca el catálogo para ver los cambios</li>";
    echo "</ul>";
    
    if ($chatsWithNames > 5) {
        echo "<h3 style='color: green;'>🎉 ¡Éxito! Hay $chatsWithNames chats con nombres</h3>";
    } else {
        echo "<h3 style='color: orange;'>⚠️ Progreso: $chatsWithNames chats con nombres</h3>";
    }
    
} catch (PDOException $e) {
    echo "<h2>❌ Error de Conexión</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2>❌ Error General</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
