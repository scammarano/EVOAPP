<?php
use App\Core\Auth;
$title = 'Create Instance - ' . APP_NAME;
?>

<div class="create-instance" style="padding: 2rem; max-width: 600px; margin: 0 auto;">
    <div class="page-header" style="margin-bottom: 2rem;">
        <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">Create Instance</h1>
        <p style="color: var(--text-secondary);">Add a new WhatsApp instance</p>
    </div>

    <form method="post" action="<?= $viewHelper->url('instances/store') ?>" id="instance-form">
        <?= \App\Core\View::csrfField() ?>
        <div class="form-group">
            <label class="form-label" for="slug">Instance Slug *</label>
            <input type="text" id="slug" name="slug" class="form-input" 
                   placeholder="e.g., main, production, test" required
                   value="<?= $viewHelper->escape($data['slug'] ?? '') ?>">
            <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                Unique identifier (letters, numbers, hyphens, underscores only)
            </small>
            <?php if (isset($errors['slug'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['slug']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <input type="text" id="description" name="description" class="form-input" 
                   placeholder="Optional description"
                   value="<?= $viewHelper->escape($data['description'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="api_key">API Key *</label>
            <input type="text" id="api_key" name="api_key" class="form-input" 
                   placeholder="Your EvolutionAPI instance key" required
                   value="<?= $viewHelper->escape($data['api_key'] ?? '') ?>">
            <?php if (isset($errors['api_key'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['api_key']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="base_url">Base URL</label>
            <input type="url" id="base_url" name="base_url" class="form-input" 
                   placeholder="https://your-evolution-api.com (optional)"
                   value="<?= $viewHelper->escape($data['base_url'] ?? '') ?>">
            <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                Leave empty to use default from configuration
            </small>
            <?php if (isset($errors['base_url'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['base_url']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="webhook_token">Webhook Token</label>
            <input type="text" id="webhook_token" name="webhook_token" class="form-input" 
                   placeholder="Optional webhook validation token"
                   value="<?= $viewHelper->escape($data['webhook_token'] ?? '') ?>">
            <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                Token to validate incoming webhooks (optional)
            </small>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="webhook_enabled" value="1" 
                       <?= (isset($data['webhook_enabled']) && $data['webhook_enabled']) ? 'checked' : '' ?>>
                <span style="color: var(--text-primary);">Enable Webhook</span>
            </label>
            <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                Receive real-time updates from EvolutionAPI
            </small>
        </div>

        <div class="form-group">
            <label class="form-label" for="forward_webhook_url">Forward Webhook URL</label>
            <input type="url" id="forward_webhook_url" name="forward_webhook_url" class="form-input" 
                   placeholder="https://your-n8n-webhook.com (optional)"
                   value="<?= $viewHelper->escape($data['forward_webhook_url'] ?? '') ?>">
            <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                Forward webhook events to another service (e.g., n8n)
            </small>
            <?php if (isset($errors['forward_webhook_url'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['forward_webhook_url']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="forward_webhook_enabled" value="1" 
                       <?= (isset($data['forward_webhook_enabled']) && $data['forward_webhook_enabled']) ? 'checked' : '' ?>>
                <span style="color: var(--text-primary);">Enable Webhook Forwarding</span>
            </label>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" 
                       <?= (isset($data['is_active']) && $data['is_active']) ? 'checked' : '' ?>>
                <span style="color: var(--text-primary);">Active</span>
            </label>
            <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                Instance will be available for use
            </small>
        </div>

        <div class="form-actions" style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">
                Create Instance
            </button>
            <a href="<?= $viewHelper->url('instances/index') ?>" class="btn btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
document.getElementById('instance-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?= $viewHelper->url('instances/store') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(async (response) => {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            const err = new Error('Invalid JSON response');
            err.responseText = text;
            throw err;
        }
    })
    .then((data) => {
        if (data.success) {
            window.location.href = '<?= $viewHelper->url('instances/index') ?>';
        } else {
            // Show errors
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-error';
            errorDiv.innerHTML = data.error || 'An error occurred';
            
            if (data.errors) {
                let errorsList = '<ul>';
                for (const [field, error] of Object.entries(data.errors)) {
                    errorsList += `<li>${error}</li>`;
                }
                errorsList += '</ul>';
                errorDiv.innerHTML += errorsList;
            }
            
            const form = document.getElementById('instance-form');
            form.insertBefore(errorDiv, form.firstChild);
            
            // Scroll to top
            window.scrollTo(0, 0);
            
            // Remove after 5 seconds
            setTimeout(() => errorDiv.remove(), 5000);
        }
    })
    .catch(error => {
        console.error('Error:', error);

        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-error';

        const debugEnabled = <?= defined('DEBUG') && DEBUG ? 'true' : 'false' ?>;
        if (debugEnabled && error && error.responseText) {
            const preview = String(error.responseText).slice(0, 2000);
            errorDiv.innerHTML = 'An error occurred while creating the instance.<br><pre style="white-space: pre-wrap; margin-top: .5rem;">' +
                preview.replace(/[&<>]/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c])) +
                '</pre>';
        } else {
            errorDiv.innerHTML = 'An error occurred while creating the instance';
        }

        const form = document.getElementById('instance-form');
        form.insertBefore(errorDiv, form.firstChild);

        // Scroll to top of main content (layout uses internal scrolling)
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.scrollTop = 0;
        } else {
            window.scrollTo(0, 0);
        }

        setTimeout(() => errorDiv.remove(), 8000);
    });
});
</script>
