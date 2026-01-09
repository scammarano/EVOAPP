<?php
namespace App\Models;

use App\Core\DB;

class Chat
{
    private static $contactJoinConfig = null;

    private static function getContactJoinConfig()
    {
        if (self::$contactJoinConfig !== null) {
            return self::$contactJoinConfig;
        }

        $hasProfilePic = DB::columnExists('contacts', 'profile_pic_url');
        $hasPhone = DB::columnExists('contacts', 'phone');
        $hasPhoneE164 = DB::columnExists('contacts', 'phone_e164');

        $joinConditions = [];

        if ($hasPhone) {
            $joinConditions[] = "ca.phone = c.remote_jid";
        }

        if ($hasPhoneE164) {
            $joinConditions[] = "REPLACE(ca.phone_e164, '+', '') = REPLACE(REPLACE(REPLACE(REPLACE(c.remote_jid, '@s.whatsapp.net', ''), '@c.us', ''), '@g.us', ''), '@lid', '')";
        }

        if ($hasProfilePic && !empty($joinConditions)) {
            self::$contactJoinConfig = [
                'avatar_select' => "ca.profile_pic_url as avatar_url",
                'join' => "LEFT JOIN contacts ca
                ON ca.instance_id = c.instance_id
               AND (" . implode(' OR ', $joinConditions) . ")",
            ];

            return self::$contactJoinConfig;
        }

        self::$contactJoinConfig = [
            'avatar_select' => "NULL as avatar_url",
            'join' => "",
        ];

        return self::$contactJoinConfig;
    }

    public static function findById($id)
    {
        $contactJoin = self::getContactJoinConfig();

        return DB::fetch("
            SELECT c.*,
                   {$contactJoin['avatar_select']}
            FROM chats c
            {$contactJoin['join']}
            WHERE c.id = ?
        ", [$id]);
    }
    
    public static function findByRemoteJid($instanceId, $remoteJid)
    {
        return DB::fetch("SELECT * FROM chats WHERE instance_id = ? AND remote_jid = ?", [$instanceId, $remoteJid]);
    }
    
    public static function createOrUpdate($data)
    {
        $existing = self::findByRemoteJid($data['instance_id'], $data['remote_jid']);
        
        if ($existing) {
            // Update existing chat
            $fields = [];
            $params = [];
            
            if (isset($data['title'])) {
                $fields[] = "title = ?";
                $params[] = $data['title'];
            }
            
            if (isset($data['last_snippet'])) {
                $fields[] = "last_snippet = ?";
                $params[] = $data['last_snippet'];
            }
            
            if (isset($data['last_message_at'])) {
                $fields[] = "last_message_at = ?";
                $params[] = $data['last_message_at'];
            }
            
            if (isset($data['unread_count'])) {
                $fields[] = "unread_count = ?";
                $params[] = $data['unread_count'];
            }
            
            if (!empty($fields)) {
                $fields[] = "updated_at = NOW()";
                $params[] = $existing['id'];
                
                DB::q("UPDATE chats SET " . implode(', ', $fields) . " WHERE id = ?", $params);
            }
            
            return $existing['id'];
        } else {
            // Create new chat
            DB::q("
                INSERT INTO chats (instance_id, remote_jid, is_group, title, last_snippet, last_message_at, unread_count, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ", [
                $data['instance_id'],
                $data['remote_jid'],
                $data['is_group'] ?? 0,
                $data['title'] ?? null,
                $data['last_snippet'] ?? null,
                $data['last_message_at'] ?? null,
                $data['unread_count'] ?? 0
            ]);
            
            return DB::lastInsertId();
        }
    }
    
    public static function getChatsByInstance($instanceId, $page = 1, $limit = 15)
    {
        $contactJoin = self::getContactJoinConfig();
        $offset = ($page - 1) * $limit;
        
        return DB::fetchAll("
            SELECT c.*, 
                   {$contactJoin['avatar_select']},
                   COALESCE(cr.last_read_ts, '1970-01-01') as user_last_read_ts,
                   CASE 
                       WHEN m.ts > COALESCE(cr.last_read_ts, '1970-01-01') AND m.from_me = 0 THEN 1
                       ELSE 0
                   END as has_unread
            FROM chats c
            {$contactJoin['join']}
            LEFT JOIN messages m ON c.id = m.chat_id
            LEFT JOIN chat_reads cr ON c.id = cr.chat_id AND cr.user_id = ?
            WHERE c.instance_id = ?
            GROUP BY c.id
            ORDER BY c.last_message_at DESC, c.updated_at DESC
            LIMIT ? OFFSET ?
        ", [$_SESSION['user_id'] ?? 0, $instanceId, $limit, $offset]);
    }
    
    public static function getChatsWithUnreadCount($instanceId)
    {
        return DB::fetchAll("
            SELECT c.*, 
                   COUNT(m.id) - COUNT(CASE WHEN m.ts <= COALESCE(cr.last_read_ts, '1970-01-01') OR m.from_me = 1 THEN 1 END) as unread_count
            FROM chats c
            LEFT JOIN messages m ON c.id = m.chat_id
            LEFT JOIN chat_reads cr ON c.id = cr.chat_id AND cr.user_id = ?
            WHERE c.instance_id = ?
            GROUP BY c.id
            HAVING unread_count > 0
            ORDER BY c.last_message_at DESC
        ", [$_SESSION['user_id'] ?? 0, $instanceId]);
    }
    
    public static function markAsRead($chatId, $userId)
    {
        DB::q("
            INSERT INTO chat_reads (user_id, chat_id, last_read_ts)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE last_read_ts = NOW()
        ", [$userId, $chatId]);
        
        // Reset unread count for this chat
        DB::q("UPDATE chats SET unread_count = 0 WHERE id = ?", [$chatId]);
    }
    
    public static function incrementUnread($chatId)
    {
        DB::q("UPDATE chats SET unread_count = unread_count + 1 WHERE id = ?", [$chatId]);
    }
    
    public static function delete($id)
    {
        DB::q("DELETE FROM chats WHERE id = ?", [$id]);
    }
    
    public static function getStatsByInstance($instanceId)
    {
        return DB::fetch("
            SELECT 
                COUNT(*) as total_chats,
                COUNT(CASE WHEN is_group = 1 THEN 1 END) as group_chats,
                COUNT(CASE WHEN is_group = 0 THEN 1 END) as individual_chats,
                SUM(unread_count) as total_unread,
                COUNT(CASE WHEN unread_count > 0 THEN 1 END) as chats_with_unread
            FROM chats
            WHERE instance_id = ?
        ", [$instanceId]);
    }
}
