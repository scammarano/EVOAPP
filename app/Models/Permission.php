<?php
namespace App\Models;

use App\Core\DB;

class Permission
{
    public static function getAll()
    {
        return DB::fetchAll("SELECT * FROM permissions ORDER BY `key`");
    }

    public static function findById($id)
    {
        return DB::fetch("SELECT * FROM permissions WHERE id = ?", [$id]);
    }
}
