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
        // Handle end_date without end_time - use far future date
        if (!empty($data['end_date']) && empty($data['end_time'])) {
            $maxDays = self::getMaxCampaignDays();
            $farFuture = new \DateTime($data['end_date']);
            $farFuture->modify('+' . $maxDays . ' days');
            $data['end_time'] = '23:59';
            $data['end_date'] = $farFuture->format('Y-m-d');
        }
        
        DB::q("
            INSERT INTO campaigns (instance_id, name, is_active, schedule_type, start_date, start_time, start_at, end_date, end_time, daily_time, end_at, timezone, weekly_days, monthly_day, next_run_at, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ", [
            $data['instance_id'],
            $data['name'],
            $data['is_active'] ?? 1,
            $data['schedule_type'],
            $data['start_date'],
            $data['start_time'],
            $data['start_date'] . ' ' . ($data['start_time'] ?? '00:00'),
            $data['end_date'] ?? null,
            $data['end_time'] ?? null,
            $data['daily_time'] ?? null,
            $data['end_date'] ? $data['end_date'] . ' ' . ($data['end_time'] ?? '23:59') : null,
            $data['timezone'] ?? 'America/Bogota',
            $data['weekly_days'] ?? null,
            $data['monthly_day'] ?? null,
            $data['next_run_at'] ?? null,
            $data['created_by']
        ]);
        
        return DB::lastInsertId();
    }
    
    public static function getMaxCampaignDays()
    {
        // Try to get from parameters table first
        try {
            $param = DB::fetch("SELECT value FROM system_parameters WHERE parameter_key = 'max_campaign_days' LIMIT 1");
            if ($param && is_numeric($param['value'])) {
                return (int)$param['value'];
            }
        } catch (\Exception $e) {
            // Table might not exist, continue with default
        }
        
        // Default to 5 years (1825 days)
        return 1825;
    }
    
    public static function setMaxCampaignDays($days)
    {
        try {
            // Update existing parameter
            DB::q("UPDATE system_parameters SET value = ?, updated_at = NOW() WHERE parameter_key = 'max_campaign_days'", [$days]);
            
            // Check if row was updated
            $affected = DB::fetch("SELECT ROW_COUNT() as affected");
            if ($affected['affected'] > 0) {
                return true;
            }
            
            // Insert new parameter if it didn't exist
            DB::q("INSERT INTO system_parameters (parameter_key, value, description, created_at, updated_at) VALUES (?, ?, 'Maximum duration in days for campaigns', NOW(), NOW())", 
                  ['max_campaign_days', $days]);
            
            return true;
            
        } catch (\Exception $e) {
            // Try to create table if it doesn't exist
            try {
                DB::q("
                    CREATE TABLE IF NOT EXISTS system_parameters (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        parameter_key VARCHAR(100) UNIQUE NOT NULL,
                        value TEXT NOT NULL,
                        description TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )
                ");
                
                // Retry the insert
                DB::q("INSERT INTO system_parameters (parameter_key, value, description, created_at, updated_at) VALUES (?, ?, 'Maximum duration in days for campaigns', NOW(), NOW())", 
                      ['max_campaign_days', $days]);
                
                return true;
                
            } catch (\Exception $e2) {
                throw new \Exception('Could not create or update system parameters table: ' . $e2->getMessage());
            }
        }
    }
    
    public static function update($id, $data)
    {
        // Handle end_date without end_time - use far future date
        if (isset($data['end_date']) && !empty($data['end_date']) && (empty($data['end_time']) || $data['end_time'] === '')) {
            $maxDays = self::getMaxCampaignDays();
            $farFuture = new \DateTime($data['end_date']);
            $farFuture->modify('+' . $maxDays . ' days');
            $data['end_time'] = '23:59';
            $data['end_date'] = $farFuture->format('Y-m-d');
        }
        
        $fields = [];
        $params = [];
        
        $updatableFields = ['name', 'is_active', 'schedule_type', 'start_date', 'start_time', 'end_date', 'end_time', 'daily_time', 'timezone', 'weekly_days', 'monthly_day', 'next_run_at'];
        
        // Handle start_at update if start_date or start_time changed
        if (isset($data['start_date']) || isset($data['start_time'])) {
            $campaign = self::findById($id);
            $startDate = $data['start_date'] ?? date('Y-m-d', strtotime($campaign['start_at']));
            $startTime = $data['start_time'] ?? date('H:i', strtotime($campaign['start_at']));
            $fields[] = "start_at = ?";
            $params[] = $startDate . ' ' . $startTime;
        }
        
        // Handle end_at update if end_date or end_time changed
        if (isset($data['end_date']) || isset($data['end_time'])) {
            if ($data['end_date'] || $data['end_time']) {
                $campaign = self::findById($id);
                $endDate = $data['end_date'] ?? (!empty($campaign['end_at']) ? date('Y-m-d', strtotime($campaign['end_at'])) : null);
                $endTime = $data['end_time'] ?? (!empty($campaign['end_at']) ? date('H:i', strtotime($campaign['end_at'])) : '23:59');
                $fields[] = "end_at = ?";
                $params[] = $endDate ? $endDate . ' ' . $endTime : null;
            } else {
                $fields[] = "end_at = ?";
                $params[] = null;
            }
        }
        
        foreach ($updatableFields as $field) {
            if (isset($data[$field]) && !in_array($field, ['start_date', 'start_time', 'end_date', 'end_time'])) {
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
        
        // Check if campaign is within its validity period
        $campaignStart = new \DateTime($campaign['start_at'], new \DateTimeZone($timezone));
        $campaignEnd = !empty($campaign['end_at']) ? new \DateTime($campaign['end_at'], new \DateTimeZone($timezone)) : null;
        
        if ($now < $campaignStart) {
            // Campaign hasn't started yet, return first run time
            return self::calculateFirstRun($campaign, $campaignStart);
        }
        
        if ($campaignEnd && $now > $campaignEnd) {
            // Campaign has ended
            return null;
        }
        
        // Campaign is active, calculate next run based on schedule type
        return self::calculateNextExecution($campaign, $now);
    }
    
    private static function calculateFirstRun($campaign, $campaignStart)
    {
        $timezone = $campaign['timezone'] ?? 'America/Bogota';
        
        switch ($campaign['schedule_type']) {
            case 'once':
                return $campaignStart->format('Y-m-d H:i:s');
                
            case 'daily':
                $executionTime = $campaign['daily_time'] ?? '09:00:00';
                $firstRun = clone $campaignStart;
                
                // Safe parse of execution time
                $timeParts = explode(':', $executionTime);
                $hour = (int)($timeParts[0] ?? 9);
                $minute = (int)($timeParts[1] ?? 0);
                $firstRun->setTime($hour, $minute, 0);
                
                // If execution time is before campaign start on first day, adjust
                if ($firstRun < $campaignStart) {
                    $firstRun->modify('+1 day');
                }
                return $firstRun->format('Y-m-d H:i:s');
                
            case 'weekly':
                if (!$campaign['weekly_days']) {
                    return null;
                }
                
                $executionTime = $campaign['daily_time'] ?? '09:00:00'; // Reuse daily_time for weekly execution time
                
                // Safe parse of execution time
                $timeParts = explode(':', $executionTime);
                $hour = (int)($timeParts[0] ?? 9);
                $minute = (int)($timeParts[1] ?? 0);
                
                $firstRun = clone $campaignStart;
                $days = explode(',', $campaign['weekly_days']);
                
                // Find first valid day on or after campaign start
                for ($i = 0; $i < 7; $i++) {
                    $dayOfWeek = (int)$firstRun->format('N'); // 1=Monday, 7=Sunday
                    
                    if (in_array($dayOfWeek, $days)) {
                        $firstRun->setTime((int)$hour, (int)$minute, 0);
                        
                        if ($firstRun >= $campaignStart) {
                            return $firstRun->format('Y-m-d H:i:s');
                        }
                    }
                    
                    $firstRun->modify('+1 day');
                }
                
                return $firstRun->format('Y-m-d H:i:s');
                
            case 'monthly':
                $executionTime = $campaign['daily_time'] ?? '09:00:00'; // Reuse daily_time for monthly execution time
                
                // Safe parse of execution time
                $timeParts = explode(':', $executionTime);
                $hour = (int)($timeParts[0] ?? 9);
                $minute = (int)($timeParts[1] ?? 0);
                
                $firstRun = clone $campaignStart;
                
                // Handle special monthly types
                if ($campaign['monthly_day'] === 'first_day') {
                    $firstRun->modify('first day of this month');
                } elseif ($campaign['monthly_day'] === 'first_weekday') {
                    $firstRun->modify('first weekday of this month');
                } else {
                    // Regular day number
                    $day = (int)($campaign['monthly_day'] ?? 1);
                    $firstRun->setDate($firstRun->format('Y'), $firstRun->format('m'), $day);
                }
                
                $firstRun->setTime((int)$hour, (int)$minute, 0);
                
                // If calculated date is before campaign start, move to next month
                if ($firstRun < $campaignStart) {
                    $firstRun->modify('+1 month');
                    
                    // Recalculate for next month
                    if ($campaign['monthly_day'] === 'first_day') {
                        $firstRun->modify('first day of this month');
                    } elseif ($campaign['monthly_day'] === 'first_weekday') {
                        $firstRun->modify('first weekday of this month');
                    }
                }
                
                return $firstRun->format('Y-m-d H:i:s');
                
            default:
                return null;
        }
    }
    
    private static function calculateNextExecution($campaign, $now)
    {
        $timezone = $campaign['timezone'] ?? 'America/Bogota';
        
        switch ($campaign['schedule_type']) {
            case 'once':
                return null; // Already executed
                
            case 'daily':
                $executionTime = $campaign['daily_time'] ?? '09:00:00';
                $nextRun = clone $now;
                
                // Safe parse of execution time
                $timeParts = explode(':', $executionTime);
                $hour = (int)($timeParts[0] ?? 9);
                $minute = (int)($timeParts[1] ?? 0);
                $nextRun->setTime($hour, $minute, 0);
                
                // If time has passed today, move to tomorrow
                if ($nextRun <= $now) {
                    $nextRun->modify('+1 day');
                }
                
                return $nextRun->format('Y-m-d H:i:s');
                
            case 'weekly':
                if (!$campaign['weekly_days']) {
                    return null;
                }
                
                $executionTime = $campaign['daily_time'] ?? '09:00:00';
                
                // Safe parse of execution time
                $timeParts = explode(':', $executionTime);
                $hour = (int)($timeParts[0] ?? 9);
                $minute = (int)($timeParts[1] ?? 0);
                
                $days = explode(',', $campaign['weekly_days']);
                $nextRun = clone $now;
                
                // Find next occurrence from today onwards
                for ($i = 0; $i < 14; $i++) { // Check 2 weeks ahead
                    $dayOfWeek = (int)$nextRun->format('N'); // 1=Monday, 7=Sunday
                    
                    if (in_array($dayOfWeek, $days)) {
                        $testRun = clone $nextRun;
                        $testRun->setTime((int)$hour, (int)$minute, 0);
                        
                        if ($testRun > $now) {
                            return $testRun->format('Y-m-d H:i:s');
                        }
                    }
                    
                    $nextRun->modify('+1 day');
                }
                
                // If no occurrence found in next 2 weeks, calculate from next week
                $nextRun = clone $now;
                $nextRun->modify('next monday');
                $nextRun->setTime((int)$hour, (int)$minute, 0);
                
                // Find first valid day in next week
                for ($i = 0; $i < 7; $i++) {
                    $dayOfWeek = (int)$nextRun->format('N');
                    if (in_array($dayOfWeek, $days)) {
                        return $nextRun->format('Y-m-d H:i:s');
                    }
                    $nextRun->modify('+1 day');
                }
                
                return $nextRun->format('Y-m-d H:i:s');
                
            case 'monthly':
                $executionTime = $campaign['daily_time'] ?? '09:00:00';
                
                // Safe parse of execution time
                $timeParts = explode(':', $executionTime);
                $hour = (int)($timeParts[0] ?? 9);
                $minute = (int)($timeParts[1] ?? 0);
                
                $nextRun = clone $now;
                
                // Handle special monthly types
                if ($campaign['monthly_day'] === 'first_day') {
                    // First day of the month
                    $nextRun->modify('first day of next month');
                    $nextRun->setTime((int)$hour, (int)$minute, 0);
                    
                } elseif ($campaign['monthly_day'] === 'first_weekday') {
                    // First weekday of the month
                    $nextRun->modify('first weekday of next month');
                    $nextRun->setTime((int)$hour, (int)$minute, 0);
                    
                } elseif ($campaign['monthly_day'] === 'first_fortnight') {
                    // First weekday of first fortnight (days 1-5)
                    $firstDay = clone $nextRun;
                    $firstDay->modify('first day of this month');
                    
                    // Find first weekday between 1st and 5th
                    for ($i = 0; $i < 5; $i++) {
                        $dayOfWeek = (int)$firstDay->format('N');
                        if ($dayOfWeek <= 5) { // Monday-Friday
                            $firstDay->setTime((int)$hour, (int)$minute, 0);
                            if ($firstDay > $now) {
                                return $firstDay->format('Y-m-d H:i:s');
                            }
                        }
                        $firstDay->modify('+1 day');
                    }
                    
                    // If passed, calculate for next month
                    $nextRun->modify('first day of next month');
                    for ($i = 0; $i < 5; $i++) {
                        $dayOfWeek = (int)$nextRun->format('N');
                        if ($dayOfWeek <= 5) {
                            $nextRun->setTime((int)$hour, (int)$minute, 0);
                            return $nextRun->format('Y-m-d H:i:s');
                        }
                        $nextRun->modify('+1 day');
                    }
                    
                } elseif ($campaign['monthly_day'] === 'second_fortnight') {
                    // First weekday after 15th
                    $day15 = clone $nextRun;
                    $day15->setDate($day15->format('Y'), $day15->format('m'), 15);
                    
                    if ($day15 <= $now) {
                        // If 15th has passed, go to next month
                        $day15->modify('first day of next month');
                    } else {
                        // Still before 15th, start from 16th
                        $day15->modify('+1 day');
                    }
                    
                    // Find first weekday after 15th
                    for ($i = 0; $i < 7; $i++) {
                        $dayOfWeek = (int)$day15->format('N');
                        if ($dayOfWeek <= 5) { // Monday-Friday
                            $day15->setTime((int)$hour, (int)$minute, 0);
                            return $day15->format('Y-m-d H:i:s');
                        }
                        $day15->modify('+1 day');
                    }
                    
                } else {
                    // Regular day number
                    $day = (int)($campaign['monthly_day'] ?? 1);
                    $nextRun->setDate($nextRun->format('Y'), $nextRun->format('m'), $day);
                    $nextRun->setTime((int)$hour, (int)$minute, 0);
                    
                    // If date has passed this month, move to next month
                    if ($nextRun <= $now) {
                        $nextRun->modify('+1 month');
                    }
                }
                
                return $nextRun->format('Y-m-d H:i:s');
                
            default:
                return null;
        }
    }
    
    public static function duplicate($id, $newName = null)
    {
        $originalCampaign = self::findById($id);
        if (!$originalCampaign) {
            return false;
        }

        // Get all related data
        $messages = self::getMessages($id);
        $targets = self::getTargets($id);

        // Prepare campaign data
        $campaignData = [
            'instance_id' => $originalCampaign['instance_id'],
            'name' => $newName ?? $originalCampaign['name'] . ' (Copia)',
            'is_active' => 0, // Start inactive
            'schedule_type' => $originalCampaign['schedule_type'],
            'start_date' => $originalCampaign['start_date'],
            'start_time' => $originalCampaign['start_time'],
            'end_date' => $originalCampaign['end_date'],
            'end_time' => $originalCampaign['end_time'],
            'daily_time' => $originalCampaign['daily_time'],
            'timezone' => $originalCampaign['timezone'],
            'weekly_days' => $originalCampaign['weekly_days'],
            'monthly_day' => $originalCampaign['monthly_day'],
            'created_by' => $originalCampaign['created_by']
        ];

        // Combine date and time for database
        if (!empty($campaignData['start_date']) && !empty($campaignData['start_time'])) {
            $campaignData['start_at'] = $campaignData['start_date'] . ' ' . $campaignData['start_time'];
        }
        
        if (!empty($campaignData['end_date']) && !empty($campaignData['end_time'])) {
            $campaignData['end_at'] = $campaignData['end_date'] . ' ' . $campaignData['end_time'];
        }

        // Calculate next run time
        $campaignData['next_run_at'] = self::calculateNextRun($campaignData);

        try {
            DB::beginTransaction();

            // Create the duplicate campaign
            $newCampaignId = self::create($campaignData);

            // Duplicate messages
            foreach ($messages as $message) {
                self::addMessage($newCampaignId, [
                    'sort_order' => $message['sort_order'],
                    'text' => $message['text'],
                    'media_path' => $message['media_path'],
                    'media_type' => $message['media_type'],
                    'caption' => $message['caption']
                ]);
            }

            // Duplicate targets
            foreach ($targets as $target) {
                self::addTarget($newCampaignId, $target['target_type'], $target['target_id']);
            }

            DB::commit();
            return $newCampaignId;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
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
    
    public static function executeAndUpdateNextRun($id)
    {
        try {
            // Execute campaign (this would call the actual execution logic)
            $result = self::executeCampaign($id);
            
            // Update next run time immediately after execution
            self::updateNextRun($id);
            
            return $result;
            
        } catch (\Exception $e) {
            // Log error but still try to update next run
            self::updateNextRun($id);
            throw $e;
        }
    }
    
    private static function executeCampaign($id)
    {
        // This method would contain the actual campaign execution logic
        // For now, return a mock result
        return [
            'sent' => 0,
            'failed' => 0,
            'message' => 'Campaign execution completed'
        ];
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

    public static function replaceMessages($campaignId, $messages)
    {
        DB::q("DELETE FROM campaign_messages WHERE campaign_id = ?", [$campaignId]);

        foreach ($messages as $index => $message) {
            $text = trim($message['text'] ?? '');
            $mediaPath = trim($message['media_path'] ?? '');
            $mediaType = trim($message['media_type'] ?? '');
            $caption = trim($message['caption'] ?? '');

            if ($text === '' && $mediaPath === '') {
                continue;
            }

            DB::q("
                INSERT INTO campaign_messages (campaign_id, sort_order, text, media_path, media_type, caption)
                VALUES (?, ?, ?, ?, ?, ?)
            ", [
                $campaignId,
                $index + 1,
                $text,
                $mediaPath !== '' ? $mediaPath : null,
                $mediaType !== '' ? $mediaType : null,
                $caption !== '' ? $caption : null
            ]);
        }
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

    public static function replaceTargets($campaignId, $targets)
    {
        DB::q("DELETE FROM campaign_targets WHERE campaign_id = ?", [$campaignId]);

        foreach ($targets as $target) {
            $type = $target['type'] ?? $target['target_type'] ?? '';
            $targetId = $target['id'] ?? $target['target_id'] ?? '';
            if (!in_array($type, ['contact', 'list'], true) || $targetId === '') {
                continue;
            }

            DB::q("
                INSERT INTO campaign_targets (campaign_id, target_type, target_id)
                VALUES (?, ?, ?)
            ", [$campaignId, $type, $targetId]);
        }
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
