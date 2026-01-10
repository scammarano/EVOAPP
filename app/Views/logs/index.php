<?php
use App\Core\Auth;
$title = 'System Logs - ' . APP_NAME;
?>

<style>
.logs-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.logs-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    text-align: center;
}

.logs-header h1 {
    margin: 0 0 10px 0;
    font-size: 2.5em;
}

.logs-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1em;
}

.logs-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.log-section {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.log-header {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.log-header h3 {
    margin: 0;
    color: #495057;
    font-size: 1.2em;
}

.log-content {
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
}

.log-entry {
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 5px;
    border-left: 4px solid;
    font-family: 'Courier New', monospace;
    font-size: 12px;
}

.log-entry.error {
    background: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
}

.log-entry.warning {
    background: #fff3cd;
    border-color: #ffc107;
    color: #856404;
}

.log-entry.notice {
    background: #d1ecf1;
    border-color: #17a2b8;
    color: #0c5460;
}

.log-entry.unknown {
    background: #e2e3e5;
    border-color: #6c757d;
    color: #383d41;
}

.log-timestamp {
    font-weight: bold;
    color: #6c757d;
    display: block;
    margin-bottom: 5px;
}

.log-level {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
    font-weight: bold;
    margin-right: 8px;
}

.log-level.ERROR {
    background: #dc3545;
    color: white;
}

.log-level.WARNING {
    background: #ffc107;
    color: #212529;
}

.log-level.NOTICE {
    background: #17a2b8;
    color: white;
}

.log-level.UNKNOWN {
    background: #6c757d;
    color: white;
}

.webhook-entry {
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 8px;
    border: 1px solid;
}

.webhook-entry.pending {
    background: #fff3cd;
    border-color: #ffc107;
}

.webhook-entry.failed {
    background: #f8d7da;
    border-color: #dc3545;
}

.webhook-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.webhook-id {
    font-weight: bold;
    color: #495057;
}

.webhook-status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.webhook-status.pending {
    background: #ffc107;
    color: #212529;
}

.webhook-status.failed {
    background: #dc3545;
    color: white;
}

.webhook-details {
    font-size: 12px;
    color: #6c757d;
}

.webhook-details span {
    margin-right: 15px;
}

.system-info {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 20px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
}

.info-item {
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}

.info-label {
    font-weight: bold;
    color: #495057;
    margin-bottom: 5px;
}

.info-value {
    color: #6c757d;
    font-family: 'Courier New', monospace;
    font-size: 12px;
}

.action-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-warning {
    background: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background: #e0a800;
}

.btn-info {
    background: #17a2b8;
    color: white;
}

.btn-info:hover {
    background: #138496;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.loading {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    border-radius: 8px;
    background: #f8f9fa;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #495057;
}

.stat-label {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}

.auto-refresh {
    display: flex;
    align-items: center;
    gap: 10px;
}

.auto-refresh input {
    margin-right: 5px;
}

.refresh-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #28a745;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}
</style>

<div class="logs-container">
    <div class="logs-header">
        <h1>🔍 System Logs</h1>
        <p>Monitor PHP errors, webhook status, and system information</p>
    </div>
    
    <div class="action-buttons">
        <button class="btn btn-primary" onclick="refreshLogs()">
            🔄 Refresh All Logs
        </button>
        <button class="btn btn-warning" onclick="clearPhpErrors()">
            🗑️ Clear PHP Errors
        </button>
        <button class="btn btn-success" onclick="retryFailedWebhooks()">
            🔄 Retry Failed Webhooks
        </button>
        <button class="btn btn-info" onclick="exportPhpErrors()">
            📥 Export PHP Errors (CSV)
        </button>
        <button class="btn btn-danger" onclick="exportDatabaseErrors()">
            🗃️ Export Database Errors (CSV)
        </button>
        <div class="auto-refresh">
            <input type="checkbox" id="auto-refresh" checked>
            <label for="auto-refresh">Auto-refresh (30s)</label>
            <div class="refresh-indicator" id="refresh-indicator"></div>
        </div>
    </div>
    
    <div class="logs-grid">
        <!-- PHP Errors Section -->
        <div class="log-section">
            <div class="log-header">
                <h3>🚨 PHP Errors</h3>
                <div>
                    <span id="php-errors-count">Loading...</span>
                    <div id="error-counters" style="margin-top: 5px; font-size: 11px;">
                        <div class="loading">Loading counters...</div>
                    </div>
                </div>
            </div>
            <div class="log-content" id="php-errors">
                <div class="loading">Loading PHP errors...</div>
            </div>
        </div>
        
        <!-- Webhook Status Section -->
        <div class="log-section">
            <div class="log-header">
                <h3>🔗 Webhook Status</h3>
                <span id="webhooks-count">Loading...</span>
            </div>
            <div class="log-content" id="webhook-logs">
                <div class="loading">Loading webhook status...</div>
            </div>
        </div>
    </div>
    
    <!-- Webhook Statistics -->
    <div class="log-section">
        <div class="log-header">
            <h3>📊 Webhook Statistics (Last 24 Hours)</h3>
        </div>
        <div class="log-content">
            <div class="stats-grid" id="webhook-stats">
                <div class="loading">Loading webhook statistics...</div>
            </div>
        </div>
    </div>
    
    <!-- System Information -->
    <div class="log-section">
        <div class="log-header">
            <h3>⚙️ System Information</h3>
        </div>
        <div class="log-content">
            <div class="info-grid" id="system-info">
                <div class="loading">Loading system information...</div>
            </div>
        </div>
    </div>
</div>

<script>
let autoRefreshInterval;

function refreshLogs() {
    refreshPhpErrors();
    refreshWebhookLogs();
    refreshSystemInfo();
}

function refreshPhpErrors() {
    fetch('index.php?r=logs/getPhpErrors')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPhpErrors(data.logs, data.error_counts, data.summary);
                document.getElementById('php-errors-count').textContent = `${data.count} recent / ${data.total_count} total`;
            } else {
                document.getElementById('php-errors').innerHTML = `<div class="loading">Error: ${data.error}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('php-errors').innerHTML = `<div class="loading">Network error: ${error.message}</div>`;
        });
}

function refreshWebhookLogs() {
    fetch('index.php?r=logs/getWebhookLogs')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayWebhookLogs(data.webhooks);
                displayWebhookStats(data.stats);
                document.getElementById('webhooks-count').textContent = `${data.count} pending/failed`;
            } else {
                document.getElementById('webhook-logs').innerHTML = `<div class="loading">Error: ${data.error}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('webhook-logs').innerHTML = `<div class="loading">Network error: ${error.message}</div>`;
        });
}

function refreshSystemInfo() {
    fetch('index.php?r=logs/getSystemInfo')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySystemInfo(data.info);
            } else {
                document.getElementById('system-info').innerHTML = `<div class="loading">Error: ${data.error}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('system-info').innerHTML = `<div class="loading">Network error: ${error.message}</div>`;
        });
}

function displayPhpErrors(logs, errorCounts, summary) {
    const container = document.getElementById('php-errors');
    const countersContainer = document.getElementById('error-counters');
    
    if (logs.length === 0) {
        container.innerHTML = '<div class="loading">No PHP errors found</div>';
        countersContainer.innerHTML = '<div style="color: #28a745;">✅ No errors</div>';
        return;
    }
    
    // Mostrar contadores
    if (errorCounts && summary) {
        countersContainer.innerHTML = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 5px; text-align: center;">
                <div style="background: #f8d7da; padding: 3px; border-radius: 3px;">
                    <strong>Total:</strong> ${summary.total_errors}
                </div>
                <div style="background: #f8d7da; padding: 3px; border-radius: 3px;">
                    <strong>Críticos:</strong> ${summary.critical_errors}
                </div>
                <div style="background: #fff3cd; padding: 3px; border-radius: 3px;">
                    <strong>Warnings:</strong> ${summary.warnings}
                </div>
                <div style="background: #d1ecf1; padding: 3px; border-radius: 3px;">
                    <strong>Notices:</strong> ${summary.notices}
                </div>
            </div>
        `;
    }
    
    container.innerHTML = logs.map(log => `
        <div class="log-entry ${log.level.toLowerCase()}">
            <div class="log-timestamp">${log.timestamp}</div>
            <span class="log-level ${log.level}">${log.level}</span>
            <div class="log-message">${log.message}</div>
        </div>
    `).join('');
}

function displayWebhookLogs(webhooks) {
    const container = document.getElementById('webhook-logs');
    
    if (webhooks.length === 0) {
        container.innerHTML = '<div class="loading">No pending or failed webhooks</div>';
        return;
    }
    
    container.innerHTML = webhooks.map(webhook => `
        <div class="webhook-entry ${webhook.status}">
            <div class="webhook-header">
                <span class="webhook-id">ID: ${webhook.id}</span>
                <span class="webhook-status ${webhook.status}">${webhook.status}</span>
            </div>
            <div class="webhook-details">
                <span>Instance: ${webhook.instance_id}</span>
                <span>Event: ${webhook.event_type}</span>
                <span>Created: ${webhook.created_at}</span>
                <span>Retries: ${webhook.retry_count}</span>
            </div>
            ${webhook.error_message ? `<div style="color: #dc3545; margin-top: 5px; font-size: 11px;">Error: ${webhook.error_message}</div>` : ''}
        </div>
    `).join('');
}

function displayWebhookStats(stats) {
    const container = document.getElementById('webhook-stats');
    
    if (stats.length === 0) {
        container.innerHTML = '<div class="loading">No webhook statistics available</div>';
        return;
    }
    
    container.innerHTML = stats.map(stat => `
        <div class="stat-item">
            <div class="stat-value">${stat.count}</div>
            <div class="stat-label">${stat.status.toUpperCase()}</div>
        </div>
    `).join('');
}

function displaySystemInfo(info) {
    const container = document.getElementById('system-info');
    
    const infoItems = [
        { label: 'PHP Version', value: info.php_version },
        { label: 'Server Software', value: info.server_software },
        { label: 'Document Root', value: info.document_root },
        { label: 'Memory Limit', value: info.memory_limit },
        { label: 'Max Execution Time', value: info.max_execution_time + 's' },
        { label: 'Upload Max Filesize', value: info.upload_max_filesize },
        { label: 'Post Max Size', value: info.post_max_size },
        { label: 'Error Log Path', value: info.error_log || 'Not set' },
        { label: 'Log Errors', value: info.log_errors ? 'Enabled' : 'Disabled' },
        { label: 'Display Errors', value: info.display_errors ? 'Enabled' : 'Disabled' },
        { label: 'Error Reporting', value: info.error_reporting },
        { label: 'Current Time', value: info.current_time },
        { label: 'Timezone', value: info.timezone },
        { label: 'DB Name', value: info.app_constants.DB_NAME },
        { label: 'EVO Base URL', value: info.app_constants.EVO_BASE_URL },
        { label: 'App Name', value: info.app_constants.APP_NAME }
    ];
    
    container.innerHTML = infoItems.map(item => `
        <div class="info-item">
            <div class="info-label">${item.label}</div>
            <div class="info-value">${item.value}</div>
        </div>
    `).join('');
}

function clearPhpErrors() {
    if (confirm('Are you sure you want to clear all PHP errors?')) {
        fetch('index.php?r=logs/clearPhpErrors')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('PHP errors cleared successfully');
                    refreshPhpErrors();
                } else {
                    alert('Failed to clear PHP errors: ' + data.error);
                }
            })
            .catch(error => {
                alert('Network error: ' + error.message);
            });
    }
}

function retryFailedWebhooks() {
    if (confirm('Are you sure you want to retry all failed webhooks?')) {
        fetch('index.php?r=logs/retryFailedWebhooks')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`${data.message}`);
                    refreshWebhookLogs();
                } else {
                    alert('Failed to retry webhooks: ' + data.error);
                }
            })
            .catch(error => {
                alert('Network error: ' + error.message);
            });
    }
}

function exportPhpErrors() {
    if (confirm('Download all PHP errors as CSV file?')) {
        window.open('index.php?r=logs/exportPhpErrors', '_blank');
    }
}

function exportDatabaseErrors() {
    if (confirm('Download all database errors as CSV file?')) {
        window.open('index.php?r=logs/exportDatabaseErrors', '_blank');
    }
}

function toggleAutoRefresh() {
    const checkbox = document.getElementById('auto-refresh');
    const indicator = document.getElementById('refresh-indicator');
    
    if (checkbox.checked) {
        autoRefreshInterval = setInterval(refreshLogs, 30000); // 30 seconds
        indicator.style.display = 'block';
    } else {
        clearInterval(autoRefreshInterval);
        indicator.style.display = 'none';
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    refreshLogs();
    
    document.getElementById('auto-refresh').addEventListener('change', toggleAutoRefresh);
    
    // Start auto-refresh if checked
    if (document.getElementById('auto-refresh').checked) {
        toggleAutoRefresh();
    }
});
</script>
