<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
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

        $instanceSlug = $_POST['instance'] ?? '';
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
            'start_at' => $_POST['start_at'] ?? '',
            'end_at' => $_POST['end_at'] ?? null,
            'timezone' => $_POST['timezone'] ?? 'America/Bogota',
            'weekly_days' => $_POST['weekly_days'] ?? null,
            'monthly_day' => $_POST['monthly_day'] ?? null,
            'created_by' => $user['id']
        ];

        // Calculate next run time
        $data['next_run_at'] = Campaign::calculateNextRun($data);

        // Validate
        $errors = $this->validateCampaignData($data);
        if (!empty($errors)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => $errors]);
            return;
        }

        try {
            $campaignId = Campaign::create($data);
            
            // Add messages
            if (isset($_POST['messages']) && is_array($_POST['messages'])) {
                foreach ($_POST['messages'] as $index => $messageData) {
                    if (!empty($messageData['text'])) {
                        $msgData = [
                            'campaign_id' => $campaignId,
                            'sort_order' => $index + 1,
                            'text' => $messageData['text'],
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

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Campaña creada correctamente', 'campaign_id' => $campaignId]);

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al crear campaña: ' . $e->getMessage()]);
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
            'start_at' => $_POST['start_at'] ?? '',
            'end_at' => $_POST['end_at'] ?? null,
            'timezone' => $_POST['timezone'] ?? 'America/Bogota',
            'weekly_days' => $_POST['weekly_days'] ?? null,
            'monthly_day' => $_POST['monthly_day'] ?? null
        ];

        // Calculate next run time
        $data['next_run_at'] = Campaign::calculateNextRun($data);

        // Validate
        $errors = $this->validateCampaignData($data, $id);
        if (!empty($errors)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => $errors]);
            return;
        }

        try {
            Campaign::update($id, $data);
            
            // Log action
            Auth::logAction('update_campaign', 'campaign', $id, $campaign, $data);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Campaña actualizada correctamente']);

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al actualizar campaña: ' . $e->getMessage()]);
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

    private function validateCampaignData($data, $excludeId = null)
    {
        $errors = [];

        // Name validation
        if (empty($data['name'])) {
            $errors['name'] = 'El nombre es obligatorio';
        }

        // Schedule validation
        if (empty($data['start_at'])) {
            $errors['start_at'] = 'La fecha de inicio es obligatoria';
        } else {
            $startDate = \DateTime::createFromFormat('Y-m-d\TH:i', $data['start_at']);
            if (!$startDate) {
                $errors['start_at'] = 'La fecha de inicio no es válida';
            }
        }

        // End date validation (if provided)
        if (!empty($data['end_at'])) {
            $endDate = \DateTime::createFromFormat('Y-m-d\TH:i', $data['end_at']);
            if (!$endDate) {
                $errors['end_at'] = 'La fecha de fin no es válida';
            } elseif ($startDate && $endDate <= $startDate) {
                $errors['end_at'] = 'La fecha de fin debe ser posterior a la fecha de inicio';
            }
        }

        // Schedule type validation
        $validScheduleTypes = ['once', 'weekly', 'monthly'];
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
