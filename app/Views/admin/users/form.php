<?php
use App\Core\Auth;

$isEdit = !empty($user) && !empty($user['id']);
$title = 'Admin - ' . ($isEdit ? 'Edit User' : 'New User') . ' - ' . APP_NAME;
$errors = $errors ?? [];
$selectedRoleId = $selectedRoleId ?? null;
$selectedInstanceIds = $selectedInstanceIds ?? [];
?>

<div style="padding: 2rem; max-width: 800px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="margin: 0; color: var(--text-primary);">
                <?= $isEdit ? 'Edit User' : 'New User' ?>
            </h1>
            <div style="color: var(--text-secondary);">Manage user profile and roles</div>
        </div>

        <a class="btn btn-secondary" href="<?= $viewHelper->url('admin/users') ?>">Back</a>
    </div>

    <form method="post" action="<?= $viewHelper->url($isEdit ? 'admin/users/update' : 'admin/users/store') ?>" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1.5rem;">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= $viewHelper->escape($user['id']) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label" for="name">Name</label>
            <input class="form-input" id="name" name="name" type="text" required value="<?= $viewHelper->escape($user['name'] ?? '') ?>">
            <?php if (isset($errors['name'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['name']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input class="form-input" id="email" name="email" type="email" required value="<?= $viewHelper->escape($user['email'] ?? '') ?>">
            <?php if (isset($errors['email'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['email']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password<?= $isEdit ? ' (leave empty to keep current)' : '' ?></label>
            <input class="form-input" id="password" name="password" type="password" <?= $isEdit ? '' : 'required' ?>>
            <?php if (isset($errors['password'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['password']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" <?= (!isset($user['is_active']) || (int)$user['is_active'] === 1) ? 'checked' : '' ?>>
                <span style="color: var(--text-primary);">Active</span>
            </label>
        </div>

        <div class="form-group">
            <label class="form-label" for="role_id">Role</label>
            <select class="form-input" id="role_id" name="role_id">
                <option value="">— No role —</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= $viewHelper->escape($role['id']) ?>" <?= ((int)$selectedRoleId === (int)$role['id']) ? 'selected' : '' ?>>
                        <?= $viewHelper->escape($role['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Instances (can view)</label>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 0.5rem;">
                <?php foreach (($instances ?? []) as $inst): ?>
                    <?php $checked = in_array((int)$inst['id'], $selectedInstanceIds, true); ?>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; background: var(--background); border: 1px solid var(--border); padding: 0.5rem 0.75rem; border-radius: 0.5rem;">
                        <input type="checkbox" name="instances[]" value="<?= $viewHelper->escape($inst['id']) ?>" <?= $checked ? 'checked' : '' ?>>
                        <span style="color: var(--text-primary);">
                            <?= $viewHelper->escape($inst['slug']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">
                <?= $isEdit ? 'Save Changes' : 'Create User' ?>
            </button>
            <a class="btn btn-secondary" href="<?= $viewHelper->url('admin/users') ?>">Cancel</a>
        </div>
    </form>
</div>
