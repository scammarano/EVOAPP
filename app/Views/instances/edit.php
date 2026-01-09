<?php
use App\Core\Auth;

$isEdit = true;
$title = 'Edit Instance - ' . APP_NAME;
$errors = $errors ?? [];
?>
<div class="create-instance" style="padding: 2rem; max-width: 600px; margin: 0 auto;">
    <div class="page-header" style="margin-bottom: 2rem;">
        <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">Edit Instance</h1>
        <p style="color: var(--text-secondary);">Update WhatsApp instance settings</p>
    </div>

    <form method="post" action="<?= $viewHelper->url('instances/update') ?>" id="instance-form">
        <input type="hidden" name="id" value="<?= $viewHelper->escape($instance['id']) ?>">

        <div class="form-group">
            <label class="form-label" for="slug">Instance Slug *</label>
            <input type="text" id="slug" name="slug" class="form-input" 
                   placeholder="e.g., main, production, test" required
                   value="<?= $viewHelper->escape($instance['slug']) ?>">
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
                   value="<?= $viewHelper->escape($instance['description'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="api_key">API Key *</label>
            <input type="text" id="api_key" name="api_key" class="form-input" 
                   placeholder="Your EvolutionAPI instance key" required
                   value="<?= $viewHelper->escape($instance['api_key']) ?>">
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
                   value="<?= $viewHelper->escape($instance['base_url'] ?? '') ?>">
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
            <?php $useWebhookToken = !empty($instance['webhook_token'] ?? ''); ?>
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" id="use_webhook_token" <?= $useWebhookToken ? 'checked' : '' ?>>
                <span style="color: var(--text-primary);">Use Webhook Token</span>
            </label>
            <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                Enable a token to validate incoming webhooks
            </small>
            <label class="form-label" for="webhook_token" style="margin-top: 0.75rem;">Webhook Token</label>
            <input type="text" id="webhook_token" name="webhook_token" class="form-input" 
                   placeholder="Optional webhook validation token"
                   value="<?= $viewHelper->escape($instance['webhook_token'] ?? '') ?>"
                   <?= $useWebhookToken ? '' : 'disabled' ?>>
            <?php if (isset($errors['webhook_token'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['webhook_token']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="webhook_enabled" value="1" 
                       <?= (int)($instance['webhook_enabled'] ?? 0) === 1 ? 'checked' : '' ?>>
                <span style="color: var(--text-primary);">Enable Webhook</span>
            </label>
            <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                Receive real-time updates from EvolutionAPI
            </small>
        </div>

        <div class="form-group">
            <label class="form-label" for="forward_webhook_url">Forward Webhook URL</label>
            <input type="url" id="forward_webhook_url" name="forward_webhook_url" class="form-input" 
                   placeholder="https://your-service.com/webhook (optional)"
                   value="<?= $viewHelper->escape($instance['forward_webhook_url'] ?? '') ?>">
            <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                Forward webhook events to external service
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
                       <?= (int)($instance['forward_webhook_enabled'] ?? 0) === 1 ? 'checked' : '' ?>>
                <span style="color: var(--text-primary);">Enable Forward Webhook</span>
            </label>
            <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                Forward webhook events to the configured URL
            </small>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" 
                       <?= (int)($instance['is_active'] ?? 0) === 1 ? 'checked' : '' ?>>
                <span style="color: var(--text-primary);">Active</span>
            </label>
            <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                Instance will be available for use
            </small>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a class="btn btn-secondary" href="<?= $viewHelper->url('instances/index') ?>">Cancel</a>
        </div>
    </form>
</div>

<script>
window.evoappDisablePolling = true;

const webhookTokenToggle = document.getElementById('use_webhook_token');
const webhookTokenInput = document.getElementById('webhook_token');

function syncWebhookTokenState() {
    const enabled = webhookTokenToggle.checked;
    webhookTokenInput.disabled = !enabled;
    if (!enabled) {
        webhookTokenInput.value = '';
    }
}

webhookTokenToggle.addEventListener('change', syncWebhookTokenState);
syncWebhookTokenState();

document.getElementById('instance-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('<?= $viewHelper->url('instances/update') ?>', {
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
        } catch (error) {
            const err = new Error('Invalid JSON response');
            err.responseText = text;
            throw err;
        }
    })
    .then((data) => {
        if (data.success) {
            window.location.href = '<?= $viewHelper->url('instances/index') ?>';
            return;
        }

        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-error';
        errorDiv.innerHTML = data.error || 'An error occurred';

        if (data.errors) {
            let errorsList = '<ul>';
            for (const [, error] of Object.entries(data.errors)) {
                errorsList += `<li>${error}</li>`;
            }
            errorsList += '</ul>';
            errorDiv.innerHTML += errorsList;
        }

        const form = document.getElementById('instance-form');
        form.insertBefore(errorDiv, form.firstChild);

        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.scrollTop = 0;
        } else {
            window.scrollTo(0, 0);
        }

        setTimeout(() => errorDiv.remove(), 8000);
    })
    .catch((error) => {
        console.error('Error:', error);

        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-error';
        errorDiv.innerHTML = 'An error occurred while updating the instance';

        const form = document.getElementById('instance-form');
        form.insertBefore(errorDiv, form.firstChild);

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
