<?php
namespace App\Core;

class Router
{
    private $routes = [
        // Auth routes
        'auth/login' => [
            'controller' => 'AuthController',
            'method' => 'login',
            'methods' => ['GET', 'POST']
        ],
        'auth/logout' => [
            'controller' => 'AuthController',
            'method' => 'logout',
            'methods' => ['GET']
        ],
        
        // Dashboard
        'dashboard/index' => [
            'controller' => 'DashboardController',
            'method' => 'index',
            'methods' => ['GET']
        ],
        
        // Instances
        'instances/index' => [
            'controller' => 'InstancesController',
            'method' => 'index',
            'methods' => ['GET']
        ],
        'instances/create' => [
            'controller' => 'InstancesController',
            'method' => 'create',
            'methods' => ['GET', 'POST']
        ],
        'instances/store' => [
            'controller' => 'InstancesController',
            'method' => 'store',
            'methods' => ['POST']
        ],
        'instances/edit' => [
            'controller' => 'InstancesController',
            'method' => 'edit',
            'methods' => ['GET', 'POST']
        ],
        'instances/update' => [
            'controller' => 'InstancesController',
            'method' => 'update',
            'methods' => ['POST']
        ],
        'instances/delete' => [
            'controller' => 'InstancesController',
            'method' => 'delete',
            'methods' => ['POST']
        ],
        'instances/view' => [
            'controller' => 'InstancesController',
            'method' => 'view',
            'methods' => ['GET']
        ],
        
        // Inbox
        'inbox/index' => [
            'controller' => 'InboxController',
            'method' => 'index',
            'methods' => ['GET']
        ],
        'inbox/chats' => [
            'controller' => 'InboxController',
            'method' => 'chatsAjax',
            'methods' => ['GET']
        ],
        'inbox/messages' => [
            'controller' => 'InboxController',
            'method' => 'messages',
            'methods' => ['GET']
        ],
        'inbox/send' => [
            'controller' => 'InboxController',
            'method' => 'sendAjax',
            'methods' => ['POST']
        ],
        'inbox/sendMedia' => [
            'controller' => 'InboxController',
            'method' => 'sendMediaAjax',
            'methods' => ['POST']
        ],
        'inbox/markRead' => [
            'controller' => 'InboxController',
            'method' => 'markRead',
            'methods' => ['POST']
        ],
        
        // Contacts
        'contacts/index' => [
            'controller' => 'ContactsController',
            'method' => 'index',
            'methods' => ['GET']
        ],
        'contacts/create' => [
            'controller' => 'ContactsController',
            'method' => 'create',
            'methods' => ['GET', 'POST']
        ],
        'contacts/store' => [
            'controller' => 'ContactsController',
            'method' => 'store',
            'methods' => ['POST']
        ],
        'contacts/edit' => [
            'controller' => 'ContactsController',
            'method' => 'edit',
            'methods' => ['GET', 'POST']
        ],
        'contacts/update' => [
            'controller' => 'ContactsController',
            'method' => 'update',
            'methods' => ['POST']
        ],
        'contacts/delete' => [
            'controller' => 'ContactsController',
            'method' => 'delete',
            'methods' => ['POST']
        ],
        'contacts/lists' => [
            'controller' => 'ContactsController',
            'method' => 'lists',
            'methods' => ['GET']
        ],
        'contacts/import' => [
            'controller' => 'ContactsController',
            'method' => 'import',
            'methods' => ['GET', 'POST']
        ],
        'contacts/export' => [
            'controller' => 'ContactsController',
            'method' => 'export',
            'methods' => ['GET']
        ],
        'contacts/candidates' => [
            'controller' => 'ContactsController',
            'method' => 'candidates',
            'methods' => ['GET']
        ],
        'contacts/saveCandidates' => [
            'controller' => 'ContactsController',
            'method' => 'saveCandidates',
            'methods' => ['POST']
        ],
        
        // Campaigns
        'campaigns/index' => [
            'controller' => 'CampaignsController',
            'method' => 'index',
            'methods' => ['GET']
        ],
        'campaigns/create' => [
            'controller' => 'CampaignsController',
            'method' => 'create',
            'methods' => ['GET', 'POST']
        ],
        'campaigns/store' => [
            'controller' => 'CampaignsController',
            'method' => 'store',
            'methods' => ['POST']
        ],
        'campaigns/edit' => [
            'controller' => 'CampaignsController',
            'method' => 'edit',
            'methods' => ['GET', 'POST']
        ],
        'campaigns/update' => [
            'controller' => 'CampaignsController',
            'method' => 'update',
            'methods' => ['POST']
        ],
        'campaigns/delete' => [
            'controller' => 'CampaignsController',
            'method' => 'delete',
            'methods' => ['POST']
        ],
        'campaigns/run' => [
            'controller' => 'CampaignsController',
            'method' => 'run',
            'methods' => ['POST']
        ],
        
        // Groups
        'groups/index' => [
            'controller' => 'GroupsController',
            'method' => 'index',
            'methods' => ['GET']
        ],
        'groups/extractParticipants' => [
            'controller' => 'GroupsController',
            'method' => 'extractParticipants',
            'methods' => ['POST']
        ],
        
        // Debug/Logs
        'debug/index' => [
            'controller' => 'DebugController',
            'method' => 'index',
            'methods' => ['GET']
        ],
        'debug/webhooks' => [
            'controller' => 'DebugController',
            'method' => 'webhooks',
            'methods' => ['GET']
        ],
        'debug/cron' => [
            'controller' => 'DebugController',
            'method' => 'cron',
            'methods' => ['GET']
        ],
        'debug/audit' => [
            'controller' => 'DebugController',
            'method' => 'audit',
            'methods' => ['GET']
        ],
        'debug/testSend' => [
            'controller' => 'DebugController',
            'method' => 'testSend',
            'methods' => ['POST']
        ],
        
        // Webhooks
        'webhook/evolution' => [
            'controller' => 'WebhookController',
            'method' => 'evolution',
            'methods' => ['POST']
        ],
    ];
    
    public function dispatch($route)
    {
        if (!isset($this->routes[$route])) {
            $this->renderError(404, "Page not found");
            return;
        }

        $routeConfig = $this->routes[$route];
        $controllerName = $routeConfig['controller'] ?? null;
        $method = $routeConfig['method'] ?? null;
        $allowedMethods = $routeConfig['methods'] ?? ['GET'];

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($requestMethod, $allowedMethods, true)) {
            http_response_code(405);
            header('Allow: ' . implode(', ', $allowedMethods));
            $this->renderError(405, "Method not allowed");
            return;
        }

        $controllerClass = "App\\Controllers\\{$controllerName}";
        
        if (!class_exists($controllerClass)) {
            $message = defined('DEBUG') && DEBUG ? "Controller not found: $controllerClass" : "Controller not found";
            $this->renderError(500, $message);
            return;
        }
        
        $controller = new $controllerClass();
        
        if (!method_exists($controller, $method)) {
            $message = defined('DEBUG') && DEBUG ? "Method not found: $method in $controllerClass" : "Method not found";
            $this->renderError(500, $message);
            return;
        }
        
        $controller->$method();
    }

    private function renderError($code, $message)
    {
        http_response_code($code);
        if (defined('DEBUG') && DEBUG) {
            echo $message;
            return;
        }

        echo "An error occurred";
    }
}
