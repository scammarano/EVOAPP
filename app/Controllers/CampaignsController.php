<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\DB;
use App\Models\Campaign;
use App\Models\Instance;

class CampaignsController
{
    public function index()
    {
        if (!Auth::hasPermission('campaigns.view')) {
            View::set('error', 'No tienes permisos para ver campañas');
            View::render('error/403');
            return;
        }

        $instanceSlug = $_GET['instance'] ?? '';
        
        if (!$instanceSlug) {
            header('Location: index.php?r=dashboard/index');
            exit;
        }

        $instance = Instance::findBySlug($instanceSlug);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            View::set('error', 'Instancia no encontrada o sin permisos');
            View::render('error/404');
            return;
        }

        $page = (int)($_GET['page'] ?? 1);
        $campaigns = Campaign::getAll($instance['id'], $page, 20);
        $totalCampaigns = Campaign::count($instance['id']);

        View::set('instance', $instance);
        View::set('campaigns', $campaigns);
        View::set('page', $page);
        View::set('totalCampaigns', $totalCampaigns);
        
        View::render('campaigns/index');
    }

    public function create()
    {
        if (!Auth::hasPermission('campaigns.edit')) {
            View::set('error', 'No tienes permisos para editar campañas');
            View::render('error/403');
            return;
        }

        $instanceSlug = $_GET['instance'] ?? '';
        $instance = Instance::findBySlug($instanceSlug);
        
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            View::set('error', 'Instancia no encontrada o sin permisos');
            View::render('error/404');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

        View::set('instance', $instance);
        View::render('campaigns/create');
    }

    public function store()
    {
        if (!Auth::hasPermission('campaigns.edit')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $instanceSlug = $_POST['instance'] ?? $_GET['instance'] ?? '';
        $instance = Instance::findBySlug($instanceSlug);
        
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $user = Auth::getCurrentUser();
        
        $data = [
            'instance_id' => $instance['id'],
            'name' => $_POST['name'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'schedule_type' => $_POST['schedule_type'] ?? 'once',
            'start_date' => $_POST['start_date'] ?? '',
            'start_time' => $_POST['start_time'] ?? '',
            'end_date' => $_POST['end_date'] ?? '',
            'end_time' => $_POST['end_time'] ?? '',
            'daily_time' => $_POST['daily_time'] ?? '',
            'timezone' => $_POST['timezone'] ?? 'America/Bogota',
            'weekly_days' => $_POST['weekly_days'] ?? null,
            'monthly_day' => $_POST['monthly_day'] ?? null,
            'created_by' => $user['id']
        ];

        // Combine date and time for database
        if (!empty($data['start_date']) && !empty($data['start_time'])) {
            $data['start_at'] = $data['start_date'] . ' ' . $data['start_time'];
        }
        
        if (!empty($data['end_date']) && !empty($data['end_time'])) {
            $data['end_at'] = $data['end_date'] . ' ' . $data['end_time'];
        }

        // Calculate next run time
        $data['next_run_at'] = Campaign::calculateNextRun($data);

        // Validate
        $errors = $this->validateCampaignData($data);
        if (!empty($errors)) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            } else {
                View::set('errors', $errors);
                View::set('data', $data);
                View::set('instance', $instance);
                View::render('campaigns/create');
                return;
            }
        }

        try {
            $campaignId = Campaign::create($data);
            
            // Add messages
            if (isset($_POST['messages']) && is_array($_POST['messages'])) {
                foreach ($_POST['messages'] as $index => $messageData) {
                    $text = trim($messageData['text'] ?? '');
                    $mediaPath = trim($messageData['media_path'] ?? '');
                    if ($text !== '' || $mediaPath !== '') {
                        $msgData = [
                            'campaign_id' => $campaignId,
                            'sort_order' => $index + 1,
                            'text' => $text,
                            'media_path' => $mediaPath !== '' ? $mediaPath : null,
                            'media_type' => $messageData['media_type'] ?? null,
                            'caption' => $messageData['caption'] ?? ''
                        ];

                        Campaign::addMessage($campaignId, $msgData);
                    }
                }
            }

            // Add targets
            if (isset($_POST['targets']) && is_array($_POST['targets'])) {
                foreach ($_POST['targets'] as $target) {
                    if (!empty($target['type']) && !empty($target['id'])) {
                        Campaign::addTarget($campaignId, $target['type'], $target['id']);
                    }
                }
            }
            
            // Log action
            Auth::logAction('create_campaign', 'campaign', $campaignId, null, $data);

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Campaña creada correctamente', 'campaign_id' => $campaignId]);
            } else {
                View::flash('success', 'Campaña creada correctamente');
                header('Location: index.php?r=campaigns/index&instance=' . urlencode($instance['slug']));
                exit;
            }

        } catch (\Exception $e) {
            $error = 'Error al crear campaña: ' . $e->getMessage();
            
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $error]);
            } else {
                View::set('error', $error);
                View::set('data', $data);
                View::set('instance', $instance);
                View::render('campaigns/create');
            }
        }
    }

    public function edit()
    {
        if (!Auth::hasPermission('campaigns.edit')) {
            View::set('error', 'No tienes permisos para editar campañas');
            View::render('error/403');
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $campaign = Campaign::findById($id);

        if (!$campaign) {
            View::set('error', 'Campaña no encontrada');
            View::render('error/404');
            return;
        }

        $instance = Instance::findById($campaign['instance_id']);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            View::set('error', 'Instancia no encontrada o sin permisos');
            View::render('error/404');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($id);
            return;
        }

        // Get campaign messages and targets
        $messages = Campaign::getMessages($id);
        $targets = Campaign::getTargets($id);

        View::set('instance', $instance);
        View::set('campaign', $campaign);
        View::set('messages', $messages);
        View::set('targets', $targets);
        View::render('campaigns/edit');
    }

    public function update($id)
    {
        if (!Auth::hasPermission('campaigns.edit')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $campaign = Campaign::findById($id);
        if (!$campaign) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Campaign not found']);
            return;
        }

        $instance = Instance::findById($campaign['instance_id']);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $data = [
            'name' => $_POST['name'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'schedule_type' => $_POST['schedule_type'] ?? 'once',
            'start_date' => $_POST['start_date'] ?? '',
            'start_time' => $_POST['start_time'] ?? '',
            'end_date' => $_POST['end_date'] ?? '',
            'end_time' => $_POST['end_time'] ?? '',
            'daily_time' => $_POST['daily_time'] ?? '',
            'timezone' => $_POST['timezone'] ?? 'America/Bogota',
            'weekly_days' => $_POST['weekly_days'] ?? null,
            'monthly_day' => $_POST['monthly_day'] ?? null
        ];

        // Combine date and time for database
        if (!empty($data['start_date']) && !empty($data['start_time'])) {
            $data['start_at'] = $data['start_date'] . ' ' . $data['start_time'];
        }
        
        if (!empty($data['end_date']) && !empty($data['end_time'])) {
            $data['end_at'] = $data['end_date'] . ' ' . $data['end_time'];
        }

        // Combine date and time for database
        if (!empty($data['start_date']) && !empty($data['start_time'])) {
            $data['start_at'] = $data['start_date'] . ' ' . $data['start_time'];
        }
        
        if (!empty($data['end_date']) && !empty($data['end_time'])) {
            $data['end_at'] = $data['end_date'] . ' ' . $data['end_time'];
        }

        // Calculate next run time
        $data['next_run_at'] = Campaign::calculateNextRun($data);

        // Validate
        $errors = $this->validateCampaignData($data, $id);
        if (!empty($errors)) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            } else {
                View::set('errors', $errors);
                View::set('campaign', array_merge($campaign, $data));
                View::set('instance', $instance);
                View::render('campaigns/edit');
                return;
            }
        }

        try {
            DB::beginTransaction();
            Campaign::update($id, $data);

            $messages = $_POST['messages'] ?? [];
            if (!is_array($messages)) {
                $messages = [];
            }
            Campaign::replaceMessages($id, $messages);

            $targets = $_POST['targets'] ?? [];
            if (!is_array($targets)) {
                $targets = [];
            }
            Campaign::replaceTargets($id, $targets);
            DB::commit();
            
            // Log action
            Auth::logAction('update_campaign', 'campaign', $id, $campaign, $data);

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Campaña actualizada correctamente']);
            } else {
                View::flash('success', 'Campaña actualizada correctamente');
                header('Location: index.php?r=campaigns/index&instance=' . urlencode($instance['slug']));
                exit;
            }

        } catch (\Exception $e) {
            DB::rollback();
            $error = 'Error al actualizar campaña: ' . $e->getMessage();
            
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $error]);
            } else {
                View::set('error', $error);
                View::set('campaign', array_merge($campaign, $data));
                View::set('instance', $instance);
                View::render('campaigns/edit');
            }
        }
    }

    public function delete()
    {
        if (!Auth::hasPermission('campaigns.edit')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $campaign = Campaign::findById($id);

        if (!$campaign) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Campaign not found']);
            return;
        }

        $instance = Instance::findById($campaign['instance_id']);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        try {
            Campaign::delete($id);
            
            // Log action
            Auth::logAction('delete_campaign', 'campaign', $id, $campaign);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Campaña eliminada correctamente']);

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al eliminar campaña: ' . $e->getMessage()]);
        }
    }

    public function run()
    {
        if (!Auth::hasPermission('campaigns.execute')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $campaign = Campaign::findById($id);

        if (!$campaign) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Campaign not found']);
            return;
        }

        $instance = Instance::findById($campaign['instance_id']);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        try {
            // Execute campaign immediately
            $runId = startCampaignRun($id);
            $result = executeCampaign($id, $runId);
            
            // Log action
            Auth::logAction('execute_campaign', 'campaign', $id, null, [
                'run_id' => $runId,
                'result' => $result
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Campaña ejecutada correctamente',
                'sent' => $result['sent'],
                'failed' => $result['failed']
            ]);

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al ejecutar campaña: ' . $e->getMessage()]);
        }
    }

    private function isAjaxRequest()
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return stripos($accept, 'application/json') !== false;
    }

    private function validateCampaignData($data, $excludeId = null)
    {
        $errors = [];

        // Name validation
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es obligatorio';
        }

        // Schedule validation
        $validScheduleTypes = ['once', 'daily', 'weekly', 'monthly'];
        if (!in_array($data['schedule_type'], $validScheduleTypes)) {
            $errors['schedule_type'] = 'Tipo de programación no válido';
        }

        // Weekly days validation
        if ($data['schedule_type'] === 'weekly' && empty($data['weekly_days'])) {
            $errors['weekly_days'] = 'Debe seleccionar al menos un día para programación semanal';
        }

        // Monthly day validation
        if ($data['schedule_type'] === 'monthly' && (empty($data['monthly_day']) || $data['monthly_day'] < 1 || $data['monthly_day'] > 31)) {
            $errors['monthly_day'] = 'El día del mes debe estar entre 1 y 31';
        }

        return $errors;
    }
}

// Helper functions (moved from cron.php)
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
    $campaign = Campaign::findById($campaignId);
    $messages = Campaign::getMessages($campaignId);
    $recipients = Campaign::getRecipients($campaignId);
    
    $sent = 0;
    $failed = 0;
    
    // Get instance for API client
    $instance = Instance::findById($campaign['instance_id']);
    $client = Instance::evoClient($instance);
    
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
                
            } catch (\Exception $e) {
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

function getMimeType($filePath)
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    return $mimeType ?: 'application/octet-stream';
}
