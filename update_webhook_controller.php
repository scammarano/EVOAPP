<?php
// Script para verificar y actualizar el WebhookController
// Ejecutar: php update_webhook_controller.php

echo "<h2>🔄 Actualizar WebhookController</h2>";

// Ruta del archivo WebhookController
$webhookControllerPath = __DIR__ . '/app/Controllers/WebhookController.php';

if (!file_exists($webhookControllerPath)) {
    echo "<p style='color: red;'>❌ Archivo WebhookController.php no encontrado</p>";
    exit;
}

// Leer el archivo actual
$currentContent = file_get_contents($webhookControllerPath);

// Verificar si tiene los nuevos casos en el switch
$hasMessageReceived = strpos($currentContent, "case 'message_received':") !== false;
$hasMessageUpdate = strpos($currentContent, "case 'message_update':") !== false;
$hasContactUpdate = strpos($currentContent, "case 'contact_update':") !== false;
$hasChatUpdate = strpos($currentContent, "case 'chat_update':") !== false;
$hasPresenceUpdate = strpos($currentContent, "case 'presence_update':") !== false;

echo "<h3>📋 Estado Actual del WebhookController:</h3>";
echo "<ul>";
echo "<li>message_received: " . ($hasMessageReceived ? "✅" : "❌") . "</li>";
echo "<li>message_update: " . ($hasMessageUpdate ? "✅" : "❌") . "</li>";
echo "<li>contact_update: " . ($hasContactUpdate ? "✅" : "❌") . "</li>";
echo "<li>chat_update: " . ($hasChatUpdate ? "✅" : "❌") . "</li>";
echo "<li>presence_update: " . ($hasPresenceUpdate ? "✅" : "❌") . "</li>";
echo "</ul>";

if (!$hasMessageReceived || !$hasMessageUpdate || !$hasContactUpdate || !$hasChatUpdate || !$hasPresenceUpdate) {
    echo "<h3 style='color: orange;'>⚠️ WebhookController necesita actualización</h3>";
    
    // Contenido actualizado del switch
    $newSwitch = <<<PHP
            switch (\$eventType) {
                case 'messages.upsert':
                    \$this->processMessageUpsert(\$event, \$payload);
                    break;
                    
                case 'messages.update':
                    \$this->processMessageUpdate(\$event, \$payload);
                    break;
                    
                case 'messages.delete':
                    \$this->processMessageDelete(\$event, \$payload);
                    break;
                    
                case 'message_received':
                    // Evento mapeado para mensajes recibidos
                    \$this->processMessageUpsert(\$event, \$payload);
                    break;
                    
                case 'message_update':
                    // Evento mapeado para actualizaciones de mensajes
                    \$this->processMessageUpdate(\$event, \$payload);
                    break;
                    
                case 'contact_update':
                    // Evento de actualización de contactos - solo registrar
                    error_log("Contact update event processed: " . json_encode(\$payload));
                    break;
                    
                case 'chat_update':
                    // Evento de actualización de chats - solo registrar
                    error_log("Chat update event processed: " . json_encode(\$payload));
                    break;
                    
                case 'presence_update':
                    // Evento de presencia - solo registrar
                    error_log("Presence update event processed: " . json_encode(\$payload));
                    break;
                    
                default:
                    // Log unknown event type
                    error_log("Unknown webhook event type: \$eventType");
                    break;
            }
PHP;
    
    // Reemplazar el switch antiguo
    $pattern = '/switch\s*\(\s*\$eventType\s*\)\s*\{[^}]*\}/s';
    
    if (preg_match($pattern, $currentContent)) {
        $updatedContent = preg_replace($pattern, $newSwitch, $currentContent);
        
        // Hacer backup del archivo original
        $backupPath = $webhookControllerPath . '.backup.' . date('Y-m-d_H-i-s');
        copy($webhookControllerPath, $backupPath);
        echo "<p style='color: blue;'>ℹ️ Backup creado: " . basename($backupPath) . "</p>";
        
        // Escribir el archivo actualizado
        if (file_put_contents($webhookControllerPath, $updatedContent)) {
            echo "<p style='color: green;'>✅ WebhookController.php actualizado exitosamente</p>";
            
            // Verificar la actualización
            $updatedContent = file_get_contents($webhookControllerPath);
            $hasMessageReceived = strpos($updatedContent, "case 'message_received':") !== false;
            $hasMessageUpdate = strpos($updatedContent, "case 'message_update':") !== false;
            $hasContactUpdate = strpos($updatedContent, "case 'contact_update':") !== false;
            $hasChatUpdate = strpos($updatedContent, "case 'chat_update':") !== false;
            $hasPresenceUpdate = strpos($updatedContent, "case 'presence_update':") !== false;
            
            echo "<h3>📋 Estado Después de Actualizar:</h3>";
            echo "<ul>";
            echo "<li>message_received: " . ($hasMessageReceived ? "✅" : "❌") . "</li>";
            echo "<li>message_update: " . ($hasMessageUpdate ? "✅" : "❌") . "</li>";
            echo "<li>contact_update: " . ($hasContactUpdate ? "✅" : "❌") . "</li>";
            echo "<li>chat_update: " . ($hasChatUpdate ? "✅" : "❌") . "</li>";
            echo "<li>presence_update: " . ($hasPresenceUpdate ? "✅" : "❌") . "</li>";
            echo "</ul>";
            
            echo "<h3>🚀 Próximos Pasos:</h3>";
            echo "<ol>";
            echo "<li>Sube el archivo WebhookController.php actualizado al servidor</li>";
            echo "<li>Refresca System Logs para verificar los cambios</li>";
            echo "<li>Los nuevos webhooks deberían procesarse correctamente</li>";
            echo "<li>Verifica que los mensajes aparezcan en el inbox</li>";
            echo "</ol>";
            
        } else {
            echo "<p style='color: red;'>❌ Error al actualizar el archivo</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No se encontró el switch para reemplazar</p>";
    }
} else {
    echo "<p style='color: green;'>✅ WebhookController.php ya está actualizado</p>";
    echo "<p>El archivo ya contiene todos los casos necesarios para procesar los eventos.</p>";
}

// Mostrar eventos recientes que necesitan procesamiento
echo "<h3>📊 Eventos que Necesitan Procesamiento:</h3>";
echo "<ul>";
echo "<li><strong>contact_update</strong> - Actualizaciones de contactos</li>";
echo "<li><strong>message_received</strong> - Mensajes recibidos</li>";
echo "<li><strong>chat_update</strong> - Actualizaciones de chats</li>";
echo "<li><strong>presence_update</strong> - Cambios de presencia</li>";
echo "<li><strong>message_update</strong> - Actualizaciones de mensajes</li>";
echo "<li><strong>send.message</strong> - Mensajes enviados</li>";
echo "<li><strong>contacts.upsert</strong> - Nuevos contactos</li>";
echo "</ul>";

echo "<h3>📋 Archivos que Necesitan Subirse al Servidor:</h3>";
echo "<ol>";
echo "<li><strong>app/Controllers/WebhookController.php</strong> - Con los nuevos casos</li>";
echo "<li><strong>app/Core/MessageSender.php</strong> - Con propiedades públicas</li>";
echo "<li><strong>app/Models/Instance.php</strong> - Con método getById</li>";
echo "</ol>";
?>
