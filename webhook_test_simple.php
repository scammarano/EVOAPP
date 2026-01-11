<?php
// Test Simple de Webhook - Sin validaciones estrictas
header('Content-Type: application/json');

// Capturar toda la información de la petición
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$instance = $_GET['instance'] ?? null;
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);

// Respuesta detallada para depuración
$response = [
    'success' => true,
    'debug_info' => [
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $method,
        'uri' => $uri,
        'query_string' => $queryString,
        'instance_param' => $instance,
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'NOT_SET',
        'has_raw_input' => !empty($rawInput),
        'raw_input_length' => strlen($rawInput),
        'json_parse_success' => json_last_error() === JSON_ERROR_NONE,
        'json_error' => json_last_error_msg(),
        'json_input' => $jsonInput
    ],
    'validation' => [
        'has_instance' => !empty($instance),
        'has_json_input' => !empty($jsonInput),
        'has_event_field' => isset($jsonInput['event']),
        'has_data_field' => isset($jsonInput['data'])
    ]
];

// Si hay datos válidos, procesar básicamente
if (!empty($instance) && !empty($jsonInput)) {
    $response['processing'] = [
        'event_type' => $jsonInput['event'] ?? 'unknown',
        'data_keys' => isset($jsonInput['data']) ? array_keys($jsonInput['data']) : [],
        'processed_at' => date('Y-m-d H:i:s')
    ];
    
    // Guardar en log para análisis
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'instance' => $instance,
        'event' => $jsonInput['event'] ?? 'unknown',
        'data_summary' => isset($jsonInput['data']) ? count($jsonInput['data']) . ' keys' : 'no data'
    ];
    
    file_put_contents('webhook_test_log.json', json_encode($logEntry) . "\n", FILE_APPEND);
    
    $response['success'] = true;
    $response['message'] = 'Webhook received and logged successfully';
} else {
    $response['success'] = false;
    $response['message'] = 'Invalid request - missing instance or JSON data';
    $response['help'] = [
        'expected_format' => [
            'url' => 'webhook_test_simple.php?instance=YOUR_INSTANCE',
            'method' => 'POST',
            'content_type' => 'application/json',
            'body' => ['event' => 'string', 'data' => 'object']
        ]
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
