<?php
namespace App\Models;

use App\Core\DB;

class AuditLog
{
    public static function getPaginated($offset = 0, $limit = 50, $filters = [])
    {
        $table = 'audit_log';
        $where = [];
        $params = [];

        // Build WHERE clause
        if (!empty($filters['user_id'])) {
            $where[] = "al.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = "al.action = ?";
            $params[] = $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = "al.entity_type = ?";
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "al.created_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "al.created_at <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT al.*, u.name as user_name
            FROM {$table} al
            LEFT JOIN users u ON al.user_id = u.id
            {$whereClause}
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;

        return DB::fetchAll($sql, $params);
    }

    public static function getCount($filters = [])
    {
        $table = 'audit_log';
        $where = [];
        $params = [];

        // Build WHERE clause
        if (!empty($filters['user_id'])) {
            $where[] = "al.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = "al.action = ?";
            $params[] = $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = "al.entity_type = ?";
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "al.created_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "al.created_at <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT COUNT(*) as total FROM {$table} al {$whereClause}";

        return DB::fetch($sql, $params)['total'];
    }

    public static function getAll($filters = [])
    {
        $table = 'audit_log';
        $where = [];
        $params = [];

        // Build WHERE clause
        if (!empty($filters['user_id'])) {
            $where[] = "al.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = "al.action = ?";
            $params[] = $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = "al.entity_type = ?";
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "al.created_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "al.created_at <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT al.*, u.name as user_name
            FROM {$table} al
            LEFT JOIN users u ON al.user_id = u.id
            {$whereClause}
            ORDER BY al.created_at DESC
        ";

        return DB::fetchAll($sql, $params);
    }
}
