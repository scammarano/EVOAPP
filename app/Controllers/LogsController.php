<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;

class LogsController
{
    public function index()
    {
        if (!Auth::isLoggedIn()) {
            header('Location: index.php?r=auth/login');
            exit;
        }
        
        View::set('user', Auth::getCurrentUser());
        View::render('logs/index');
    }
    
    public function getPhpErrors()
    {
        header('Content-Type: application/json');
        
        if (!Auth::isLoggedIn()) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $logFile = $this->getPhpErrorLogPath();
        $logs = $this->readLogFile($logFile, 100); // Últimas 100 líneas
        $allLogs = $this->readLogFile($logFile, 10000); // Todos los logs para conteo
        
        // Contar errores por tipo
        $errorCounts = [
            'ERROR' => 0,
            'WARNING' => 0,
            'NOTICE' => 0,
            'FATAL' => 0,
            'UNKNOWN' => 0
        ];
        
        foreach ($allLogs as $log) {
            $level = strtoupper($log['level']);
            if (isset($errorCounts[$level])) {
                $errorCounts[$level]++;
            } else {
                $errorCounts['UNKNOWN']++;
            }
        }
        
        echo json_encode([
            'success' => true,
            'logs' => $logs,
            'file' => $logFile,
            'count' => count($logs),
            'total_count' => count($allLogs),
            'error_counts' => $errorCounts,
            'summary' => [
                'total_errors' => array_sum($errorCounts),
                'critical_errors' => $errorCounts['ERROR'] + $errorCounts['FATAL'],
                'warnings' => $errorCounts['WARNING'],
                'notices' => $errorCounts['NOTICE']
            ]
        ]);
    }
    
    public function getWebhookLogs()
    {
        header('Content-Type: application/json');
        
        if (!Auth::isLoggedIn()) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        try {
            // Conectar a la base de datos
            $pdo = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // Obtener webhooks pendientes
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    instance_id,
                    event_type,
                    status,
                    created_at,
                    processed_at,
                    error_message,
                    retry_count,
                    data
                FROM webhook_events 
                WHERE status = 'pending' OR status = 'failed'
                ORDER BY created_at DESC 
                LIMIT 50
            ");
            $stmt->execute();
            $webhooks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Obtener estadísticas
            $stmt = $pdo->prepare("
                SELECT 
                    status,
                    COUNT(*) as count
                FROM webhook_events 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY status
            ");
            $stmt->execute();
            $stats = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'webhooks' => $webhooks,
                'stats' => $stats,
                'count' => count($webhooks)
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function getSystemInfo()
    {
        header('Content-Type: application/json');
        
        if (!Auth::isLoggedIn()) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $info = [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'error_log' => ini_get('error_log'),
            'log_errors' => ini_get('log_errors'),
            'display_errors' => ini_get('display_errors'),
            'error_reporting' => $this->getErrorReportingLevel(),
            'current_time' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
            'app_constants' => [
                'DB_HOST' => defined('DB_HOST') ? '***' : 'NOT_DEFINED',
                'DB_NAME' => defined('DB_NAME') ? DB_NAME : 'NOT_DEFINED',
                'EVO_BASE_URL' => defined('EVO_BASE_URL') ? EVO_BASE_URL : 'NOT_DEFINED',
                'APP_NAME' => defined('APP_NAME') ? APP_NAME : 'NOT_DEFINED',
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'info' => $info
        ]);
    }
    
    public function clearPhpErrors()
    {
        header('Content-Type: application/json');
        
        if (!Auth::isLoggedIn()) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $logFile = $this->getPhpErrorLogPath();
        $result = $this->clearLogFile($logFile);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'PHP error log cleared successfully' : 'Failed to clear PHP error log'
        ]);
    }
    
    public function retryFailedWebhooks()
    {
        header('Content-Type: application/json');
        
        if (!Auth::isLoggedIn()) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        try {
            $pdo = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // Resetear webhooks pendientes y fallidos
            $stmt = $pdo->prepare("
                UPDATE webhook_events 
                SET status = 'pending', 
                    retry_count = 0, 
                    error_message = NULL, 
                    processed_at = NULL
                WHERE status IN ('pending', 'failed')
            ");
            $affected = $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => "Reset $affected webhook events to pending status",
                'affected' => $affected
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function exportPhpErrors()
    {
        if (!Auth::isLoggedIn()) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $logFile = $this->getPhpErrorLogPath();
        $logs = $this->readLogFile($logFile, 10000); // Todos los logs
        
        // Crear CSV
        $csvContent = "Timestamp,Level,Message,Raw Line\n";
        
        foreach ($logs as $log) {
            $timestamp = $log['timestamp'];
            $level = $log['level'];
            $message = str_replace(["\n", "\r", "\t", '"'], [' ', ' ', ' ', '""'], $log['message']);
            $raw = str_replace(["\n", "\r", "\t", '"'], [' ', ' ', ' ', '""'], $log['raw']);
            
            $csvContent .= "\"$timestamp\",\"$level\",\"$message\",\"$raw\"\n";
        }
        
        // Configurar headers para descarga
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="php_errors_' . date('Y-m-d_H-i-s') . '.csv"');
        header('Content-Length: ' . strlen($csvContent));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        echo $csvContent;
        exit;
    }
    
    public function exportDatabaseErrors()
    {
        if (!Auth::isLoggedIn()) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        try {
            $pdo = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // Buscar errores recientes en diferentes tablas
            $csvContent = "Timestamp,Table,Error,SQL,File,Line\n";
            
            // Buscar en tabla de chats (error del CSV anterior)
            try {
                $stmt = $pdo->query("
                    SELECT 
                        NOW() as timestamp,
                        'chats' as table_name,
                        'Column not found' as error_type,
                        CONCAT('Unknown column ', COLUMN_NAME) as error_detail,
                        TABLE_NAME as sql_query,
                        'Unknown' as file_name,
                        'Unknown' as line_number
                    FROM information_schema.COLUMNS 
                    WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
                    AND COLUMN_NAME = 'created_at'
                    AND TABLE_NAME LIKE '%chat%'
                    LIMIT 10
                ");
                
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $timestamp = $row['timestamp'];
                    $table = $row['table_name'];
                    $error = $row['error_type'] . ': ' . $row['error_detail'];
                    $sql = $row['sql_query'];
                    $file = $row['file_name'];
                    $line = $row['line_number'];
                    
                    $csvContent .= "\"$timestamp\",\"$table\",\"$error\",\"$sql\",\"$file\",\"$line\"\n";
                }
            } catch (\Exception $e) {
                $csvContent .= "\"".date('Y-m-d H:i:s')."\",\"database\",\"Error getting database errors: ".$e->getMessage()."\",\"\",\"\",\"\"\n";
            }
            
            // Configurar headers para descarga
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="database_errors_' . date('Y-m-d_H-i-s') . '.csv"');
            header('Content-Length: ' . strlen($csvContent));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            echo $csvContent;
            exit;
            
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function getPhpErrorLogPath()
    {
        // Intentar diferentes ubicaciones comunes de logs
        $possiblePaths = [
            ini_get('error_log'),
            '/var/log/php_errors.log',
            '/var/log/apache2/error.log',
            '/var/log/nginx/error.log',
            '/usr/local/cpanel/logs/error_log',
            sys_get_temp_dir() . '/php_errors.log',
            $_SERVER['DOCUMENT_ROOT'] . '/../logs/php_errors.log',
            $_SERVER['DOCUMENT_ROOT'] . '/logs/php_errors.log',
        ];
        
        foreach ($possiblePaths as $path) {
            if (!empty($path) && file_exists($path) && is_readable($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    private function readLogFile($logFile, $lines = 100)
    {
        if (!$logFile || !file_exists($logFile)) {
            return [['timestamp' => date('Y-m-d H:i:s'), 'level' => 'ERROR', 'message' => 'Log file not found: ' . ($logFile ?? 'NULL')]];
        }
        
        $logs = [];
        $handle = fopen($logFile, 'r');
        
        if (!$handle) {
            return [['timestamp' => date('Y-m-d H:i:s'), 'level' => 'ERROR', 'message' => 'Cannot read log file: ' . $logFile]];
        }
        
        // Leer las últimas N líneas
        $buffer = [];
        while (($line = fgets($handle)) !== false) {
            $buffer[] = trim($line);
            if (count($buffer) > $lines) {
                array_shift($buffer);
            }
        }
        fclose($handle);
        
        // Parsear cada línea
        foreach ($buffer as $line) {
            if (empty($line)) continue;
            
            $parsed = $this->parseLogLine($line);
            if ($parsed) {
                $logs[] = $parsed;
            }
        }
        
        return array_reverse($logs); // Más recientes primero
    }
    
    private function parseLogLine($line)
    {
        // Patrones comunes de logs PHP
        $patterns = [
            // Apache/Nginx style: [Mon Jan 01 00:00:00 2025] PHP Fatal error: message
            '/^\[([^\]]+)\]\s+PHP\s+(\w+):\s*(.+)$/',
            // PHP-FPM style: [01-Jan-2025 00:00:00] PHP Fatal error: message
            '/^\[([^\]]+)\]\s+PHP\s+(\w+):\s*(.+)$/',
            // Simple format: 2025-01-01 00:00:00 ERROR: message
            '/^(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s+(\w+):\s*(.+)$/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line, $matches)) {
                return [
                    'timestamp' => $matches[1],
                    'level' => strtoupper($matches[2]),
                    'message' => $matches[3],
                    'raw' => $line
                ];
            }
        }
        
        // Si no coincide con ningún patrón, devolver línea cruda
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'UNKNOWN',
            'message' => $line,
            'raw' => $line
        ];
    }
    
    private function clearLogFile($logFile)
    {
        if (!$logFile || !file_exists($logFile)) {
            return false;
        }
        
        return file_put_contents($logFile, '') !== false;
    }
    
    private function getErrorReportingLevel()
    {
        $level = ini_get('error_reporting');
        $levels = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            E_ALL => 'E_ALL'
        ];
        
        $result = [];
        foreach ($levels as $const => $name) {
            if ($level & $const) {
                $result[] = $name;
            }
        }
        
        return implode(' | ', $result);
    }
}
