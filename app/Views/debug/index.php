<?php
View::set('title', 'Debug - ' . ($instance['slug'] ?? '') . ' - ' . APP_NAME);
?>

<div class="debug-page" style="padding: 1rem;">
    <div class="debug-header" style="margin-bottom: 2rem; padding: 0 1rem;">
        <h2 style="color: var(--text-primary); margin-bottom: 0.5rem;">Debug & Logs</h2>
        <p style="color: var(--text-secondary);">
            System monitoring and troubleshooting tools
            <?php if ($instance): ?>
                for instance: <strong><?= View::escape($instance['slug']) ?></strong>
            <?php endif; ?>
        </p>
    </div>

    <!-- Overview Stats -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem; padding: 0 1rem;">
        <div class="stat-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.5rem;">
            <div class="stat-value" style="font-size: 2rem; font-weight: 600; color: var(--text-primary);">
                <?= View::escape($webhookStats['total']) ?>
            </div>
            <div class="stat-label" style="color: var(--text-secondary);">Total Webhooks</div>
            <div class="stat-change" style="font-size: 0.75rem; margin-top: 0.5rem; color: var(--info);">
                <?= View::escape($webhookStats['today']) ?> today
            </div>
        </div>
        
        <div class="stat-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.5rem;">
            <div class="stat-value" style="font-size: 2rem; font-weight: 600; color: var(--text-primary);">
                <?= View::escape($webhookStats['pending']) ?>
            </div>
            <div class="stat-label" style="color: var(--text-secondary);">Pending Webhooks</div>
            <div class="stat-change" style="font-size: 0.75rem; margin-top: 0.5rem; color: var(--warning);">
                Need processing
            </div>
        </div>
        
        <div class="stat-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.5rem;">
            <div class="stat-value" style="font-size: 2rem; font-weight: 600; color: var(--text-primary);">
                <?= View::escape($cronStats['total_runs']) ?>
            </div>
            <div class="stat-label" style="color: var(--text-secondary);">Cron Runs</div>
            <div class="stat-change" style="font-size: 0.75rem; margin-top: 0.5rem; color: var(--success);">
                <?= View::escape($cronStats['successful_runs']) ?> successful
            </div>
        </div>
        
        <div class="stat-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.5rem;">
            <div class="stat-value" style="font-size: 2rem; font-weight: 600; color: var(--text-primary);">
                <?= View::escape($cronStats['failed_runs']) ?>
            </div>
            <div class="stat-label" style="color: var(--text-secondary);">Failed Runs</div>
            <div class="stat-change" style="font-size: 0.75rem; margin-top: 0.5rem; color: var(--error);">
                Need attention
            </div>
        </div>
    </div>

    <!-- Recent Errors -->
    <?php if (!empty($recentErrors)): ?>
        <div class="recent-errors" style="margin-bottom: 2rem; padding: 0 1rem;">
            <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Recent Errors</h3>
            <div class="errors-list" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; overflow: hidden;">
                <?php foreach ($recentErrors as $error): ?>
                    <div class="error-item" style="padding: 1rem; border-bottom: 1px solid var(--border);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                            <div>
                                <span style="background: var(--error); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; text-transform: uppercase;">
                                    <?= View::escape($error['source']) ?>
                                </span>
                                <span style="color: var(--text-secondary); font-size: 0.875rem; margin-left: 0.5rem;">
                                    <?= View::timeAgo($error['created_at']) ?>
                                </span>
                            </div>
                            <span style="color: var(--text-secondary); font-size: 0.75rem;">
                                <?= View::formatDate($error['created_at'], 'M j, Y H:i:s') ?>
                            </span>
                        </div>
                        <div style="color: var(--error); font-family: monospace; font-size: 0.875rem; word-break: break-all;">
                            <?= View::escape($error['error_text']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Debug Tools -->
    <div class="debug-tools" style="padding: 0 1rem;">
        <h3 style="color: var(--text-primary); margin-bottom: 1.5rem;">Debug Tools</h3>
        
        <div class="tools-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            <!-- Webhook Logs -->
            <div class="tool-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.5rem;">
                <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                    <span style="font-size: 2rem; margin-right: 1rem;">🔗</span>
                    <div>
                        <h4 style="color: var(--text-primary); margin-bottom: 0.25rem;">Webhook Logs</h4>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">View incoming webhook events</p>
                    </div>
                </div>
                <a href="<?= View::url('debug/webhooks') ?><?= $instance ? '?instance=' . urlencode($instance['slug']) : '' ?>" 
                   class="btn btn-secondary" style="width: 100%;">
                    View Logs
                </a>
            </div>

            <!-- Cron Logs -->
            <div class="tool-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.5rem;">
                <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                    <span style="font-size: 2rem; margin-right: 1rem;">⏰</span>
                    <div>
                        <h4 style="color: var(--text-primary); margin-bottom: 0.25rem;">Cron Logs</h4>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">View scheduled task executions</p>
                    </div>
                </div>
                <a href="<?= View::url('debug/cron') ?>" 
                   class="btn btn-secondary" style="width: 100%;">
                    View Logs
                </a>
            </div>

            <!-- Audit Log -->
            <div class="tool-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.5rem;">
                <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                    <span style="font-size: 2rem; margin-right: 1rem;">📋</span>
                    <div>
                        <h4 style="color: var(--text-primary); margin-bottom: 0.25rem;">Audit Log</h4>
                        <p style="color: var(--text-secondary); font-size: 0.875rem;">View user actions and changes</p>
                    </div>
                </div>
                <a href="<?= View::url('debug/audit') ?>" 
                   class="btn btn-secondary" style="width: 100%;">
                    View Audit
                </a>
            </div>

            <?php if ($instance && Auth::hasPermission('debug.test')): ?>
                <!-- Test Send -->
                <div class="tool-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.5rem;">
                    <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                        <span style="font-size: 2rem; margin-right: 1rem;">🧪</span>
                        <div>
                            <h4 style="color: var(--text-primary); margin-bottom: 0.25rem;">Test Send</h4>
                            <p style="color: var(--text-secondary); font-size: 0.875rem;">Send test message</p>
                        </div>
                    </div>
                    <button onclick="showTestSendModal()" class="btn btn-primary" style="width: 100%;">
                        Test Message
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Test Send Modal -->
<?php if ($instance && Auth::hasPermission('debug.test')): ?>
<div id="test-send-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--surface); border-radius: 0.75rem; padding: 2rem; width: 90%; max-width: 500px; border: 1px solid var(--border);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="color: var(--text-primary);">Send Test Message</h3>
            <button onclick="hideTestSendModal()" style="background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        
        <form id="test-send-form" style="display: flex; flex-direction: column; gap: 1rem;">
            <?= \App\Core\View::csrfField() ?>
            <input type="hidden" name="instance" value="<?= View::escape($instance['slug']) ?>">
            
            <div class="form-group">
                <label class="form-label" for="test-number">Phone Number</label>
                <input type="text" id="test-number" name="number" class="form-input" 
                       placeholder="+1234567890" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="test-message">Message</label>
                <textarea id="test-message" name="message" class="form-textarea" rows="4" required>This is a test message from EVOAPP</textarea>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Send Test</button>
                <button type="button" onclick="hideTestSendModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
<?php if ($instance && Auth::hasPermission('debug.test')): ?>
function showTestSendModal() {
    document.getElementById('test-send-modal').style.display = 'flex';
}

function hideTestSendModal() {
    document.getElementById('test-send-modal').style.display = 'none';
}

document.getElementById('test-send-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?= View::url('debug/testSend') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Test message sent successfully!');
            hideTestSendModal();
            document.getElementById('test-send-form').reset();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while sending the test message');
    });
});

// Close modal when clicking outside
document.getElementById('test-send-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideTestSendModal();
    }
});
<?php endif; ?>

// Auto-refresh stats every 30 seconds
setTimeout(() => {
    location.reload();
}, 30000);
</script>
