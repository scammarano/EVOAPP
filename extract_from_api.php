<?php
// Script para extraer datos directamente desde la API Evolution
// Ejecutar: https://camcam.com.ve/evoappws/extract_from_api.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>📡 Extracción Directa desde API Evolution</h2>";
    
    // Obtener instancia activa
    $stmt = $pdo->query("SELECT * FROM instances WHERE status = 'connected' LIMIT 1");
    $instance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$instance) {
        echo "<p style='color: red;'>❌ No hay instancias conectadas</p>";
        return;
    }
    
    echo "<h3>📱 Instancia: {$instance['name']} ({$instance['slug']})</h3>";
    
    // 1. Extraer todos los chats desde la API
    echo "<h3>💬 Extrayendo Chats desde API</h3>";
    
    $apiUrl = $instance['url_api'] . '/chats';
    $token = $instance['token'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "<p style='color: red;'>❌ Error en API: HTTP $httpCode</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        return;
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['data'])) {
        echo "<p style='color: red;'>❌ Respuesta inválida de API</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        return;
    }
    
    $chats = $data['data'];
    $chatsInserted = 0;
    $chatsUpdated = 0;
    $groupsFound = 0;
    
    echo "<p>📊 Procesando " . count($chats) . " chats desde API...</p>";
    
    foreach ($chats as $chat) {
        $chatId = $chat['id'] ?? '';
        $chatName = $chat['name'] ?? '';
        $remoteJid = $chat['remoteJid'] ?? '';
        $isGroup = strpos($chatId, '@g.us') !== false;
        
        if ($isGroup) {
            $groupsFound++;
        }
        
        // Validar que tenga datos básicos
        if (empty($chatId) || empty($remoteJid)) {
            continue;
        }
        
        // Verificar si ya existe
        $stmt = $pdo->prepare("SELECT id, name FROM chats WHERE id = ?");
        $stmt->execute([$chatId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO chats (id, instance_id, name, remote_jid, is_group, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $chatId,
                    $instance['id'],
                    $chatName,
                    $remoteJid,
                    $isGroup ? 1 : 0
                ]);
                $chatsInserted++;
            } catch (PDOException $e) {
                // Si hay duplicado, actualizar
                $stmt = $pdo->prepare("
                    UPDATE chats SET name = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$chatName, $chatId]);
                $chatsUpdated++;
            }
        } else {
            // Actualizar si hay nombre nuevo
            if (!empty($chatName) && $existing['name'] !== $chatName) {
                $stmt = $pdo->prepare("
                    UPDATE chats SET name = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$chatName, $chatId]);
                $chatsUpdated++;
            }
        }
    }
    
    echo "<p style='color: green;'>✅ Chats insertados: <strong>$chatsInserted</strong></p>";
    echo "<p style='color: blue;'>✅ Chats actualizados: <strong>$chatsUpdated</strong></p>";
    echo "<p style='color: orange;'>👥 Grupos encontrados: <strong>$groupsFound</strong></p>";
    
    // 2. Extraer participantes de grupos
    echo "<h3>👥 Extrayendo Participantes de Grupos</h3>";
    
    $participantsInserted = 0;
    $groupsProcessed = 0;
    
    // Obtener solo los grupos
    $stmt = $pdo->prepare("SELECT id, name FROM chats WHERE is_group = 1 AND instance_id = ?");
    $stmt->execute([$instance['id']]);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($groups as $group) {
        $groupId = $group['id'];
        $groupName = $group['name'];
        
        echo "<p>📊 Procesando grupo: {$groupName} ($groupId)</p>";
        
        $apiUrl = $instance['url_api'] . "/group/{$groupId}/participants";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            echo "<p style='color: orange;'>⚠️ Error obteniendo participantes de $groupId: HTTP $httpCode</p>";
            continue;
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['data'])) {
            echo "<p style='color: orange;'>⚠️ Sin datos de participantes para $groupId</p>";
            continue;
        }
        
        $participants = $data['data'];
        
        foreach ($participants as $participant) {
            $participantJid = $participant['id'] ?? '';
            $participantName = $participant['pushName'] ?? $participant['name'] ?? '';
            $isAdmin = $participant['isAdmin'] ?? ($participant['type'] === 'admin' ? 1 : 0);
            
            if (empty($participantJid)) {
                continue;
            }
            
            // Extraer teléfono
            $participantPhone = str_replace(['@s.whatsapp.net', '@g.us', '@lid'], '', $participantJid);
            
            // Verificar si ya existe
            $stmt = $pdo->prepare("SELECT id FROM group_participants WHERE group_id = ? AND participant_jid = ?");
            $stmt->execute([$groupId, $participantJid]);
            
            if ($stmt->rowCount() == 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO group_participants (group_id, group_name, participant_jid, participant_name, participant_phone, is_admin, instance_id, joined_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $groupId,
                    $groupName,
                    $participantJid,
                    $participantName,
                    $participantPhone,
                    $isAdmin,
                    $instance['id']
                ]);
                $participantsInserted++;
                
                // Guardar como contacto si tiene nombre
                if (!empty($participantName)) {
                    saveAsContact($instance['id'], $participantJid, $participantName, $participantPhone);
                }
            }
        }
        
        $groupsProcessed++;
        
        // Pequeña pausa para no sobrecargar la API
        usleep(500000); // 0.5 segundos
    }
    
    echo "<p style='color: green;'>✅ Grupos procesados: <strong>$groupsProcessed</strong></p>";
    echo "<p style='color: green;'>✅ Participantes insertados: <strong>$participantsInserted</strong></p>";
    
    // 3. Sincronización final
    echo "<h3>🔄 Sincronización Final</h3>";
    
    // Actualizar chats desde contacts
    $stmt = $pdo->query("
        UPDATE chats c
        INNER JOIN contacts co ON c.remote_jid = co.remote_jid
        SET c.name = COALESCE(co.name, c.name)
        WHERE c.name IS NULL OR c.name = ''
    ");
    $updatedFromContacts = $stmt->rowCount();
    
    echo "<p style='color: green;'>✅ Chats actualizados desde contacts: <strong>$updatedFromContacts</strong></p>";
    
    // 4. Resultados finales
    echo "<h3>📊 Resultados Finales</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contacts");
    $finalContactsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats");
    $finalChatsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats WHERE is_group = 1");
    $finalGroupsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats WHERE name IS NOT NULL AND name != ''");
    $finalChatsWithNames = $stmt->rowCount();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM group_participants");
    $finalParticipantsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Tipo</th><th>Total</th><th>Con Nombres</th><th>Status</th></tr>";
    echo "<tr><td>📇 Contacts</td><td>$finalContactsCount</td><td>-</td><td>✅</td></tr>";
    echo "<tr><td>💬 Chats</td><td>$finalChatsCount</td><td>$finalChatsWithNames</td><td>✅</td></tr>";
    echo "<tr><td>👥 Grupos</td><td>$finalGroupsCount</td><td>-</td><td>✅</td></tr>";
    echo "<tr><td>👤 Participantes</td><td>$finalParticipantsCount</td><td>-</td><td>✅</td></tr>";
    echo "</table>";
    
    echo "<h3 style='color: green;'>🎉 ¡Extracción desde API Completada!</h3>";
    echo "<p>El sistema ahora tiene datos actualizados directamente desde la API de Evolution.</p>";
    echo "<p>🔄 Refresca el catálogo para ver los cambios.</p>";
    
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
