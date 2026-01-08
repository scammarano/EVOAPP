<?php
$title = 'Login - ' . APP_NAME;
?>

<div class="auth-container" style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--background);">
    <div class="login-card" style="background: var(--surface); padding: 2rem; border-radius: 1rem; border: 1px solid var(--border); width: 100%; max-width: 400px;">
        <div class="login-header" style="text-align: center; margin-bottom: 2rem;">
            <h1 style="color: var(--primary-green); font-size: 2rem; margin-bottom: 0.5rem;"><?= $viewHelper->escape(APP_NAME) ?></h1>
            <p style="color: var(--text-secondary);">Multi-instance WhatsApp Management</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?= $viewHelper->escape($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="<?= $viewHelper->url('auth/login') ?>" style="display: flex; flex-direction: column; gap: 1rem;">
            <?= \App\Core\View::csrfField() ?>
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-input" required 
                       placeholder="admin@evoapp.com" value="admin@evoapp.com">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" required 
                       placeholder="Password">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                Sign In
            </button>
        </form>
        
        <div class="login-footer" style="margin-top: 2rem; text-align: center; color: var(--text-secondary); font-size: 0.875rem;">
            <p>Default credentials: admin@evoapp.com / admin123</p>
        </div>
    </div>
</div>
