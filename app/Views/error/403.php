<?php
View::set('title', 'Access Denied - ' . APP_NAME);
?>

<div class="error-page" style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--background);">
    <div class="error-container" style="text-align: center; max-width: 400px;">
        <div style="font-size: 4rem; margin-bottom: 1rem;">🔒</div>
        <h1 style="color: var(--text-primary); font-size: 2rem; margin-bottom: 1rem;">403</h1>
        <h2 style="color: var(--text-primary); margin-bottom: 1rem;">Access Denied</h2>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">
            You don't have permission to access this page. Please contact your administrator if you think this is an error.
        </p>
        
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <a href="<?= View::url('dashboard/index') ?>" class="btn btn-primary">
                Go to Dashboard
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                Go Back
            </button>
        </div>
    </div>
</div>
