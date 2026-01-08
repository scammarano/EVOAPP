<?php
$title = 'Page Not Found - ' . APP_NAME;
?>

<div class="error-page" style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--background);">
    <div class="error-container" style="text-align: center; max-width: 400px;">
        <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
        <h1 style="color: var(--text-primary); font-size: 2rem; margin-bottom: 1rem;">404</h1>
        <h2 style="color: var(--text-primary); margin-bottom: 1rem;">Page Not Found</h2>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">
            The page you're looking for doesn't exist or you don't have permission to access it.
        </p>
        
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <a href="<?= $viewHelper->url('dashboard/index') ?>" class="btn btn-primary">
                Go to Dashboard
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                Go Back
            </button>
        </div>
    </div>
</div>
