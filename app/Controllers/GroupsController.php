<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Chat;
use App\Models\Instance;

class GroupsController
{
    public function index()
    {
        if (!Auth::hasPermission('groups.view')) {
            View::set('error', 'No tienes permisos para ver grupos');
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

        // Get group chats
        $groups = $this->getGroupsByInstance($instance['id']);

        View::set('instance', $instance);
        View::set('groups', $groups);
        View::render('groups/index');
    }

    public function extractParticipants()
    {
        if (!Auth::hasPermission('groups.extract')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $instanceSlug = $_POST['instance'] ?? '';
        $groupJid = $_POST['group_jid'] ?? '';

        if (!$instanceSlug || !$groupJid) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Instance and group_jid required']);
            return;
        }

        $instance = Instance::findBySlug($instanceSlug);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        try {
            $client = Instance::evoClient($instance);
            $participants = $client->getGroupParticipants($instanceSlug, $groupJid);

            // Save participants as contact candidates
            $savedCandidates = $this->saveParticipantsAsCandidates($instance['id'], $groupJid, $participants);

            // Log action
            Auth::logAction('extract_group_participants', 'group', null, null, [
                'instance_id' => $instance['id'],
                'group_jid' => $groupJid,
                'participants_count' => count($participants),
                'candidates_saved' => $savedCandidates
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "$savedCandidates participantes guardados como candidatos",
                'participants_count' => count($participants),
                'candidates_saved' => $savedCandidates
            ]);

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al extraer participantes: ' . $e->getMessage()]);
        }
    }

    private function getGroupsByInstance($instanceId)
    {
        return App\Core\DB::fetchAll("
            SELECT 
                c.*,
                COUNT(m.id) as message_count,
                MAX(m.ts) as last_message_at
            FROM chats c
            LEFT JOIN messages m ON c.id = m.chat_id
            WHERE c.instance_id = ? AND c.is_group = 1
            GROUP BY c.id
            ORDER BY c.last_message_at DESC
        ", [$instanceId]);
    }

    private function saveParticipantsAsCandidates($instanceId, $groupJid, $participants)
    {
        $saved = 0;

        foreach ($participants as $participant) {
            $phone = $this->extractPhoneFromJid($participant['id'] ?? '');
            
            if ($phone) {
                // Check if already exists as contact
                $existingContact = App\Core\DB::fetch("
                    SELECT id FROM contacts 
                    WHERE instance_id = ? AND phone_e164 = ?
                ", [$instanceId, $phone]);

                if (!$existingContact) {
                    // Check if already exists as candidate
                    $existingCandidate = App\Core\DB::fetch("
                        SELECT id FROM contact_candidates 
                        WHERE instance_id = ? AND phone_e164 = ? AND source_remote_jid = ?
                    ", [$instanceId, $phone, $groupJid]);

                    if (!$existingCandidate) {
                        App\Core\DB::q("
                            INSERT INTO contact_candidates 
                            (instance_id, source_type, source_remote_jid, phone_e164, name_guess, raw_json, created_at, status)
                            VALUES (?, 'group', ?, ?, ?, ?, NOW(), 'new')
                        ", [
                            $instanceId,
                            $groupJid,
                            $phone,
                            $participant['pushname'] ?? $participant['name'] ?? null,
                            json_encode($participant)
                        ]);

                        $saved++;
                    }
                }
            }
        }

        return $saved;
    }

    private function extractPhoneFromJid($jid)
    {
        // Extract phone from JID format: phone@s.whatsapp.net
        if (preg_match('/^(\d+)@s\.whatsapp\.net$/', $jid, $matches)) {
            return '+' . $matches[1];
        }
        return null;
    }
}
