<?php
use App\Core\Auth;
$title = 'Campaigns - ' . ($instance['slug'] ?? '') . ' - ' . APP_NAME;
?>

<div class="campaigns-page" style="padding: 1rem;">
    <!-- Header -->
    <div class="campaigns-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding: 0 1rem;">
        <div>
            <h2 style="color: var(--text-primary); margin-bottom: 0.25rem;">Campaigns</h2>
            <p style="color: var(--text-secondary); font-size: 0.875rem;">
                <?= $viewHelper->escape($totalCampaigns) ?> total campaigns
            </p>
        </div>
        
        <?php if (Auth::hasPermission('campaigns.edit')): ?>
            <a href="<?= $viewHelper->url('campaigns/create') ?>&instance=<?= $viewHelper->escape($instance['slug']) ?>" 
               class="btn btn-primary btn-sm">
                + Create Campaign
            </a>
        <?php endif; ?>
    </div>

    <!-- Campaigns List -->
    <div class="campaigns-table" style="background: var(--surface); border-radius: 0.75rem; overflow: hidden; border: 1px solid var(--border); margin: 0 1rem;">
        <?php if (empty($campaigns)): ?>
            <div class="empty-state" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                <div style="font-size: 2rem; margin-bottom: 1rem;">📢</div>
                <h3>No campaigns found</h3>
                <p>Create your first campaign to start sending bulk messages</p>
                
                <?php if (Auth::hasPermission('campaigns.edit')): ?>
                    <div style="margin-top: 1rem;">
                        <a href="<?= $viewHelper->url('campaigns/create') ?>&instance=<?= $viewHelper->escape($instance['slug']) ?>" 
                           class="btn btn-primary btn-sm">
                            Create Campaign
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Schedule</th>
                        <th>Next Run</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $campaign): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 500; color: var(--text-primary);">
                                    <?= $viewHelper->escape($campaign['name']) ?>
                                </div>
                                <div style="color: var(--text-secondary); font-size: 0.75rem;">
                                    by <?= $viewHelper->escape($campaign['created_by_name']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span class="status-dot" style="
                                        width: 8px; height: 8px; border-radius: 50%; 
                                        background: <?= $campaign['is_active'] ? 'var(--success)' : 'var(--error)' ?>;
                                    "></span>
                                    <span style="font-size: 0.875rem;">
                                        <?= $campaign['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                    <?php
                                    switch ($campaign['schedule_type']) {
                                        case 'once':
                                            echo 'Once';
                                            break;
                                        case 'weekly':
                                            echo 'Weekly';
                                            if ($campaign['weekly_days']) {
                                                $days = explode(',', $campaign['weekly_days']);
                                                $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                                $dayLabels = [];
                                                foreach ($days as $day) {
                                                    if (isset($dayNames[$day - 1])) {
                                                        $dayLabels[] = $dayNames[$day - 1];
                                                    }
                                                }
                                                echo ' (' . implode(', ', $dayLabels) . ')';
                                            }
                                            break;
                                        case 'monthly':
                                            echo 'Monthly (Day ' . $campaign['monthly_day'] . ')';
                                            break;
                                        default:
                                            echo $viewHelper->escape($campaign['schedule_type']);
                                    }
                                    ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($campaign['next_run_at']): ?>
                                    <div style="font-size: 0.875rem; color: var(--text-primary);">
                                        <?= $viewHelper->formatDate($campaign['next_run_at'], 'M j, Y H:i') ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);">
                                        <?= $viewHelper->timeAgo($campaign['next_run_at']) ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary); font-size: 0.875rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="color: var(--text-secondary); font-size: 0.875rem;">
                                    <?= $viewHelper->formatDate($campaign['created_at'], 'M j, Y') ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                    <?php if (Auth::hasPermission('campaigns.execute') && $campaign['is_active']): ?>
                                        <button onclick="runCampaign(<?= $viewHelper->escape($campaign['id']) ?>)" 
                                                class="btn btn-primary btn-sm" title="Run Now">
                                            ▶️
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (Auth::hasPermission('campaigns.edit')): ?>
                                        <a href="<?= $viewHelper->url('campaigns/edit') ?>&id=<?= $viewHelper->escape($campaign['id']) ?>" 
                                           class="btn btn-secondary btn-sm" title="Edit">
                                            ✏️
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (Auth::hasPermission('campaigns.edit')): ?>
                                        <button onclick="deleteCampaign(<?= $viewHelper->escape($campaign['id']) ?>, '<?= $viewHelper->escape($campaign['name']) ?>')" 
                                                class="btn btn-secondary btn-sm" title="Delete" style="color: var(--error);">
                                            🗑️
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($totalCampaigns > 20): ?>
                <div class="pagination" style="padding: 1rem; border-top: 1px solid var(--border); display: flex; justify-content: center; gap: 0.5rem;">
                    <?php
                    $totalPages = ceil($totalCampaigns / 20);
                    for ($i = 1; $i <= $totalPages; $i++):
                        if ($i == $page):
                    ?>
                        <span class="btn btn-secondary" style="cursor: default;"><?= $i ?></span>
                        <?php else: ?>
                        <a href="<?= $viewHelper->url('campaigns/index') ?>&instance=<?= $viewHelper->escape($instance['slug']) ?>&page=<?= $i ?>" 
                           class="btn btn-secondary"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<<<<<<< HEAD
<script>
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function getCsrfTokenFieldName() {
    const meta = document.querySelector('meta[name="csrf-token-name"]');
    return meta ? meta.content : 'csrf_token';
}

function runCampaign(campaignId) {
    if (!confirm('Are you sure you want to run this campaign now?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', campaignId);
    formData.append('instance', '<?= View::escape($instance['slug']) ?>');
    const csrfToken = getCsrfToken();
    if (csrfToken) {
        formData.append(getCsrfTokenFieldName(), csrfToken);
    }
    
    fetch('<?= View::url('campaigns/run') ?>', {
        method: 'POST',
        body: formData
    })
=======
<script>
function runCampaign(campaignId) {
    if (!confirm('Are you sure you want to run this campaign now?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', campaignId);
    formData.append('instance', '<?= $viewHelper->escape($instance['slug']) ?>');
    
    fetch('<?= $viewHelper->url('campaigns/run') ?>', {
        method: 'POST',
        body: formData
    })
>>>>>>> main
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Campaign executed successfully!\nSent: ${data.sent}\nFailed: ${data.failed}`);
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while running the campaign');
    });
}

<<<<<<< HEAD
function deleteCampaign(campaignId, campaignName) {
    if (!confirm(`Are you sure you want to delete campaign "${campaignName}"? This action cannot be undone.`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', campaignId);
    const csrfToken = getCsrfToken();
    if (csrfToken) {
        formData.append(getCsrfTokenFieldName(), csrfToken);
    }
    
    fetch('<?= View::url('campaigns/delete') ?>', {
        method: 'POST',
        body: formData
=======
function deleteCampaign(campaignId, campaignName) {
    if (!confirm(`Are you sure you want to delete campaign "${campaignName}"? This action cannot be undone.`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', campaignId);
    
    fetch('<?= $viewHelper->url('campaigns/delete') ?>', {
        method: 'POST',
        body: formData
>>>>>>> main
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the campaign');
    });
}
</script>
