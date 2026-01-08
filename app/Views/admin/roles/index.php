<?php
use App\Core\Auth;
$title = 'Admin - Roles - ' . APP_NAME;
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="margin: 0; color: var(--text-primary);">Roles</h1>
            <div style="color: var(--text-secondary);">Manage roles and permissions</div>
        </div>

        <?php if (Auth::hasPermission('users.manage')): ?>
            <a class="btn btn-primary" href="<?= $viewHelper->url('admin/roles/create') ?>">+ New Role</a>
        <?php endif; ?>
    </div>

    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; overflow: hidden;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $r): ?>
                    <tr>
                        <td><?= $viewHelper->escape($r['id']) ?></td>
                        <td><?= $viewHelper->escape($r['name']) ?></td>
                        <td><?= $viewHelper->escape($r['description'] ?? '—') ?></td>
                        <td>
                            <a class="btn btn-secondary btn-sm" href="<?= $viewHelper->url('admin/roles/edit') ?>&id=<?= $viewHelper->escape($r['id']) ?>">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($roles)): ?>
                    <tr>
                        <td colspan="4" style="padding: 2rem; text-align: center; color: var(--text-secondary);">No roles found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
