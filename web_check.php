<?php
// Script simple para verificar permisos vía web
// Acceder: https://camcam.com.ve/evoappws/web_check.php

echo "<h2>🔍 Verificando Permisos MySQL</h2>";

try {
    $host = 'localhost';
    $dbname = 'grupoecc_evoappWS';
    $user = 'grupoecc_toto';
    $pass = 'Toto123*.A';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h3>✅ Conexión Exitosa</h3>";
    
    // Verificar información_schema
    echo "<h4>📊 Probando information_schema</h4>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES LIMIT 1");
        $count = $stmt->fetchColumn();
        echo "<p style='color: green;'>✅ Puede leer information_schema: $count tablas</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error en information_schema: " . $e->getMessage() . "</p>";
    }
    
    // Verificar columnas
    echo "<h4>🗂️ Verificando Columnas</h4>";
    $tables = ['chats', 'evo_instances'];
    
    foreach ($tables as $table) {
        echo "<h5>Tabla: $table</h5>";
        
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE 'created_at'");
            $has_created_at = $stmt->rowCount() > 0;
            echo "<p>created_at: " . ($has_created_at ? "✅ EXISTE" : "❌ NO EXISTE") . "</p>";
            
            $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE 'webhook_timestamp'");
            $has_webhook = $stmt->rowCount() > 0;
            echo "<p>webhook_timestamp: " . ($has_webhook ? "✅ EXISTE" : "❌ NO EXISTE") . "</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error de Conexión</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h4>📋 Próximos Pasos</h4>";
echo "<ol>";
echo "<li>Si created_at NO EXISTE: Ejecuta el SQL para agregarla</li>";
echo "<li>Si webhook_timestamp NO EXISTE: Ejecuta el SQL para agregarla</li>";
echo "<li>Si information_schema da error: Pide permisos al administrador</li>";
echo "</ol>";
?>
