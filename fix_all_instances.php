<?php
// Script para corregir todas las instancias automáticamente
// Ejecutar: php fix_all_instances.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔧 Corregir Todas las Instancias</h2>";
    
    // Verificar si existe la columna name
    $columnCheck = $pdo->query("SHOW COLUMNS FROM evo_instances LIKE 'name'");
    $hasNameColumn = $columnCheck->rowCount() > 0;
    
    // Obtener todas las instancias
    if ($hasNameColumn) {
        $stmt = $pdo->query("SELECT * FROM evo_instances ORDER BY name");
    } else {
        $stmt = $pdo->query("SELECT * FROM evo_instances ORDER BY id");
    }
    $instances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($instances)) {
        echo "<h3 style='color: red;'>❌ No se encontraron instancias</h3>";
        return;
    }
    
    echo "<h3>📊 Procesando " . count($instances) . " instancias...</h3>";
    
    $fixedCount = 0;
    $errorCount = 0;
    
    foreach ($instances as $instance) {
        $slug = $instance['slug'] ?? 'N/A';
        $name = $instance['name'] ?? $slug;
        
        echo "<h4>🔧 Procesando: $name ($slug)</h4>";
        
        try {
            $corrections = [];
            
            // 1. Corregir Base URL
            if (empty($instance['base_url']) || $instance['base_url'] === 'NULL') {
                $suggestedBaseUrl = 'https://evolutionapi-evolution-api.xs639b.easypanel.host';
                $stmt = $pdo->prepare("UPDATE evo_instances SET base_url = ? WHERE slug = ?");
                $stmt->execute([$suggestedBaseUrl, $slug]);
                $corrections[] = "Base URL configurada";
            }
            
            // 2. Corregir Webhook URL
            if (empty($instance['webhook_url']) || $instance['webhook_url'] === 'NULL') {
                $suggestedWebhookUrl = 'https://camcam.com.ve/evoappws/webhook/' . $slug;
                $stmt = $pdo->prepare("UPDATE evo_instances SET webhook_url = ? WHERE slug = ?");
                $stmt->execute([$suggestedWebhookUrl, $slug]);
                $corrections[] = "Webhook URL configurada";
            }
            
            // 3. Generar Webhook Token
            if (empty($instance['webhook_token']) || $instance['webhook_token'] === 'NULL') {
                $suggestedToken = 'webhook_' . bin2hex(random_bytes(16));
                $stmt = $pdo->prepare("UPDATE evo_instances SET webhook_token = ? WHERE slug = ?");
                $stmt->execute([$suggestedToken, $slug]);
                $corrections[] = "Webhook Token generado";
            }
            
            if (!empty($corrections)) {
                echo "<p style='color: green;'>✅ Correcciones aplicadas: " . implode(', ', $corrections) . "</p>";
                $fixedCount++;
            } else {
                echo "<p style='color: blue;'>ℹ️ No se necesitaron correcciones</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
            $errorCount++;
        }
        
        echo "<hr>";
    }
    
    echo "<h3>📈 Resumen:</h3>";
    echo "<p style='color: green;'>✅ Instancias corregidas: $fixedCount</p>";
    echo "<p style='color: red;'>❌ Errores: $errorCount</p>";
    
    // Verificar estado final
    echo "<h3>🔍 Estado Final:</h3>";
    if ($hasNameColumn) {
        $stmt = $pdo->query("SELECT name, slug, base_url, webhook_url, webhook_token, is_active FROM evo_instances ORDER BY name");
    } else {
        $stmt = $pdo->query("SELECT slug, base_url, webhook_url, webhook_token, is_active FROM evo_instances ORDER BY id");
    }
    $instances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    if ($hasNameColumn) {
        echo "<tr><th>Nombre</th><th>Slug</th><th>Base URL</th><th>Webhook URL</th><th>Webhook Token</th><th>Estado</th></tr>";
    } else {
        echo "<tr><th>Slug</th><th>Base URL</th><th>Webhook URL</th><th>Webhook Token</th><th>Estado</th></tr>";
    }
    
    foreach ($instances as $instance) {
        $baseUrlStatus = (!empty($instance['base_url']) && $instance['base_url'] !== 'NULL') ? '✅' : '❌';
        $webhookUrlStatus = (!empty($instance['webhook_url']) && $instance['webhook_url'] !== 'NULL') ? '✅' : '❌';
        $webhookTokenStatus = (!empty($instance['webhook_token']) && $instance['webhook_token'] !== 'NULL') ? '✅' : '❌';
        $activeStatus = $instance['is_active'] ? '✅ Activa' : '❌ Inactiva';
        
        echo "<tr>";
        if ($hasNameColumn) {
            echo "<td><strong>{$instance['name']}</strong></td>";
            echo "<td>{$instance['slug']}</td>";
        } else {
            echo "<td><strong>{$instance['slug']}</strong></td>";
        }
        echo "<td>$baseUrlStatus</td>";
        echo "<td>$webhookUrlStatus</td>";
        echo "<td>$webhookTokenStatus</td>";
        echo "<td>$activeStatus</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3>🚀 Próximos Pasos:</h3>";
    echo "<ol>";
    echo "<li>Verificar que todas las instancias tengan API Keys válidas</li>";
    echo "<li>Probar la conexión de cada instancia</li>";
    echo "<li>Configurar los webhooks en Evolution API con las URLs generadas</li>";
    echo "<li>Verificar que los webhooks se reciban correctamente</li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error de Base de Datos</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
