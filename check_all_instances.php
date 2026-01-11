<?php
// Script para verificar configuración de todas las instancias
// Ejecutar: php check_all_instances.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔍 Configuración de Todas las Instancias</h2>";
    
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
    
    echo "<h3>📊 Resumen de Instancias:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Nombre</th><th>Slug</th><th>Base URL</th><th>API Key</th><th>Webhook URL</th><th>Estado</th><th>Último Webhook</th><th>Acciones</th></tr>";
    
    foreach ($instances as $instance) {
        $name = $instance['name'] ?? ($instance['slug'] ?? 'N/A');
        $slug = $instance['slug'] ?? 'N/A';
        $baseUrl = $instance['base_url'] ?? 'NULL';
        $apiKey = $instance['api_key'] ?? 'NULL';
        $webhookUrl = $instance['webhook_url'] ?? 'NULL';
        $isActive = $instance['is_active'] ?? 0;
        $webhookTimestamp = $instance['webhook_timestamp'] ?? 'NULL';
        
        // Estados
        $baseUrlStatus = (empty($baseUrl) || $baseUrl === 'NULL') ? '❌' : '✅';
        $apiKeyStatus = (empty($apiKey) || $apiKey === 'NULL' || strlen($apiKey) < 10) ? '❌' : '✅';
        $webhookUrlStatus = (empty($webhookUrl) || $webhookUrl === 'NULL') ? '❌' : '✅';
        $activeStatus = $isActive ? '✅ Activa' : '❌ Inactiva';
        
        // Último webhook
        if ($webhookTimestamp && $webhookTimestamp !== 'NULL') {
            $timestamp = strtotime($webhookTimestamp);
            $hours = (time() - $timestamp) / 3600;
            if ($hours > 24) {
                $webhookStatus = '❌ Muy antiguo';
            } elseif ($hours > 1) {
                $webhookStatus = '⚠️ Antiguo';
            } else {
                $webhookStatus = '✅ Reciente';
            }
        } else {
            $webhookStatus = '❌ Sin webhooks';
        }
        
        // Ocultar parte del API key
        if (strlen($apiKey) > 10) {
            $apiKey = substr($apiKey, 0, 8) . '...' . substr($apiKey, -4);
        }
        
        echo "<tr>";
        echo "<td><strong>$name</strong></td>";
        echo "<td>$slug</td>";
        echo "<td>$baseUrlStatus</td>";
        echo "<td>$apiKeyStatus</td>";
        echo "<td>$webhookUrlStatus</td>";
        echo "<td>$activeStatus</td>";
        echo "<td>$webhookStatus</td>";
        echo "<td><a href='?fix_instance=$slug'>🔧 Corregir</a></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Procesar corrección si se solicita
    if (isset($_GET['fix_instance']) && !empty($_GET['fix_instance'])) {
        $slug = $_GET['fix_instance'];
        
        echo "<h3>🔧 Corregir Instancia: $slug</h3>";
        
        // Buscar instancia
        $stmt = $pdo->prepare("SELECT * FROM evo_instances WHERE slug = ?");
        $stmt->execute([$slug]);
        $instance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$instance) {
            echo "<p style='color: red;'>❌ Instancia no encontrada</p>";
            return;
        }
        
        // Correcciones
        $corrections = [];
        
        // 1. Base URL
        if (empty($instance['base_url']) || $instance['base_url'] === 'NULL') {
            $suggestedBaseUrl = 'https://evolutionapi-evolution-api.xs639b.easypanel.host';
            $corrections[] = "Base URL: $suggestedBaseUrl";
            
            if (isset($_GET['apply_base_url'])) {
                $stmt = $pdo->prepare("UPDATE evo_instances SET base_url = ? WHERE slug = ?");
                $stmt->execute([$suggestedBaseUrl, $slug]);
                echo "<p style='color: green;'>✅ Base URL corregida</p>";
            }
        }
        
        // 2. Webhook URL
        if (empty($instance['webhook_url']) || $instance['webhook_url'] === 'NULL') {
            $suggestedWebhookUrl = 'https://camcam.com.ve/evoappws/webhook/' . $slug;
            $corrections[] = "Webhook URL: $suggestedWebhookUrl";
            
            if (isset($_GET['apply_webhook_url'])) {
                $stmt = $pdo->prepare("UPDATE evo_instances SET webhook_url = ? WHERE slug = ?");
                $stmt->execute([$suggestedWebhookUrl, $slug]);
                echo "<p style='color: green;'>✅ Webhook URL corregida</p>";
            }
        }
        
        // 3. Webhook Token
        if (empty($instance['webhook_token']) || $instance['webhook_token'] === 'NULL') {
            $suggestedToken = 'webhook_' . bin2hex(random_bytes(16));
            $corrections[] = "Webhook Token: $suggestedToken";
            
            if (isset($_GET['apply_webhook_token'])) {
                $stmt = $pdo->prepare("UPDATE evo_instances SET webhook_token = ? WHERE slug = ?");
                $stmt->execute([$suggestedToken, $slug]);
                echo "<p style='color: green;'>✅ Webhook Token generado</p>";
            }
        }
        
        // Mostrar correcciones sugeridas
        if (!empty($corrections)) {
            echo "<h4>📋 Correcciones Sugeridas:</h4>";
            echo "<ul>";
            foreach ($corrections as $correction) {
                echo "<li>$correction</li>";
            }
            echo "</ul>";
            
            echo "<h4>🚀 Aplicar Correcciones:</h4>";
            echo "<ul>";
            if (empty($instance['base_url']) || $instance['base_url'] === 'NULL') {
                echo "<li><a href='?fix_instance=$slug&apply_base_url=1'>🔧 Aplicar Base URL</a></li>";
            }
            if (empty($instance['webhook_url']) || $instance['webhook_url'] === 'NULL') {
                echo "<li><a href='?fix_instance=$slug&apply_webhook_url=1'>🔧 Aplicar Webhook URL</a></li>";
            }
            if (empty($instance['webhook_token']) || $instance['webhook_token'] === 'NULL') {
                echo "<li><a href='?fix_instance=$slug&apply_webhook_token=1'>🔧 Generar Webhook Token</a></li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: green;'>✅ La instancia parece estar correctamente configurada</p>";
        }
        
        // Probar conexión
        echo "<h4>🔧 Prueba de Conexión:</h4>";
        $currentBaseUrl = $instance['base_url'] ?? '';
        $currentApiKey = $instance['api_key'] ?? '';
        
        if (!empty($currentBaseUrl) && !empty($currentApiKey)) {
            $baseUrl = rtrim($currentBaseUrl, '/');
            $testUrl = $baseUrl . '/instance';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $testUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $currentApiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo "<p style='color: red;'>❌ Error CURL: " . htmlspecialchars($error) . "</p>";
            } else {
                echo "<p style='color: green;'>✅ Conexión exitosa (HTTP $httpCode)</p>";
                echo "<h5>Respuesta:</h5>";
                echo "<pre style='background: #f0f0f0; padding: 10px; overflow: auto; max-height: 200px;'>";
                echo htmlspecialchars($response);
                echo "</pre>";
            }
        } else {
            echo "<p style='color: red;'>❌ No se puede probar: Base URL o API Key faltantes</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error de Base de Datos</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
