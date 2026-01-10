<?php
// Generar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contactos - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .contact-card {
            transition: transform 0.2s;
        }
        .contact-card:hover {
            transform: translateY(-2px);
        }
        .extract-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .extract-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/dashboard">
                <i class="fas fa-address-book mr-2"></i><?= APP_NAME ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard">
                            <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/instances">
                            <i class="fas fa-server mr-1"></i> Instancias
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/inbox">
                            <i class="fas fa-comments mr-1"></i> Inbox
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/campaigns">
                            <i class="fas fa-bullhorn mr-1"></i> Campañas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/contacts">
                            <i class="fas fa-address-book mr-1"></i> Contactos
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user mr-1"></i> <?= $_SESSION['user_name'] ?? 'Usuario' ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/profile">Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Alertas -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-2"></i>
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><i class="fas fa-address-book mr-2"></i>Contactos</h2>
                <p class="text-muted">Gestiona y extrae contactos de WhatsApp</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/contacts/create" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i> Nuevo Contacto
                </a>
            </div>
        </div>

        <!-- Extracción de Contactos -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-download mr-2"></i>Extracción de Contactos</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-comments mr-2"></i>Extraer de Chats</h6>
                                <p class="text-muted">Extrae contactos de todos tus chats de WhatsApp</p>
                                <form method="POST" action="/contacts/extract/chats" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="extract-btn" onclick="return confirm('¿Estás seguro de extraer contactos de todos los chats?')">
                                        <i class="fas fa-comments mr-2"></i>Extraer de Chats
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-users mr-2"></i>Extraer de Grupos</h6>
                                <p class="text-muted">Extrae contactos de tus grupos de WhatsApp</p>
                                <form method="POST" action="/contacts/extract/groups" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="extract-btn" onclick="return confirm('¿Estás seguro de extraer contactos de todos los grupos?')">
                                        <i class="fas fa-users mr-2"></i>Extraer de Grupos
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle mr-2"></i>Información de Extracción</h6>
                                <ul class="mb-0">
                                    <li><strong>De Chats:</strong> Extrae todos los contactos con los que has tenido conversaciones</li>
                                    <li><strong>De Grupos:</strong> Extrae todos los miembros de tus grupos de WhatsApp</li>
                                    <li><strong>Automático:</strong> El sistema detecta nombres, teléfonos y evita duplicados</li>
                                    <li><strong>Seguro:</strong> Solo extrae información pública de tus conversaciones</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h4><?= count($contacts ?? []) ?></h4>
                        <p>Total Contactos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-user-check fa-2x mb-2"></i>
                        <h4><?= count(array_filter($contacts ?? [], fn($c) => !empty($c['name']))) ?></h4>
                        <p>Con Nombre</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-envelope fa-2x mb-2"></i>
                        <h4><?= count(array_filter($contacts ?? [], fn($c) => !empty($c['email']))) ?></h4>
                        <p>Con Email</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-building fa-2x mb-2"></i>
                        <h4><?= count(array_filter($contacts ?? [], fn($c) => !empty($c['company']))) ?></h4>
                        <p>Con Empresa</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Búsqueda -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="/contacts">
                            <div class="row">
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="Buscar por nombre, teléfono, email..." 
                                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <div class="btn-group w-100">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search mr-1"></i> Buscar
                                        </button>
                                        <a href="/contacts" class="btn btn-secondary">
                                            <i class="fas fa-times mr-1"></i> Limpiar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Contactos -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Lista de Contactos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($contacts ?? [])): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-address-book fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay contactos</h5>
                                <p class="text-muted">Extrae contactos de tus chats o agrégalos manualmente.</p>
                                <div class="mt-3">
                                    <button class="btn btn-outline-primary me-2" onclick="document.querySelector('form[action=\"/contacts/extract/chats\"]').submit()">
                                        <i class="fas fa-comments mr-1"></i> Extraer de Chats
                                    </button>
                                    <button class="btn btn-outline-primary" onclick="document.querySelector('form[action=\"/contacts/extract/groups\"]').submit()">
                                        <i class="fas fa-users mr-1"></i> Extraer de Grupos
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Teléfono</th>
                                            <th>Email</th>
                                            <th>Empresa</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contacts as $contact): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                            <?= substr($contact['name'] ?? 'Unknown', 0, 1) ?>
                                                        </div>
                                                        <div>
                                                            <strong><?= htmlspecialchars($contact['name'] ?? 'Unknown') ?></strong>
                                                            <?php if (!empty($contact['source'])): ?>
                                                                <br><small class="text-muted">Origen: <?= htmlspecialchars($contact['source']) ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-phone mr-1"></i>
                                                        <?= htmlspecialchars($contact['phone'] ?? '') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($contact['email'])): ?>
                                                        <a href="mailto:<?= htmlspecialchars($contact['email']) ?>" class="text-decoration-none">
                                                            <i class="fas fa-envelope mr-1"></i>
                                                            <?= htmlspecialchars($contact['email']) ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($contact['company'] ?? '—') ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y', strtotime($contact['created_at'] ?? 'now')) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="/contacts/edit/<?= $contact['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="/inbox/compose/<?= $contact['phone'] ?>" class="btn btn-sm btn-outline-success" title="Enviar Mensaje">
                                                            <i class="fas fa-comment"></i>
                                                        </a>
                                                        <form method="POST" action="/contacts/delete/<?= $contact['id'] ?>" style="display: inline;">
                                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Estás seguro de eliminar este contacto?')" title="Eliminar">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevenir refresh automático
        document.addEventListener('DOMContentLoaded', function() {
            // Eliminar cualquier temporizador existente
            if (window.refreshInterval) {
                clearInterval(window.refreshInterval);
                window.refreshInterval = null;
            }
            
            // Prevenir submit que cause refresh no deseado
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                if (!form.hasAttribute('data-no-prevent')) {
                    // Solo prevenir si no es un formulario de extracción
                    if (!form.action.includes('extract')) {
                        form.addEventListener('submit', function(e) {
                            // Permitir el submit normal, solo prevenir refresh automático
                            setTimeout(() => {
                                if (window.refreshInterval) {
                                    clearInterval(window.refreshInterval);
                                    window.refreshInterval = null;
                                }
                            }, 100);
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
