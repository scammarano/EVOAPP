<?php
// Script forzado para sincronizar nombres - método alternativo
// Ejecutar: https://camcam.com.ve/evoappws/force_sync_names.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔥 Forzar Sincronización de Nombres</h2>";
    
    // 1. Extraer nombres de logs recientes (basado en los logs que vimos)
    echo "<h3>📇 Extraer Nombres de System Logs</h3>";
    
    // Basado en los logs, sabemos que estos nombres existen:
    $knownNames = [
        'Anderson Pacheco' => '584127231258@s.whatsapp.net',
        'Rafael Velásquez' => '584143197300@s.whatsapp.net',
        'Salvatore Cammarano' => '584143238051@s.whatsapp.net',
        'Arie Blum' => '584142404172-1496408994@g.us',
        'Jose Antonio Cagiao' => '93492931997795@lid',
        'Henry LoM' => '584142404172-1496408994@g.us'
    ];
    
    $contactsInserted = 0;
    $contactsUpdated = 0;
    
    foreach ($knownNames as $name => $remoteJid) {
        // Extraer teléfono del remoteJid
        $phone = str_replace(['@s.whatsapp.net', '@g.us', '@lid'], '', $remoteJid);
        
        // Verificar si ya existe
        $stmt = $pdo->prepare("SELECT id, name FROM contacts WHERE remote_jid = ?");
        $stmt->execute([$remoteJid]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing) {
            // Insertar nuevo contacto
            $stmt = $pdo->prepare("
                INSERT INTO contacts (instance_id, phone_e164, name, push_name, remote_jid, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([1, $phone, $name, $name, $remoteJid]);
            $contactsInserted++;
            echo "<p style='color: green;'>✅ Insertado: $name ($remoteJid)</p>";
        } else {
            // Actualizar si el nombre es diferente
            if ($existing['name'] !== $name) {
                $stmt = $pdo->prepare("
                    UPDATE contacts SET name = ?, push_name = ?, updated_at = NOW() 
                    WHERE remote_jid = ?
                ");
                $stmt->execute([$name, $name, $remoteJid]);
                $contactsUpdated++;
                echo "<p style='color: blue;'>✅ Actualizado: $name ($remoteJid)</p>";
            }
        }
    }
    
    echo "<p style='color: green;'>📇 Contactos insertados: <strong>$contactsInserted</strong></p>";
    echo "<p style='color: blue;'>📇 Contactos actualizados: <strong>$contactsUpdated</strong></p>";
    
    // 2. Forzar actualización de chats con estos contactos
    echo "<h3>💬 Forzar Actualización de Chats</h3>";
    
    $chatsUpdated = 0;
    
    foreach ($knownNames as $name => $remoteJid) {
        // Actualizar chats donde remote_jid coincide
        $stmt = $pdo->prepare("
            UPDATE chats SET name = ?, updated_at = NOW() 
            WHERE remote_jid = ? AND (name IS NULL OR name != ?)
        ");
        $stmt->execute([$name, $remoteJid, $name]);
        $rows = $stmt->rowCount();
        
        if ($rows > 0) {
            $chatsUpdated += $rows;
            echo "<p style='color: green;'>✅ Chat actualizado: $name ($rows filas)</p>";
        }
        
        // También intentar por phone
        $phone = str_replace(['@s.whatsapp.net', '@g.us', '@lid'], '', $remoteJid);
        $stmt = $pdo->prepare("
            UPDATE chats SET name = ?, updated_at = NOW() 
            WHERE remote_jid LIKE ? AND (name IS NULL OR name != ?)
        ");
        $stmt->execute([$name, "%$phone%", $name]);
        $rows = $stmt->rowCount();
        
        if ($rows > 0) {
            $chatsUpdated += $rows;
            echo "<p style='color: green;'>✅ Chat actualizado por teléfono: $name ($rows filas)</p>";
        }
    }
    
    echo "<p style='color: green;'>💬 Chats actualizados: <strong>$chatsUpdated</strong></p>";
    
    // 3. Extraer nombres directamente de webhooks de chats
    echo "<h3>🔄 Extraer Nombres de Webhooks de Chats</h3>";
    
    $stmt = $pdo->query("
        SELECT payload_json
        FROM webhook_events 
        WHERE event_type IN ('chats.upsert', 'chat_update')
        AND status = 'processed'
        AND payload_json LIKE '%name%'
        ORDER BY created_at DESC
        LIMIT 50
    ");
    
    $chatEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $directUpdates = 0;
    
    foreach ($chatEvents as $event) {
        $payload = json_decode($event['payload_json'], true);
        
        if (isset($payload['data'])) {
            $data = $payload['data'];
            $chatsList = is_array($data) ? $data : [$data];
            
            foreach ($chatsList as $chat) {
                if (isset($chat['id']) && isset($chat['name']) && !empty($chat['name'])) {
                    $chatId = $chat['id'];
                    $chatName = trim($chat['name']);
                    
                    if (!empty($chatName)) {
                        // Actualizar directamente
                        $stmt = $pdo->prepare("
                            UPDATE chats SET name = ?, updated_at = NOW() 
                            WHERE id = ? AND (name IS NULL OR name != ?)
                        ");
                        $result = $stmt->execute([$chatName, $chatId, $chatName]);
                        
                        if ($result && $stmt->rowCount() > 0) {
                            $directUpdates++;
                            echo "<p style='color: blue;'>✅ Nombre directo: $chatName ($chatId)</p>";
                        }
                    }
                }
            }
        }
    }
    
    echo "<p style='color: blue;'>🔄 Actualizaciones directas: <strong>$directUpdates</strong></p>";
    
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
    echo "<tr><td>Contacts</td><td>$contactsCount</td><td>-</td><td>" . ($contactsCount > 1 ? '✅' : '❌') . "</td></tr>";
    echo "<tr><td>Chats</td><td>$chatsCount</td><td>$chatsWithNames</td><td>" . ($chatsWithNames > 1 ? '✅' : '❌') . "</td></tr>";
    echo "</table>";
    
    // 5. Mostrar todos los contactos
    echo "<h4>📇 Todos los Contactos:</h4>";
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
    echo "<h4>💬 Chats con Nombres:</h4>";
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
    
    echo "<h3>🎯 Resumen de la Sincronización Forzada</h3>";
    echo "<ul>";
    echo "<li>✅ Contactos insertados: $contactsInserted</li>";
    echo "<li>✅ Contactos actualizados: $contactsUpdated</li>";
    echo "<li>✅ Chats actualizados: $chatsUpdated</li>";
    echo "<li>✅ Actualizaciones directas: $directUpdates</li>";
    echo "<li>🔄 Refresca el catálogo para ver los cambios</li>";
    echo "</ul>";
    
    if ($chatsWithNames >= 5) {
        echo "<h3 style='color: green;'>🎉 ¡Éxito! Hay $chatsWithNames chats con nombres</h3>";
        echo "<p>El catálogo debería mostrar muchos más nombres ahora.</p>";
    } elseif ($chatsWithNames >= 3) {
        echo "<h3 style='color: blue;'>👍 ¡Buen Progreso! Hay $chatsWithNames chats con nombres</h3>";
        echo "<p>Los nombres deberían ser visibles en el catálogo.</p>";
    } else {
        echo "<h3 style='color: orange;'>⚠️ Se necesitan más nombres: $chatsWithNames chats con nombres</h3>";
        echo "<p>Ejecuta el diagnóstico para entender mejor el problema.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>❌ Error de Conexión</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2>❌ Error General</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
