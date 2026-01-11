<?php
// Script para verificar configuración de instancia 2CAMCARGO
// Ejecutar: php check_instance_config.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔍 Configuración de Instancia 2CAMCARGO</h2>";
    
    // Buscar instancia 2CAMCARGO
    $stmt = $pdo->prepare("SELECT * FROM evo_instances WHERE slug = ?");
    $stmt->execute(['2CAMCARGO']);
    $instance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$instance) {
        echo "<h3 style='color: red;'>❌ Instancia 2CAMCARGO no encontrada</h3>";
        return;
    }
    
    echo "<h3>✅ Instancia Encontrada</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Valor</th><th>Estado</th></tr>";
    
    // Verificar campos importantes
    $fields = [
        'id' => 'ID',
        'name' => 'Nombre',
        'slug' => 'Slug',
        'base_url' => 'Base URL',
        'api_key' => 'API Key',
        'webhook_url' => 'Webhook URL',
        'webhook_token' => 'Webhook Token',
        'is_active' => 'Activa',
        'webhook_timestamp' => 'Último Webhook'
    ];
    
    foreach ($fields as $field => $label) {
        $value = $instance[$field] ?? 'NULL';
        $status = '';
        
        switch ($field) {
            case 'base_url':
                if (empty($value) || $value === 'NULL') {
                    $status = '❌ FALTANTE';
                } elseif (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $status = '❌ URL INVÁLIDA';
                } else {
                    $status = '✅ OK';
                }
                break;
                
            case 'api_key':
                if (empty($value) || $value === 'NULL') {
                    $status = '❌ FALTANTE';
                } elseif (strlen($value) < 10) {
                    $status = '❌ MUY CORTO';
                } else {
                    $status = '✅ OK';
                }
                break;
                
            case 'webhook_url':
                if (empty($value) || $value === 'NULL') {
                    $status = '❌ FALTANTE';
                } elseif (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $status = '❌ URL INVÁLIDA';
                } else {
                    $status = '✅ OK';
                }
                break;
                
            case 'is_active':
                $status = $value ? '✅ ACTIVA' : '❌ INACTIVA';
                break;
                
            case 'webhook_timestamp':
                if (empty($value) || $value === 'NULL') {
                    $status = '❌ SIN WEBHOOKS';
                } else {
                    $timestamp = strtotime($value);
                    $hours = (time() - $timestamp) / 3600;
                    if ($hours > 24) {
                        $status = '⚠️ MUY ANTIGUO';
                    } elseif ($hours > 1) {
                        $status = '⚠️ ANTIGUO';
                    } else {
                        $status = '✅ RECIENTE';
                    }
                }
                break;
                
            default:
                $status = $value ? '✅' : '❌';
        }
        
        // Ocultar parte del API key
        if ($field === 'api_key' && strlen($value) > 10) {
            $value = substr($value, 0, 8) . '...' . substr($value, -4);
        }
        
        echo "<tr>";
        echo "<td><strong>$label</strong></td>";
        echo "<td>" . htmlspecialchars($value) . "</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Probar conexión a la API
    echo "<h3>🔧 Prueba de Conexión a API</h3>";
    
    if (!empty($instance['base_url']) && !empty($instance['api_key'])) {
        $baseUrl = rtrim($instance['base_url'], '/');
        $testUrl = $baseUrl . '/instance';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $instance['api_key']
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
            echo "<h4>Respuesta:</h4>";
            echo "<pre style='background: #f0f0f0; padding: 10px; overflow: auto; max-height: 200px;'>";
            echo htmlspecialchars($response);
            echo "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ No se puede probar: Base URL o API Key faltantes</p>";
    }
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error de Base de Datos</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
