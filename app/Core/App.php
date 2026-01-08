<?php
namespace App\Core;

use App\Core\Router;
use App\Core\Auth;
use App\Core\DB;

class App
{
    private $router;
    
    public function __construct()
    {
        // Set error handler
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);

        // Initialize database
        DB::init();
        
        // Initialize router
        $this->router = new Router();
    }
    
    public function run()
    {
        $route = $_GET['r'] ?? 'dashboard/index';
        
        if ($this->shouldValidateCsrf($route)) {
            $this->validateCsrf();
        }

        // Check if route requires authentication
        if (!$this->isPublicRoute($route)) {
            Auth::requireLogin();
        }
        
        $this->router->dispatch($route);
    }
    
    private function isPublicRoute($route)
    {
        $publicRoutes = [
            'auth/login',
            'auth/logout',
            'webhook/evolution'
        ];
        
        return in_array($route, $publicRoutes);
    }

    private function shouldValidateCsrf($route)
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'POST') {
            return false;
        }

        $csrfExemptRoutes = [
            'webhook/evolution'
        ];

        return !in_array($route, $csrfExemptRoutes, true);
    }

    private function validateCsrf()
    {
        $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!Auth::validateCsrfToken($token)) {
            if ($this->isAjaxRequest()) {
                http_response_code(419);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Invalid CSRF token']);
                exit;
            }

            $this->showErrorPage(419, "Invalid CSRF token");
        }
    }

    private function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    public function handleError($severity, $message, $file, $line)
    {
        if (!(error_reporting() & $severity)) {
            return;
        }
        
        $error = "Error: $message in $file on line $line";
        
        if (DEBUG) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
            echo "<strong>Error:</strong> $message<br>";
            echo "<strong>File:</strong> $file:$line<br>";
            echo "</div>";
        } else {
            error_log($error);
            $this->showErrorPage(500, "Internal Server Error");
        }
    }
    
    public function handleException($exception)
    {
        $error = "Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine();
        
        if (DEBUG) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
            echo "<strong>Exception:</strong> " . $exception->getMessage() . "<br>";
            echo "<strong>File:</strong> " . $exception->getFile() . ":" . $exception->getLine() . "<br>";
            echo "<pre>" . $exception->getTraceAsString() . "</pre>";
            echo "</div>";
        } else {
            error_log($error);
            $this->showErrorPage(500, "Internal Server Error");
        }
    }
    
    private function showErrorPage($code, $message)
    {
        http_response_code($code);
        echo "<!DOCTYPE html><html><head><title>Error $code</title></head><body>";
        echo "<h1>Error $code</h1><p>$message</p>";
        echo "</body></html>";
        exit;
    }
}
