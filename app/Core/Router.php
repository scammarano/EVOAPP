<?php
namespace App\Core;

class Router
{
    private $routes = [
        // Auth routes
        'auth/login' => ['AuthController', 'login'],
        'auth/logout' => ['AuthController', 'logout'],

        // Admin
        'admin/users' => ['AdminUsersController', 'index'],
        'admin/users/create' => ['AdminUsersController', 'create'],
        'admin/users/store' => ['AdminUsersController', 'store'],
        'admin/users/edit' => ['AdminUsersController', 'edit'],
        'admin/users/update' => ['AdminUsersController', 'update'],
        'admin/users/toggleActive' => ['AdminUsersController', 'toggleActive'],

        'admin/roles' => ['AdminRolesController', 'index'],
        'admin/roles/create' => ['AdminRolesController', 'create'],
        'admin/roles/store' => ['AdminRolesController', 'store'],
        'admin/roles/edit' => ['AdminRolesController', 'edit'],
        'admin/roles/update' => ['AdminRolesController', 'update'],
        
        // Dashboard
        'dashboard/index' => ['DashboardController', 'index'],
        
        // Instances
        'instances/index' => ['InstancesController', 'index'],
        'instances/create' => ['InstancesController', 'create'],
        'instances/store' => ['InstancesController', 'store'],
        'instances/edit' => ['InstancesController', 'edit'],
        'instances/update' => ['InstancesController', 'update'],
        'instances/regenerateQR' => ['InstancesController', 'regenerateQR'],
        'instances/refreshStatus' => ['InstancesController', 'refreshStatus'],
        'instances/view' => ['InstancesController', 'view'],
        
        // Inbox
        'inbox/index' => ['InboxController', 'index'],
        'inbox/chats' => ['InboxController', 'chatsAjax'],
        'inbox/messages' => ['InboxController', 'messages'],
        'inbox/send' => ['InboxController', 'sendAjax'],
        'inbox/sendMedia' => ['InboxController', 'sendMediaAjax'],
        'inbox/sendEmoji' => ['InboxController', 'sendEmojiAjax'],
        'inbox/markRead' => ['InboxController', 'markRead'],
        'inbox/refreshStatus' => ['InboxController', 'refreshStatus'],
        'inbox/stats' => ['InboxController', 'statsAjax'],
        
        // Contacts
        'contacts/index' => ['ContactsController', 'index'],
        'contacts/create' => ['ContactsController', 'create'],
        'contacts/store' => ['ContactsController', 'store'],
        'contacts/edit' => ['ContactsController', 'edit'],
        'contacts/update' => ['ContactsController', 'update'],
        'contacts/delete' => ['ContactsController', 'delete'],
        'contacts/lists' => ['ContactsSyncController', 'lists'],
        'contacts/createList' => ['ContactsSyncController', 'createList'],
        'contacts/storeList' => ['ContactsSyncController', 'storeList'],
        'contacts/editList' => ['ContactsSyncController', 'editList'],
        'contacts/updateList' => ['ContactsSyncController', 'updateList'],
        'contacts/deleteList' => ['ContactsSyncController', 'deleteList'],
        'contacts/sync' => ['ContactsSyncController', 'index'],
        'contacts/syncContacts' => ['ContactsSyncController', 'syncContacts'],
        'contacts/syncGroups' => ['ContactsSyncController', 'syncGroups'],
        'contacts/syncAll' => ['ContactsSyncController', 'syncAll'],
        'contacts/import' => ['ContactsController', 'import'],
        'contacts/export' => ['ContactsController', 'export'],
        
        // Audit & Monitoring
        'audit/index' => ['AuditController', 'index'],
        'audit/export' => ['AuditController', 'export'],
        'cron/index' => ['CronController', 'index'],
        'cron/export' => ['CronController', 'export'],
        'cron/clear' => ['CronController', 'clear'],
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
        
        // Diagnostic
        'diagnostic/index' => ['DiagnosticController', 'index'],
        'diagnostic/testInstance' => ['DiagnosticController', 'testInstance'],
        'diagnostic/testAll' => ['DiagnosticController', 'testAll'],
        
        // Webhooks
        'webhook/evolution' => ['WebhookController', 'evolution'],
        'webhook/events' => ['WebhookController', 'events'],
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
        
        // Extract parameters from URL for specific routes
        $params = $this->extractParameters($route);
        
        // Call method with parameters if available
        if (!empty($params)) {
            $controller->$method(...$params);
        } else {
            $controller->$method();
        }
    }
    
    private function extractParameters($route)
    {
        $params = [];
        
        // Extract ID from URL for update routes
        if ($route === 'instances/update') {
            $id = $_GET['id'] ?? $_POST['id'] ?? null;
            if ($id !== null) {
                $params[] = (int)$id;
            }
        }
        
        if ($route === 'instances/edit') {
            $id = $_GET['id'] ?? null;
            if ($id !== null) {
                $params[] = (int)$id;
            }
        }
        
        if ($route === 'contacts/edit') {
            $id = $_GET['id'] ?? null;
            if ($id !== null) {
                $params[] = (int)$id;
            }
        }
        
        if ($route === 'campaigns/edit') {
            $id = $_GET['id'] ?? null;
            if ($id !== null) {
                $params[] = (int)$id;
            }
        }
        
        return $params;
    }
}
