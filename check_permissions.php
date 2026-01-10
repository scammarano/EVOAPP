<?php
// Script para verificar permisos del usuario MySQL
// Ejecutar: php check_permissions.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Verificando permisos para usuario '$user' ===\n";
    
    // Verificar permisos en information_schema
    echo "\n--- Permisos en information_schema ---\n";
    $stmt = $pdo->query("SHOW GRANTS FOR '$user'@'$host'");
    $grants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $has_information_schema = false;
    $has_all_databases = false;
    
    foreach ($grants as $grant) {
        if (strpos($grant['Grants'], 'information_schema') !== false) {
            $has_information_schema = true;
            echo "✅ Tiene permiso en information_schema\n";
        }
        if (strpos($grant['Grants'], 'ALL PRIVILEGES') !== false) {
            $has_all_databases = true;
            echo "✅ Tiene permisos ALL PRIVILEGES\n";
        }
    }
    
    if (!$has_information_schema && !$has_all_databases) {
        echo "❌ NO tiene permisos en information_schema\n";
        echo "❌ NO tiene permisos ALL PRIVILEGES\n";
        echo "\n--- Solución ---\n";
        echo "1. Ejecuta como root: mysql -u root -p\n";
        echo "2. Copia y pega: GRANT SELECT ON information_schema.* TO '$user'@'$host';\n";
        echo "3. Luego: FLUSH PRIVILEGES;\n";
    }
    
    // Verificar si puede leer information_schema
    echo "\n--- Probando acceso a information_schema ---\n";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES LIMIT 1");
        $count = $stmt->fetchColumn();
        echo "✅ Puede leer information_schema: $count tablas encontradas\n";
    } catch (PDOException $e) {
        echo "❌ NO puede leer information_schema: " . $e->getMessage() . "\n";
    }
    
    // Verificar estructura de tablas
    echo "\n--- Verificando tablas principales ---\n";
    $tables = ['chats', 'evo_instances', 'webhook_events'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE 'created_at'");
            $has_created_at = $stmt->rowCount() > 0;
            
            $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE 'webhook_timestamp'");
            $has_webhook_timestamp = $stmt->rowCount() > 0;
            
            echo "$table:\n";
            echo "  - created_at: " . ($has_created_at ? '✅' : '❌') . "\n";
            echo "  - webhook_timestamp: " . ($has_webhook_timestamp ? '✅' : '❌') . "\n";
            
        } catch (PDOException $e) {
            echo "$table: ❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
}
?>
