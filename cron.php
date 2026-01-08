#!/usr/bin/env php
<?php
/**
 * EVOAPP Cron Runner
 * Processes webhook events, campaigns, and maintenance tasks
 */

// Set working directory
$workingDir = dirname(__FILE__);
chdir($workingDir);

// Load configuration
require_once __DIR__ . '/config/config.php';

// Initialize autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize database
App\Core\DB::init();

// Log cron start
$logJob = 'cron_main_' . date('Y-m-d_H-i-s');
App\Core\DB::q("
    INSERT INTO cron_log (job_key, started_at, ok)
    VALUES (?, NOW(), 0)
", [$logJob]);

$logId = App\Core\DB::lastInsertId();
$summary = [];
$errors = [];

try {
    // Process pending webhook events
    $summary['webhooks'] = processWebhookEvents();
    
    // Run due campaigns
    $summary['campaigns'] = runDueCampaigns();
    
    // Cleanup old data
    $summary['cleanup'] = performCleanup();
    
    // Update campaign schedules
    $summary['schedules'] = updateCampaignSchedules();
    
    // Mark cron as successful
    App\Core\DB::q("
        UPDATE cron_log 
        SET finished_at = NOW(), ok = 1, summary = ?
        WHERE id = ?
    ", [json_encode($summary), $logId]);
    
} catch (Exception $e) {
    // Log error
    App\Core\DB::q("
        UPDATE cron_log 
        SET finished_at = NOW(), ok = 0, error_text = ?
        WHERE id = ?
    ", [$e->getMessage(), $logId]);
    
    throw $e;
}

// Functions

function processWebhookEvents()
{
    $processed = 0;
    $errors = 0;
    
    $events = App\Models\WebhookEvent::getPending(100);
    
    foreach ($events as $event) {
        try {
            $controller = new App\Controllers\WebhookController();
            $controller->processWebhookEvent($event['id']);
            $processed++;
        } catch (Exception $e) {
            App\Models\WebhookEvent::markProcessed($event['id'], $e->getMessage());
            $errors++;
        }
    }
    
    return [
        'processed' => $processed,
        'errors' => $errors,
        'total' => $processed + $errors
    ];
}

function runDueCampaigns()
{
    $ran = 0;
    $sent = 0;
    $failed = 0;
    
    $campaigns = App\Models\Campaign::getDueCampaigns();
    
    foreach ($campaigns as $campaign) {
        try {
            $runId = startCampaignRun($campaign['id']);
            $result = executeCampaign($campaign['id'], $runId);
            
            $ran++;
            $sent += $result['sent'];
            $failed += $result['failed'];
            
            // Update next run time
            App\Models\Campaign::updateNextRun($campaign['id']);
            
        } catch (Exception $e) {
            $errors[] = "Campaign {$campaign['id']}: " . $e->getMessage();
        }
    }
    
    return [
        'campaigns_run' => $ran,
        'messages_sent' => $sent,
        'messages_failed' => $failed,
        'errors' => $errors ?? []
    ];
}

function startCampaignRun($campaignId)
{
    App\Core\DB::q("
        INSERT INTO campaign_runs (campaign_id, run_at, status, total)
        VALUES (?, NOW(), 'running', 0)
    ", [$campaignId]);
    
    return App\Core\DB::lastInsertId();
}

function executeCampaign($campaignId, $runId)
{
    $campaign = App\Models\Campaign::findById($campaignId);
    $messages = App\Models\Campaign::getMessages($campaignId);
    $recipients = App\Models\Campaign::getRecipients($campaignId);
    
    $sent = 0;
    $failed = 0;
    
    // Get instance for API client
    $instance = App\Models\Instance::findById($campaign['instance_id']);
    $client = App\Models\Instance::evoClient($instance);
    
    foreach ($recipients as $recipient) {
        foreach ($messages as $message) {
            try {
                if ($message['media_path']) {
                    // Send media message
                    $result = $client->sendMedia(
                        $instance['slug'],
                        $recipient['phone_e164'],
                        $message['media_path'],
                        $message['media_type'],
                        getMimeType($message['media_path']),
                        $message['caption']
                    );
                } else {
                    // Send text message
                    $result = $client->sendText(
                        $instance['slug'],
                        $recipient['phone_e164'],
                        $message['text']
                    );
                }
                
                // Log successful send
                App\Core\DB::q("
                    INSERT INTO campaign_run_items (run_id, contact_id, status, response_json, sent_at)
                    VALUES (?, ?, 'sent', ?, NOW())
                ", [$runId, $recipient['id'], json_encode($result)]);
                
                $sent++;
                
                // Rate limiting
                usleep(500000); // 0.5 seconds between messages
                
            } catch (Exception $e) {
                // Log failed send
                App\Core\DB::q("
                    INSERT INTO campaign_run_items (run_id, contact_id, status, error_text)
                    VALUES (?, ?, 'failed', ?)
                ", [$runId, $recipient['id'], $e->getMessage()]);
                
                $failed++;
            }
        }
    }
    
    // Update run status
    App\Core\DB::q("
        UPDATE campaign_runs 
        SET status = 'completed', total = ?, ok_count = ?, fail_count = ?, finished_at = NOW()
        WHERE id = ?
    ", [$sent + $failed, $sent, $failed, $runId]);
    
    return ['sent' => $sent, 'failed' => $failed];
}

function performCleanup()
{
    $cleaned = 0;
    
    // Clean old webhook events (keep 30 days)
    App\Models\WebhookEvent::cleanup(30);
    $cleaned++;
    
    // Clean old cron logs (keep 90 days)
    App\Core\DB::q("
        DELETE FROM cron_log 
        WHERE started_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
    ");
    $cleaned++;
    
    return ['tasks' => $cleaned];
}

function updateCampaignSchedules()
{
    $updated = 0;
    
    // Get all active campaigns
    $campaigns = App\Core\DB::fetchAll("
        SELECT id FROM campaigns 
        WHERE is_active = 1 
        AND (next_run_at IS NULL OR next_run_at <= NOW())
    ");
    
    foreach ($campaigns as $campaign) {
        if (App\Models\Campaign::updateNextRun($campaign['id'])) {
            $updated++;
        }
    }
    
    return ['campaigns_updated' => $updated];
}

function getMimeType($filePath)
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    return $mimeType ?: 'application/octet-stream';
}

echo "Cron completed successfully. Log ID: $logId\n";
echo "Summary: " . json_encode($summary, JSON_PRETTY_PRINT) . "\n";
