<?php
// Script para probar conexión correcta a Evolution API
// Ejecutar: php test_evolution_api.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔧 Prueba Correcta de Evolution API</h2>";
    
    // Obtener todas las instancias
    $stmt = $pdo->query("SELECT * FROM evo_instances ORDER BY name");
    $instances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($instances as $instance) {
        $name = $instance['name'];
        $slug = $instance['slug'];
        $baseUrl = $instance['base_url'];
        $apiKey = $instance['api_key'];
        
        echo "<h3>🔧 Probando: $name ($slug)</h3>";
        
        if (empty($baseUrl) || empty($apiKey)) {
            echo "<p style='color: red;'>❌ Base URL o API Key faltantes</p>";
            continue;
        }
        
        $baseUrl = rtrim($baseUrl, '/');
        
        // Probar endpoint correcto de Evolution API
        $endpoints = [
            '/instance' => 'GET - Obtener instancia',
            '/instance/status' => 'GET - Estado de instancia',
            '/sendMessage' => 'POST - Enviar mensaje (prueba)'
        ];
        
        foreach ($endpoints as $endpoint => $description) {
            echo "<h4>📡 Probando: $description</h4>";
            
            $url = $baseUrl . $endpoint;
            $headers = [
                'Content-Type: application/json',
                'apikey: ' . $apiKey
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            if (strpos($endpoint, 'POST') !== false) {
                curl_setopt($ch, CURLOPT_POST, true);
                // Datos de prueba para sendMessage
                $testData = [
                    'number' => 'test',
                    'text' => 'test message'
                ];
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo "<p style='color: red;'>❌ Error CURL: " . htmlspecialchars($error) . "</p>";
            } else {
                $statusColor = ($httpCode >= 200 && $httpCode < 300) ? 'green' : 'orange';
                echo "<p style='color: $statusColor;'>✅ HTTP $httpCode - Conexión exitosa</p>";
                
                echo "<h5>Respuesta:</h5>";
                echo "<pre style='background: #f0f0f0; padding: 10px; overflow: auto; max-height: 200px;'>";
                echo htmlspecialchars($response);
                echo "</pre>";
                
                // Analizar respuesta
                $responseData = json_decode($response, true);
                if ($responseData) {
                    if (isset($responseData['error'])) {
                        echo "<p style='color: orange;'>⚠️ Error de API: " . htmlspecialchars($responseData['error']) . "</p>";
                    } elseif (isset($responseData['status']) && $responseData['status'] === 'connected') {
                        echo "<p style='color: green;'>✅ Instancia conectada y funcionando</p>";
                    } elseif (isset($responseData['connected']) && $responseData['connected']) {
                        echo "<p style='color: green;'>✅ Instancia conectada</p>";
                    } else {
                        echo "<p style='color: blue;'>ℹ️ Respuesta recibida, verificar estado</p>";
                    }
                }
            }
            
            echo "<hr>";
        }
        
        echo "<br>";
    }
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error de Base de Datos</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
