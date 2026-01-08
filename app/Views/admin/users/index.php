<?php
use App\Core\Auth;
$title = 'Admin - Users - ' . APP_NAME;
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="margin: 0; color: var(--text-primary);">Users</h1>
            <div style="color: var(--text-secondary);">Manage users and roles</div>
        </div>

        <?php if (Auth::hasPermission('users.manage')): ?>
            <a class="btn btn-primary" href="<?= $viewHelper->url('admin/users/create') ?>">+ New User</a>
        <?php endif; ?>
    </div>

    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; overflow: hidden;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $viewHelper->escape($u['id']) ?></td>
                        <td><?= $viewHelper->escape($u['name']) ?></td>
                        <td><?= $viewHelper->escape($u['email']) ?></td>
                        <td><?= $viewHelper->escape($u['roles'] ?: '—') ?></td>
                        <td>
                            <?php if ((int)$u['is_active'] === 1): ?>
                                <span style="color: var(--success); font-weight: 500;">Active</span>
                            <?php else: ?>
                                <span style="color: var(--error); font-weight: 500;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $viewHelper->escape($u['last_login_at'] ?? '—') ?></td>
                        <td>
                            <a class="btn btn-secondary btn-sm" href="<?= $viewHelper->url('admin/users/edit') ?>&id=<?= $viewHelper->escape($u['id']) ?>">Edit</a>

                            <form method="post" action="<?= $viewHelper->url('admin/users/toggleActive') ?>" style="display: inline-block; margin-left: 0.5rem;">
                                <input type="hidden" name="id" value="<?= $viewHelper->escape($u['id']) ?>">
                                <input type="hidden" name="is_active" value="<?= (int)$u['is_active'] === 1 ? 0 : 1 ?>">
                                <button type="submit" class="btn btn-secondary btn-sm" style="color: <?= (int)$u['is_active'] === 1 ? 'var(--error)' : 'var(--success)' ?>;">
                                    <?= (int)$u['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" style="padding: 2rem; text-align: center; color: var(--text-secondary);">No users found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (($totalUsers ?? 0) > 20): ?>
            <div style="padding: 1rem; border-top: 1px solid var(--border); display: flex; justify-content: center; gap: 0.5rem;">
                <?php
                $totalPages = (int)ceil(($totalUsers ?? 0) / 20);
                for ($i = 1; $i <= $totalPages; $i++):
                    if ($i === (int)$page):
                ?>
                    <span class="btn btn-secondary" style="cursor: default;"><?= $i ?></span>
                <?php else: ?>
                    <a class="btn btn-secondary" href="<?= $viewHelper->url('admin/users') ?>&page=<?= $i ?>"><?= $i ?></a>
                <?php
                    endif;
                endfor;
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>
