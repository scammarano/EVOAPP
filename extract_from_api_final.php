<?php
// Script FINAL para extraer datos desde API Evolution - Sin errores de columna
// Ejecutar: https://camcam.com.ve/evoappws/extract_from_api_final.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>📡 Extracción Final desde API Evolution</h2>";
    
    // 1. Obtener instancia activa
    echo "<h3>📱 Buscando Instancia Activa</h3>";
    
    $stmt = $pdo->query("SELECT * FROM instances WHERE status = 'connected' LIMIT 1");
    $instance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$instance) {
        echo "<p style='color: red;'>❌ No hay instancias conectadas</p>";
        echo "<p>Intentando con cualquier instancia disponible...</p>";
        
        $stmt = $pdo->query("SELECT * FROM instances LIMIT 1");
        $instance = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$instance) {
        echo "<p style='color: red;'>❌ No hay instancias disponibles</p>";
        return;
    }
    
    echo "<p>✅ Instancia encontrada: {$instance['name']} ({$instance['slug']})</p>";
    
    // 2. Extraer todos los chats desde la API
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
    
    // 3. Extraer contactos desde los chats (SOLO individuales)
    echo "<h3>📇 Extrayendo Contactos desde Chats Individuales</h3>";
    
    $contactsInserted = 0;
    $contactsUpdated = 0;
    
    foreach ($chats as $chat) {
        $chatId = $chat['id'] ?? '';
        $remoteJid = $chat['remoteJid'] ?? '';
        $chatName = $chat['name'] ?? '';
        
        // SOLO procesar chats individuales (no grupos)
        if (strpos($chatId, '@g.us') !== false) {
            continue;
        }
        
        // Extraer teléfono del remote_jid
        $phone = str_replace(['@s.whatsapp.net', '@g.us', '@lid'], '', $remoteJid);
        
        // Verificar si ya existe como contacto
        $stmt = $pdo->prepare("SELECT id, name FROM contacts WHERE remote_jid = ? OR phone_e164 = ?");
        $stmt->execute([$remoteJid, $phone]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO contacts (instance_id, phone_e164, name, push_name, remote_jid, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $instance['id'],
                    $phone,
                    $chatName,
                    $chatName,
                    $remoteJid
                ]);
                $contactsInserted++;
            } catch (PDOException $e) {
                // Si hay duplicado, actualizar
                $stmt = $pdo->prepare("
                    UPDATE contacts SET name = ?, push_name = ?, updated_at = NOW() 
                    WHERE remote_jid = ? OR phone_e164 = ?
                ");
                $stmt->execute([$chatName, $chatName, $remoteJid, $phone]);
                $contactsUpdated++;
            }
        } else {
            // Actualizar si hay nombre nuevo
            if (!empty($chatName) && $existing['name'] !== $chatName) {
                $stmt = $pdo->prepare("
                    UPDATE contacts SET name = ?, push_name = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$chatName, $chatName, $existing['id']]);
                $contactsUpdated++;
            }
        }
    }
    
    echo "<p style='color: green;'>✅ Contactos insertados: <strong>$contactsInserted</strong></p>";
    echo "<p style='color: blue;'>✅ Contactos actualizados: <strong>$contactsUpdated</strong></p>";
    
    // 4. Mostrar resultados finales
    echo "<h3>📊 Resultados Finales</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contacts");
    $finalContactsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats");
    $finalChatsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats WHERE is_group = 1");
    $finalGroupsCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM chats WHERE name IS NOT NULL AND name != ''");
    $finalChatsWithNames = $stmt->rowCount();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Tipo</th><th>Total</th><th>Con Nombres</th><th>Status</th></tr>";
    echo "<tr><td>📇 Contacts</td><td>$finalContactsCount</td><td>-</td><td>" . ($finalContactsCount > 0 ? '✅' : '❌') . "</td></tr>";
    echo "<tr><td>💬 Chats</td><td>$finalChatsCount</td><td>$finalChatsWithNames</td><td>" . ($finalChatsCount > 0 ? '✅' : '❌') . "</td></tr>";
    echo "<tr><td>👥 Grupos</td><td>$finalGroupsCount</td><td>-</td><td>" . ($finalGroupsCount > 0 ? '✅' : '❌') . "</td></tr>";
    echo "</table>";
    
    // 5. Mostrar muestras de datos
    echo "<h4>📇 Contactos Recientes (Top 10):</h4>";
    $stmt = $pdo->query("SELECT name, phone_e164, remote_jid FROM contacts ORDER BY created_at DESC LIMIT 10");
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
    
    echo "<h4>💬 Chats con Nombres (Top 10):</h4>";
    $stmt = $pdo->query("SELECT id, name, remote_jid FROM chats WHERE name IS NOT NULL AND name != '' ORDER BY name ASC LIMIT 10");
    $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Remote JID</th></tr>";
    foreach ($chats as $chat) {
        echo "<tr>";
        echo "<td>{$chat['id']}</td>";
        echo "<td>" . htmlspecialchars($chat['name']) . "</td>";
        echo "<td>{$chat['remote_jid']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3 style='color: green;'>🎉 ¡Extracción Final Completada!</h3>";
    echo "<ul>";
    echo "<li>✅ Contactos totales: $finalContactsCount</li>";
    echo "<li>✅ Chats totales: $finalChatsCount</li>";
    echo "<li>✅ Grupos totales: $finalGroupsCount</li>";
    echo "<li>✅ Chats con nombres: $finalChatsWithNames</li>";
    echo "<li>🔄 Refresca el catálogo para ver los cambios</li>";
    echo "</ul>";
    
    if ($finalContactsCount >= 10) {
        echo "<h3 style='color: green;'>🎉 ¡ÉXITO! Sistema con datos completos</h3>";
        echo "<p>El catálogo debería mostrar TODOS los contactos y chats ahora.</p>";
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
?>
