<?php
namespace App\Core;

class Router
{
    private $routes = [
        // Auth routes
        'auth/login' => ['AuthController', 'login'],
        'auth/logout' => ['AuthController', 'logout'],
        
        // Dashboard
        'dashboard/index' => ['DashboardController', 'index'],
        
        // Instances
        'instances/index' => ['InstancesController', 'index'],
        'instances/create' => ['InstancesController', 'create'],
        'instances/store' => ['InstancesController', 'store'],
        'instances/edit' => ['InstancesController', 'edit'],
        'instances/update' => ['InstancesController', 'update'],
        'instances/delete' => ['InstancesController', 'delete'],
        'instances/view' => ['InstancesController', 'view'],
        
        // Inbox
        'inbox/index' => ['InboxController', 'index'],
        'inbox/chats' => ['InboxController', 'chatsAjax'],
        'inbox/messages' => ['InboxController', 'messages'],
        'inbox/send' => ['InboxController', 'sendAjax'],
        'inbox/sendMedia' => ['InboxController', 'sendMediaAjax'],
        'inbox/markRead' => ['InboxController', 'markRead'],
        
        // Contacts
        'contacts/index' => ['ContactsController', 'index'],
        'contacts/create' => ['ContactsController', 'create'],
        'contacts/store' => ['ContactsController', 'store'],
        'contacts/edit' => ['ContactsController', 'edit'],
        'contacts/update' => ['ContactsController', 'update'],
        'contacts/delete' => ['ContactsController', 'delete'],
        'contacts/lists' => ['ContactsController', 'lists'],
        'contacts/import' => ['ContactsController', 'import'],
        'contacts/export' => ['ContactsController', 'export'],
        'contacts/candidates' => ['ContactsController', 'candidates'],
        'contacts/saveCandidates' => ['ContactsController', 'saveCandidates'],
        
        // Campaigns
        'campaigns/index' => ['CampaignsController', 'index'],
        'campaigns/create' => ['CampaignsController', 'create'],
        'campaigns/store' => ['CampaignsController', 'store'],
        'campaigns/edit' => ['CampaignsController', 'edit'],
        'campaigns/update' => ['CampaignsController', 'update'],
        'campaigns/delete' => ['CampaignsController', 'delete'],
        'campaigns/run' => ['CampaignsController', 'run'],
        
        // Groups
        'groups/index' => ['GroupsController', 'index'],
        'groups/extractParticipants' => ['GroupsController', 'extractParticipants'],
        
        // Debug/Logs
        'debug/index' => ['DebugController', 'index'],
        'debug/webhooks' => ['DebugController', 'webhooks'],
        'debug/cron' => ['DebugController', 'cron'],
        'debug/audit' => ['DebugController', 'audit'],
        'debug/testSend' => ['DebugController', 'testSend'],
        
        // Webhooks
        'webhook/evolution' => ['WebhookController', 'evolution'],
    ];
    
    public function dispatch($route)
    {
        if (!isset($this->routes[$route])) {
            http_response_code(404);
            echo "Page not found: $route";
            return;
        }
        
        [$controllerName, $method] = $this->routes[$route];
        $controllerClass = "App\\Controllers\\{$controllerName}";
        
        if (!class_exists($controllerClass)) {
            http_response_code(500);
            echo "Controller not found: $controllerClass";
            return;
        }
        
        $controller = new $controllerClass();
        
        if (!method_exists($controller, $method)) {
            http_response_code(500);
            echo "Method not found: $method in $controllerClass";
            return;
        }
        
        $controller->$method();
    }
}
