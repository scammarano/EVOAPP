<?php
// Script para procesar webhooks pendientes
// Ejecutar: php process_pending_webhooks.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔄 Procesar Webhooks Pendientes</h2>";
    
    // Contar webhooks pendientes
    $stmt = $pdo->query("SELECT COUNT(*) FROM webhook_events WHERE status = 'pending'");
    $pendingCount = $stmt->fetchColumn();
    
    echo "<h3>📊 Estadísticas Actuales:</h3>";
    echo "<p>🔄 Webhooks pendientes: <strong>$pendingCount</strong></p>";
    
    // Obtener webhooks pendientes para procesar
    $stmt = $pdo->query("
        SELECT we.*, ei.slug as instance_slug 
        FROM webhook_events we
        LEFT JOIN evo_instances ei ON we.instance_id = ei.id
        WHERE we.status = 'pending'
        ORDER BY we.created_at ASC
        LIMIT 100
    ");
    $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($webhooks)) {
        echo "<p style='color: green;'>✅ No hay webhooks pendientes</p>";
        return;
    }
    
    echo "<h3>🔄 Procesando " . count($webhooks) . " webhooks...</h3>";
    
    $processed = 0;
    $errors = 0;
    
    foreach ($webhooks as $webhook) {
        $eventId = $webhook['id'];
        $eventType = $webhook['event_type'];
        $instanceSlug = $webhook['instance_slug'];
        
        echo "<h4>🔄 Procesando Evento #$eventId: $eventType</h4>";
        
        try {
            // Mapear eventos a tipos conocidos
            $knownEvents = [
                'contacts.update' => 'contact_update',
                'chats.upsert' => 'chat_update',
                'chats.update' => 'chat_update',
                'presence.update' => 'presence_update',
                'messages.upsert' => 'message_received',
                'messages.update' => 'message_update',
                'send.message' => 'message_sent',
                'statusMessage' => 'status_update'
            ];
            
            $mappedType = $knownEvents[$eventType] ?? $eventType;
            
            // Actualizar tipo de evento
            $stmt = $pdo->prepare("UPDATE webhook_events SET event_type = ? WHERE id = ?");
            $stmt->execute([$mappedType, $eventId]);
            
            // Marcar como procesado si es un evento que no necesita procesamiento adicional
            if (in_array($mappedType, ['contact_update', 'chat_update', 'presence_update'])) {
                $stmt = $pdo->prepare("UPDATE webhook_events SET status = 'processed', processed_at = NOW() WHERE id = ?");
                $stmt->execute([$eventId]);
                echo "<p style='color: green;'>✅ Evento $mappedType procesado (registro simple)</p>";
            } else {
                // Para mensajes, mantener como pending para procesamiento normal
                echo "<p style='color: blue;'>ℹ️ Evento $mappedType mantenido como pending (requiere procesamiento)</p>";
            }
            
            $processed++;
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error procesando evento #$eventId: " . $e->getMessage() . "</p>";
            
            // Marcar como error
            $stmt = $pdo->prepare("UPDATE webhook_events SET status = 'error', processed_at = NOW(), error_message = ? WHERE id = ?");
            $stmt->execute([$e->getMessage(), $eventId]);
            
            $errors++;
        }
        
        echo "<hr>";
    }
    
    echo "<h3>📈 Resumen:</h3>";
    echo "<p style='color: green;'>✅ Procesados: $processed</p>";
    echo "<p style='color: red;'>❌ Errores: $errors</p>";
    
    // Mostrar estadísticas actualizadas
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM webhook_events GROUP BY status");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📊 Estadísticas Actualizadas:</h3>";
    foreach ($stats as $stat) {
        $status = strtoupper($stat['status']);
        $count = $stat['count'];
        $color = $status === 'PENDING' ? 'orange' : ($status === 'PROCESSED' ? 'green' : 'red');
        echo "<p style='color: $color;'>$status: $count</p>";
    }
    
    echo "<h3>🚀 Próximos Pasos:</h3>";
    echo "<ol>";
    echo "<li>Refrescar System Logs para ver los cambios</li>";
    echo "<li>Verificar que los mensajes aparezcan en el inbox</li>";
    echo "<li>Probar el envío de mensajes desde diagnostic</li>";
    echo "<li>Monitorear nuevos webhooks que lleguen</li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error de Base de Datos</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
