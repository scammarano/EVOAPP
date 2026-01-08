<?php
namespace App\Models;

use App\Core\DB;

class ContactList
{
    public static function create($data)
    {
        $table = 'contact_lists';
        $sql = "INSERT INTO {$table} (instance_id, name, description, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())";
        
        DB::q($sql, [
            $data['instance_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['is_active'] ?? 1
        ]);
        
        return DB::lastInsertId();
    }
    
    public static function update($id, $data)
    {
        $table = 'contact_lists';
        $sql = "UPDATE {$table} SET 
                    name = ?, 
                    description = ?, 
                    is_active = ?, 
                    updated_at = NOW() 
                WHERE id = ?";
        
        DB::q($sql, [
            $data['name'],
            $data['description'] ?? null,
            $data['is_active'] ?? 1,
            $id
        ]);
    }
    
    public static function delete($id)
    {
        $table = 'contact_lists';
        
        // Delete list
        DB::q("DELETE FROM {$table} WHERE id = ?", [$id]);
        
        // Delete list-contact associations
        DB::q("DELETE FROM contact_list_members WHERE list_id = ?", [$id]);
    }
    
    public static function findById($id)
    {
        $table = 'contact_lists';
        $sql = "SELECT * FROM {$table} WHERE id = ?";
        return DB::fetch($sql, [$id]);
    }
    
    public static function findByName($name, $excludeId = null)
    {
        $table = 'contact_lists';
        $sql = "SELECT * FROM {$table} WHERE name = ?";
        $params = [$name];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        return DB::fetch($sql, $params);
    }
    
    public static function getByInstance($instanceId, $offset = 0, $limit = 20)
    {
        $table = 'contact_lists';
        $sql = "SELECT cl.*, 
                        (SELECT COUNT(*) FROM contact_list_members clm WHERE clm.list_id = cl.id) as contact_count
                 FROM {$table} cl 
                 WHERE cl.instance_id = ? 
                 ORDER BY cl.created_at DESC 
                 LIMIT ? OFFSET ?";
        
        return DB::fetchAll($sql, [$instanceId, $limit, $offset]);
    }
    
    public static function countByInstance($instanceId)
    {
        $table = 'contact_lists';
        $sql = "SELECT COUNT(*) as total FROM {$table} WHERE instance_id = ?";
        return DB::fetch($sql, [$instanceId])['total'];
    }
    
    public static function getMembers($listId, $offset = 0, $limit = 20)
    {
        $sql = "SELECT c.*, clm.added_at as list_added_at
                 FROM contact_list_members clm
                 JOIN contacts c ON clm.contact_id = c.id
                 WHERE clm.list_id = ?
                 ORDER BY clm.added_at DESC
                 LIMIT ? OFFSET ?";
        
        return DB::fetchAll($sql, [$listId, $limit, $offset]);
    }
    
    public static function addContact($listId, $contactId)
    {
        $table = 'contact_list_members';
        $sql = "INSERT IGNORE INTO {$table} (list_id, contact_id, added_at) VALUES (?, ?, NOW())";
        DB::q($sql, [$listId, $contactId]);
    }
    
    public static function removeContact($listId, $contactId)
    {
        $table = 'contact_list_members';
        $sql = "DELETE FROM {$table} WHERE list_id = ? AND contact_id = ?";
        DB::q($sql, [$listId, $contactId]);
    }
    
    public static function getContactStats($listId)
    {
        $sql = "SELECT 
                    COUNT(*) as total_contacts,
                    COUNT(CASE WHEN c.is_active = 1 THEN 1 END) as active_contacts
                 FROM contact_list_members clm
                 JOIN contacts c ON clm.contact_id = c.id
                 WHERE clm.list_id = ?";
        
        return DB::fetch($sql, [$listId]);
    }
}
