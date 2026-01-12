<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Campaign;

class SystemController
{
    public function parameters()
    {
        if (!Auth::hasPermission('system.admin')) {
            View::set('error', 'No tienes permisos de administrador');
            View::render('error/403');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->updateParameters();
            return;
        }

        // Get current parameters
        $maxDays = Campaign::getMaxCampaignDays();
        
        View::set('maxCampaignDays', $maxDays);
        View::render('system/parameters');
    }

    public function updateParameters()
    {
        if (!Auth::hasPermission('system.admin')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        try {
            $maxDays = (int)($_POST['max_campaign_days'] ?? 1825);
            
            // Validate
            if ($maxDays < 1 || $maxDays > 36500) { // Max 100 years
                throw new \Exception('Los días máximos deben estar entre 1 y 36500');
            }

            // Update parameter
            Campaign::setMaxCampaignDays($maxDays);

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Parámetros actualizados correctamente']);
            } else {
                View::flash('success', 'Parámetros actualizados correctamente');
                header('Location: index.php?r=system/parameters');
                exit;
            }

        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            } else {
                View::set('error', $e->getMessage());
                View::render('system/parameters');
            }
        }
    }

    private function isAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
