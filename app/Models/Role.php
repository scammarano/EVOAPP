<?php
namespace App\Models;

use App\Core\DB;

class Role
{
    public static function getAll()
    {
        return DB::fetchAll("SELECT * FROM roles ORDER BY name");
    }

    public static function findById($id)
    {
        return DB::fetch("SELECT * FROM roles WHERE id = ?", [$id]);
    }

    public static function create($data)
    {
        DB::q(
            "INSERT INTO roles (name, description) VALUES (?, ?)",
            [
                $data['name'],
                $data['description'] ?? null,
            ]
        );

        return DB::lastInsertId();
    }

    public static function update($id, $data)
    {
        $fields = [];
        $params = [];

        if (isset($data['name'])) {
            $fields[] = 'name = ?';
            $params[] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $fields[] = 'description = ?';
            $params[] = $data['description'];
        }

        if (empty($fields)) {
            return;
        }

        $params[] = $id;
        DB::q("UPDATE roles SET " . implode(', ', $fields) . " WHERE id = ?", $params);
    }

    public static function getPermissionIds($roleId)
    {
        $rows = DB::fetchAll(
            "SELECT permission_id FROM role_permissions WHERE role_id = ?",
            [$roleId]
        );

        return array_map('intval', array_column($rows, 'permission_id'));
    }

    public static function setPermissions($roleId, $permissionIds)
    {
        $permissionIds = array_values(array_filter(array_map('intval', $permissionIds ?? [])));

        DB::beginTransaction();
        try {
            DB::q("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);

            foreach ($permissionIds as $permissionId) {
                DB::q(
                    "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                    [$roleId, $permissionId]
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();
            throw $e;
        }
    }
}
