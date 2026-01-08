<?php
namespace App\Models;

use App\Core\DB;

class Instance
{
    public static function findBySlug($slug)
    {
        return DB::fetch("SELECT * FROM instances WHERE slug = ?", [$slug]);
    }
    
    public static function findById($id)
    {
        return DB::fetch("SELECT * FROM instances WHERE id = ?", [$id]);
    }
    
    public static function getAll($activeOnly = true)
    {
        $sql = "SELECT * FROM instances";
        $params = [];
        
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        
        $sql .= " ORDER BY slug";
        
        return DB::fetchAll($sql, $params);
    }
    
    public static function create($data)
    {
        DB::q("
            INSERT INTO instances (slug, description, is_active, api_key, base_url, webhook_token, webhook_enabled, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ", [
            $data['slug'],
            $data['description'] ?? null,
            $data['is_active'] ?? 1,
            $data['api_key'],
            $data['base_url'] ?? null,
            $data['webhook_token'] ?? null,
            $data['webhook_enabled'] ?? 1
        ]);
        
        return DB::lastInsertId();
    }
    
    public static function update($id, $data)
    {
        $fields = [];
        $params = [];
        
        $updatableFields = ['slug', 'description', 'is_active', 'api_key', 'base_url', 'webhook_token', 'webhook_enabled', 'forward_webhook_url', 'forward_webhook_enabled'];
        
        foreach ($updatableFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (!empty($fields)) {
            $fields[] = "updated_at = NOW()";
            $params[] = $id;
            
            DB::q("UPDATE instances SET " . implode(', ', $fields) . " WHERE id = ?", $params);
        }
    }
    
    public static function delete($id)
    {
        DB::q("DELETE FROM instances WHERE id = ?", [$id]);
    }
    
    public static function getAccessibleInstances($userId = null)
    {
        $sql = "SELECT * FROM instances";
        $params = [];
        
        // For now, return all instances (we'll add permissions later)
        if ($userId) {
            // TODO: Add user permissions logic
        }
        
        $sql .= " WHERE is_active = 1 ORDER BY slug";
        
        return DB::fetchAll($sql, $params);
    }
    
    public static function getStats()
    {
        return DB::fetchAll("
            SELECT 
                i.id,
                i.slug,
                i.description,
                i.is_active,
                i.last_webhook_at,
                COUNT(DISTINCT c.id) as chat_count,
                COUNT(DISTINCT CASE WHEN c.unread_count > 0 THEN c.id END) as unread_chats,
                COUNT(DISTINCT m.id) as message_count,
                MAX(m.ts) as last_message_at,
                SUM(c.unread_count) as total_unread
            FROM instances i
            LEFT JOIN chats c ON i.id = c.instance_id
            LEFT JOIN messages m ON c.id = m.chat_id
            GROUP BY i.id, i.slug, i.description, i.is_active, i.last_webhook_at
            ORDER BY i.slug
        ");
    }
    
    public static function updateWebhookTimestamp($id)
    {
        DB::q("UPDATE instances SET last_webhook_at = NOW() WHERE id = ?", [$id]);
    }
    
    // Evolution API client wrapper
    public static function evoClient($instance)
    {
        $baseUrl = $instance['base_url'] ?? EVO_BASE_URL;
        $apiKey = $instance['api_key'];
        
        return new class($baseUrl, $apiKey) {
            private $baseUrl;
            private $apiKey;
            
            public function __construct($baseUrl, $apiKey)
            {
                $this->baseUrl = rtrim($baseUrl, '/');
                $this->apiKey = $apiKey;
            }
            
            private function request($method, $endpoint, $data = null)
            {
                $url = $this->baseUrl . $endpoint;
                $headers = [
                    'Content-Type: application/json',
                    'apikey: ' . $this->apiKey
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                
                if ($method === 'POST' && $data) {
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                if ($error) {
                    throw new \Exception("CURL Error: $error");
                }
                
                $responseData = json_decode($response, true);
                
                if ($httpCode >= 400) {
                    throw new \Exception("API Error [$httpCode]: " . ($responseData['message'] ?? 'Unknown error'));
                }
                
                return $responseData;
            }
            
            public function findChats($instanceSlug)
            {
                return $this->request('POST', "/chat/findChats/$instanceSlug");
            }
            
            public function findMessages($instanceSlug, $remoteJid, $page = 1, $limit = 25)
            {
                return $this->request('POST', "/chat/findMessages/$instanceSlug", [
                    'remoteJid' => $remoteJid,
                    'page' => $page,
                    'limit' => $limit
                ]);
            }
            
            public function sendText($instanceSlug, $number, $text)
            {
                return $this->request('POST', "/message/sendText/$instanceSlug", [
                    'number' => $number,
                    'text' => $text
                ]);
            }
            
            public function sendMedia($instanceSlug, $number, $mediaPath, $mediaType, $mimeType, $caption = null)
            {
                $url = $this->baseUrl . "/message/sendMedia/$instanceSlug";
                
                $headers = [
                    'apikey: ' . $this->apiKey
                ];
                
                $postFields = [
                    'number' => $number,
                    'mediatype' => $mediaType,
                    'mimetype' => $mimeType,
                    'media' => new \CURLFile($mediaPath, $mimeType, basename($mediaPath))
                ];
                
                if ($caption) {
                    $postFields['caption'] = $caption;
                }
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                if ($error) {
                    throw new \Exception("CURL Error: $error");
                }
                
                $responseData = json_decode($response, true);
                
                if ($httpCode >= 400) {
                    throw new \Exception("API Error [$httpCode]: " . ($responseData['message'] ?? 'Unknown error'));
                }
                
                return $responseData;
            }
            
            public function getGroupParticipants($instanceSlug, $groupJid)
            {
                return $this->request('GET', "/group/participants/$instanceSlug?groupJid=" . urlencode($groupJid));
            }
        };
    }
}
