<?php
// Actualizador de configuración de webhook en base de datos local
header('Content-Type: application/json');

require_once 'app/Core/DB.php';

// Inicializar conexión
App\Core\DB::init();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $instanceId = $input['instance_id'] ?? null;
    $webhookUrl = $input['webhook_url'] ?? null;
    $webhookEnabled = $input['webhook_enabled'] ?? false;
    
    if (!$instanceId) {
        throw new Exception('Instance ID is required');
    }
    
    // Actualizar configuración en evo_instances
    $updateQuery = "UPDATE evo_instances SET 
                    forward_webhook_url = ?, 
                    webhook_enabled = ?, 
                    webhook_updated_at = NOW() 
                    WHERE id = ?";
    
    $result = App\Core\DB::q($updateQuery, [$webhookUrl, $webhookEnabled ? 1 : 0, $instanceId]);
    
    if ($result->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Webhook configuration updated successfully',
            'instance_id' => $instanceId,
            'webhook_url' => $webhookUrl,
            'webhook_enabled' => $webhookEnabled
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No rows affected - instance may not exist'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
