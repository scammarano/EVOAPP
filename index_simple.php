<?php

/**
 * EVOAPP - Sistema de Gestión Evolution API
 * Archivo principal de entrada - Versión Simple
 */

// Iniciar sesión
session_start();

// Incluir configuración
require_once __DIR__ . '/config/orconfig.php';

// Autocarga de clases
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Sistema de rutas simple
$request_uri = $_SERVER['REQUEST_URI'];
$request_uri = strtok($request_uri, '?');
$request_method = $_SERVER['REQUEST_METHOD'];

// Router simple
switch ($request_uri) {
    case '/':
    case '/dashboard':
        require_once __DIR__ . '/app/Controllers/DashboardController.php';
        $controller = new App\Controllers\DashboardController();
        $controller->index();
        break;
        
    case '/login':
        require_once __DIR__ . '/app/Controllers/AuthController.php';
        $controller = new App\Controllers\AuthController();
        $controller->login();
        break;
        
    case '/logout':
        require_once __DIR__ . '/app/Controllers/AuthController.php';
        $controller = new App\Controllers\AuthController();
        $controller->logout();
        break;
        
    case '/instances':
        require_once __DIR__ . '/app/Controllers/InstanceController.php';
        $controller = new App\Controllers\InstanceController();
        $controller->index();
        break;
        
    case '/inbox':
        require_once __DIR__ . '/app/Controllers/InboxController.php';
        $controller = new App\Controllers\InboxController();
        $controller->index();
        break;
        
    case '/api/chats':
        require_once __DIR__ . '/app/Controllers/InboxController.php';
        $controller = new App\Controllers\InboxController();
        $controller->chatsAjax();
        break;
        
    case '/api/messages':
        require_once __DIR__ . '/app/Controllers/InboxController.php';
        $controller = new App\Controllers\InboxController();
        $controller->messages();
        break;
        
    case '/api/send':
        if ($request_method === 'POST') {
            require_once __DIR__ . '/app/Controllers/InboxController.php';
            $controller = new App\Controllers\InboxController();
            $controller->sendAjax();
        } else {
            header('Location: /inbox');
            exit;
        }
        break;
        
    case '/api/send-media':
        if ($request_method === 'POST') {
            require_once __DIR__ . '/app/Controllers/InboxController.php';
            $controller = new App\Controllers\InboxController();
            $controller->sendMediaAjax();
        } else {
            header('Location: /inbox');
            exit;
        }
        break;
        
    case '/contacts':
        require_once __DIR__ . '/app/Controllers/ContactController.php';
        $controller = new App\Controllers\ContactController();
        $controller->index();
        break;
        
    case '/contacts/extract/chats':
        if ($request_method === 'POST') {
            require_once __DIR__ . '/app/Controllers/ContactController.php';
            $controller = new App\Controllers\ContactController();
            $controller->extractFromChats();
        } else {
            header('Location: /contacts');
            exit;
        }
        break;
        
    case '/contacts/extract/groups':
        if ($request_method === 'POST') {
            require_once __DIR__ . '/app/Controllers/ContactController.php';
            $controller = new App\Controllers\ContactController();
            $controller->extractFromGroups();
        } else {
            header('Location: /contacts');
            exit;
        }
        break;
        
    case '/campaigns':
        require_once __DIR__ . '/app/Controllers/CampaignController.php';
        $controller = new App\Controllers\CampaignController();
        $controller->index();
        break;
        
    case '/campaigns/create':
        require_once __DIR__ . '/app/Controllers/CampaignController.php';
        $controller = new App\Controllers\CampaignController();
        $controller->create();
        break;
        
    case '/api/health':
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'timestamp' => date('c'),
            'app_name' => APP_NAME,
            'version' => '1.0.0',
            'php_version' => PHP_VERSION,
            'database' => 'connected'
        ]);
        break;
        
    default:
        http_response_code(404);
        echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página No Encontrada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card text-center">
                    <div class="card-body">
                        <h1 class="display-4 text-danger">404</h1>
                        <h3>Página No Encontrada</h3>
                        <p class="text-muted">La página que buscas no existe.</p>
                        <a href="/dashboard" class="btn btn-primary">Ir al Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
        break;
}
