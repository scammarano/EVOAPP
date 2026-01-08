<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\AuditLog;

class AuditController
{
    public function index()
    {
        if (!Auth::hasPermission('audit.view')) {
            View::set('error', 'No tienes permisos para ver logs de auditoría');
            View::render('error/403');
            return;
        }

        $page = (int)($_GET['page'] ?? 1);
        $limit = 50;
        $offset = ($page - 1) * $limit;

        // Get filters
        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'action' => $_GET['action'] ?? null,
            'entity_type' => $_GET['entity_type'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];

        $logs = AuditLog::getPaginated($offset, $limit, $filters);
        $total = AuditLog::getCount($filters);

        View::set('logs', $logs);
        View::set('total', $total);
        View::set('page', $page);
        View::set('limit', $limit);
        View::set('filters', $filters);
        View::render('audit/index');
    }

    public function export()
    {
        if (!Auth::hasPermission('audit.export')) {
            View::set('error', 'No tienes permisos para exportar logs');
            View::render('error/403');
            return;
        }

        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'action' => $_GET['action'] ?? null,
            'entity_type' => $_GET['entity_type'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];

        $logs = AuditLog::getAll($filters);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Header
        fputcsv($output, [
            'ID',
            'Usuario',
            'Acción',
            'Entidad',
            'ID Entidad',
            'Antes',
            'Después',
            'IP',
            'Fecha'
        ]);
        
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['user_name'] ?? 'N/A',
                $log['action'],
                $log['entity_type'] ?? 'N/A',
                $log['entity_id'] ?? 'N/A',
                $log['before_json'] ?? 'N/A',
                $log['after_json'] ?? 'N/A',
                $log['ip_address'] ?? 'N/A',
                $log['created_at']
            ]);
        }
        
        fclose($output);
        readfile('php://output');
        exit;
    }
}
