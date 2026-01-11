<?php
// Script final para arreglar chats y sincronizar correctamente
// Ejecutar: https://camcam.com.ve/evoappws/fix_chats_final.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔧 Arreglar Chats (Versión Final)</h2>";
    
    // 1. Hacer remote_jid nullable en chats
    echo "<h3>🔨 Ajustar Columna remote_jid</h3>";
    try {
        $pdo->query("ALTER TABLE chats MODIFY COLUMN remote_jid VARCHAR(255) DEFAULT NULL");
        echo "<p style='color: green;'>✅ Columna 'remote_jid' ahora es NULLABLE</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Error modificando 'remote_jid': " . $e->getMessage() . "</p>";
    }
    
    // 2. Limpiar chats con remote_jid NULL para evitar conflictos
    echo "<h3>🧹 Limpiar Datos Inválidos</h3>";
    try {
        $stmt = $pdo->query("DELETE FROM chats WHERE remote_jid IS NULL AND name IS NULL");
        $deletedCount = $stmt->rowCount();
        echo "<p style='color: blue;'>🗑️ Chats inválidos eliminados: <strong>$deletedCount</strong></p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Error limpiando chats: " . $e->getMessage() . "</p>";
    }
    
    // 3. Sincronizar chats con validación mejorada
    echo "<h3>🔄 Sincronizar Chats (Validación Mejorada)</h3>";
    
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
    $chatsSkipped = 0;
    
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
                    
                    // Validación mejorada: al menos tener ID
                    if (empty($chatId)) {
                        $chatsSkipped++;
                        continue;
                    }
                    
                    // Determinar si es grupo
                    $isGroup = strpos($chatId, '@g.us') !== false;
                    
                    // Si no hay remoteJid, usar el chatId como fallback
                    if (empty($remoteJid)) {
                        $remoteJid = $chatId;
                    }
                    
                    try {
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
                            // Actualizar solo si hay nombre o remote_jid diferente
                            $stmt = $pdo->prepare("
                                UPDATE chats SET 
                                    name = COALESCE(?, name),
                                    remote_jid = COALESCE(?, remote_jid),
                                    updated_at = ? 
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
                    } catch (Exception $e) {
                        echo "<p style='color: orange;'>⚠️ Error con chat $chatId: " . $e->getMessage() . "</p>";
                        $chatsSkipped++;
                    }
                }
            }
        }
    }
    
    echo "<p style='color: green;'>✅ Chats insertados: <strong>$chatsInserted</strong></p>";
    echo "<p style='color: blue;'>✅ Chats actualizados: <strong>$chatsUpdated</strong></p>";
    echo "<p style='color: orange;'>⚠️ Chats omitidos: <strong>$chatsSkipped</strong></p>";
    
    // 4. Extraer nombres de contactos para chats existentes
    echo "<h3>🔄 Actualizar Nombres en Chats desde Contacts</h3>";
    
    $stmt = $pdo->query("
        UPDATE chats c
        LEFT JOIN contacts co ON c.remote_jid = co.remote_jid
        SET c.name = COALESCE(co.name, c.name)
        WHERE c.name IS NULL AND co.name IS NOT NULL
    ");
    $updatedFromContacts = $stmt->rowCount();
    
    echo "<p style='color: green;'>✅ Chats actualizados desde contacts: <strong>$updatedFromContacts</strong></p>";
    
    // 5. Mostrar resultados finales
    echo "<h3>📊 Resultados Finales</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contacts");
    $contactsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats");
    $chatsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats WHERE name IS NOT NULL");
    $chatsWithNames = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Tabla</th><th>Total</th><th>Con Nombres</th><th>Status</th></tr>";
    echo "<tr><td>Contacts</td><td>$contactsCount</td><td>-</td><td>" . ($contactsCount > 0 ? '✅' : '❌') . "</td></tr>";
    echo "<tr><td>Chats</td><td>$chatsCount</td><td>$chatsWithNames</td><td>" . ($chatsCount > 0 ? '✅' : '❌') . "</td></tr>";
    echo "</table>";
    
    // 6. Mostrar muestra de datos
    echo "<h4>📇 Contactos Disponibles:</h4>";
    $stmt = $pdo->query("SELECT name, phone_e164, remote_jid FROM contacts ORDER BY created_at DESC LIMIT 5");
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Name</th><th>Phone</th><th>Remote JID</th></tr>";
    foreach ($contacts as $contact) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($contact['name'] ?? 'SIN NOMBRE') . "</td>";
        echo "<td>{$contact['phone_e164']}</td>";
        echo "<td>{$contact['remote_jid']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h4>💬 Chats con Nombres:</h4>";
    $stmt = $pdo->query("SELECT id, name, remote_jid, is_group FROM chats WHERE name IS NOT NULL ORDER BY created_at DESC LIMIT 10");
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
    
    // 7. Verificar webhook events recientes para más datos
    echo "<h3>📡 Webhook Events Recientes para Procesar</h3>";
    $stmt = $pdo->query("
        SELECT event_type, COUNT(*) as count
        FROM webhook_events 
        WHERE event_type IN ('contacts.update', 'contact_update', 'chats.upsert', 'chat_update', 'chats.update')
        AND status = 'processed'
        GROUP BY event_type
        ORDER BY count DESC
    ");
    $eventCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Event Type</th><th>Count</th></tr>";
    foreach ($eventCounts as $event) {
        echo "<tr>";
        echo "<td>{$event['event_type']}</td>";
        echo "<td>{$event['count']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>🎯 Próximos Pasos</h3>";
    echo "<ul>";
    echo "<li>✅ Estructura de chats arreglada</li>";
    echo "<li>✅ Contactos sincronizados: $contactsCount</li>";
    echo "<li>✅ Chats actualizados: $chatsCount ($chatsWithNames con nombres)</li>";
    echo "<li>🔄 Refrescar el catálogo para ver los nombres</li>";
    echo "<li>📱 Probar envío de mensajes para ver nombres</li>";
    echo "<li>📊 Monitorear si nuevos contactos se sincronizan</li>";
    echo "</ul>";
    
    if ($chatsWithNames > 0) {
        echo "<h3 style='color: green;'>🎉 ¡Éxito! Hay $chatsWithNames chats con nombres</h3>";
        echo "<p>Los nombres deberían ser visibles ahora en el catálogo.</p>";
    } else {
        echo "<h3 style='color: orange;'>⚠️ Aún no hay chats con nombres</h3>";
        echo "<p>Puede que necesites esperar más webhook events o ejecutar el script de procesamiento de webhooks pendientes.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>❌ Error de Conexión</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
