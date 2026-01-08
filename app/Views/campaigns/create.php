<?php
use App\Core\Auth;
$title = 'Campaigns - Create - ' . APP_NAME;
$errors = $errors ?? [];
$data = $data ?? [];
?>

<div style="padding: 2rem; max-width: 800px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="margin: 0; color: var(--text-primary);">New Campaign</h1>
            <div style="color: var(--text-secondary);">Create a new messaging campaign</div>
        </div>

        <a class="btn btn-secondary" href="<?= $viewHelper->url('campaigns/index') ?>&instance=<?= $viewHelper->escape($_GET['instance'] ?? '') ?>">Back</a>
    </div>

    <form method="post" action="<?= $viewHelper->url('campaigns/store') ?>&instance=<?= $viewHelper->escape($_GET['instance'] ?? '') ?>" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.5rem;">
        <div class="form-group">
            <label class="form-label" for="name">Campaign Name</label>
            <input class="form-input" id="name" name="name" type="text" required value="<?= $viewHelper->escape($data['name'] ?? '') ?>">
            <?php if (isset($errors['name'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['name']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="schedule_type">Schedule Type</label>
            <select class="form-input" id="schedule_type" name="schedule_type" required>
                <option value="once" <?= ($data['schedule_type'] ?? '') === 'once' ? 'selected' : '' ?>>Run Once</option>
                <option value="weekly" <?= ($data['schedule_type'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                <option value="monthly" <?= ($data['schedule_type'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="start_at">Start Date & Time</label>
            <input class="form-input" id="start_at" name="start_at" type="datetime-local" required value="<?= $viewHelper->escape($data['start_at'] ?? '') ?>">
            <?php if (isset($errors['start_at'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['start_at']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="end_at">End Date & Time (optional)</label>
            <input class="form-input" id="end_at" name="end_at" type="datetime-local" value="<?= $viewHelper->escape($data['end_at'] ?? '') ?>">
            <?php if (isset($errors['end_at'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['end_at']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="timezone">Timezone</label>
            <select class="form-input" id="timezone" name="timezone">
                <option value="America/Bogota" <?= ($data['timezone'] ?? '') === 'America/Bogota' ? 'selected' : '' ?>>America/Bogota</option>
                <option value="America/Mexico_City" <?= ($data['timezone'] ?? '') === 'America/Mexico_City' ? 'selected' : '' ?>>America/Mexico_City</option>
                <option value="America/Argentina/Buenos_Aires" <?= ($data['timezone'] ?? '') === 'America/Argentina/Buenos_Aires' ? 'selected' : '' ?>>America/Argentina/Buenos_Aires</option>
                <option value="America/Santiago" <?= ($data['timezone'] ?? '') === 'America/Santiago' ? 'selected' : '' ?>>America/Santiago</option>
                <option value="America/Lima" <?= ($data['timezone'] ?? '') === 'America/Lima' ? 'selected' : '' ?>>America/Lima</option>
                <option value="UTC" <?= ($data['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
            </select>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" <?= (!isset($data['is_active']) || (int)($data['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
                <span style="color: var(--text-primary);">Active</span>
            </label>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Create Campaign</button>
            <a class="btn btn-secondary" href="<?= $viewHelper->url('campaigns/index') ?>&instance=<?= $viewHelper->escape($_GET['instance'] ?? '') ?>">Cancel</a>
        </div>
    </form>
</div>
