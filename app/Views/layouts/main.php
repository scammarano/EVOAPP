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
    <title><?= $viewHelper->escape($title ?? APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="<?= $viewHelper->asset('css/app.css') ?>">
</head>
<body class="hold-transition <?= Auth::isLoggedIn() ? 'sidebar-mini' : 'login-page' ?>" data-instance="<?= $viewHelper->escape($currentInstanceSlug) ?>">
    <?php if (Auth::isLoggedIn()): ?>
        <div class="wrapper">
            <nav class="main-header navbar navbar-expand navbar-white navbar-light">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                    </li>
                    <li class="nav-item d-none d-sm-inline-block">
                        <a href="<?= $viewHelper->url('dashboard/index') ?>" class="nav-link">Dashboard</a>
                    </li>
                </ul>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $viewHelper->url('auth/logout') ?>" title="Logout">Logout</a>
                    </li>
                </ul>
            </nav>

            <aside class="main-sidebar sidebar-dark-primary elevation-4">
                <a href="<?= $viewHelper->url('dashboard/index') ?>" class="brand-link">
                    <span class="brand-text font-weight-light"><?= $viewHelper->escape(APP_NAME) ?></span>
                </a>
                <div class="sidebar">
                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                            <li class="nav-item">
                                <a href="<?= $viewHelper->url('dashboard/index') ?>" class="nav-link">
                                    <i class="nav-icon fas fa-tachometer-alt"></i>
                                    <p>Dashboard</p>
                                </a>
                            </li>

                            <?php if (Auth::hasPermission('users.manage')): ?>
                                <li class="nav-header">ADMIN</li>
                                <li class="nav-item">
                                    <a href="<?= $viewHelper->url('admin/users') ?>" class="nav-link">
                                        <i class="nav-icon fas fa-users"></i>
                                        <p>Users</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= $viewHelper->url('admin/roles') ?>" class="nav-link">
                                        <i class="nav-icon fas fa-user-shield"></i>
                                        <p>Roles</p>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if (isset($instance)): ?>
                                <li class="nav-header">INSTANCE</li>
                                <li class="nav-item">
                                    <a href="<?= $viewHelper->url('dashboard/index') ?>" class="nav-link">
                                        <i class="nav-icon fas fa-arrow-left"></i>
                                        <p><?= $viewHelper->escape($instance['slug']) ?></p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" class="nav-link instance-tab <?= (strpos($_GET['r'] ?? '', 'inbox') !== false) ? 'active' : '' ?>"
                                       data-route="inbox/index">
                                        <i class="nav-icon far fa-comments"></i>
                                        <p>Inbox</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" class="nav-link instance-tab <?= (strpos($_GET['r'] ?? '', 'campaigns') !== false) ? 'active' : '' ?>"
                                       data-route="campaigns/index">
                                        <i class="nav-icon fas fa-bullhorn"></i>
                                        <p>Campaigns</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" class="nav-link instance-tab <?= (strpos($_GET['r'] ?? '', 'contacts') !== false) ? 'active' : '' ?>"
                                       data-route="contacts/index">
                                        <i class="nav-icon fas fa-address-book"></i>
                                        <p>Contacts</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" class="nav-link instance-tab <?= (strpos($_GET['r'] ?? '', 'groups') !== false) ? 'active' : '' ?>"
                                       data-route="groups/index">
                                        <i class="nav-icon fas fa-users"></i>
                                        <p>Groups</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" class="nav-link instance-tab <?= (strpos($_GET['r'] ?? '', 'debug') !== false) ? 'active' : '' ?>"
                                       data-route="debug/index">
                                        <i class="nav-icon fas fa-bug"></i>
                                        <p>Debug</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= $viewHelper->url('logs/index') ?>" class="nav-link">
                                        <i class="nav-icon fas fa-file-alt"></i>
                                        <p>System Logs</p>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </aside>

            <div class="content-wrapper">
                <section class="content pt-3">
                    <div class="container-fluid">
                        <?php if (isset($_SESSION['flash_error']) || isset($_SESSION['flash']['error'])): ?>
                            <div class="alert alert-danger">
                                <?= $viewHelper->escape($_SESSION['flash_error'] ?? $_SESSION['flash']['error']) ?>
                            </div>
                            <?php unset($_SESSION['flash_error'], $_SESSION['flash']['error']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['flash_success']) || isset($_SESSION['flash']['success'])): ?>
                            <div class="alert alert-success">
                                <?= $viewHelper->escape($_SESSION['flash_success'] ?? $_SESSION['flash']['success']) ?>
                            </div>
                            <?php unset($_SESSION['flash_success'], $_SESSION['flash']['success']); ?>
                        <?php endif; ?>

                        <?= $content ?? '' ?>
                    </div>
                </section>
            </div>
        </div>
    <?php else: ?>
        <?php if (isset($_SESSION['flash_error']) || isset($_SESSION['flash']['error'])): ?>
            <div class="alert alert-danger">
                <?= $viewHelper->escape($_SESSION['flash_error'] ?? $_SESSION['flash']['error']) ?>
            </div>
            <?php unset($_SESSION['flash_error'], $_SESSION['flash']['error']); ?>
        <?php endif; ?>

        <?= $content ?? '' ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <script src="<?= $viewHelper->asset('js/app.js') ?>"></script>
</body>
</html>
