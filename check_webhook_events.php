<?php
// Script para verificar estructura de tabla webhook_events
// Ejecutar: php check_webhook_events.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔍 Estructura de tabla 'webhook_events'</h2>";
    
    // Mostrar estructura completa
    echo "<h3>📋 Columnas Actuales:</h3>";
    $stmt = $pdo->query("DESCRIBE webhook_events");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
        $columns[] = $row['Field'];
    }
    echo "</table>";
    
    // Verificar columnas necesarias
    echo "<h3>✅ Verificación de Columnas Necesarias:</h3>";
    $required_columns = [
        'id' => 'ID del evento',
        'instance_id' => 'ID de la instancia',
        'event_type' => 'Tipo de evento',
        'status' => 'Estado del evento',
        'retry_count' => 'Contador de reintentos',
        'error_message' => 'Mensaje de error',
        'processed_at' => 'Fecha de procesamiento',
        'created_at' => 'Fecha de creación',
        'payload' => 'Datos del evento'
    ];
    
    foreach ($required_columns as $column => $description) {
        $exists = in_array($column, $columns);
        $status = $exists ? "✅" : "❌";
        echo "<p><strong>$status $column:</strong> $description</p>";
    }
    
    // Mostrar algunos registros
    echo "<h3>📊 Registros Recientes:</h3>";
    $stmt = $pdo->query("SELECT * FROM webhook_events ORDER BY id DESC LIMIT 5");
    
    if ($stmt->rowCount() > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Instance</th><th>Event</th><th>Status</th><th>Retry</th><th>Error</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['instance_id']}</td>";
            echo "<td>{$row['event_type']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>" . ($row['retry_count'] ?? 'N/A') . "</td>";
            echo "<td>" . (strlen($row['error_message'] ?? '') > 50 ? substr($row['error_message'], 0, 50) . '...' : ($row['error_message'] ?? 'N/A')) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay registros en la tabla webhook_events</p>";
    }
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
