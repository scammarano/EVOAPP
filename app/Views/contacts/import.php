<?php
use App\Core\Auth;

$title = 'Import Contacts - ' . ($instance['slug'] ?? '') . ' - ' . APP_NAME;
$errors = $errors ?? [];
?>
<div class="import-contacts" style="padding: 2rem; max-width: 600px; margin: 0 auto;">
    <div class="page-header" style="margin-bottom: 2rem;">
        <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">Import Contacts</h1>
        <p style="color: var(--text-secondary);">Import contacts from CSV file to <?= $viewHelper->escape($instance['slug']) ?></p>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error" style="margin-bottom: 1.5rem;">
            <?= $viewHelper->escape($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= $viewHelper->url('contacts/import') ?>&instance=<?= $viewHelper->escape($instance['slug']) ?>" 
          enctype="multipart/form-data" id="import-form">
        <div class="form-group">
            <label class="form-label" for="csv_file">CSV File *</label>
            <input type="file" id="csv_file" name="csv_file" class="form-input" 
                   accept=".csv,text/csv" required>
            <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                Select a CSV file with contacts to import
            </small>
        </div>

        <div class="form-group">
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                <h3 style="color: var(--text-primary); margin-bottom: 1rem;">CSV Format</h3>
                <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">
                    Your CSV file should have the following columns (in order):
                </p>
                <ol style="color: var(--text-secondary); margin-left: 1.5rem; line-height: 1.6;">
                    <li><strong>Phone</strong> (required) - Phone number in E.164 format (+1234567890)</li>
                    <li><strong>Name</strong> (optional) - Contact name</li>
                    <li><strong>Email</strong> (optional) - Email address</li>
                    <li><strong>Company</strong> (optional) - Company name</li>
                    <li><strong>Notes</strong> (optional) - Additional notes</li>
                </ol>
                
                <div style="margin-top: 1rem; padding: 0.75rem; background: var(--background); border-radius: 0.25rem;">
                    <code style="font-size: 0.875rem;">
                        +1234567890,John Doe,john@example.com,ACME Corp,Important client
                    </code>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Import Contacts</button>
            <a class="btn btn-secondary" href="<?= $viewHelper->url('contacts/index') ?>&instance=<?= $viewHelper->escape($instance['slug']) ?>">Cancel</a>
        </div>
    </form>
</div>
