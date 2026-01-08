<?php
use App\Core\Auth;
use App\Core\View;
$title = $title ?? APP_NAME;
$currentInstanceSlug = $instance['slug'] ?? ($_GET['instance'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $viewHelper->escape(Auth::generateCsrfToken()) ?>">
    <meta name="csrf-token-name" content="<?= $viewHelper->escape(CSRF_TOKEN_NAME) ?>">
    <title><?= $viewHelper->escape($title ?? APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= $viewHelper->asset('css/app.css') ?>">
</head>
<body data-instance="<?= $viewHelper->escape($currentInstanceSlug) ?>">
    <?php if (Auth::isLoggedIn()): ?>
        <div class="app-container">
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="header">
                    <h1><?= $viewHelper->escape(APP_NAME) ?></h1>
                    <div class="header-actions">
                        <a href="<?= $viewHelper->url('dashboard/index') ?>" class="btn-icon" title="Dashboard">
                            <span class="icon-dashboard"></span>
                        </a>
                        <button class="btn-icon" onclick="window.location.href='<?= $viewHelper->url('auth/logout') ?>'" title="Logout">
                            <span class="icon-close"></span>
                        </button>
                    </div>
                </div>
                
                <div class="sidebar-content">
                    <?php if (isset($instance)): ?>
                        <!-- Instance Navigation -->
                        <div class="instance-header">
                            <div class="instance-name"><?= $viewHelper->escape($instance['slug']) ?></div>
                            <div class="instance-actions">
                                <a href="<?= $viewHelper->url('dashboard/index') ?>" class="btn-icon" title="Back to Dashboard">
                                    <span class="icon-close"></span>
                                </a>
                            </div>
                        </div>
                        
                        <div class="instance-tabs">
                            <button class="instance-tab <?= (strpos($_GET['r'] ?? '', 'inbox') !== false) ? 'active' : '' ?>" 
                                    data-route="inbox/index">
                                Inbox
                            </button>
                            <button class="instance-tab <?= (strpos($_GET['r'] ?? '', 'campaigns') !== false) ? 'active' : '' ?>" 
                                    data-route="campaigns/index">
                                Campaigns
                            </button>
                            <button class="instance-tab <?= (strpos($_GET['r'] ?? '', 'contacts') !== false) ? 'active' : '' ?>" 
                                    data-route="contacts/index">
                                Contacts
                            </button>
                            <button class="instance-tab <?= (strpos($_GET['r'] ?? '', 'groups') !== false) ? 'active' : '' ?>" 
                                    data-route="groups/index">
                                Groups
                            </button>
                            <button class="instance-tab <?= (strpos($_GET['r'] ?? '', 'debug') !== false) ? 'active' : '' ?>" 
                                    data-route="debug/index">
                                Debug
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['flash_error'])): ?>
                        <div class="alert alert-error">
                            <?= $viewHelper->escape($_SESSION['flash_error']) ?>
                        </div>
                        <?php unset($_SESSION['flash_error']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['flash_success'])): ?>
                        <div class="alert alert-success">
                            <?= $viewHelper->escape($_SESSION['flash_success']) ?>
                        </div>
                        <?php unset($_SESSION['flash_success']); ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="main-content">
                <?= $content ?? '' ?>
            </div>
        </div>
    <?php else: ?>
        <div class="auth-container">
            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-error">
                    <?= $viewHelper->escape($_SESSION['flash_error']) ?>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>
            
            <?= $content ?? '' ?>
        </div>
    <?php endif; ?>
    
    <script src="<?= $viewHelper->asset('js/app.js') ?>"></script>
</body>
</html>
