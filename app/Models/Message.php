<?php
namespace App\Models;

use App\Core\DB;

class Message
{
    public static function findById($id)
    {
        return DB::fetch("SELECT * FROM messages WHERE id = ?", [$id]);
    }
    
    public static function findByMessageId($instanceId, $messageId)
    {
        return DB::fetch("SELECT * FROM messages WHERE instance_id = ? AND message_id = ?", [$instanceId, $messageId]);
    }
    
    public static function create($data)
    {
        // Check if message already exists
        $existing = self::findByMessageId($data['instance_id'], $data['message_id']);
        if ($existing) {
            return $existing['id'];
        }
        
        DB::q("
            INSERT INTO messages (instance_id, chat_id, message_id, from_me, ts, msg_type, body_text, participant_jid, media_url, local_path, status, raw_json, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ", [
            $data['instance_id'],
            $data['chat_id'],
            $data['message_id'],
            $data['from_me'] ?? 0,
            $data['ts'],
            $data['msg_type'] ?? 'text',
            $data['body_text'] ?? null,
            $data['participant_jid'] ?? null,
            $data['media_url'] ?? null,
            $data['local_path'] ?? null,
            $data['status'] ?? null,
            $data['raw_json'] ?? null
        ]);
        
        return DB::lastInsertId();
    }
    
    public static function getMessagesByChat($chatId, $page = 1, $limit = 25)
    {
        $offset = ($page - 1) * $limit;
        
        return DB::fetchAll("
            SELECT m.*,
                   CASE 
                       WHEN m.from_me = 1 THEN 'Me'
                       WHEN c.is_group = 1 AND m.participant_jid IS NOT NULL THEN 
                           COALESCE(sub.name, REPLACE(m.participant_jid, '@s.whatsapp.net', ''))
                       ELSE c.title
                   END as display_name
            FROM messages m
            JOIN chats c ON m.chat_id = c.id
            LEFT JOIN contacts sub ON c.instance_id = sub.instance_id AND REPLACE(m.participant_jid, '@s.whatsapp.net', '') = REPLACE(sub.phone_e164, '+', '')
            WHERE m.chat_id = ?
            ORDER BY m.ts DESC
            LIMIT ? OFFSET ?
        ", [$chatId, $limit, $offset]);
    }
    
    public static function getMessagesByChatAsc($chatId, $beforeTs = null, $limit = 25)
    {
        $sql = "
            SELECT m.*,
                   CASE 
                       WHEN m.from_me = 1 THEN 'Me'
                       WHEN c.is_group = 1 AND m.participant_jid IS NOT NULL THEN 
                           COALESCE(sub.name, REPLACE(m.participant_jid, '@s.whatsapp.net', ''))
                       ELSE c.title
                   END as display_name
            FROM messages m
            JOIN chats c ON m.chat_id = c.id
            LEFT JOIN contacts sub ON c.instance_id = sub.instance_id AND REPLACE(m.participant_jid, '@s.whatsapp.net', '') = REPLACE(sub.phone_e164, '+', '')
            WHERE m.chat_id = ?
        ";
        
        $params = [$chatId];
        
        if ($beforeTs) {
            $sql .= " AND m.ts < ?";
            $params[] = $beforeTs;
        }
        
        $sql .= " ORDER BY m.ts DESC LIMIT ?";
        $params[] = $limit;
        
        return DB::fetchAll($sql, $params);
    }
    
    public static function updateStatus($messageId, $status)
    {
        DB::q("UPDATE messages SET status = ? WHERE message_id = ?", [$status, $messageId]);
    }
    
    public static function getLastMessageByChat($chatId)
    {
        return DB::fetch("
            SELECT * FROM messages 
            WHERE chat_id = ? 
            ORDER BY ts DESC 
            LIMIT 1
        ", [$chatId]);
    }
    
    public static function getUnreadCount($chatId, $userId)
    {
        return DB::fetch("
            SELECT COUNT(*) as count
            FROM messages m
            JOIN chats c ON m.chat_id = c.id
            LEFT JOIN chat_reads cr ON c.id = cr.chat_id AND cr.user_id = ?
            WHERE m.chat_id = ? 
              AND m.from_me = 0 
              AND m.ts > COALESCE(cr.last_read_ts, '1970-01-01')
        ", [$userId, $chatId])['count'];
    }
    
    public static function search($instanceId, $query, $limit = 50)
    {
        return DB::fetchAll("
            SELECT m.*, c.title as chat_title, c.remote_jid
            FROM messages m
            JOIN chats c ON m.chat_id = c.id
            WHERE m.instance_id = ? 
              AND (m.body_text LIKE ? OR m.message_id LIKE ?)
            ORDER BY m.ts DESC
            LIMIT ?
        ", [
            $instanceId,
            "%$query%",
            "%$query%",
            $limit
        ]);
    }
    
    public static function getStatsByInstance($instanceId)
    {
        return DB::fetch("
            SELECT 
                COUNT(*) as total_messages,
                COUNT(CASE WHEN from_me = 1 THEN 1 END) as sent_messages,
                COUNT(CASE WHEN from_me = 0 THEN 1 END) as received_messages,
                COUNT(CASE WHEN msg_type != 'text' THEN 1 END) as media_messages,
                COUNT(CASE WHEN DATE(ts) = CURDATE() THEN 1 END) as today_messages
            FROM messages m
            JOIN chats c ON m.chat_id = c.id
            WHERE c.instance_id = ?
        ", [$instanceId]);
    }
    
    public static function delete($id)
    {
        DB::q("DELETE FROM messages WHERE id = ?", [$id]);
    }
    
    public static function deleteByChat($chatId)
    {
        DB::q("DELETE FROM messages WHERE chat_id = ?", [$chatId]);
    }
}
