<?php
use App\Core\Auth;
$title = 'Instances - ' . APP_NAME;
?>

<div class="instances-page" style="padding: 2rem;">
    <div class="page-header" style="display: flex; justify-content: between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">Instances</h1>
            <p style="color: var(--text-secondary);">Manage your WhatsApp instances</p>
        </div>
        
        <?php if (Auth::hasPermission('instances.manage')): ?>
            <a href="<?= $viewHelper->url('instances/create') ?>" class="btn btn-primary">
                + Add New Instance
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($instances)): ?>
        <div class="empty-state" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📱</div>
            <h3>No instances found</h3>
            <p>Create your first WhatsApp instance to get started</p>
            
            <?php if (Auth::hasPermission('instances.manage')): ?>
                <div style="margin-top: 2rem;">
                    <a href="<?= View::url('instances/create') ?>" class="btn btn-primary">
                        Create Instance
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="instances-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php foreach ($instances as $instance): ?>
                <div class="instance-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.5rem;">
                    <div class="instance-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <h3 style="color: var(--text-primary); margin-bottom: 0.25rem;"><?= $viewHelper->escape($instance['slug']) ?></h3>
                            <?php if ($instance['description']): ?>
                                <p style="color: var(--text-secondary); font-size: 0.875rem;"><?= $viewHelper->escape($instance['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="instance-status <?= $instance['is_active'] ? 'active' : 'inactive' ?>" style="font-size: 0.75rem;">
                            <span class="status-dot"></span>
                            <?= $instance['is_active'] ? 'Active' : 'Inactive' ?>
                        </div>
                    </div>
                    
                    <div class="instance-info" style="margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: var(--text-secondary);">Webhook:</span>
                            <span style="color: <?= $instance['webhook_enabled'] ? 'var(--success)' : 'var(--text-secondary)' ?>;">
                                <?= $instance['webhook_enabled'] ? 'Enabled' : 'Disabled' ?>
                            </span>
                        </div>
                        
                        <?php if ($instance['last_webhook_at']): ?>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="color: var(--text-secondary);">Last webhook:</span>
                                <span style="color: var(--text-primary); font-size: 0.875rem;">
                                    <?= View::timeAgo($instance['last_webhook_at']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($instance['forward_webhook_enabled']): ?>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">Forward:</span>
                                <span style="color: var(--info); font-size: 0.875rem;">Enabled</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="instance-actions" style="display: flex; gap: 0.5rem;">
                        <a href="<?= $viewHelper->url('inbox/index') ?>?instance=<?= $viewHelper->escape($instance['slug']) ?>" 
                           class="btn btn-primary btn-sm" style="flex: 1; text-align: center;">
                            Open Inbox
                        </a>
                        
                        <?php if (Auth::hasPermission('instances.manage')): ?>
                            <a href="<?= $viewHelper->url('instances/edit') ?>?id=<?= $viewHelper->escape($instance['id']) ?>" 
                               class="btn btn-secondary btn-sm" title="Edit">
                                ⚙️
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Auto-refresh every 30 seconds
setTimeout(() => {
    window.location.reload();
}, 30000);
</script>
