<?php
// Script para diagnosticar payloads de webhooks
// Ejecutar: https://camcam.com.ve/evoappws/debug_webhook_payloads.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔍 Diagnóstico de Webhook Payloads</h2>";
    
    // 1. Analizar payloads de contactos
    echo "<h3>📇 Análisis de Payloads de Contactos</h3>";
    
    $stmt = $pdo->query("
        SELECT payload_json, created_at
        FROM webhook_events 
        WHERE event_type IN ('contacts.update', 'contact_update')
        AND status = 'processed'
        ORDER BY created_at DESC
        LIMIT 5
    ");
    
    $contactEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>#</th><th>Fecha</th><th>Tiene pushName</th><th>Tiene remoteJid</th><th>Ejemplo Payload</th></tr>";
    
    foreach ($contactEvents as $index => $event) {
        $payload = json_decode($event['payload_json'], true);
        $hasPushName = false;
        $hasRemoteJid = false;
        $example = '';
        
        if (isset($payload['data'])) {
            $data = $payload['data'];
            $contactsList = is_array($data) ? $data : [$data];
            
            foreach ($contactsList as $contact) {
                if (isset($contact['pushName']) && !empty($contact['pushName'])) {
                    $hasPushName = true;
                    $example = $contact['pushName'];
                }
                if (isset($contact['remoteJid'])) {
                    $hasRemoteJid = true;
                }
                if ($hasPushName && $hasRemoteJid) break;
            }
        }
        
        echo "<tr>";
        echo "<td>" . ($index + 1) . "</td>";
        echo "<td>{$event['created_at']}</td>";
        echo "<td>" . ($hasPushName ? '✅' : '❌') . "</td>";
        echo "<td>" . ($hasRemoteJid ? '✅' : '❌') . "</td>";
        echo "<td>" . htmlspecialchars($example) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Analizar payloads de chats
    echo "<h3>💬 Análisis de Payloads de Chats</h3>";
    
    $stmt = $pdo->query("
        SELECT payload_json, created_at
        FROM webhook_events 
        WHERE event_type IN ('chats.upsert', 'chat_update')
        AND status = 'processed'
        ORDER BY created_at DESC
        LIMIT 5
    ");
    
    $chatEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>#</th><th>Fecha</th><th>Tiene name</th><th>Tiene remoteJid</th><th>Ejemplo Payload</th></tr>";
    
    foreach ($chatEvents as $index => $event) {
        $payload = json_decode($event['payload_json'], true);
        $hasName = false;
        $hasRemoteJid = false;
        $example = '';
        
        if (isset($payload['data'])) {
            $data = $payload['data'];
            $chatsList = is_array($data) ? $data : [$data];
            
            foreach ($chatsList as $chat) {
                if (isset($chat['name']) && !empty($chat['name'])) {
                    $hasName = true;
                    $example = $chat['name'];
                }
                if (isset($chat['remoteJid'])) {
                    $hasRemoteJid = true;
                }
                if ($hasName && $hasRemoteJid) break;
            }
        }
        
        echo "<tr>";
        echo "<td>" . ($index + 1) . "</td>";
        echo "<td>{$event['created_at']}</td>";
        echo "<td>" . ($hasName ? '✅' : '❌') . "</td>";
        echo "<td>" . ($hasRemoteJid ? '✅' : '❌') . "</td>";
        echo "<td>" . htmlspecialchars($example) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Mostrar ejemplos completos de payloads
    echo "<h3>📋 Ejemplos Completos de Payloads</h3>";
    
    // Ejemplo de contacto
    $stmt = $pdo->query("
        SELECT payload_json
        FROM webhook_events 
        WHERE event_type IN ('contacts.update', 'contact_update')
        AND status = 'processed'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $contactEvent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contactEvent) {
        echo "<h4>📇 Ejemplo de Contact Payload:</h4>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
        echo htmlspecialchars(json_encode(json_decode($contactEvent['payload_json']), JSON_PRETTY_PRINT));
        echo "</pre>";
    }
    
    // Ejemplo de chat
    $stmt = $pdo->query("
        SELECT payload_json
        FROM webhook_events 
        WHERE event_type IN ('chats.upsert', 'chat_update')
        AND status = 'processed'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $chatEvent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($chatEvent) {
        echo "<h4>💬 Ejemplo de Chat Payload:</h4>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
        echo htmlspecialchars(json_encode(json_decode($chatEvent['payload_json']), JSON_PRETTY_PRINT));
        echo "</pre>";
    }
    
    // 4. Verificar relación entre chats y contacts
    echo "<h3>🔍 Relación Chats-Contacts</h3>";
    
    $stmt = $pdo->query("
        SELECT 
            c.id as chat_id,
            c.name as chat_name,
            c.remote_jid as chat_remote_jid,
            co.name as contact_name,
            co.remote_jid as contact_remote_jid
        FROM chats c
        LEFT JOIN contacts co ON c.remote_jid = co.remote_jid
        LIMIT 10
    ");
    
    $relations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Chat ID</th><th>Chat Name</th><th>Chat Remote JID</th><th>Contact Name</th><th>Contact Remote JID</th><th>Match</th></tr>";
    
    foreach ($relations as $rel) {
        $match = ($rel['chat_remote_jid'] === $rel['contact_remote_jid']) ? '✅' : '❌';
        echo "<tr>";
        echo "<td>{$rel['chat_id']}</td>";
        echo "<td>" . htmlspecialchars($rel['chat_name'] ?? 'NULL') . "</td>";
        echo "<td>{$rel['chat_remote_jid']}</td>";
        echo "<td>" . htmlspecialchars($rel['contact_name'] ?? 'NULL') . "</td>";
        echo "<td>{$rel['contact_remote_jid'] ?? 'NULL'}</td>";
        echo "<td>$match</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 5. Estadísticas de campos faltantes
    echo "<h3>📊 Estadísticas de Campos Faltantes</h3>";
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_events,
            SUM(CASE WHEN payload_json LIKE '%pushName%' THEN 1 ELSE 0 END) as with_pushname,
            SUM(CASE WHEN payload_json LIKE '%remoteJid%' THEN 1 ELSE 0 END) as with_remotejid,
            SUM(CASE WHEN payload_json LIKE '%name%' THEN 1 ELSE 0 END) as with_name
        FROM webhook_events 
        WHERE event_type IN ('contacts.update', 'contact_update')
        AND status = 'processed'
    ");
    
    $contactStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_events,
            SUM(CASE WHEN payload_json LIKE '%name%' THEN 1 ELSE 0 END) as with_name,
            SUM(CASE WHEN payload_json LIKE '%remoteJid%' THEN 1 ELSE 0 END) as with_remotejid
        FROM webhook_events 
        WHERE event_type IN ('chats.upsert', 'chat_update')
        AND status = 'processed'
    ");
    
    $chatStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Tipo</th><th>Total Events</th><th>Con pushName/name</th><th>Con remoteJid</th><th>Completos</th></tr>";
    echo "<tr><td>Contacts</td><td>{$contactStats['total_events']}</td><td>{$contactStats['with_pushname']}</td><td>{$contactStats['with_remotejid']}</td><td>" . min($contactStats['with_pushname'], $contactStats['with_remotejid']) . "</td></tr>";
    echo "<tr><td>Chats</td><td>{$chatStats['total_events']}</td><td>{$chatStats['with_name']}</td><td>{$chatStats['with_remotejid']}</td><td>" . min($chatStats['with_name'], $chatStats['with_remotejid']) . "</td></tr>";
    echo "</table>";
    
    echo "<h3>🎯 Diagnóstico Completado</h3>";
    echo "<p>Basado en los resultados, el problema puede ser:</p>";
    echo "<ul>";
    echo "<li>📇 Los payloads de contactos no tienen pushName</li>";
    echo "<li>💬 Los remoteJid no coinciden entre chats y contacts</li>";
    echo "<li>🔄 Los nombres están en diferentes campos</li>";
    echo "<li>📊 Se necesita procesar más webhooks</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error de Conexión</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2>❌ Error General</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
