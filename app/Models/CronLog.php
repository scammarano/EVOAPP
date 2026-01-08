<?php
namespace App\Models;

use App\Core\DB;

class CronLog
{
    public static function getPaginated($offset = 0, $limit = 50, $filters = [])
    {
        $table = 'cron_log';
        $where = [];
        $params = [];

        // Build WHERE clause
        if (!empty($filters['job_key'])) {
            $where[] = "cl.job_key = ?";
            $params[] = $filters['job_key'];
        }

        if (!empty($filters['status'])) {
            $where[] = "cl.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "cl.started_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "cl.started_at <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT *
            FROM {$table} cl
            {$whereClause}
            ORDER BY cl.started_at DESC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;

        return DB::fetchAll($sql, $params);
    }

    public static function getCount($filters = [])
    {
        $table = 'cron_log';
        $where = [];
        $params = [];

        // Build WHERE clause
        if (!empty($filters['job_key'])) {
            $where[] = "cl.job_key = ?";
            $params[] = $filters['job_key'];
        }

        if (!empty($filters['status'])) {
            $where[] = "cl.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "cl.started_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "cl.started_at <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT COUNT(*) as total FROM {$table} cl {$whereClause}";

        return DB::fetch($sql, $params)['total'];
    }

    public static function getAll($filters = [])
    {
        $table = 'cron_log';
        $where = [];
        $params = [];

        // Build WHERE clause
        if (!empty($filters['job_key'])) {
            $where[] = "cl.job_key = ?";
            $params[] = $filters['job_key'];
        }

        if (!empty($filters['status'])) {
            $where[] = "cl.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "cl.started_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "cl.started_at <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT *
            FROM {$table} cl
            {$whereClause}
            ORDER BY cl.started_at DESC
        ";

        return DB::fetchAll($sql, $params);
    }

    public static function clearOld($days = 30)
    {
        $table = 'cron_log';
        $sql = "DELETE FROM {$table} WHERE started_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        DB::q($sql, [$days]);
        return DB::lastInsertId();
    }
}
