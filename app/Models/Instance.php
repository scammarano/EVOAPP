<?php
namespace App\Models;

use App\Core\DB;

class Instance
{
    private static $resolvedTable = null;

    public static function tableName()
    {
        return 'evo_instances';
    }

    public static function findBySlug($slug)
    {
        $table = self::tableName();
        return DB::fetch("SELECT * FROM {$table} WHERE slug = ?", [$slug]);
    }
    
    public static function findById($id)
    {
        $table = self::tableName();
        return DB::fetch("SELECT * FROM {$table} WHERE id = ?", [$id]);
    }
    
    public static function getAll($activeOnly = true)
    {
        $table = self::tableName();
        $sql = "SELECT * FROM {$table}";
        $params = [];
        
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        
        $sql .= " ORDER BY slug";
        
        return DB::fetchAll($sql, $params);
    }
    
    public static function create($data)
    {
        $table = self::tableName();
        DB::q("
            INSERT INTO {$table} (slug, description, is_active, api_key, base_url, webhook_token, webhook_enabled, created_at, updated_at)
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
        $table = self::tableName();
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
            
            DB::q("UPDATE {$table} SET " . implode(', ', $fields) . " WHERE id = ?", $params);
        }
    }
    
    public static function delete($id)
    {
        $table = self::tableName();
        DB::q("DELETE FROM {$table} WHERE id = ?", [$id]);
    }
    
    public static function getAccessibleInstances($userId = null)
    {
        $table = self::tableName();
        $sql = "SELECT * FROM {$table}";
        $params = [];
        
        // For now, return all instances (we'll add permissions later)
        if ($userId) {
            // TODO: Add user permissions logic
        }
        
        $sql .= " ORDER BY slug";
        
        return DB::fetchAll($sql, $params);
    }
    
    public static function getStats()
    {
        $table = self::tableName();
        
        try {
            // Enhanced query with real stats
            $sql = "
                SELECT i.*, 
                       (SELECT COUNT(*) FROM chats WHERE instance_id = i.id) as chat_count,
                       (SELECT COUNT(*) FROM messages m JOIN chats c ON m.chat_id = c.id WHERE c.instance_id = i.id) as message_count,
                       COALESCE((SELECT SUM(unread_count) FROM chats WHERE instance_id = i.id), 0) as total_unread
                FROM {$table} i
                ORDER BY i.slug
            ";
            
            return DB::fetchAll($sql);
            
        } catch (\Exception $e) {
            // If tables don't exist, return empty stats
            return [];
        }
    }

    public static function getStatsByInstance($instanceId)
    {
        try {
            return DB::fetch("
                SELECT
                    (SELECT COUNT(*) FROM chats WHERE instance_id = ?) as chat_count,
                    (SELECT COUNT(*) FROM messages m JOIN chats c ON m.chat_id = c.id WHERE c.instance_id = ?) as message_count,
                    COALESCE((SELECT SUM(unread_count) FROM chats WHERE instance_id = ?), 0) as total_unread
            ", [$instanceId, $instanceId, $instanceId]);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public static function getInstanceProfile($instanceId)
    {
        $table = self::tableName();
        return DB::fetch("SELECT * FROM instance_profiles WHERE instance_id = ?", [$instanceId]);
    }
    
    public static function saveInstanceProfile($instanceId, $profileData)
    {
        $table = 'instance_profiles';
        
        // Check if profile exists
        $existing = DB::fetch("SELECT id FROM {$table} WHERE instance_id = ?", [$instanceId]);
        
        if ($existing) {
            // Update existing profile
            $sql = "UPDATE {$table} SET 
                        profile_image_url = ?, 
                        description = ?, 
                        updated_at = NOW() 
                    WHERE instance_id = ?";
            
            DB::q($sql, [
                $profileData['profile_image_url'] ?? null,
                $profileData['description'] ?? null,
                $instanceId
            ]);
            
            return $existing['id'];
        } else {
            // Insert new profile
            $sql = "INSERT INTO {$table} (instance_id, profile_image_url, description, created_at, updated_at) 
                    VALUES (?, ?, ?, NOW(), NOW())";
            
            DB::q($sql, [
                $instanceId,
                $profileData['profile_image_url'] ?? null,
                $profileData['description'] ?? null
            ]);
            
            return DB::lastInsertId();
        }
    }
    
    public static function getWithRelations($id)
    {
        $table = self::tableName();
        
        try {
            $sql = "
                SELECT i.*, 
                       u.name as created_by_name,
                       u.email as created_by_email
                FROM {$table} i
                LEFT JOIN users u ON u.id = i.created_by
                WHERE i.id = ?
            ";
            
            return DB::fetch($sql, [$id]);
            
        } catch (\Exception $e) {
            // If tables don't exist, return basic instance data
            return self::findById($id);
        }
    }
    
    public static function getChats($instanceId, $page = 1, $limit = 50)
    {
        $table = 'chats';
        $offset = ($page - 1) * $limit;
        
        $sql = "
            SELECT c.*, 
                   COUNT(m.id) as message_count,
                   COUNT(cr.id) as unread_count
            FROM {$table} c
            LEFT JOIN messages m ON m.chat_id = c.id
            LEFT JOIN chat_reads cr ON cr.chat_id = c.id AND cr.user_id = ?
            WHERE c.instance_id = ?
            GROUP BY c.id
            ORDER BY c.last_message_at DESC
            LIMIT ? OFFSET ?
        ";
        
        return DB::fetchAll($sql, [$_SESSION['user_id'] ?? null, $instanceId, $limit, $offset]);
    }
    
    public static function getMessages($instanceId, $chatId, $page = 1, $limit = 50)
    {
        $table = 'messages';
        $offset = ($page - 1) * $limit;
        
        $sql = "
            SELECT m.*, 
                   c.title as chat_title,
                   c.is_group as chat_is_group
            FROM {$table} m
            LEFT JOIN chats c ON c.id = m.chat_id
            WHERE m.instance_id = ? AND m.chat_id = ?
            ORDER BY m.ts DESC
            LIMIT ? OFFSET ?
        ";
        
        return DB::fetchAll($sql, [$instanceId, $chatId, $limit, $offset]);
    }
    
    public static function getContacts($instanceId, $page = 1, $limit = 50)
    {
        $table = 'contacts';
        $offset = ($page - 1) * $limit;
        
        $sql = "
            SELECT * FROM {$table}
            WHERE instance_id = ?
            ORDER BY name ASC
            LIMIT ? OFFSET ?
        ";
        
        return DB::fetchAll($sql, [$instanceId, $limit, $offset]);
    }
    
    public static function getContactLists($instanceId, $page = 1, $limit = 50)
    {
        $table = 'contact_lists';
        $offset = ($page - 1) * $limit;
        
        $sql = "
            SELECT cl.*, 
                   COUNT(cli.id) as contact_count
            FROM {$table} cl
            LEFT JOIN contact_list_items cli ON cli.list_id = cl.id
            WHERE cl.instance_id = ?
            GROUP BY cl.id
            ORDER BY cl.name ASC
            LIMIT ? OFFSET ?
        ";
        
        return DB::fetchAll($sql, [$instanceId, $limit, $offset]);
    }
    
    public static function getCampaigns($instanceId, $page = 1, $limit = 20)
    {
        $table = 'campaigns';
        $offset = ($page - 1) * $limit;
        
        $sql = "
            SELECT c.*, 
                   u.name as created_by_name
            FROM {$table} c
            LEFT JOIN users u ON u.id = c.created_by
            WHERE c.instance_id = ?
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        return DB::fetchAll($sql, [$instanceId, $limit, $offset]);
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
                    'file' => new \CURLFile($mediaPath, $mimeType, basename($mediaPath))
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
            
            // Profile and Status methods (Evolution API v2.3.7)
            public function getProfile($instanceSlug)
            {
                return $this->request('GET', "/profile/$instanceSlug");
            }
            
            public function updateProfilePicture($instanceSlug, $imagePath)
            {
                $url = $this->baseUrl . "/profile/picture/$instanceSlug";
                
                $headers = [
                    'apikey: ' . $this->apiKey
                ];
                
                $postFields = [
                    'picture' => new \CURLFile($imagePath, mime_content_type($imagePath), basename($imagePath))
                ];
                
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
            
            public function getStatus($instanceSlug)
            {
                return $this->request('GET', "/status/$instanceSlug");
            }
            
            public function updateStatus($instanceSlug, $content, $type = 'text', $caption = null)
            {
                $data = [
                    'type' => $type,
                    'content' => $content
                ];
                
                if ($caption) {
                    $data['caption'] = $caption;
                }
                
                return $this->request('POST', "/status/$instanceSlug", $data);
            }
            
            public function updateStatusImage($instanceSlug, $imagePath, $caption = null)
            {
                $url = $this->baseUrl . "/status/$instanceSlug";
                
                $headers = [
                    'apikey: ' . $this->apiKey
                ];
                
                $postFields = [
                    'type' => 'image',
                    'image' => new \CURLFile($imagePath, mime_content_type($imagePath), basename($imagePath))
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
            
            public function scheduleStatus($instanceSlug, $content, $scheduledAt, $type = 'text', $caption = null)
            {
                $data = [
                    'type' => $type,
                    'content' => $content,
                    'scheduledAt' => $scheduledAt
                ];
                
                if ($caption) {
                    $data['caption'] = $caption;
                }
                
                return $this->request('POST', "/schedule/status/$instanceSlug", $data);
            }
            
            public function scheduleStatusImage($instanceSlug, $imagePath, $scheduledAt, $caption = null)
            {
                $url = $this->baseUrl . "/schedule/status/$instanceSlug";
                
                $headers = [
                    'apikey: ' . $this->apiKey
                ];
                
                $postFields = [
                    'type' => 'image',
                    'image' => new \CURLFile($imagePath, mime_content_type($imagePath), basename($imagePath)),
                    'scheduledAt' => $scheduledAt
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
            
            public function deleteStatus($instanceSlug)
            {
                return $this->request('DELETE', "/status/$instanceSlug");
            }
        };
    }
    
    public static function saveScheduledStatus($instanceId, $statusData)
    {
        $table = 'instance_status_scheduled';
        $fields = ['instance_id', 'content_type', 'content', 'scheduled_at'];
        $params = [$instanceId, $statusData['content_type'], $statusData['content'], $statusData['scheduled_at']];
        
        if (isset($statusData['caption'])) {
            $fields[] = 'caption';
            $params[] = $statusData['caption'];
        }
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . str_repeat('?,', count($params)) . ")";
        DB::q($sql, $params);
    }
    
    public static function getScheduledStatuses($instanceId, $limit = 10)
    {
        $table = 'instance_status_scheduled';
        return DB::fetchAll("SELECT * FROM {$table} WHERE instance_id = ? ORDER BY scheduled_at DESC LIMIT ?", [$instanceId, $limit]);
    }
    
    public static function updateScheduledStatus($statusId, $status, $errorText = null)
    {
        $table = 'instance_status_scheduled';
        $fields = ['status = ?', 'sent_at = ?'];
        $params = [$status, $status ? 'NOW()' : null];
        
        if ($errorText) {
            $fields[] = 'error_text = ?';
            $params[] = $errorText;
        }
        
        $params[] = $statusId;
        
        $sql = "UPDATE {$table} SET " . implode(', ', $fields) . " WHERE id = ?";
        DB::q($sql, $params);
    }
    
    public static function deleteScheduledStatus($statusId)
    {
        $table = 'instance_status_scheduled';
        DB::q("DELETE FROM {$table} WHERE id = ?", [$statusId]);
    }
}
