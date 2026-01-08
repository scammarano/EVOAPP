<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\CronLog;

class CronController
{
    public function index()
    {
        if (!Auth::hasPermission('cron.view')) {
            View::set('error', 'No tienes permisos para ver logs del cron');
            View::render('error/403');
            return;
        }

        $page = (int)($_GET['page'] ?? 1);
        $limit = 50;
        $offset = ($page - 1) * $limit;

        // Get filters
        $filters = [
            'job_key' => $_GET['job_key'] ?? null,
            'status' => $_GET['status'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];

        $logs = CronLog::getPaginated($offset, $limit, $filters);
        $total = CronLog::getCount($filters);

        View::set('logs', $logs);
        View::set('total', $total);
        View::set('page', $page);
        View::set('limit', $limit);
        View::set('filters', $filters);
        View::render('cron/index');
    }

    public function export()
    {
        if (!Auth::hasPermission('cron.export')) {
            View::set('error', 'No tienes permisos para exportar logs del cron');
            View::render('error/403');
            return;
        }

        $filters = [
            'job_key' => $_GET['job_key'] ?? null,
            'status' => $_GET['status'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];

        $logs = CronLog::getAll($filters);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="cron_logs_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Header
        fputcsv($output, [
            'ID',
            'Job',
            'Estado',
            'Inicio',
            'Fin',
            'Duración',
            'Resumen',
            'Fecha'
        ]);
        
        foreach ($logs as $log) {
            $duration = '';
            if ($log['started_at'] && $log['finished_at']) {
                $start = new \DateTime($log['started_at']);
                $end = new \DateTime($log['finished_at']);
                $duration = $start->diff($end)->format('%H:%M:%S');
            }
            
            fputcsv($output, [
                $log['id'],
                $log['job_key'],
                $log['status'],
                $log['started_at'] ?? 'N/A',
                $log['finished_at'] ?? 'N/A',
                $duration,
                $log['summary'] ?? 'N/A',
                $log['started_at'] ?? 'N/A'
            ]);
        }
        
        fclose($output);
        readfile('php://output');
        exit;
    }

    public function clear()
    {
        if (!Auth::hasPermission('cron.manage')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $days = (int)($_POST['days'] ?? 30);
        
        try {
            $deleted = CronLog::clearOld($days);
            
            // Log action
            Auth::logAction('clear_cron_logs', 'cron_log', null, ['days' => $days, 'deleted_count' => $deleted]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Se eliminaron {$deleted} logs antiguos (más de {$days} días)"
            ]);
            
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al limpiar logs: ' . $e->getMessage()]);
        }
    }
}
