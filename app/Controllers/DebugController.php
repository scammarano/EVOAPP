<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\WebhookEvent;
use App\Models\Instance;
use App\Models\Campaign;

class DebugController
{
    public function index()
    {
        if (!Auth::hasPermission('logs.view')) {
            View::set('error', 'No tienes permisos para ver logs');
            View::render('error/403');
            return;
        }

        $instanceSlug = $_GET['instance'] ?? '';
        $instance = null;
        
        if ($instanceSlug) {
            $instance = Instance::findBySlug($instanceSlug);
            if (!$instance || !Auth::canViewInstance($instance['id'])) {
                View::set('error', 'Instancia no encontrada o sin permisos');
                View::render('error/404');
                return;
            }
        }

        // Get overview stats
        $webhookStats = WebhookEvent::getStats($instance['id'] ?? null);
        $cronStats = $this->getCronStats();
        $recentErrors = $this->getRecentErrors($instance['id'] ?? null);

        View::set('instance', $instance);
        View::set('webhookStats', $webhookStats);
        View::set('cronStats', $cronStats);
        View::set('recentErrors', $recentErrors);
        View::render('debug/index');
    }

    public function webhooks()
    {
        if (!Auth::hasPermission('logs.view')) {
            View::set('error', 'No tienes permisos para ver logs');
            View::render('error/403');
            return;
        }

        $instanceSlug = $_GET['instance'] ?? '';
        $instance = null;
        
        if ($instanceSlug) {
            $instance = Instance::findBySlug($instanceSlug);
            if (!$instance || !Auth::canViewInstance($instance['id'])) {
                View::set('error', 'Instancia no encontrada o sin permisos');
                View::render('error/404');
                return;
            }
        }

        $page = (int)($_GET['page'] ?? 1);
        $events = WebhookEvent::getRecent($instance['id'] ?? null, 50);
        $stats = WebhookEvent::getStats($instance['id'] ?? null);

        View::set('instance', $instance);
        View::set('events', $events);
        View::set('stats', $stats);
        View::set('page', $page);
        View::render('debug/webhooks');
    }

    public function cron()
    {
        if (!Auth::hasPermission('logs.view')) {
            View::set('error', 'No tienes permisos para ver logs');
            View::render('error/403');
            return;
        }

        $page = (int)($_GET['page'] ?? 1);
        $logs = $this->getCronLogs($page, 50);
        $stats = $this->getCronStats();

        View::set('logs', $logs);
        View::set('stats', $stats);
        View::set('page', $page);
        View::render('debug/cron');
    }

    public function audit()
    {
        if (!Auth::hasPermission('audit.view')) {
            View::set('error', 'No tienes permisos para ver auditoría');
            View::render('error/403');
            return;
        }

        $page = (int)($_GET['page'] ?? 1);
        $logs = $this->getAuditLogs($page, 50);

        View::set('logs', $logs);
        View::set('page', $page);
        View::render('debug/audit');
    }

    public function testSend()
    {
        if (!Auth::hasPermission('debug.test')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $instanceSlug = $_POST['instance'] ?? '';
        $number = $_POST['number'] ?? '';
        $message = $_POST['message'] ?? 'Test message from EVOAPP';

        if (!$instanceSlug || !$number) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Instance and number required']);
            return;
        }

        $instance = Instance::findBySlug($instanceSlug);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        try {
            $client = Instance::evoClient($instance);
            $result = $client->sendText($instanceSlug, $number, $message);

            // Log action
            Auth::logAction('test_send', 'test', null, null, [
                'instance_id' => $instance['id'],
                'number' => $number,
                'message' => $message,
                'result' => $result
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Mensaje de prueba enviado correctamente',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al enviar mensaje: ' . $e->getMessage()]);
        }
    }

    private function getCronLogs($page = 1, $limit = 50)
    {
        $offset = ($page - 1) * $limit;
        
        return App\Core\DB::fetchAll("
            SELECT * FROM cron_log
            ORDER BY started_at DESC
            LIMIT ? OFFSET ?
        ", [$limit, $offset]);
    }

    private function getCronStats()
    {
        return App\Core\DB::fetch("
            SELECT 
                COUNT(*) as total_runs,
                COUNT(CASE WHEN ok = 1 THEN 1 END) as successful_runs,
                COUNT(CASE WHEN ok = 0 THEN 1 END) as failed_runs,
                COUNT(CASE WHEN DATE(started_at) = CURDATE() THEN 1 END) as today_runs,
                MAX(started_at) as last_run
            FROM cron_log
        ");
    }

    private function getAuditLogs($page = 1, $limit = 50)
    {
        $offset = ($page - 1) * $limit;
        
        return App\Core\DB::fetchAll("
            SELECT 
                al.*,
                u.name as user_name,
                u.email as user_email
            FROM audit_log al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ", [$limit, $offset]);
    }

    private function getRecentErrors($instanceId = null)
    {
        $sql = "
            SELECT 'webhook' as source, error_text, created_at
            FROM webhook_events
            WHERE status = 'error'
        ";
        
        $params = [];
        
        if ($instanceId) {
            $sql .= " AND instance_id = ?";
            $params[] = $instanceId;
        }
        
        $sql .= "
            UNION ALL
            SELECT 'cron' as source, error_text, started_at as created_at
            FROM cron_log
            WHERE ok = 0
            ORDER BY created_at DESC
            LIMIT 10
        ";
        
        return App\Core\DB::fetchAll($sql, $params);
    }
}
