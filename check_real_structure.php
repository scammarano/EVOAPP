<?php
// Script para verificar estructura real de tabla evo_instances
// Ejecutar: php check_real_structure.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔍 Estructura Real de tabla 'evo_instances'</h2>";
    
    // Mostrar estructura completa
    echo "<h3>📋 Columnas Actuales:</h3>";
    $stmt = $pdo->query("DESCRIBE evo_instances");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
        $columns[] = $row['Field'];
    }
    echo "</table>";
    
    // Mostrar datos existentes
    echo "<h3>📊 Datos Existentes:</h3>";
    $stmt = $pdo->query("SELECT * FROM evo_instances LIMIT 10");
    $instances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($instances)) {
        echo "<p style='color: red;'>❌ No hay datos en la tabla</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        
        // Encabezado con columnas existentes
        echo "<tr>";
        foreach ($columns as $column) {
            echo "<th><strong>$column</strong></th>";
        }
        echo "</tr>";
        
        // Datos
        foreach ($instances as $instance) {
            echo "<tr>";
            foreach ($columns as $column) {
                $value = $instance[$column] ?? 'NULL';
                
                // Ocultar parte del API key
                if ($column === 'api_key' && strlen($value) > 10) {
                    $value = substr($value, 0, 8) . '...' . substr($value, -4);
                }
                
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Verificar qué columnas faltan
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
    
    $missing_columns = [];
    foreach ($required_columns as $column => $description) {
        $exists = in_array($column, $columns);
        $status = $exists ? "✅" : "❌";
        echo "<p><strong>$status $column:</strong> $description</p>";
        
        if (!$exists) {
            $missing_columns[] = $column;
        }
    }
    
    // Si faltan columnas, mostrar SQL para agregarlas
    if (!empty($missing_columns)) {
        echo "<h3>🔧 SQL para Agregar Columnas Faltantes:</h3>";
        echo "<pre style='background: #f0f0f0; padding: 10px; overflow: auto;'>";
        
        foreach ($missing_columns as $column) {
            switch ($column) {
                case 'name':
                    echo "-- Agregar columna name\n";
                    echo "ALTER TABLE evo_instances \n";
                    echo "ADD COLUMN name VARCHAR(100) NULL DEFAULT NULL \n";
                    echo "AFTER id;\n\n";
                    break;
                    
                case 'slug':
                    echo "-- Agregar columna slug\n";
                    echo "ALTER TABLE evo_instances \n";
                    echo "ADD COLUMN slug VARCHAR(50) NULL DEFAULT NULL \n";
                    echo "AFTER name;\n\n";
                    break;
                    
                case 'base_url':
                    echo "-- Agregar columna base_url\n";
                    echo "ALTER TABLE evo_instances \n";
                    echo "ADD COLUMN base_url VARCHAR(255) NULL DEFAULT NULL \n";
                    echo "AFTER api_key;\n\n";
                    break;
                    
                case 'webhook_url':
                    echo "-- Agregar columna webhook_url\n";
                    echo "ALTER TABLE evo_instances \n";
                    echo "ADD COLUMN webhook_url VARCHAR(500) NULL DEFAULT NULL \n";
                    echo "AFTER base_url;\n\n";
                    break;
                    
                case 'webhook_token':
                    echo "-- Agregar columna webhook_token\n";
                    echo "ALTER TABLE evo_instances \n";
                    echo "ADD COLUMN webhook_token VARCHAR(100) NULL DEFAULT NULL \n";
                    echo "AFTER webhook_url;\n\n";
                    break;
                    
                case 'forward_webhook_enabled':
                    echo "-- Agregar columna forward_webhook_enabled\n";
                    echo "ALTER TABLE evo_instances \n";
                    echo "ADD COLUMN forward_webhook_enabled TINYINT(1) DEFAULT 0 \n";
                    echo "AFTER webhook_token;\n\n";
                    break;
                    
                case 'forward_webhook_url':
                    echo "-- Agregar columna forward_webhook_url\n";
                    echo "ALTER TABLE evo_instances \n";
                    echo "ADD COLUMN forward_webhook_url VARCHAR(500) NULL DEFAULT NULL \n";
                    echo "AFTER forward_webhook_enabled;\n\n";
                    break;
                    
                case 'is_active':
                    echo "-- Agregar columna is_active\n";
                    echo "ALTER TABLE evo_instances \n";
                    echo "ADD COLUMN is_active TINYINT(1) DEFAULT 1 \n";
                    echo "AFTER forward_webhook_url;\n\n";
                    break;
                    
                case 'webhook_timestamp':
                    echo "-- Agregar columna webhook_timestamp\n";
                    echo "ALTER TABLE evo_instances \n";
                    echo "ADD COLUMN webhook_timestamp TIMESTAMP NULL DEFAULT NULL \n";
                    echo "AFTER is_active;\n\n";
                    break;
            }
        }
        
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
