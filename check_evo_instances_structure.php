<?php
// Script para verificar estructura de tabla evo_instances
// Ejecutar: php check_evo_instances_structure.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔍 Estructura de tabla 'evo_instances'</h2>";
    
    // Mostrar estructura completa
    echo "<h3>📋 Columnas Actuales:</h3>";
    $stmt = $pdo->query("DESCRIBE evo_instances");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
        $columns[] = $row['Field'];
    }
    echo "</table>";
    
    // Verificar columnas necesarias
    echo "<h3>✅ Verificación de Columnas Necesarias:</h3>";
    $required_columns = [
        'id' => 'ID de la instancia',
        'name' => 'Nombre de la instancia',
        'slug' => 'Slug único',
        'base_url' => 'URL base de Evolution API',
        'api_key' => 'API Key de Evolution API',
        'webhook_url' => 'URL para recibir webhooks',
        'webhook_token' => 'Token de seguridad para webhooks',
        'forward_webhook_enabled' => 'Habilitar reenvío de webhooks',
        'forward_webhook_url' => 'URL para reenviar webhooks',
        'is_active' => 'Estado activo/inactivo',
        'webhook_timestamp' => 'Último webhook recibido'
    ];
    
    foreach ($required_columns as $column => $description) {
        $exists = in_array($column, $columns);
        $status = $exists ? "✅" : "❌";
        echo "<p><strong>$status $column:</strong> $description</p>";
    }
    
    // Mostrar datos de instancia 2CAMCARGO
    echo "<h3>📊 Datos de Instancia 2CAMCARGO:</h3>";
    $stmt = $pdo->prepare("SELECT * FROM evo_instances WHERE slug = ?");
    $stmt->execute(['2CAMCARGO']);
    $instance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($instance) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        
        foreach ($instance as $field => $value) {
            // Ocultar parte del API key
            if ($field === 'api_key' && strlen($value) > 10) {
                $value = substr($value, 0, 8) . '...' . substr($value, -4);
            }
            echo "<tr>";
            echo "<td><strong>$field</strong></td>";
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Instancia 2CAMCARGO no encontrada</p>";
    }
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
