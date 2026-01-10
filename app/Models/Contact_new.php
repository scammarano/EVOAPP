<?php
namespace App\Models;

use App\Core\Database;

class Contact
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    public function getAll($limit = 20, $offset = 0, $search = '')
    {
        $sql = "SELECT * FROM contacts";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " WHERE name LIKE ? OR phone LIKE ? OR email LIKE ? OR company LIKE ?";
            $searchParam = "%$search%";
            $params = [$searchParam, $searchParam, $searchParam, $searchParam];
        }
        
        $sql .= " ORDER BY name ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getById($id)
    {
        $sql = "SELECT * FROM contacts WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    public function create($data)
    {
        $sql = "INSERT INTO contacts (name, phone, phone_e164, email, company, address, notes, source, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'] ?? null,
            $data['phone'] ?? '',
            $data['phone_e164'] ?? $data['phone'],
            $data['email'] ?? null,
            $data['company'] ?? null,
            $data['address'] ?? null,
            $data['notes'] ?? null,
            $data['source'] ?? 'manual'
        ]);
    }
    
    public function createIfNotExists($data)
    {
        // Verificar si ya existe
        $existing = $this->getByPhone($data['phone']);
        
        if ($existing) {
            return false; // Ya existe
        }
        
        return $this->create($data);
    }
    
    public function getByPhone($phone)
    {
        $sql = "SELECT * FROM contacts WHERE phone = ? OR phone_e164 = ?";
        return $this->db->fetch($sql, [$phone, $phone]);
    }
    
    public function update($id, $data)
    {
        $sql = "UPDATE contacts SET name = ?, email = ?, company = ?, address = ?, notes = ?, updated_at = NOW() WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'] ?? null,
            $data['email'] ?? null,
            $data['company'] ?? null,
            $data['address'] ?? null,
            $data['notes'] ?? null,
            $id
        ]);
    }
    
    public function delete($id)
    {
        $sql = "DELETE FROM contacts WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
}
