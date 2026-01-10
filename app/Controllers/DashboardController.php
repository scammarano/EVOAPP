<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Instance;

class DashboardController
{
    public function index()
    {
        $user = Auth::getCurrentUser();
        $instances = Instance::getAccessibleInstances($user['id']);
        
        // Get instance stats directly
        $statsResult = Instance::getStats();
        $instanceStats = $statsResult['data'] ?? [];
        $statsError = ($statsResult['status'] ?? 'error') === 'ok' ? null : ($statsResult['message'] ?? 'No se pudieron cargar las estadísticas.');
        
        // Calculate global stats
        $globalStats = [
            'instances' => count($instances),
            'chats' => 0,
            'messages' => 0,
            'campaigns' => 0,
            'contacts' => 0,
            'unread' => 0
        ];
        
        foreach ($instanceStats as $stat) {
            $globalStats['chats'] += $stat['chat_count'];
            $globalStats['messages'] += $stat['message_count'];
            $globalStats['unread'] += $stat['total_unread'];
        }
        
        View::set('user', $user);
        View::set('instances', $instances);
        View::set('globalStats', $globalStats);
        View::set('instanceStats', $instanceStats);
        View::set('statsError', $statsError);
        
        View::render('dashboard/index');
    }
}
