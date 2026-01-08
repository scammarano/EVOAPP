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
                        <?php
                            $row = $stat['instance'] ?? $stat;
                            $slug = $row['slug'] ?? '';
                            $description = $row['description'] ?? '';
                            $isActive = (int)($row['is_active'] ?? 0) === 1;

                            $chats = (int)($stat['chat_count'] ?? $stat['chats'] ?? 0);
                            $messageCount = (int)($stat['message_count'] ?? $stat['messages'] ?? 0);
                            $totalUnread = (int)($stat['total_unread'] ?? $stat['unread_messages'] ?? 0);
                            $todayMessages = (int)($stat['today_messages'] ?? 0);
                            $lastActivity = $stat['last_webhook_at'] ?? $stat['last_activity'] ?? null;
                        ?>
                        <tr>
                            <td>
                                <div>
                                    <strong><?= $viewHelper->escape($slug) ?></strong>
                                    <?php if ($description): ?>
                                        <br><small style="color: var(--text-secondary);"><?= $viewHelper->escape($description) ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="instance-status <?= $isActive ? 'active' : 'inactive' ?>">
                                    <span class="status-dot"></span>
                                    <?= $isActive ? 'Active' : 'Inactive' ?>
                                </div>
                            </td>
                            <td><?= $viewHelper->escape($chats) ?></td>
                            <td>
                                <?php if ($totalUnread > 0): ?>
                                    <span style="color: var(--error); font-weight: 500;">
                                        <?= $viewHelper->escape($totalUnread) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--success);">0</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $viewHelper->escape(number_format($messageCount)) ?></td>
                            <td>
                                <?php if ($todayMessages > 0): ?>
                                    <span style="color: var(--primary-green);"><?= $viewHelper->escape($todayMessages) ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($lastActivity): ?>
                                    <span title="<?= $viewHelper->escape($lastActivity) ?>">
                                        <?= $viewHelper->timeAgo($lastActivity) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">Never</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= $viewHelper->url('inbox/index') ?>&instance=<?= $viewHelper->escape($slug) ?>" 
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
