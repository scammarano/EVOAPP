<?php
use App\Core\Auth;

$title = 'Audit Logs - ' . APP_NAME;
?>

<div class="audit-page">
    <div class="page-header">
        <h1>📋 Logs de Auditoría</h1>
        <div class="page-actions">
            <button class="btn btn-small" onclick="exportLogs()">
                <span class="icon-download"></span>
                Exportar CSV
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="user_id">Usuario:</label>
                    <select name="user_id" id="user_id">
                        <option value="">Todos</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= ($filters['user_id'] ?? null) == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="action">Acción:</label>
                    <select name="action" id="action">
                        <option value="">Todas</option>
                        <option value="create" <?= ($filters['action'] ?? null) === 'create' ? 'selected' : '' ?>>Crear</option>
                        <option value="update" <?= ($filters['action'] ?? null) === 'update' ? 'selected' : '' ?>>Actualizar</option>
                        <option value="delete" <?= ($filters['action'] ?? null) === 'delete' ? 'selected' : '' ?>>Eliminar</option>
                        <option value="login" <?= ($filters['action'] ?? null) === 'login' ? 'selected' : '' ?>>Login</option>
                        <option value="logout" <?= ($filters['action'] ?? null) === 'logout' ? 'selected' : '' ?>>Logout</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="entity_type">Entidad:</label>
                    <select name="entity_type" id="entity_type">
                        <option value="">Todas</option>
                        <option value="instance" <?= ($filters['entity_type'] ?? null) === 'instance' ? 'selected' : '' ?>>Instancia</option>
                        <option value="user" <?= ($filters['entity_type'] ?? null) === 'user' ? 'selected' : '' ?>>Usuario</option>
                        <option value="chat" <?= ($filters['entity_type'] ?? null) === 'chat' ? 'selected' : '' ?>>Chat</option>
                        <option value="message" <?= ($filters['entity_type'] ?? null) === 'message' ? 'selected' : '' ?>>Mensaje</option>
                        <option value="contact" <?= ($filters['entity_type'] ?? null) === 'contact' ? 'selected' : '' ?>>Contacto</option>
                        <option value="campaign" <?= ($filters['entity_type'] ?? null) === 'campaign' ? 'selected' : '' ?>>Campaña</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date_from">Desde:</label>
                    <input type="datetime-local" name="date_from" id="date_from" 
                           value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                </div>
                
                <div class="filter-group">
                    <label for="date_to">Hasta:</label>
                    <input type="datetime-local" name="date_to" id="date_to" 
                           value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="logs-section">
        <?php if (!empty($logs)): ?>
            <div class="logs-info">
                Mostrando <?= count($logs) ?> de <?= $total ?> registros
                (Página <?= $page ?> de <?= ceil($total / $limit) ?>)
            </div>
            
            <div class="logs-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Entidad</th>
                            <th>ID Entidad</th>
                            <th>IP</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= $log['id'] ?></td>
                                <td><?= htmlspecialchars($log['user_name'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="action-badge action-<?= $log['action'] ?>">
                                        <?= $this->getActionLabel($log['action']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($log['entity_type'] ?? 'N/A') ?></td>
                                <td><?= $log['entity_id'] ?? 'N/A' ?></td>
                                <td><?= $log['ip_address'] ?? 'N/A' ?></td>
                                <td><?= $viewHelper->formatDateTime($log['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total > $limit): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $this->buildFilterString() ?>" class="btn">← Anterior</a>
                    <?php endif; ?>
                    
                    <span class="page-info">Página <?= $page ?> de <?= ceil($total / $limit) ?></span>
                    
                    <?php if ($page < ceil($total / $limit)): ?>
                        <a href="?page=<?= $page + 1 ?><?= $this->buildFilterString() ?>" class="btn">Siguiente →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-logs">
                <div class="icon">📋</div>
                <h3>No se encontraron logs</h3>
                <p>No hay registros de auditoría que coincidan con los filtros seleccionados.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.audit-page {
    padding: 1rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border);
}

.page-header h1 {
    margin: 0;
    color: var(--text-primary);
}

.page-actions {
    display: flex;
    gap: 1rem;
}

.filters-section {
    background: var(--surface);
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 2rem;
}

.filter-form {
    display: flex;
    gap: 1rem;
    align-items: end;
    flex-wrap: wrap;
}

.filter-row {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.filter-group input,
.filter-group select {
    padding: 0.5rem;
    border: 1px solid var(--border);
    border-radius: 0.25rem;
    background: var(--background);
    color: var(--text-primary);
}

.logs-section {
    background: var(--surface);
    border-radius: 0.5rem;
    padding: 1rem;
}

.logs-info {
    margin-bottom: 1rem;
    padding: 1rem;
    background: var(--background);
    border-radius: 0.25rem;
    text-align: center;
    color: var(--text-secondary);
}

.logs-table {
    overflow-x: auto;
    border-radius: 0.5rem;
    border: 1px solid var(--border);
}

.logs-table table {
    width: 100%;
    border-collapse: collapse;
}

.logs-table th,
.logs-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid var(--border);
}

.logs-table th {
    background: var(--background);
    font-weight: 600;
    color: var(--text-primary);
    position: sticky;
    top: 0;
}

.logs-table tr:hover {
    background: var(--background);
}

.action-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.action-create { background: #10b981; color: white; }
.action-update { background: #3b82f6; color: white; }
.action-delete { background: #ef4444; color: white; }
.action-login { background: #22c55e; color: white; }
.action-logout { background: #f59e0b; color: white; }

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
}

.pagination .btn {
    padding: 0.5rem 1rem;
    text-decoration: none;
}

.page-info {
    color: var(--text-secondary);
    font-weight: 500;
}

.no-logs {
    text-align: center;
    padding: 3rem;
    color: var(--text-secondary);
}

.no-logs .icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.no-logs h3 {
    margin: 0;
    color: var(--text-primary);
}
</style>

<?php
// Helper function
function getActionLabel($action)
{
    $labels = [
        'create' => 'Crear',
        'update' => 'Actualizar',
        'delete' => 'Eliminar',
        'login' => 'Login',
        'logout' => 'Logout',
        'send_message' => 'Enviar Mensaje',
        'send_media' => 'Enviar Archivo',
        'mark_read' => 'Marcar Leído',
        'regenerate_qr' => 'Regenerar QR',
        'clear_cron_logs' => 'Limpiar Logs'
    ];
    
    return $labels[$action] ?? $action;
}

function buildFilterString()
{
    $filters = [];
    
    if (!empty($_GET['user_id'])) {
        $filters[] = 'user_id=' . urlencode($_GET['user_id']);
    }
    
    if (!empty($_GET['action'])) {
        $filters[] = 'action=' . urlencode($_GET['action']);
    }
    
    if (!empty($_GET['entity_type'])) {
        $filters[] = 'entity_type=' . urlencode($_GET['entity_type']);
    }
    
    if (!empty($_GET['date_from'])) {
        $filters[] = 'date_from=' . urlencode($_GET['date_from']);
    }
    
    if (!empty($_GET['date_to'])) {
        $filters[] = 'date_to=' . urlencode($_GET['date_to']);
    }
    
    return !empty($filters) ? '&' . implode('&', $filters) : '';
}
?>
