<?php
// Script para corregir configuración de instancia 2CAMCARGO
// Ejecutar: php fix_instance_config.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔧 Corregir Configuración de Instancia 2CAMCARGO</h2>";
    
    // Buscar instancia 2CAMCARGO
    $stmt = $pdo->prepare("SELECT * FROM evo_instances WHERE slug = ?");
    $stmt->execute(['2CAMCARGO']);
    $instance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$instance) {
        echo "<h3 style='color: red;'>❌ Instancia 2CAMCARGO no encontrada</h3>";
        return;
    }
    
    echo "<h3>📋 Configuración Actual:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Valor Actual</th></tr>";
    
    $fields = ['base_url', 'api_key', 'webhook_url', 'webhook_token'];
    foreach ($fields as $field) {
        $value = $instance[$field] ?? 'NULL';
        if ($field === 'api_key' && strlen($value) > 10) {
            $value = substr($value, 0, 8) . '...' . substr($value, -4);
        }
        echo "<tr><td><strong>$field</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
    }
    echo "</table>";
    
    // Correcciones sugeridas
    echo "<h3>🔧 Correcciones Sugeridas:</h3>";
    
    // 1. Corregir Base URL si es necesario
    $currentBaseUrl = $instance['base_url'] ?? '';
    $suggestedBaseUrl = 'https://evolutionapi-evolution-api.xs639b.easypanel.host';
    
    if (empty($currentBaseUrl) || !filter_var($currentBaseUrl, FILTER_VALIDATE_URL)) {
        echo "<p style='color: orange;'>⚠️ Base URL inválida o vacía. Sugerencia: $suggestedBaseUrl</p>";
        
        if (isset($_GET['fix_base_url']) && $_GET['fix_base_url'] === 'true') {
            $stmt = $pdo->prepare("UPDATE evo_instances SET base_url = ? WHERE slug = ?");
            $stmt->execute([$suggestedBaseUrl, '2CAMCARGO']);
            echo "<p style='color: green;'>✅ Base URL corregida</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Base URL parece correcta</p>";
    }
    
    // 2. Verificar Webhook URL
    $currentWebhookUrl = $instance['webhook_url'] ?? '';
    $suggestedWebhookUrl = 'https://camcam.com.ve/evoappws/webhook/2CAMCARGO';
    
    if (empty($currentWebhookUrl) || !filter_var($currentWebhookUrl, FILTER_VALIDATE_URL)) {
        echo "<p style='color: orange;'>⚠️ Webhook URL inválida o vacía. Sugerencia: $suggestedWebhookUrl</p>";
        
        if (isset($_GET['fix_webhook_url']) && $_GET['fix_webhook_url'] === 'true') {
            $stmt = $pdo->prepare("UPDATE evo_instances SET webhook_url = ? WHERE slug = ?");
            $stmt->execute([$suggestedWebhookUrl, '2CAMCARGO']);
            echo "<p style='color: green;'>✅ Webhook URL corregida</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Webhook URL parece correcta</p>";
    }
    
    // 3. Verificar API Key
    $currentApiKey = $instance['api_key'] ?? '';
    if (empty($currentApiKey) || strlen($currentApiKey) < 10) {
        echo "<p style='color: red;'>❌ API Key faltante o muy corta. Debe configurarla manualmente.</p>";
    } else {
        echo "<p style='color: green;'>✅ API Key configurada</p>";
    }
    
    // 4. Generar Webhook Token si no existe
    $currentWebhookToken = $instance['webhook_token'] ?? '';
    if (empty($currentWebhookToken)) {
        $suggestedToken = 'webhook_' . bin2hex(random_bytes(16));
        echo "<p style='color: orange;'>⚠️ Webhook Token faltante. Sugerencia: $suggestedToken</p>";
        
        if (isset($_GET['fix_webhook_token']) && $_GET['fix_webhook_token'] === 'true') {
            $stmt = $pdo->prepare("UPDATE evo_instances SET webhook_token = ? WHERE slug = ?");
            $stmt->execute([$suggestedToken, '2CAMCARGO']);
            echo "<p style='color: green;'>✅ Webhook Token generado</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Webhook Token configurado</p>";
    }
    
    // Botones para aplicar correcciones
    echo "<h3>🚀 Aplicar Correcciones</h3>";
    echo "<p>Click en los enlaces para aplicar las correcciones:</p>";
    echo "<ul>";
    echo "<li><a href='?fix_base_url=true'>🔧 Corregir Base URL</a></li>";
    echo "<li><a href='?fix_webhook_url=true'>🔧 Corregir Webhook URL</a></li>";
    echo "<li><a href='?fix_webhook_token=true'>🔧 Generar Webhook Token</a></li>";
    echo "</ul>";
    
    echo "<p><strong>Nota:</strong> La API Key debe configurarse manualmente con el valor correcto de Evolution API.</p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error de Base de Datos</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
