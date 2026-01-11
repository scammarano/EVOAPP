<?php
// Script para limpiar webhooks antiguos y resetear el sistema
// Ejecutar: php clear_old_webhooks.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🧹 Limpiar Webhooks Antiguos</h2>";
    
    // Contar webhooks por estado
    echo "<h3>📊 Estadísticas Actuales:</h3>";
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM webhook_events GROUP BY status");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total = 0;
    foreach ($stats as $stat) {
        $status = strtoupper($stat['status']);
        $count = $stat['count'];
        $total += $count;
        $color = $status === 'PENDING' ? 'orange' : ($status === 'PROCESSED' ? 'green' : 'red');
        echo "<p style='color: $color;'>$status: $count</p>";
    }
    echo "<p><strong>Total: $total</strong></p>";
    
    // Opciones de limpieza
    echo "<h3>🧹 Opciones de Limpieza:</h3>";
    
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        
        switch ($action) {
            case 'clear_processed':
                echo "<h4>🗑️ Limpiando webhooks procesados...</h4>";
                $stmt = $pdo->query("DELETE FROM webhook_events WHERE status = 'processed'");
                $deleted = $stmt->rowCount();
                echo "<p style='color: green;'>✅ $deleted webhooks procesados eliminados</p>";
                break;
                
            case 'clear_errors':
                echo "<h4>🗑️ Limpiando webhooks con error...</h4>";
                $stmt = $pdo->query("DELETE FROM webhook_events WHERE status = 'error'");
                $deleted = $stmt->rowCount();
                echo "<p style='color: green;'>✅ $deleted webhooks con error eliminados</p>";
                break;
                
            case 'reset_pending':
                echo "<h4>🔄 Reseteando webhooks pendientes...</h4>";
                $stmt = $pdo->query("
                    UPDATE webhook_events 
                    SET status = 'pending', 
                        retry_count = 0, 
                        error_message = NULL, 
                        processed_at = NULL 
                    WHERE status IN ('pending', 'error')
                ");
                $updated = $stmt->rowCount();
                echo "<p style='color: green;'>✅ $updated webhooks reseteados a pending</p>";
                break;
                
            case 'clear_all':
                echo "<h4>🗑️ Limpiando TODOS los webhooks...</h4>";
                $stmt = $pdo->query("DELETE FROM webhook_events");
                $deleted = $stmt->rowCount();
                echo "<p style='color: green;'>✅ $deleted webhooks eliminados</p>";
                break;
                
            case 'optimize_table':
                echo "<h4>🔧 Optimizando tabla...</h4>";
                $stmt = $pdo->query("OPTIMIZE TABLE webhook_events");
                echo "<p style='color: green;'>✅ Tabla webhook_events optimizada</p>";
                break;
        }
        
        echo "<hr>";
        echo "<p><a href='clear_old_webhooks.php'>🔄 Volver al menú</a></p>";
        
    } else {
        echo "<p>Selecciona una acción:</p>";
        echo "<ul>";
        echo "<li><a href='?action=clear_processed'>🗑️ Limpiar webhooks procesados</a></li>";
        echo "<li><a href='?action=clear_errors'>🗑️ Limpiar webhooks con error</a></li>";
        echo "<li><a href='?action=reset_pending'>🔄 Resetear webhooks pendientes</a></li>";
        echo "<li><a href='?action=clear_all'>🗑️ Limpiar TODOS los webhooks</a></li>";
        echo "<li><a href='?action=optimize_table'>🔧 Optimizar tabla</a></li>";
        echo "</ul>";
        
        echo "<h3>⚠️ Recomendación:</h3>";
        echo "<p>1. Primero ejecuta <strong>Limpiar webhooks procesados</strong></p>";
        echo "<p>2. Luego ejecuta <strong>Limpiar webhooks con error</strong></p>";
        echo "<p>3. Finalmente ejecuta <strong>Optimizar tabla</strong></p>";
        echo "<p>4. Para empezar fresco, ejecuta <strong>Limpiar TODOS los webhooks</strong></p>";
    }
    
    // Mostrar estadísticas actualizadas
    if (isset($_GET['action'])) {
        echo "<h3>📊 Estadísticas Actualizadas:</h3>";
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM webhook_events GROUP BY status");
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($stats)) {
            echo "<p style='color: green;'>✅ No hay webhooks en la tabla</p>";
        } else {
            foreach ($stats as $stat) {
                $status = strtoupper($stat['status']);
                $count = $stat['count'];
                $color = $status === 'PENDING' ? 'orange' : ($status === 'PROCESSED' ? 'green' : 'red');
                echo "<p style='color: $color;'>$status: $count</p>";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error de Base de Datos</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
