<?php
$title = 'Login - ' . APP_NAME;
?>

<div class="login-box">
    <div class="login-logo">
        <b><?= $viewHelper->escape(APP_NAME) ?></b>
    </div>
    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Multi-instance WhatsApp Management</p>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?= $viewHelper->escape($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= $viewHelper->url('auth/login') ?>">
                <div class="input-group mb-3">
                    <input type="email" id="email" name="email" class="form-control" required
                           placeholder="admin@evoapp.com" value="admin@evoapp.com">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                </div>

                <div class="input-group mb-3">
                    <input type="password" id="password" name="password" class="form-control" required
                           placeholder="Password">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                    </div>
                </div>
            </form>

            <p class="mt-3 mb-0 text-center text-muted">
                <small>Default credentials: admin@evoapp.com / admin123</small>
            </p>
        </div>
    </div>
</div>
