<?php
namespace App\Models;

use App\Core\DB;

class User
{
    public static function findByEmail($email)
    {
        return DB::fetch("SELECT * FROM users WHERE email = ?", [$email]);
    }
    
    public static function findById($id)
    {
        return DB::fetch("SELECT * FROM users WHERE id = ?", [$id]);
    }
    
    public static function getRoles($userId)
    {
        $roles = DB::fetchAll("
            SELECT r.name 
            FROM roles r
            JOIN user_roles ur ON r.id = ur.role_id
            WHERE ur.user_id = ?
        ", [$userId]);
        
        return array_column($roles, 'name');
    }

    public static function getRoleIds($userId)
    {
        $rows = DB::fetchAll(
            "SELECT role_id FROM user_roles WHERE user_id = ?",
            [$userId]
        );

        return array_map('intval', array_column($rows, 'role_id'));
    }
    
    public static function getPermissions($userId)
    {
        $permissions = DB::fetchAll("
            SELECT p.key 
            FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            JOIN user_roles ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = ?
        ", [$userId]);
        
        return array_column($permissions, 'key');
    }
    
    public static function canAccessInstance($userId, $instanceId)
    {
        $access = DB::fetch("
            SELECT 1 FROM user_instances 
            WHERE user_id = ? AND instance_id = ? AND can_view = 1
        ", [$userId, $instanceId]);
        
        return !empty($access);
    }
    
    public static function getAccessibleInstances($userId)
    {
        if (in_array('admin', self::getRoles($userId))) {
            return DB::fetchAll("SELECT * FROM instances WHERE is_active = 1 ORDER BY slug");
        }
        
        return DB::fetchAll("
            SELECT i.* 
            FROM instances i
            JOIN user_instances ui ON i.id = ui.instance_id
            WHERE ui.user_id = ? AND ui.can_view = 1 AND i.is_active = 1
            ORDER BY i.slug
        ", [$userId]);
    }
    
    public static function updateLastLogin($userId)
    {
        DB::q("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$userId]);
    }
    
    public static function create($data)
    {
        DB::q("
            INSERT INTO users (email, password_hash, name, is_active, created_at, updated_at)
            VALUES (?, ?, ?, 1, NOW(), NOW())
        ", [
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['name']
        ]);
        
        $userId = DB::lastInsertId();
        
        // Assign roles
        if (!empty($data['roles'])) {
            foreach ($data['roles'] as $roleId) {
                DB::q("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)", [$userId, $roleId]);
            }
        }
        
        return $userId;
    }
    
    public static function update($userId, $data)
    {
        $fields = [];
        $params = [];
        
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $params[] = $data['email'];
        }
        
        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $params[] = $data['name'];
        }
        
        if (isset($data['is_active'])) {
            $fields[] = "is_active = ?";
            $params[] = $data['is_active'];
        }
        
        if (!empty($data['password'])) {
            $fields[] = "password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (!empty($fields)) {
            $fields[] = "updated_at = NOW()";
            $params[] = $userId;
            
            DB::q("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?", $params);
        }
        
        // Update roles
        if (isset($data['roles'])) {
            DB::q("DELETE FROM user_roles WHERE user_id = ?", [$userId]);
            foreach ($data['roles'] as $roleId) {
                DB::q("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)", [$userId, $roleId]);
            }
        }
    }
    
    public static function delete($userId)
    {
        DB::q("DELETE FROM users WHERE id = ?", [$userId]);
    }
    
    public static function getAll($page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        
        return DB::fetchAll("
            SELECT u.*, GROUP_CONCAT(r.name) as roles
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            GROUP BY u.id
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?
        ", [$limit, $offset]);
    }
    
    public static function count()
    {
        return DB::fetch("SELECT COUNT(*) as count FROM users")['count'];
    }
}
