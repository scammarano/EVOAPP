<?php
// Debug de Webhook Evolution API
header('Content-Type: application/json');

echo json_encode([
    'debug_info' => [
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'],
        'query_string' => $_SERVER['QUERY_STRING'] ?? '',
        'get_params' => $_GET,
        'post_params' => $_POST,
        'headers' => getallheaders(),
        'raw_input' => file_get_contents('php://input'),
        'json_input' => json_decode(file_get_contents('php://input'), true),
        'instance_param' => $_GET['instance'] ?? 'NOT_FOUND',
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'NOT_SET'
    ]
]);
?>
