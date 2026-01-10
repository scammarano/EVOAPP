<?php
// Script para verificar columnas en la base de datos
// Ejecutar: php check_columns.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Verificando columnas en tabla 'chats' ===\n";
    
    // Verificar columna created_at
    $stmt = $pdo->query("SHOW COLUMNS FROM chats LIKE 'created_at'");
    $created_at_exists = $stmt->rowCount() > 0;
    
    if ($created_at_exists) {
        echo "✅ Columna 'created_at' EXISTE\n";
        $column_info = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   - Tipo: {$column_info['Type']}\n";
        echo "   - Default: {$column_info['Default']}\n";
    } else {
        echo "❌ Columna 'created_at' NO EXISTE\n";
    }
    
    // Verificar columna webhook_timestamp
    echo "\n=== Verificando columnas en tabla 'evo_instances' ===\n";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM evo_instances LIKE 'webhook_timestamp'");
    $webhook_timestamp_exists = $stmt->rowCount() > 0;
    
    if ($webhook_timestamp_exists) {
        echo "✅ Columna 'webhook_timestamp' EXISTE\n";
        $column_info = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   - Tipo: {$column_info['Type']}\n";
        echo "   - Default: {$column_info['Default']}\n";
    } else {
        echo "❌ Columna 'webhook_timestamp' NO EXISTE\n";
    }
    
    // Mostrar estructura completa de las tablas
    echo "\n=== Estructura tabla 'chats' ===\n";
    $stmt = $pdo->query("DESCRIBE chats");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
    }
    
    echo "\n=== Estructura tabla 'evo_instances' ===\n";
    $stmt = $pdo->query("DESCRIBE evo_instances");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
}
?>
