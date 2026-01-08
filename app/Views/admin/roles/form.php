<?php

$isEdit = !empty($role) && !empty($role['id']);
$title = 'Admin - ' . ($isEdit ? 'Edit Role' : 'New Role') . ' - ' . APP_NAME;
$errors = $errors ?? [];
$selectedPermissionIds = $selectedPermissionIds ?? [];
$isAdminRole = $isEdit && (($role['name'] ?? '') === 'admin');
?>

<div style="padding: 2rem; max-width: 900px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="margin: 0; color: var(--text-primary);">
                <?= $isEdit ? 'Edit Role' : 'New Role' ?>
            </h1>
            <div style="color: var(--text-secondary);">Assign permissions to a role</div>
        </div>

        <a class="btn btn-secondary" href="<?= $viewHelper->url('admin/roles') ?>">Back</a>
    </div>

    <form method="post" action="<?= $viewHelper->url($isEdit ? 'admin/roles/update' : 'admin/roles/store') ?>" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.5rem;">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= $viewHelper->escape($role['id']) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label" for="name">Name</label>
            <input class="form-input" id="name" name="name" type="text" <?= $isAdminRole ? 'disabled' : 'required' ?> value="<?= $viewHelper->escape($role['name'] ?? '') ?>">
            <?php if ($isAdminRole): ?>
                <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">Admin role name cannot be changed</small>
            <?php endif; ?>
            <?php if (isset($errors['name'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['name']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <input class="form-input" id="description" name="description" type="text" value="<?= $viewHelper->escape($role['description'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Permissions</label>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 0.5rem;">
                <?php foreach ($permissions as $perm): ?>
                    <?php $checked = in_array((int)$perm['id'], $selectedPermissionIds, true); ?>
                    <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer; background: var(--background); border: 1px solid var(--border); padding: 0.5rem 0.75rem; border-radius: 0.5rem;">
                        <input type="checkbox" name="permissions[]" value="<?= $viewHelper->escape($perm['id']) ?>" <?= $checked ? 'checked' : '' ?>>
                        <span>
                            <div style="color: var(--text-primary); font-weight: 500;">
                                <?= $viewHelper->escape($perm['key']) ?>
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.875rem;">
                                <?= $viewHelper->escape($perm['description'] ?? '') ?>
                            </div>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">
                <?= $isEdit ? 'Save Changes' : 'Create Role' ?>
            </button>
            <a class="btn btn-secondary" href="<?= $viewHelper->url('admin/roles') ?>">Cancel</a>
        </div>
    </form>
</div>
