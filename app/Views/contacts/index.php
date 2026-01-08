<?php
use App\Core\Auth;
$title = 'Contacts - ' . ($instance['slug'] ?? '') . ' - ' . APP_NAME;
?>

<div class="contacts-page" style="padding: 1rem;">
    <!-- Header -->
    <div class="contacts-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding: 0 1rem;">
        <div>
            <h2 style="color: var(--text-primary); margin-bottom: 0.25rem;">Contacts</h2>
            <p style="color: var(--text-secondary); font-size: 0.875rem;">
                <?= $viewHelper->escape($stats['total_contacts']) ?> total contacts
                <?php if ($stats['today_added'] > 0): ?>
                    • +<?= $viewHelper->escape($stats['today_added']) ?> today
                <?php endif; ?>
            </p>
        </div>
        
        <div class="contacts-actions" style="display: flex; gap: 0.5rem;">
            <?php if (Auth::hasPermission('contacts.import')): ?>
                <a href="<?= $viewHelper->url('contacts/import') ?>?instance=<?= $viewHelper->escape($instance['slug']) ?>" 
                   class="btn btn-secondary btn-sm">
                    📥 Import
                </a>
            <?php endif; ?>
            
            <?php if (Auth::hasPermission('contacts.export')): ?>
                <a href="<?= $viewHelper->url('contacts/export') ?>?instance=<?= $viewHelper->escape($instance['slug']) ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                   class="btn btn-secondary btn-sm">
                    📤 Export
                </a>
            <?php endif; ?>
            
            <?php if (Auth::hasPermission('contacts.edit')): ?>
                <a href="<?= $viewHelper->url('contacts/create') ?>?instance=<?= $viewHelper->escape($instance['slug']) ?>" 
                   class="btn btn-primary btn-sm">
                    + Add Contact
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Search -->
    <div class="search-section" style="padding: 0 1rem; margin-bottom: 1.5rem;">
        <form method="get" action="<?= $viewHelper->url('contacts/index') ?>" style="display: flex; gap: 0.5rem;">
            <input type="hidden" name="r" value="contacts/index">
            <input type="hidden" name="instance" value="<?= $viewHelper->escape($instance['slug']) ?>">
            
            <input type="text" name="search" class="form-input" style="flex: 1;" 
                   placeholder="Search contacts by name, phone, email..." 
                   value="<?= $viewHelper->escape($search) ?>">
            
            <button type="submit" class="btn btn-primary">Search</button>
            
            <?php if ($search): ?>
                <a href="<?= $viewHelper->url('contacts/index') ?>?instance=<?= $viewHelper->escape($instance['slug']) ?>" 
                   class="btn btn-secondary">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; padding: 0 1rem; margin-bottom: 1.5rem;">
        <div class="stat-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
            <div class="stat-value" style="font-size: 1.5rem; font-weight: 600; color: var(--text-primary);">
                <?= $viewHelper->escape($stats['total_contacts']) ?>
            </div>
            <div class="stat-label" style="color: var(--text-secondary); font-size: 0.875rem;">Total Contacts</div>
        </div>
        
        <div class="stat-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
            <div class="stat-value" style="font-size: 1.5rem; font-weight: 600; color: var(--text-primary);">
                <?= $viewHelper->escape($stats['with_name']) ?>
            </div>
            <div class="stat-label" style="color: var(--text-secondary); font-size: 0.875rem;">With Name</div>
        </div>
        
        <div class="stat-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
            <div class="stat-value" style="font-size: 1.5rem; font-weight: 600; color: var(--text-primary);">
                <?= $viewHelper->escape($stats['with_email']) ?>
            </div>
            <div class="stat-label" style="color: var(--text-secondary); font-size: 0.875rem;">With Email</div>
        </div>
        
        <div class="stat-card" style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
            <div class="stat-value" style="font-size: 1.5rem; font-weight: 600; color: var(--text-primary);">
                <?= $viewHelper->escape($stats['with_company']) ?>
            </div>
            <div class="stat-label" style="color: var(--text-secondary); font-size: 0.875rem;">With Company</div>
        </div>
    </div>

    <!-- Contacts Table -->
    <div class="contacts-table" style="background: var(--surface); border-radius: 0.75rem; overflow: hidden; border: 1px solid var(--border); margin: 0 1rem;">
        <?php if (empty($contacts)): ?>
            <div class="empty-state" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                <div style="font-size: 2rem; margin-bottom: 1rem;">👥</div>
                <h3>No contacts found</h3>
                <p>
                    <?php if ($search): ?>
                        No contacts match your search criteria
                    <?php else: ?>
                        Start by adding your first contact or importing from CSV
                    <?php endif; ?>
                </p>
                
                <?php if (Auth::hasPermission('contacts.edit') && !$search): ?>
                    <div style="margin-top: 1rem;">
                        <a href="<?= $viewHelper->url('contacts/create') ?>?instance=<?= $viewHelper->escape($instance['slug']) ?>" 
                           class="btn btn-primary btn-sm">
                            Add Contact
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Company</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $contact): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 500; color: var(--text-primary);">
                                    <strong><?= $viewHelper->escape($contact['name'] ?: 'Unknown') ?></strong>
                                </div>
                            </td>
                            <td>
                                <span style="color: var(--text-secondary); font-family: monospace;">
                                    <?= $viewHelper->escape($contact['phone_e164']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($contact['email']): ?>
                                    <a href="mailto:<?= $viewHelper->escape($contact['email']) ?>" 
                                       style="color: var(--primary-green); text-decoration: none;">
                                        <?= $viewHelper->escape($contact['email']) ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $viewHelper->escape($contact['company'] ?: '—') ?>
                            </td>
                            <td>
                                <span style="color: var(--text-secondary); font-size: 0.875rem;">
                                    <?= $viewHelper->formatDate($contact['created_at'], 'M j, Y') ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.25rem;">
                                    <?php if (Auth::hasPermission('contacts.edit')): ?>
                                        <a href="<?= $viewHelper->url('contacts/edit') ?>?id=<?= $viewHelper->escape($contact['id']) ?>" 
                                           class="btn btn-secondary btn-sm" title="Edit">
                                            ✏️
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="<?= $viewHelper->url('inbox/index') ?>?instance=<?= $viewHelper->escape($instance['slug']) ?>" 
                                       class="btn btn-primary btn-sm" title="Send Message">
                                        💬
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($totalContacts > 20): ?>
                <div class="pagination" style="padding: 1rem; border-top: 1px solid var(--border); display: flex; justify-content: center; gap: 0.5rem;">
                    <?php
                    $totalPages = ceil($totalContacts / 20);
                    for ($i = 1; $i <= $totalPages; $i++):
                        if ($i == $page):
                    ?>
                        <span class="btn btn-secondary" style="cursor: default;"><?= $i ?></span>
                        <?php else: ?>
                        <a href="<?= $viewHelper->url('contacts/index') ?>?instance=<?= $viewHelper->escape($instance['slug']) ?>&page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                           class="btn btn-secondary"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
