<?php
namespace App\Models;

use App\Core\DB;

class WebhookEvent
{
    public static function create($data)
    {
        DB::q("
            INSERT INTO webhook_events (instance_id, event_type, remote_jid, message_id, participant_jid, payload_json, received_at, status)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 'pending')
        ", [
            $data['instance_id'],
            $data['event_type'],
            $data['remote_jid'] ?? null,
            $data['message_id'] ?? null,
            $data['participant_jid'] ?? null,
            json_encode($data['payload'])
        ]);
        
        return DB::lastInsertId();
    }
    
    public static function getPending($limit = 100)
    {
        return DB::fetchAll("
            SELECT we.*, i.slug
            FROM webhook_events we
            JOIN evo_instances i ON we.instance_id = i.id
            WHERE we.status = 'pending'
            ORDER BY we.received_at ASC
            LIMIT ?
        ", [$limit]);
    }
    
    public static function markProcessed($id, $error = null)
    {
        if ($error) {
            DB::q("
                UPDATE webhook_events 
                SET status = 'error', processed_at = NOW(), error_text = ?
                WHERE id = ?
            ", [$error, $id]);
        } else {
            DB::q("
                UPDATE webhook_events 
                SET status = 'processed', processed_at = NOW()
                WHERE id = ?
            ", [$id]);
        }
    }
    
    public static function getEventsSince($instanceId, $lastEventId, $limit = 50)
    {
        return DB::fetchAll("
            SELECT * FROM webhook_events
            WHERE instance_id = ? AND id > ?
            ORDER BY id ASC
            LIMIT ?
        ", [$instanceId, $lastEventId, $limit]);
    }
    
    public static function getRecent($instanceId = null, $limit = 50)
    {
        $sql = "
            SELECT we.*, i.slug as instance_slug
            FROM webhook_events we
            JOIN evo_instances i ON we.instance_id = i.id
        ";
        
        $params = [];
        
        if ($instanceId) {
            $sql .= " WHERE we.instance_id = ?";
            $params[] = $instanceId;
        }
        
        $sql .= " ORDER BY we.received_at DESC LIMIT ?";
        $params[] = $limit;
        
        return DB::fetchAll($sql, $params);
    }
    
    public static function getStats($instanceId = null)
    {
        $sql = "
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'processed' THEN 1 END) as processed,
                COUNT(CASE WHEN status = 'error' THEN 1 END) as errors,
                COUNT(CASE WHEN DATE(received_at) = CURDATE() THEN 1 END) as today
            FROM webhook_events we
        ";
        
        $params = [];
        
        if ($instanceId) {
            $sql .= " WHERE we.instance_id = ?";
            $params[] = $instanceId;
        }
        
        return DB::fetch($sql, $params);
    }
    
    public static function cleanup($daysOld = 30)
    {
        DB::q("
            DELETE FROM webhook_events 
            WHERE status IN ('processed', 'error') 
              AND received_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ", [$daysOld]);
        
        return DB::lastInsertId();
    }
    
    public static function reprocess($id)
    {
        DB::q("UPDATE webhook_events SET status = 'pending', processed_at = NULL, error_text = NULL WHERE id = ?", [$id]);
    }
}
