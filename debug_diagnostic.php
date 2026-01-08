<?php
// Script de debugging para el diagnóstico
// Ejecutar: php debug_diagnostic.php

require_once 'app/Core/DB.php';
require_once 'app/Models/Instance.php';

echo "=== DEBUG DIAGNÓSTICO ===\n";

try {
    // 1. Verificar configuración
    echo "1. Verificando configuración...\n";
    echo "EVO_BASE_URL: " . EVO_BASE_URL . "\n";
    
    // 2. Obtener instancias
    echo "\n2. Obteniendo instancias...\n";
    $instances = Instance::getAll(false);
    echo "Instancias encontradas: " . count($instances) . "\n";
    
    foreach ($instances as $instance) {
        echo "\n--- Instancia: {$instance['slug']} ---\n";
        echo "ID: {$instance['id']}\n";
        echo "Base URL: " . ($instance['base_url'] ?: EVO_BASE_URL) . "\n";
        echo "API Key: " . ($instance['api_key'] ? substr($instance['api_key'], 0, 8) . '...' : 'NO CONFIGURADA') . "\n";
        echo "Last Webhook: " . ($instance['last_webhook_at'] ?: 'NUNCA') . "\n";
        
        // 3. Test de conexión básico
        $baseUrl = $instance['base_url'] ?: EVO_BASE_URL;
        $apiKey = $instance['api_key'];
        
        if (!$apiKey) {
            echo "❌ ERROR: API Key no configurada\n";
            continue;
        }
        
        echo "3. Probando conexión...\n";
        $url = $baseUrl . "/message/sendText/" . $instance['slug'];
        echo "URL: $url\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'number' => '+10000000000',
            'text' => 'DEBUG TEST'
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo "HTTP Code: $httpCode\n";
        if ($error) {
            echo "❌ CURL Error: $error\n";
        } else {
            echo "✅ Conexión exitosa\n";
            echo "Response: " . substr($response, 0, 200) . "...\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR GENERAL: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
?>
