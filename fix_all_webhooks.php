<?php
// Script para corregir todas las instancias automáticamente
// Ejecutar: php fix_all_webhooks.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔧 Corregir Todas las Instancias (Webhooks)</h2>";
    
    // Obtener todas las instancias
    $stmt = $pdo->query("SELECT * FROM evo_instances ORDER BY name");
    $instances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($instances)) {
        echo "<h3 style='color: red;'>❌ No se encontraron instancias</h3>";
        return;
    }
    
    echo "<h3>📊 Procesando " . count($instances) . " instancias...</h3>";
    
    $fixedCount = 0;
    $errorCount = 0;
    
    foreach ($instances as $instance) {
        $slug = $instance['slug'];
        $name = $instance['name'];
        
        echo "<h4>🔧 Procesando: $name ($slug)</h4>";
        
        try {
            $corrections = [];
            
            // 1. Corregir Webhook URL
            if (empty($instance['webhook_url']) || $instance['webhook_url'] === 'NULL') {
                $suggestedWebhookUrl = 'https://camcam.com.ve/evoappws/webhook/' . $slug;
                $stmt = $pdo->prepare("UPDATE evo_instances SET webhook_url = ? WHERE slug = ?");
                $stmt->execute([$suggestedWebhookUrl, $slug]);
                $corrections[] = "Webhook URL configurada: $suggestedWebhookUrl";
            }
            
            // 2. Generar Webhook Token
            if (empty($instance['webhook_token']) || $instance['webhook_token'] === 'NULL') {
                $suggestedToken = 'webhook_' . bin2hex(random_bytes(16));
                $stmt = $pdo->prepare("UPDATE evo_instances SET webhook_token = ? WHERE slug = ?");
                $stmt->execute([$suggestedToken, $slug]);
                $corrections[] = "Webhook Token generado: $suggestedToken";
            }
            
            // 3. Habilitar reenvío de webhooks
            if (empty($instance['forward_webhook_enabled']) || $instance['forward_webhook_enabled'] === 'NULL') {
                $stmt = $pdo->prepare("UPDATE evo_instances SET forward_webhook_enabled = 0 WHERE slug = ?");
                $stmt->execute([$slug]);
                $corrections[] = "Forward webhook deshabilitado (por defecto)";
            }
            
            if (!empty($corrections)) {
                echo "<p style='color: green;'>✅ Correcciones aplicadas:</p>";
                echo "<ul>";
                foreach ($corrections as $correction) {
                    echo "<li>$correction</li>";
                }
                echo "</ul>";
                $fixedCount++;
            } else {
                echo "<p style='color: blue;'>ℹ️ No se necesitaron correcciones</p>";
            }
            
            // Mostrar configuración final
            echo "<h5>📋 Configuración Final:</h5>";
            echo "<ul>";
            echo "<li><strong>Base URL:</strong> " . htmlspecialchars($instance['base_url']) . "</li>";
            echo "<li><strong>Webhook URL:</strong> " . htmlspecialchars($instance['webhook_url'] ?? 'NULL') . "</li>";
            echo "<li><strong>Webhook Token:</strong> " . htmlspecialchars($instance['webhook_token'] ?? 'NULL') . "</li>";
            echo "<li><strong>Estado:</strong> " . ($instance['is_active'] ? 'Activa' : 'Inactiva') . "</li>";
            echo "</ul>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
            $errorCount++;
        }
        
        echo "<hr>";
    }
    
    echo "<h3>📈 Resumen:</h3>";
    echo "<p style='color: green;'>✅ Instancias corregidas: $fixedCount</p>";
    echo "<p style='color: red;'>❌ Errores: $errorCount</p>";
    
    echo "<h3>🚀 Próximos Pasos:</h3>";
    echo "<ol>";
    echo "<li>Configurar los webhooks en Evolution API con las URLs generadas</li>";
    echo "<li>Usar los tokens generados para seguridad</li>";
    echo "<li>Probar el envío de mensajes desde el diagnostic</li>";
    echo "<li>Verificar que los webhooks se reciban correctamente</li>";
    echo "</ol>";
    
    echo "<h3>📋 URLs de Webhook Generadas:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Instancia</th><th>Webhook URL</th><th>Token</th></tr>";
    
    $stmt = $pdo->query("SELECT name, slug, webhook_url, webhook_token FROM evo_instances ORDER BY name");
    $instances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($instances as $instance) {
        echo "<tr>";
        echo "<td><strong>{$instance['name']}</strong></td>";
        echo "<td>" . htmlspecialchars($instance['webhook_url'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($instance['webhook_token'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error de Base de Datos</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
