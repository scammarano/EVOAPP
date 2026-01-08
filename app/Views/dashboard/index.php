<?php
use App\Core\Auth;
$title = 'Dashboard - ' . APP_NAME;
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Dashboard</h1>
        <p class="dashboard-subtitle">Welcome back, <?= $viewHelper->escape($user['name']) ?>!</p>
    </div>
    
    <!-- Global Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $viewHelper->escape($globalStats['instances']) ?></div>
            <div class="stat-label">Instances</div>
            <div class="stat-change positive">Active</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?= $viewHelper->escape($globalStats['chats']) ?></div>
            <div class="stat-label">Total Chats</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?= $viewHelper->escape($globalStats['messages']) ?></div>
            <div class="stat-label">Messages</div>
            <div class="stat-change positive">All time</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?= $viewHelper->escape($globalStats['unread']) ?></div>
            <div class="stat-label">Unread</div>
            <div class="stat-change <?= $globalStats['unread'] > 0 ? 'negative' : 'positive' ?>">
                <?= $globalStats['unread'] > 0 ? 'Needs attention' : 'All read' ?>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?= $viewHelper->escape($globalStats['campaigns']) ?></div>
            <div class="stat-label">Campaigns</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?= $viewHelper->escape($globalStats['contacts']) ?></div>
            <div class="stat-label">Contacts</div>
        </div>
    </div>
    
    <!-- Instances Table -->
    <div class="instances-section">
        <h2 style="margin-bottom: 1rem; color: var(--text-primary);">Instances</h2>
        
        <div class="instances-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Instance</th>
                        <th>Status</th>
                        <th>Chats</th>
                        <th>Unread</th>
                        <th>Messages</th>
                        <th>Today</th>
                        <th>Last Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($instanceStats as $stat): ?>
                        <tr>
                            <td>
                                <div>
                                    <strong><?= $viewHelper->escape($stat['instance']['slug']) ?></strong>
                                    <?php if ($stat['instance']['description']): ?>
                                        <br><small style="color: var(--text-secondary);"><?= $viewHelper->escape($stat['instance']['description']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="instance-status <?= $stat['instance']['is_active'] ? 'active' : 'inactive' ?>">
                                    <span class="status-dot"></span>
                                    <?= $stat['instance']['is_active'] ? 'Active' : 'Inactive' ?>
                                </div>
                            </td>
                            <td><?= $viewHelper->escape($stat['chats']) ?></td>
                            <td>
                                <?php if ($stat['total_unread'] > 0): ?>
                                    <span style="color: var(--error); font-weight: 500;">
                                        <?= $viewHelper->escape($stat['total_unread']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--success);">0</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $viewHelper->escape(number_format($stat['message_count'])) ?></td>
                            <td>
                                <?php if ($stat['message_count'] > 0): ?>
                                    <span style="color: var(--primary-green);"><?= $viewHelper->escape($stat['message_count']) ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($stat['last_webhook_at']): ?>
                                    <span title="<?= $viewHelper->escape($stat['last_webhook_at']) ?>">
                                        <?= $viewHelper->timeAgo($stat['last_webhook_at']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">Never</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= $viewHelper->url('inbox/index') ?>?instance=<?= $viewHelper->escape($stat['instance']['slug']) ?>" 
                                   class="btn btn-primary btn-sm" target="_blank">
                                    Open
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($instanceStats)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                                No instances found. 
                                <?php if (Auth::hasPermission('instances.manage')): ?>
                                    <a href="<?= $viewHelper->url('instances/create') ?>" style="color: var(--primary-green);">Create your first instance</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if (Auth::hasPermission('instances.manage')): ?>
        <div style="margin-top: 2rem; text-align: center;">
            <a href="<?= $viewHelper->url('instances/create') ?>" class="btn btn-primary">
                + Add New Instance
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
// Auto-refresh dashboard every 30 seconds
setTimeout(() => {
    window.location.reload();
}, 30000);
</script>
