<?php
use App\Core\Auth;

$title = 'Create Contact - ' . APP_NAME;
$errors = $errors ?? [];
?>
<div class="create-contact" style="padding: 2rem; max-width: 600px; margin: 0 auto;">
    <div class="page-header" style="margin-bottom: 2rem;">
        <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">Create Contact</h1>
        <p style="color: var(--text-secondary);">Add a new contact to <?= $viewHelper->escape($instance['slug']) ?></p>
    </div>

    <form method="post" action="<?= $viewHelper->url('contacts/store') ?>" id="contact-form">
        <input type="hidden" name="instance" value="<?= $viewHelper->escape($instance['slug']) ?>">

        <div class="form-group">
            <label class="form-label" for="name">Name *</label>
            <input type="text" id="name" name="name" class="form-input" 
                   placeholder="Contact name" required
                   value="<?= $viewHelper->escape($_POST['name'] ?? '') ?>">
            <?php if (isset($errors['name'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['name']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="phone_e164">Phone Number *</label>
            <input type="tel" id="phone_e164" name="phone_e164" class="form-input" 
                   placeholder="+1234567890" required
                   value="<?= $viewHelper->escape($_POST['phone_e164'] ?? '') ?>">
            <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                Include country code (E.164 format)
            </small>
            <?php if (isset($errors['phone_e164'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['phone_e164']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="form-input" 
                   placeholder="contact@example.com"
                   value="<?= $viewHelper->escape($_POST['email'] ?? '') ?>">
            <?php if (isset($errors['email'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['email']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="company">Company</label>
            <input type="text" id="company" name="company" class="form-input" 
                   placeholder="Company name"
                   value="<?= $viewHelper->escape($_POST['company'] ?? '') ?>">
            <?php if (isset($errors['company'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['company']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="birthday">Birthday</label>
            <input type="date" id="birthday" name="birthday" class="form-input" 
                   value="<?= $viewHelper->escape($_POST['birthday'] ?? '') ?>">
            <?php if (isset($errors['birthday'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['birthday']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-input" rows="4" 
                      placeholder="Additional notes about this contact"><?= $viewHelper->escape($_POST['notes'] ?? '') ?></textarea>
            <?php if (isset($errors['notes'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['notes']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Create Contact</button>
            <a class="btn btn-secondary" href="<?= $viewHelper->url('contacts/index') ?>&instance=<?= urlencode($instance['slug']) ?>">Cancel</a>
        </div>
    </form>
</div>
