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
            <label class="form-label" for="webhook_token">Webhook Token</label>
            <input type="text" id="webhook_token" name="webhook_token" class="form-input" 
                   placeholder="Optional webhook validation token"
                   value="<?= $viewHelper->escape($instance['webhook_token'] ?? '') ?>">
            <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                Token to validate incoming webhooks (optional)
            </small>
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
