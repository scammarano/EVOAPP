<?php
namespace App\Models;

use App\Core\DB;

class Contact
{
    public static function findById($id)
    {
        return DB::fetch("SELECT * FROM contacts WHERE id = ?", [$id]);
    }
    
    public static function findByPhone($instanceId, $phone)
    {
        return DB::fetch("SELECT * FROM contacts WHERE instance_id = ? AND phone_e164 = ?", [$instanceId, $phone]);
    }
    
    public static function createOrUpdate($data)
    {
        $existing = self::findByPhone($data['instance_id'], $data['phone_e164']);
        
        if ($existing) {
            // Update existing contact
            $fields = [];
            $params = [];
            
            $updatableFields = ['name', 'company', 'email', 'birthday', 'notes'];
            
            foreach ($updatableFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (!empty($fields)) {
                $fields[] = "updated_at = NOW()";
                $params[] = $existing['id'];
                
                DB::q("UPDATE contacts SET " . implode(', ', $fields) . " WHERE id = ?", $params);
            }
            
            return $existing['id'];
        } else {
            // Create new contact
            DB::q("
                INSERT INTO contacts (instance_id, phone_e164, name, company, email, birthday, notes, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ", [
                $data['instance_id'],
                $data['phone_e164'],
                $data['name'] ?? null,
                $data['company'] ?? null,
                $data['email'] ?? null,
                $data['birthday'] ?? null,
                $data['notes'] ?? null
            ]);
            
            return DB::lastInsertId();
        }
    }
    
    public static function getAll($instanceId, $page = 1, $limit = 20, $search = null)
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM contacts WHERE instance_id = ?";
        $params = [$instanceId];
        
        if ($search) {
            $sql .= " AND (name LIKE ? OR phone_e164 LIKE ? OR email LIKE ? OR company LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        }
        
        $sql .= " ORDER BY name ASC, phone_e164 ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return DB::fetchAll($sql, $params);
    }
    
    public static function count($instanceId, $search = null)
    {
        $sql = "SELECT COUNT(*) as count FROM contacts WHERE instance_id = ?";
        $params = [$instanceId];
        
        if ($search) {
            $sql .= " AND (name LIKE ? OR phone_e164 LIKE ? OR email LIKE ? OR company LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        }
        
        return DB::fetch($sql, $params)['count'];
    }
    
    public static function delete($id)
    {
        DB::q("DELETE FROM contacts WHERE id = ?", [$id]);
    }
    
    public static function getFromList($listId, $page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        
        return DB::fetchAll("
            SELECT c.* 
            FROM contacts c
            JOIN contact_list_items cli ON c.id = cli.contact_id
            WHERE cli.list_id = ?
            ORDER BY c.name ASC
            LIMIT ? OFFSET ?
        ", [$listId, $limit, $offset]);
    }
    
    public static function countFromList($listId)
    {
        return DB::fetch("
            SELECT COUNT(*) as count
            FROM contact_list_items cli
            WHERE cli.list_id = ?
        ", [$listId])['count'];
    }
    
    public static function importFromCsv($instanceId, $filePath)
    {
        $contacts = [];
        $handle = fopen($filePath, 'r');
        
        if (!$handle) {
            throw new \Exception("Cannot open file: $filePath");
        }
        
        // Skip header if exists
        $header = fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) continue;
            
            $contact = [
                'instance_id' => $instanceId,
                'phone_e164' => self::formatPhone($row[0]),
                'name' => $row[1] ?? null,
                'email' => $row[2] ?? null,
                'company' => $row[3] ?? null,
                'notes' => $row[4] ?? null
            ];
            
            $contacts[] = $contact;
        }
        
        fclose($handle);
        
        // Insert contacts
        foreach ($contacts as $contact) {
            self::createOrUpdate($contact);
        }
        
        return count($contacts);
    }
    
    public static function exportToCsv($instanceId, $search = null)
    {
        $contacts = self::getAll($instanceId, 1, 10000, $search);
        
        $output = fopen('php://temp', 'r+');
        
        // Header
        fputcsv($output, ['Phone', 'Name', 'Email', 'Company', 'Birthday', 'Notes']);
        
        foreach ($contacts as $contact) {
            fputcsv($output, [
                $contact['phone_e164'],
                $contact['name'],
                $contact['email'],
                $contact['company'],
                $contact['birthday'],
                $contact['notes']
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    private static function formatPhone($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add + if missing
        if (substr($phone, 0, 1) !== '+') {
            $phone = '+' . $phone;
        }
        
        return $phone;
    }
    
    public static function getStats($instanceId)
    {
        return DB::fetch("
            SELECT 
                COUNT(*) as total_contacts,
                COUNT(CASE WHEN name IS NOT NULL AND name != '' THEN 1 END) as with_name,
                COUNT(CASE WHEN email IS NOT NULL AND email != '' THEN 1 END) as with_email,
                COUNT(CASE WHEN company IS NOT NULL AND company != '' THEN 1 END) as with_company,
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_added
            FROM contacts
            WHERE instance_id = ?
        ", [$instanceId]);
    }
}
