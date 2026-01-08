<?php
namespace App\Models;

use App\Core\DB;

class Campaign
{
    public static function findById($id)
    {
        return DB::fetch("SELECT * FROM campaigns WHERE id = ?", [$id]);
    }
    
    public static function create($data)
    {
        DB::q("
            INSERT INTO campaigns (instance_id, name, is_active, schedule_type, start_date, start_time, end_date, end_time, daily_time, timezone, weekly_days, monthly_day, next_run_at, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ", [
            $data['instance_id'],
            $data['name'],
            $data['is_active'] ?? 1,
            $data['schedule_type'],
            $data['start_date'],
            $data['start_time'],
            $data['end_date'],
            $data['end_time'],
            $data['daily_time'] ?? null,
            $data['timezone'] ?? 'America/Bogota',
            $data['weekly_days'] ?? null,
            $data['monthly_day'] ?? null,
            $data['next_run_at'] ?? null,
            $data['created_by']
        ]);
        
        return DB::lastInsertId();
    }
    
    public static function update($id, $data)
    {
        $fields = [];
        $params = [];
        
        $updatableFields = ['name', 'is_active', 'schedule_type', 'start_date', 'start_time', 'end_date', 'end_time', 'timezone', 'weekly_days', 'monthly_day', 'next_run_at'];
        
        foreach ($updatableFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (!empty($fields)) {
            $fields[] = "updated_at = NOW()";
            $params[] = $id;
        }
        
        DB::q("UPDATE campaigns SET " . implode(', ', $fields) . " WHERE id = ?", $params);
    }
    
    public static function delete($id)
    {
        DB::q("DELETE FROM campaigns WHERE id = ?", [$id]);
    }
    
    public static function getAll($instanceId = null, $page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "
            SELECT c.*, i.slug as instance_slug, u.name as created_by_name
            FROM campaigns c
            JOIN evo_instances i ON c.instance_id = i.id
            JOIN users u ON c.created_by = u.id
        ";
        
        $params = [];
        
        if ($instanceId) {
            $sql .= " WHERE c.instance_id = ?";
            $params[] = $instanceId;
        }
        
        $sql .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return DB::fetchAll($sql, $params);
    }
    
    public static function count($instanceId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM campaigns c";
        $params = [];
        
        if ($instanceId) {
            $sql .= " WHERE c.instance_id = ?";
            $params[] = $instanceId;
        }
        
        return DB::fetch($sql, $params)['count'];
    }
    
    public static function getDueCampaigns()
    {
        return DB::fetchAll("
            SELECT * FROM campaigns
            WHERE is_active = 1 
              AND next_run_at <= NOW()
              AND (end_at IS NULL OR end_at >= NOW())
        ");
    }
    
    public static function calculateNextRun($campaign)
    {
        $timezone = $campaign['timezone'] ?? 'America/Bogota';
        $now = new \DateTime('now', new \DateTimeZone($timezone));
        
        switch ($campaign['schedule_type']) {
            case 'once':
                return $campaign['start_date'] . ' ' . ($campaign['start_time'] ?? '00:00');
                
            case 'daily':
                // Run every day at the specified time
                if (!$campaign['start_time']) {
                    return null;
                }
                
                $nextRun = clone $now;
                $startTime = new \DateTime($campaign['start_date'] . ' ' . $campaign['start_time'], new \DateTimeZone($timezone));
                $nextRun->setTime($startTime->format('H'), $startTime->format('i'), 0);
                
                // If time has passed today, move to tomorrow
                if ($nextRun <= $now) {
                    $nextRun->modify('+1 day');
                }
                
                return $nextRun->format('Y-m-d H:i:s');
                
            case 'weekly':
                if (!$campaign['weekly_days']) {
                    return null;
                }
                
                $days = explode(',', $campaign['weekly_days']);
                $nextRun = clone $now;
                
                // Find next occurrence
                for ($i = 0; $i < 7; $i++) {
                    $dayOfWeek = (int)$nextRun->format('N'); // 1=Monday, 7=Sunday
                    
                    if (in_array($dayOfWeek, $days)) {
                        // Set time from start_date and start_time
                        $startTime = new \DateTime($campaign['start_date'] . ' ' . ($campaign['start_time'] ?? '00:00'), new \DateTimeZone($timezone));
                        $nextRun->setTime($startTime->format('H'), $startTime->format('i'), 0);
                        
                        if ($nextRun > $now) {
                            return $nextRun->format('Y-m-d H:i:s');
                        }
                    }
                    
                    $nextRun->modify('+1 day');
                }
                
                return $nextRun->format('Y-m-d H:i:s');
                
            case 'monthly':
                if (!$campaign['monthly_day']) {
                    return null;
                }
                
                $nextRun = clone $now;
                $startTime = new \DateTime($campaign['start_date'] . ' ' . ($campaign['start_time'] ?? '00:00'), new \DateTimeZone($timezone));
                $nextRun->setDate($nextRun->format('Y'), $nextRun->format('m'), $campaign['monthly_day']);
                $nextRun->setTime($startTime->format('H'), $startTime->format('i'), 0);
                
                // If date has passed this month, move to next month
                if ($nextRun <= $now) {
                    $nextRun->modify('+1 month');
                }
                
                return $nextRun->format('Y-m-d H:i:s');
                
            default:
                return null;
        }
    }
    
    public static function updateNextRun($id)
    {
        $campaign = self::findById($id);
        if (!$campaign) {
            return false;
        }
        
        $nextRun = self::calculateNextRun($campaign);
        
        if ($nextRun) {
            DB::q("UPDATE campaigns SET next_run_at = ? WHERE id = ?", [$nextRun, $id]);
        }
        
        return $nextRun;
    }
    
    public static function getMessages($campaignId)
    {
        return DB::fetchAll("
            SELECT * FROM campaign_messages
            WHERE campaign_id = ?
            ORDER BY sort_order ASC
        ", [$campaignId]);
    }
    
    public static function addMessage($campaignId, $data)
    {
        $sortOrder = $data['sort_order'] ?? (DB::fetch("SELECT MAX(sort_order) as max_order FROM campaign_messages WHERE campaign_id = ?", [$campaignId])['max_order'] + 1);
        
        DB::q("
            INSERT INTO campaign_messages (campaign_id, sort_order, text, media_path, media_type, caption)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $campaignId,
            $sortOrder,
            $data['text'],
            $data['media_path'] ?? null,
            $data['media_type'] ?? null,
            $data['caption'] ?? null
        ]);
        
        return DB::lastInsertId();
    }
    
    public static function updateMessage($id, $data)
    {
        $fields = [];
        $params = [];
        
        $updatableFields = ['sort_order', 'text', 'media_path', 'media_type', 'caption'];
        
        foreach ($updatableFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (!empty($fields)) {
            $params[] = $id;
            DB::q("UPDATE campaign_messages SET " . implode(', ', $fields) . " WHERE id = ?", $params);
        }
    }
    
    public static function deleteMessage($id)
    {
        DB::q("DELETE FROM campaign_messages WHERE id = ?", [$id]);
    }
    
    public static function getTargets($campaignId)
    {
        return DB::fetchAll("
            SELECT ct.*, 
                   CASE ct.target_type
                       WHEN 'contact' THEN c.name
                       WHEN 'list' THEN cl.name
                   END as target_name
            FROM campaign_targets ct
            LEFT JOIN contacts c ON ct.target_type = 'contact' AND ct.target_id = c.id
            LEFT JOIN contact_lists cl ON ct.target_type = 'list' AND ct.target_id = cl.id
            WHERE ct.campaign_id = ?
        ", [$campaignId]);
    }
    
    public static function addTarget($campaignId, $targetType, $targetId)
    {
        DB::q("
            INSERT IGNORE INTO campaign_targets (campaign_id, target_type, target_id)
            VALUES (?, ?, ?)
        ", [$campaignId, $targetType, $targetId]);
    }
    
    public static function removeTarget($campaignId, $targetType, $targetId)
    {
        DB::q("
            DELETE FROM campaign_targets 
            WHERE campaign_id = ? AND target_type = ? AND target_id = ?
        ", [$campaignId, $targetType, $targetId]);
    }
    
    public static function getRecipients($campaignId)
    {
        $recipients = [];
        
        // Get individual contacts
        $contacts = DB::fetchAll("
            SELECT DISTINCT c.* 
            FROM campaign_targets ct
            JOIN contacts c ON ct.target_type = 'contact' AND ct.target_id = c.id
            WHERE ct.campaign_id = ?
        ", [$campaignId]);
        
        foreach ($contacts as $contact) {
            $recipients[] = $contact;
        }
        
        // Get contacts from lists
        $listContacts = DB::fetchAll("
            SELECT DISTINCT c.* 
            FROM campaign_targets ct
            JOIN contact_list_items cli ON ct.target_type = 'list' AND ct.target_id = cli.list_id
            JOIN contacts c ON cli.contact_id = c.id
            WHERE ct.campaign_id = ?
        ", [$campaignId]);
        
        foreach ($listContacts as $contact) {
            $recipients[] = $contact;
        }
        
        // Remove duplicates
        $uniqueRecipients = [];
        $seen = [];
        
        foreach ($recipients as $contact) {
            $key = $contact['instance_id'] . '_' . $contact['phone_e164'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueRecipients[] = $contact;
            }
        }
        
        return $uniqueRecipients;
    }
}
