<?php
// Script FINAL para extraer TODOS los datos - Schema Compatible
// Ejecutar: https://camcam.com.ve/evoappws/extract_all_data_final.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>📊 Extracción COMPLETA de Datos (Schema Compatible)</h2>";
    
    // 1. Verificar y corregir schema de la tabla contacts
    echo "<h3>🔧 Verificando Schema de Tablas</h3>";
    
    // Verificar tabla contacts
    $stmt = $pdo->query("DESCRIBE contacts");
    $contactsColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $contactsColumnNames = array_map(function($col) { return $col['Field']; }, $contactsColumns);
    
    echo "<p>📇 Columnas en contacts: " . implode(', ', $contactsColumnNames) . "</p>";
    
    // Agregar columnas faltantes si es necesario
    $requiredContactsColumns = ['id', 'instance_id', 'phone_e164', 'name', 'push_name', 'remote_jid', 'created_at', 'updated_at'];
    
    foreach ($requiredContactsColumns as $column) {
        if (!in_array($column, $contactsColumnNames)) {
            echo "<p style='color: orange;'>⚠️ Agregando columna {$column} a contacts...</p>";
            
            $columnType = 'VARCHAR(255)';
            if ($column === 'id') $columnType = 'INT AUTO_INCREMENT PRIMARY KEY';
            elseif ($column === 'instance_id') $columnType = 'INT';
            elseif ($column === 'created_at') $columnType = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
            elseif ($column === 'updated_at') $columnType = 'TIMESTAMP NULL DEFAULT NULL';
            
            try {
                $sql = "ALTER TABLE contacts ADD COLUMN {$column} {$columnType}";
                if ($column === 'id') {
                    echo "<p style='color: blue;'>ℹ️ La columna id ya debería existir como clave primaria</p>";
                } else {
                    $pdo->query($sql);
                    echo "<p style='color: green;'>✅ Columna {$column} agregada</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ Error agregando {$column}: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Verificar tabla chats
    $stmt = $pdo->query("DESCRIBE chats");
    $chatsColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $chatsColumnNames = array_map(function($col) { return $col['Field']; }, $chatsColumns);
    
    echo "<p>💬 Columnas en chats: " . implode(', ', $chatsColumnNames) . "</p>";
    
    // Agregar columnas faltantes si es necesario
    $requiredChatsColumns = ['id', 'instance_id', 'name', 'remote_jid', 'is_group', 'created_at', 'updated_at'];
    
    foreach ($requiredChatsColumns as $column) {
        if (!in_array($column, $chatsColumnNames)) {
            echo "<p style='color: orange;'>⚠️ Agregando columna {$column} a chats...</p>";
            
            $columnType = 'VARCHAR(255)';
            if ($column === 'id') $columnType = 'VARCHAR(255) PRIMARY KEY';
            elseif ($column === 'instance_id') $columnType = 'INT';
            elseif ($column === 'is_group') $columnType = 'TINYINT(1) DEFAULT 0';
            elseif ($column === 'created_at') $columnType = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
            elseif ($column === 'updated_at') $columnType = 'TIMESTAMP NULL DEFAULT NULL';
            
            try {
                $pdo->query("ALTER TABLE chats ADD COLUMN {$column} {$columnType}");
                echo "<p style='color: green;'>✅ Columna {$column} agregada a chats</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ Error agregando {$column} a chats: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // 2. Extraer TODOS los contactos de TODOS los webhooks
    echo "<h3>📇 Extrayendo TODOS los Contactos (Schema Compatible)</h3>";
    
    $contactsInserted = 0;
    $contactsUpdated = 0;
    $totalContactEvents = 0;
    
    // Procesar sin LIMIT/OFFSET problemático
    $stmt = $pdo->query("
        SELECT 
            instance_id,
            payload_json,
            created_at
        FROM webhook_events 
        WHERE event_type IN ('contacts.update', 'contact_update')
        AND status = 'processed'
        ORDER BY created_at ASC
    ");
    
    $contactEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalContactEvents = count($contactEvents);
    
    echo "<p>📊 Procesando <strong>$totalContactEvents</strong> eventos de contactos...</p>";
    
    foreach ($contactEvents as $event) {
        $payload = json_decode($event['payload_json'], true);
        
        if (isset($payload['data'])) {
            $data = $payload['data'];
            $contactsList = is_array($data) ? $data : [$data];
            
            foreach ($contactsList as $contact) {
                if (isset($contact['remoteJid'])) {
                    $remoteJid = $contact['remoteJid'];
                    $pushName = $contact['pushName'] ?? '';
                    $profilePic = $contact['profilePicUrl'] ?? '';
                    
                    // Extraer número de teléfono
                    $phone = str_replace(['@s.whatsapp.net', '@g.us', '@lid'], '', $remoteJid);
                    
                    // Verificar si ya existe
                    $stmt = $pdo->prepare("SELECT id, name FROM contacts WHERE remote_jid = ?");
                    $stmt->execute([$remoteJid]);
                    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$existing) {
                        // Insertar nuevo contacto - SOLO columnas existentes
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
                        // Actualizar si hay datos nuevos
                        $updates = [];
                        $params = [];
                        
                        if (!empty($pushName) && $existing['name'] !== $pushName) {
                            $updates[] = "name = ?";
                            $updates[] = "push_name = ?";
                            $params[] = $pushName;
                            $params[] = $pushName;
                        }
                        
                        $updates[] = "updated_at = ?";
                        $params[] = $event['created_at'];
                        $params[] = $remoteJid;
                        
                        if (!empty($updates)) {
                            $sql = "UPDATE contacts SET " . implode(', ', $updates) . " WHERE remote_jid = ?";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);
                            $contactsUpdated++;
                        }
                    }
                }
            }
        }
    }
    
    echo "<p style='color: green;'>✅ Total eventos de contactos procesados: <strong>$totalContactEvents</strong></p>";
    echo "<p style='color: green;'>✅ Contactos insertados: <strong>$contactsInserted</strong></p>";
    echo "<p style='color: blue;'>✅ Contactos actualizados: <strong>$contactsUpdated</strong></p>";
    
    // 3. Extraer TODOS los chats y grupos
    echo "<h3>💬 Extrayendo TODOS los Chats y Grupos</h3>";
    
    $chatsInserted = 0;
    $chatsUpdated = 0;
    
    $stmt = $pdo->query("
        SELECT 
            instance_id,
            payload_json,
            created_at
        FROM webhook_events 
        WHERE event_type IN ('chats.upsert', 'chat_update', 'chats.update')
        AND status = 'processed'
        ORDER BY created_at ASC
    ");
    
    $chatEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalChatEvents = count($chatEvents);
    
    echo "<p>📊 Procesando <strong>$totalChatEvents</strong> eventos de chats...</p>";
    
    foreach ($chatEvents as $event) {
        $payload = json_decode($event['payload_json'], true);
        
        if (isset($payload['data'])) {
            $data = $payload['data'];
            $chatsList = is_array($data) ? $data : [$data];
            
            foreach ($chatsList as $chat) {
                if (isset($chat['id'])) {
                    $chatId = $chat['id'];
                    $chatName = $chat['name'] ?? '';
                    $remoteJid = $chat['remoteJid'] ?? '';
                    $isGroup = strpos($chatId, '@g.us') !== false;
                    
                    // Verificar si ya existe
                    $stmt = $pdo->prepare("SELECT id, name FROM chats WHERE id = ?");
                    $stmt->execute([$chatId]);
                    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$existing) {
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
                        // Actualizar si hay datos nuevos
                        if (!empty($chatName) && $existing['name'] !== $chatName) {
                            $stmt = $pdo->prepare("
                                UPDATE chats SET name = ?, updated_at = ? 
                                WHERE id = ?
                            ");
                            $stmt->execute([$chatName, $event['created_at'], $chatId]);
                            $chatsUpdated++;
                        }
                    }
                }
            }
        }
    }
    
    echo "<p style='color: green;'>✅ Total eventos de chats procesados: <strong>$totalChatEvents</strong></p>";
    echo "<p style='color: green;'>✅ Chats insertados: <strong>$chatsInserted</strong></p>";
    echo "<p style='color: blue;'>✅ Chats actualizados: <strong>$chatsUpdated</strong></p>";
    
    // 4. Extraer participantes de grupos
    echo "<h3>👥 Extrayendo Participantes de Grupos</h3>";
    
    // Verificar si tenemos tabla de participantes o si necesitamos crearla
    $stmt = $pdo->query("SHOW TABLES LIKE 'group_participants'");
    $hasParticipantsTable = $stmt->rowCount() > 0;
    
    if (!$hasParticipantsTable) {
        echo "<p style='color: orange;'>⚠️ Creando tabla group_participants...</p>";
        
        $createTable = "
        CREATE TABLE group_participants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id VARCHAR(255) NOT NULL,
            group_name VARCHAR(255),
            participant_jid VARCHAR(255) NOT NULL,
            participant_name VARCHAR(255),
            participant_phone VARCHAR(50),
            is_admin TINYINT(1) DEFAULT 0,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            instance_id INT,
            INDEX idx_group (group_id),
            INDEX idx_participant (participant_jid),
            INDEX idx_instance (instance_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->query($createTable);
        echo "<p style='color: green;'>✅ Tabla group_participants creada</p>";
    }
    
    // Buscar eventos que puedan tener datos de participantes
    $participantsInserted = 0;
    
    $stmt = $pdo->query("
        SELECT payload_json, created_at
        FROM webhook_events 
        WHERE event_type LIKE '%participant%' 
        OR event_type LIKE '%group%'
        OR event_type LIKE '%member%'
        AND status = 'processed'
        ORDER BY created_at DESC
        LIMIT 200
    ");
    
    $participantEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>📊 Buscando eventos con datos de participantes...</p>";
    
    foreach ($participantEvents as $event) {
        $payload = json_decode($event['payload_json'], true);
        
        // Buscar diferentes estructuras posibles de participantes
        $participants = [];
        
        if (isset($payload['data']['participants'])) {
            $participants = $payload['data']['participants'];
        } elseif (isset($payload['data']['groupInfo']['participants'])) {
            $participants = $payload['data']['groupInfo']['participants'];
        } elseif (isset($payload['participants'])) {
            $participants = $payload['participants'];
        }
        
        if (!empty($participants)) {
            $groupId = $payload['data']['id'] ?? $payload['groupId'] ?? '';
            $groupName = $payload['data']['subject'] ?? $payload['data']['name'] ?? '';
            
            foreach ($participants as $participant) {
                $participantJid = $participant['id'] ?? $participant['jid'] ?? $participant['remoteJid'] ?? '';
                $participantName = $participant['name'] ?? $participant['pushName'] ?? '';
                $isAdmin = $participant['isAdmin'] ?? $participant['type'] === 'admin' ? 1 : 0;
                
                if (!empty($participantJid)) {
                    // Extraer teléfono del participante
                    $participantPhone = str_replace(['@s.whatsapp.net', '@g.us', '@lid'], '', $participantJid);
                    
                    // Verificar si ya existe
                    $stmt = $pdo->prepare("SELECT id FROM group_participants WHERE group_id = ? AND participant_jid = ?");
                    $stmt->execute([$groupId, $participantJid]);
                    
                    if ($stmt->rowCount() == 0) {
                        $stmt = $pdo->prepare("
                            INSERT INTO group_participants (group_id, group_name, participant_jid, participant_name, participant_phone, is_admin, instance_id, joined_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $groupId,
                            $groupName,
                            $participantJid,
                            $participantName,
                            $participantPhone,
                            $isAdmin,
                            1, // instance_id por defecto
                            $event['created_at']
                        ]);
                        $participantsInserted++;
                        
                        // También guardar como contacto si tiene nombre
                        if (!empty($participantName)) {
                            saveAsContact($event['instance_id'], $participantJid, $participantName, $participantPhone);
                        }
                    }
                }
            }
        }
    }
    
    echo "<p style='color: green;'>✅ Participantes insertados: <strong>$participantsInserted</strong></p>";
    
    // 5. Sincronizar nombres entre contacts y chats
    echo "<h3>🔄 Sincronización Final de Nombres</h3>";
    
    // Actualizar chats desde contacts
    $stmt = $pdo->query("
        UPDATE chats c
        INNER JOIN contacts co ON c.remote_jid = co.remote_jid
        SET c.name = COALESCE(co.name, c.name)
        WHERE c.name IS NULL OR c.name = ''
    ");
    $updatedFromContacts = $stmt->rowCount();
    
    // Actualizar chats por teléfono
    $stmt = $pdo->query("
        UPDATE chats c
        INNER JOIN contacts co ON REPLACE(c.remote_jid, '@s.whatsapp.net', '') = co.phone_e164
        SET c.name = COALESCE(co.name, c.name)
        WHERE c.name IS NULL OR c.name = ''
    ");
    $updatedByPhone = $stmt->rowCount();
    
    echo "<p style='color: green;'>✅ Chats actualizados desde contacts: <strong>$updatedFromContacts</strong></p>";
    echo "<p style='color: green;'>✅ Chats actualizados por teléfono: <strong>$updatedByPhone</strong></p>";
    
    // 6. Mostrar resultados finales completos
    echo "<h3>📊 Resultados Finales COMPLETOS</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contacts");
    $finalContactsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats");
    $finalChatsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats WHERE is_group = 1");
    $groupsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats WHERE name IS NOT NULL AND name != ''");
    $chatsWithNames = $stmt->rowCount();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM group_participants");
    $participantsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Tipo</th><th>Total</th><th>Con Nombres</th><th>Status</th></tr>";
    echo "<tr><td>📇 Contacts</td><td>$finalContactsCount</td><td>-</td><td>" . ($finalContactsCount > 0 ? '✅' : '❌') . "</td></tr>";
    echo "<tr><td>💬 Chats</td><td>$finalChatsCount</td><td>$chatsWithNames</td><td>" . ($finalChatsCount > 0 ? '✅' : '❌') . "</td></tr>";
    echo "<tr><td>👥 Grupos</td><td>$groupsCount</td><td>-</td><td>" . ($groupsCount > 0 ? '✅' : '❌') . "</td></tr>";
    echo "<tr><td>👤 Participantes</td><td>$participantsCount</td><td>-</td><td>" . ($participantsCount > 0 ? '✅' : '❌') . "</td></tr>";
    echo "</table>";
    
    // 7. Mostrar muestras de datos
    echo "<h4>📇 Contactos (Top 20):</h4>";
    $stmt = $pdo->query("SELECT name, phone_e164, remote_jid FROM contacts ORDER BY name ASC LIMIT 20");
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
    
    echo "<h4>💬 Grupos (Top 10):</h4>";
    $stmt = $pdo->query("SELECT id, name, remote_jid FROM chats WHERE is_group = 1 AND name IS NOT NULL ORDER BY name ASC LIMIT 10");
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Remote JID</th></tr>";
    foreach ($groups as $group) {
        echo "<tr>";
        echo "<td>{$group['id']}</td>";
        echo "<td>" . htmlspecialchars($group['name']) . "</td>";
        echo "<td>{$group['remote_jid']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($participantsCount > 0) {
        echo "<h4>👤 Participantes (Top 10):</h4>";
        $stmt = $pdo->query("SELECT group_name, participant_name, participant_jid, is_admin FROM group_participants ORDER BY group_name, participant_name LIMIT 10");
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Grupo</th><th>Participante</th><th>JID</th><th>Admin</th></tr>";
        foreach ($participants as $participant) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($participant['group_name']) . "</td>";
            echo "<td>" . htmlspecialchars($participant['participant_name'] ?? 'SIN NOMBRE') . "</td>";
            echo "<td>{$participant['participant_jid']}</td>";
            echo "<td>" . ($participant['is_admin'] ? 'Sí' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>🎉 ¡Extracción COMPLETA Finalizada!</h3>";
    echo "<ul>";
    echo "<li>✅ Contactos totales: $finalContactsCount</li>";
    echo "<li>✅ Chats totales: $finalChatsCount</li>";
    echo "<li>✅ Grupos totales: $groupsCount</li>";
    echo "<li>✅ Participantes totales: $participantsCount</li>";
    echo "<li>✅ Chats con nombres: $chatsWithNames</li>";
    echo "<li>🔄 Refresca el catálogo para ver TODOS los cambios</li>";
    echo "</ul>";
    
    if ($finalContactsCount >= 10) {
        echo "<h3 style='color: green;'>🎉 ¡ÉXITO COMPLETO! Sistema con datos completos</h3>";
        echo "<p>El catálogo debería mostrar TODOS los contactos, chats y grupos ahora.</p>";
    } else {
        echo "<h3 style='color: blue;'>👍 ¡Buen Progreso! Datos extraídos correctamente</h3>";
        echo "<p>El sistema tiene los datos disponibles para mostrar.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>❌ Error de Conexión</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2>❌ Error General</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Función auxiliar para guardar como contacto
function saveAsContact($instanceId, $remoteJid, $name, $phone) {
    global $pdo;
    
    try {
        // Verificar si ya existe
        $stmt = $pdo->prepare("SELECT id FROM contacts WHERE remote_jid = ?");
        $stmt->execute([$remoteJid]);
        
        if ($stmt->rowCount() == 0) {
            // Insertar nuevo contacto
            $stmt = $pdo->prepare("
                INSERT INTO contacts (instance_id, phone_e164, name, push_name, remote_jid, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$instanceId, $phone, $name, $name, $remoteJid]);
        } else {
            // Actualizar si tiene nombre
            if (!empty($name)) {
                $stmt = $pdo->prepare("
                    UPDATE contacts SET name = ?, push_name = ?, updated_at = NOW() 
                    WHERE remote_jid = ? AND (name IS NULL OR name = '')
                ");
                $stmt->execute([$name, $name, $remoteJid]);
            }
        }
    } catch (Exception $e) {
        error_log("Error guardando contacto: " . $e->getMessage());
    }
}
?>
