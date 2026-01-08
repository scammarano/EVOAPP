<?php
use App\Core\Auth;

$title = 'Sync Contacts - ' . APP_NAME;
?>

<div class="sync-contacts">
    <div class="page-header">
        <h1>🔄 Sincronizar Contactos y Grupos</h1>
        <div class="page-actions">
            <a href="<?= $viewHelper->url('contacts/index') ?>&instance=<?= $viewHelper->escape($instance['slug']) ?>" class="btn btn-secondary">
                <span class="icon-back"></span>
                Volver a Contactos
            </a>
        </div>
    </div>

    <div class="sync-container">
        <div class="sync-info">
            <div class="info-card">
                <h3>📱 Información de la Instancia</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">Nombre:</span>
                        <span class="value"><?= $viewHelper->escape($instance['name'] ?? $instance['slug']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Slug:</span>
                        <span class="value"><?= $viewHelper->escape($instance['slug']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Estado:</span>
                        <span class="value status-<?= $instance['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $instance['is_active'] ? 'Activa' : 'Inactiva' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="sync-actions">
            <div class="action-card">
                <h3>🔄 Sincronización</h3>
                <div class="sync-description">
                    <p>Extrae todos los contactos y grupos desde Evolution API y los sincroniza con la base de datos local.</p>
                    <div class="sync-warning">
                        <strong>⚠️ Nota:</strong> Esta operación puede tomar varios minutos dependiendo de la cantidad de contactos.
                    </div>
                </div>
                
                <div class="sync-buttons">
                    <button id="sync-contacts-btn" class="btn btn-primary" onclick="syncContacts()">
                        <span class="icon-sync"></span>
                        Sincronizar Contactos
                    </button>
                    
                    <button id="sync-groups-btn" class="btn btn-secondary" onclick="syncGroups()">
                        <span class="icon-users"></span>
                        Sincronizar Grupos
                    </button>
                    
                    <button id="sync-all-btn" class="btn btn-success" onclick="syncAll()">
                        <span class="icon-refresh"></span>
                        Sincronizar Todo
                    </button>
                </div>
            </div>

            <div class="action-card">
                <h3>📊 Estadísticas Actuales</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number" id="total-contacts">0</div>
                        <div class="stat-label">Contactos Totales</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="total-groups">0</div>
                        <div class="stat-label">Grupos Totales</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="active-contacts">0</div>
                        <div class="stat-label">Contactos Activos</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="last-sync">Nunca</div>
                        <div class="stat-label">Última Sincronización</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sync-results" id="sync-results" style="display: none;">
            <div class="results-card">
                <h3>📋 Resultados de Sincronización</h3>
                <div class="results-content" id="results-content">
                    <!-- Results will be inserted here -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.sync-contacts {
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
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

.sync-container {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
}

.info-card,
.action-card,
.results-card {
    background: var(--surface);
    border-radius: 0.5rem;
    padding: 1.5rem;
    border: 1px solid var(--border);
}

.info-card h3,
.action-card h3,
.results-card h3 {
    margin: 0 0 1rem 0;
    color: var(--text-primary);
}

.info-grid {
    display: grid;
    gap: 1rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border);
}

.info-item .label {
    font-weight: 600;
    color: var(--text-secondary);
}

.info-item .value {
    color: var(--text-primary);
}

.status-active {
    color: #22c55e;
    font-weight: 600;
}

.status-inactive {
    color: #dc3545;
    font-weight: 600;
}

.sync-description {
    margin-bottom: 1.5rem;
}

.sync-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 0.25rem;
    padding: 0.75rem;
    margin-top: 1rem;
    color: #856404;
}

.sync-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: var(--background);
    border-radius: 0.25rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.results-content {
    max-height: 300px;
    overflow-y: auto;
}

.sync-results {
    grid-column: 1 / -1;
}

@media (max-width: 768px) {
    .sync-container {
        grid-template-columns: 1fr;
    }
    
    .sync-buttons {
        flex-direction: column;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
function syncContacts() {
    showSyncProgress('Sincronizando contactos...');
    
    fetch('index.php?r=contacts/syncContacts', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            instance: '<?= $viewHelper->escape($instance['slug']) ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSyncResults(data);
            updateStats();
        } else {
            showSyncError(data.error);
        }
    })
    .catch(error => {
        showSyncError('Error de red: ' + error.message);
    });
}

function syncGroups() {
    showSyncProgress('Sincronizando grupos...');
    
    fetch('index.php?r=contacts/syncGroups', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            instance: '<?= $viewHelper->escape($instance['slug']) ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSyncResults(data);
            updateStats();
        } else {
            showSyncError(data.error);
        }
    })
    .catch(error => {
        showSyncError('Error de red: ' + error.message);
    });
}

function syncAll() {
    showSyncProgress('Sincronizando contactos y grupos...');
    
    fetch('index.php?r=contacts/syncAll', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            instance: '<?= $viewHelper->escape($instance['slug']) ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSyncResults(data);
            updateStats();
        } else {
            showSyncError(data.error);
        }
    })
    .catch(error => {
        showSyncError('Error de red: ' + error.message);
    });
}

function showSyncProgress(message) {
    const resultsDiv = document.getElementById('sync-results');
    const contentDiv = document.getElementById('results-content');
    
    resultsDiv.style.display = 'block';
    contentDiv.innerHTML = `
        <div class="sync-loading">
            <div class="spinner"></div>
            <p>${message}</p>
        </div>
    `;
}

function showSyncResults(data) {
    const contentDiv = document.getElementById('results-content');
    
    if (data.stats) {
        contentDiv.innerHTML = `
            <div class="sync-success">
                <div class="success-icon">✅</div>
                <h4>Sincronización completada</h4>
                <div class="stats-summary">
                    <div class="summary-item">
                        <span class="label">Contactos sincronizados:</span>
                        <span class="value">${data.stats.synced_contacts || 0}</span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Contactos actualizados:</span>
                        <span class="value">${data.stats.updated_contacts || 0}</span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Grupos sincronizados:</span>
                        <span class="value">${data.stats.synced_groups || 0}</span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Grupos actualizados:</span>
                        <span class="value">${data.stats.updated_groups || 0}</span>
                    </div>
                </div>
            </div>
        `;
    } else {
        showSyncError(data.error || 'Error desconocido');
    }
}

function showSyncError(error) {
    const contentDiv = document.getElementById('results-content');
    contentDiv.innerHTML = `
        <div class="sync-error">
            <div class="error-icon">❌</div>
            <h4>Error en sincronización</h4>
            <p class="error-message">${error}</p>
        </div>
    `;
}

function updateStats() {
    // Update last sync time
    document.getElementById('last-sync').textContent = new Date().toLocaleString();
    
    // Fetch updated stats
    fetch('index.php?r=contacts/getStats&instance=<?= $viewHelper->escape($instance['slug']) ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('total-contacts').textContent = data.total_contacts || 0;
                document.getElementById('total-groups').textContent = data.total_groups || 0;
                document.getElementById('active-contacts').textContent = data.active_contacts || 0;
            }
        });
}

// Load initial stats on page load
document.addEventListener('DOMContentLoaded', updateStats);
</script>
