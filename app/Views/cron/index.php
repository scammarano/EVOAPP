<?php
use App\Core\Auth;

$title = 'Cron Logs - ' . APP_NAME;
?>

<div class="cron-page">
    <div class="page-header">
        <h1>⚙️ Logs del Cron</h1>
        <div class="page-actions">
            <button class="btn btn-small" onclick="exportLogs()">
                <span class="icon-download"></span>
                Exportar CSV
            </button>
            <button class="btn btn-small btn-danger" onclick="clearLogs()">
                <span class="icon-trash"></span>
                Limpiar Logs
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="job_key">Job:</label>
                    <select name="job_key" id="job_key">
                        <option value="">Todos</option>
                        <option value="send_campaigns" <?= ($filters['job_key'] ?? null) === 'send_campaigns' ? 'selected' : '' ?>>Enviar Campañas</option>
                        <option value="process_webhooks" <?= ($filters['job_key'] ?? null) === 'process_webhooks' ? 'selected' : '' ?>>Procesar Webhooks</option>
                        <option value="cleanup_old_data" <?= ($filters['job_key'] ?? null) === 'cleanup_old_data' ? 'selected' : '' ?>>Limpiar Datos Antiguos</option>
                        <option value="send_scheduled_status" <?= ($filters['job_key'] ?? null) === 'send_scheduled_status' ? 'selected' : '' ?>>Enviar Estados Programados</option>
                        <option value="sync_contacts" <?= ($filters['job_key'] ?? null) === 'sync_contacts' ? 'selected' : '' ?>>Sincronizar Contactos</option>
                        <option value="backup_database" <?= ($filters['job_key'] ?? null) === 'backup_database' ? 'selected' : '' ?>>Backup Base de Datos</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="status">Estado:</label>
                    <select name="status" id="status">
                        <option value="">Todos</option>
                        <option value="running" <?= ($filters['status'] ?? null) === 'running' ? 'selected' : '' ?>>🟢 Ejecutándose</option>
                        <option value="completed" <?= ($filters['status'] ?? null) === 'completed' ? 'selected' : '' ?>>✅ Completado</option>
                        <option value="failed" <?= ($filters['status'] ?? null) === 'failed' ? 'selected' : '' ?>>❌ Fallido</option>
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

    <!-- Clear Logs Modal -->
    <div id="clear-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>⚠️ Confirmar Limpieza</h3>
            <p>¿Cuántos días de logs deseas eliminar?</p>
            <form method="POST" onsubmit="clearLogsSubmit(event)">
                <div class="form-group">
                    <label for="days">Días a conservar:</label>
                    <select name="days" id="days" required>
                        <option value="7">7 días</option>
                        <option value="15">15 días</option>
                        <option value="30" selected>30 días (por defecto)</option>
                        <option value="60">60 días</option>
                        <option value="90">90 días</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeClearModal()">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar Logs</button>
                </div>
            </form>
        </div>
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
                            <th>Job</th>
                            <th>Estado</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Duración</th>
                            <th>Resumen</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= $log['id'] ?></td>
                                <td>
                                    <span class="job-badge job-<?= $log['job_key'] ?>">
                                        <?= $this->getJobLabel($log['job_key']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $log['status'] ?>">
                                        <?= $this->getStatusLabel($log['status']) ?>
                                    </span>
                                </td>
                                <td><?= $viewHelper->formatDateTime($log['started_at']) ?></td>
                                <td><?= $viewHelper->formatDateTime($log['finished_at']) ?></td>
                                <td><?= $log['duration'] ?? 'N/A' ?></td>
                                <td><?= htmlspecialchars($log['summary'] ?? 'N/A') ?></td>
                                <td><?= $viewHelper->formatDateTime($log['started_at']) ?></td>
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
                <div class="icon">⚙️</div>
                <h3>No se encontraron logs</h3>
                <p>No hay registros del cron que coincidan con los filtros seleccionados.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function clearLogs() {
    document.getElementById('clear-modal').style.display = 'flex';
}

function closeClearModal() {
    document.getElementById('clear-modal').style.display = 'none';
}

function clearLogsSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    fetch('index.php?r=cron/clear', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeClearModal();
            // Reload page after 2 seconds
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al limpiar logs');
    });
}

function exportLogs() {
    window.location.href = 'index.php?r=cron/export<?= $this->buildFilterString() ?>';
}
</script>

<style>
.cron-page {
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

.job-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.job-send_campaigns { background: #10b981; color: white; }
.job-process_webhooks { background: #3b82f6; color: white; }
.job-cleanup_old_data { background: #f59e0b; color: white; }
.job-send_scheduled_status { background: #22c55e; color: white; }
.job-sync_contacts { background: #8b5cf6; color: white; }
.job-backup_database { background: #6366f1; color: white; }

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-running { background: #10b981; color: white; }
.status-completed { background: #22c55e; color: white; }
.status-failed { background: #ef4444; color: white; }

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

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: var(--surface);
    border-radius: 0.5rem;
    padding: 2rem;
    max-width: 400px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
}

.modal-content h3 {
    margin: 0 0 1rem 0;
    color: var(--text-primary);
}

.modal-content p {
    margin: 0 0 1.5rem 0;
    color: var(--text-secondary);
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-primary);
}

.form-group select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--border);
    border-radius: 0.25rem;
    background: var(--background);
    color: var(--text-primary);
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.btn-danger {
    background: var(--danger, #dc3545);
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}
</style>

<?php
// Helper functions
function getJobLabel($jobKey)
{
    $labels = [
        'send_campaigns' => 'Enviar Campañas',
        'process_webhooks' => 'Procesar Webhooks',
        'cleanup_old_data' => 'Limpiar Datos Antiguos',
        'send_scheduled_status' => 'Enviar Estados Programados',
        'sync_contacts' => 'Sincronizar Contactos',
        'backup_database' => 'Backup Base de Datos'
    ];
    
    return $labels[$jobKey] ?? $jobKey;
}

function getStatusLabel($status)
{
    $labels = [
        'running' => '🟢 Ejecutándose',
        'completed' => '✅ Completado',
        'failed' => '❌ Fallido'
    ];
    
    return $labels[$status] ?? $status;
}

function buildFilterString()
{
    $filters = [];
    
    if (!empty($_GET['job_key'])) {
        $filters[] = 'job_key=' . urlencode($_GET['job_key']);
    }
    
    if (!empty($_GET['status'])) {
        $filters[] = 'status=' . urlencode($_GET['status']);
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
