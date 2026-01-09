<?php
use App\Core\Auth;
$title = 'Diagnostic - ' . APP_NAME;
?>

<div class="diagnostic-container">
    <div class="diagnostic-header">
        <h1>🔍 Diagnóstico de Instancias</h1>
        <p>Prueba la conexión y envío de mensajes de todas las instancias Evolution API</p>
    </div>
    
    <div class="diagnostic-actions">
        <div class="test-config">
            <div class="form-group">
                <label>Número para todas las pruebas:</label>
                <input type="text" id="global-test-number" value="+10000000000" placeholder="+584143238051">
            </div>
            <div class="form-group">
                <label>Mensaje de prueba:</label>
                <input type="text" id="global-test-text" value="Mensaje de prueba - EVOAPP Diagnostic" placeholder="Mensaje de prueba">
            </div>
            <div class="form-group">
                <label>Tipo de prueba:</label>
                <select id="test-type">
                    <option value="single">📤 Mensaje Simple</option>
                    <option value="burst">⚡ Ráfaga (3 mensajes)</option>
                    <option value="media">📎 Mensaje con Adjunto</option>
                </select>
            </div>
        </div>
        <div class="selection-tools">
            <label class="select-all-toggle">
                <input type="checkbox" id="select-all-instances" onchange="toggleAllInstances(this.checked)">
                Seleccionar todas las instancias
            </label>
            <span class="selection-hint">Si seleccionas alguna, solo se probarán esas instancias.</span>
        </div>
        <div class="action-buttons">
            <button type="button" class="btn btn-primary" onclick="testAllInstances()">
                🚀 Probar Todas las Instancias
            </button>
            <button type="button" class="btn btn-secondary" onclick="refreshInstances()">
                🔄 Actualizar Lista
            </button>
        </div>
    </div>
    
    <div class="instances-grid">
        <?php foreach ($instances as $instance): ?>
            <div class="instance-card" id="instance-<?= $instance['id'] ?>">
                <div class="instance-header">
                    <h3><?= $viewHelper->escape($instance['slug']) ?></h3>
                    <label class="instance-select">
                        <input type="checkbox" class="instance-checkbox" value="<?= $instance['id'] ?>">
                        Seleccionar
                    </label>
                    <div class="instance-status" id="status-<?= $instance['id'] ?>">
                        <span class="status-dot unknown"></span>
                        <span class="status-text">Sin probar</span>
                    </div>
                </div>
                
                <div class="instance-info">
                    <div class="info-item">
                        <label>Base URL:</label>
                        <span><?= $viewHelper->escape($instance['base_url'] ?: EVO_BASE_URL) ?></span>
                    </div>
                    <div class="info-item">
                        <label>API Key:</label>
                        <span><?= $instance['api_key'] ? substr($instance['api_key'], 0, 8) . '...' : 'No configurada' ?></span>
                    </div>
                    <div class="info-item">
                        <label>Webhook:</label>
                        <span><?= $instance['last_webhook_at'] ? date('d/m H:i', strtotime($instance['last_webhook_at'])) : 'Nunca' ?></span>
                    </div>
                </div>
                
                <div class="instance-actions">
                    <button type="button" class="btn btn-small" onclick="testInstance(<?= $instance['id'] ?>)">
                        🧪 Probar Conexión
                    </button>
                    <button type="button" class="btn btn-small" onclick="testSendMessage(<?= $instance['id'] ?>)">
                        📤 Enviar Prueba
                    </button>
                </div>
                
                <div class="test-results" id="results-<?= $instance['id'] ?>" style="display: none;">
                    <!-- Results will be loaded here -->
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Modal para enviar mensaje de prueba -->
    <div id="test-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Enviar Mensaje de Prueba</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Número de prueba:</label>
                    <input type="text" id="test-number" value="+10000000000" placeholder="+584143238051">
                </div>
                <div class="form-group">
                    <label>Mensaje de prueba:</label>
                    <textarea id="test-text" rows="3">Mensaje de prueba - EVOAPP Diagnostic</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="sendTestMessage()">Enviar Prueba</button>
            </div>
        </div>
    </div>
</div>

<style>
.diagnostic-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
    color: #212529;
}

.diagnostic-header {
    text-align: center;
    margin-bottom: 2rem;
}

.diagnostic-header h1 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.diagnostic-actions {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.test-config {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.selection-tools {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    align-items: center;
    color: #212529;
}

.select-all-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.selection-hint {
    font-size: 0.85rem;
    color: #6c757d;
}

.form-group select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
    background: white;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.instances-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.instance-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
    color: #212529;
}

.instance-header {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
}

.instance-header h3 {
    margin: 0;
    color: var(--text-primary);
}

.instance-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.instance-select {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.85rem;
    color: #495057;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-dot.unknown { background: #6c757d; }
.status-dot.success { background: #28a745; }
.status-dot.error { background: #dc3545; }
.status-dot.testing { background: #ffc107; animation: pulse 1s infinite; }

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.instance-info {
    margin-bottom: 1rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.info-item label {
    font-weight: 500;
    color: var(--text-secondary);
}

.instance-actions {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.test-results {
    margin-top: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
    font-size: 0.85rem;
    color: #212529;
}

.test-result {
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    border-radius: 4px;
}

.test-result.info {
    background: #e3f2fd;
    color: #0d47a1;
}

.test-result.success {
    background: #d4edda;
    color: #155724;
}

.test-result.error {
    background: #f8d7da;
    color: #721c24;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 8px;
    padding: 0;
    max-width: 500px;
    width: 90%;
}

.modal-header {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 1rem;
}

.modal-footer {
    padding: 1rem;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
}
</style>

<script>
window.evoappDisablePolling = true;
</script>

<script>
let currentInstanceId = null;

function testInstance(instanceId) {
    console.log('Iniciando prueba para instancia:', instanceId);
    updateStatus(instanceId, 'testing', 'Probando...');
    
    const number = document.getElementById('global-test-number').value;
    const text = document.getElementById('global-test-text').value;
    const testType = document.getElementById('test-type').value;
    
    console.log('Enviando petición:', { instanceId, number, text, testType });
    
    fetch('index.php?r=diagnostic/testInstance', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `instance_id=${instanceId}&test_number=${encodeURIComponent(number)}&test_text=${encodeURIComponent(text)}&test_type=${testType}`
    })
    .then(response => {
        console.log('Respuesta recibida:', response);
        return response.json();
    })
    .then(data => {
        console.log('Datos procesados:', data);
        if (data.success) {
            displayResults(instanceId, data.result);
            updateStatus(instanceId, 'success', 'Completado');
        } else {
            displayError(instanceId, data.error);
            updateStatus(instanceId, 'error', 'Error');
        }
    })
    .catch(error => {
        console.error('Error en la petición:', error);
        displayError(instanceId, error.message);
        updateStatus(instanceId, 'error', 'Error');
    });
}

function testSendMessage(instanceId) {
    currentInstanceId = instanceId;
    document.getElementById('test-modal').style.display = 'flex';
}

function sendTestMessage() {
    if (!currentInstanceId) return;
    
    const number = document.getElementById('test-number').value;
    const text = document.getElementById('test-text').value;
    
    updateStatus(currentInstanceId, 'testing', 'Enviando...');
    closeModal();
    
    fetch('index.php?r=diagnostic/testInstance', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `instance_id=${currentInstanceId}&test_number=${encodeURIComponent(number)}&test_text=${encodeURIComponent(text)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayResults(currentInstanceId, data.result);
            updateStatus(currentInstanceId, 'success', 'Enviado');
        } else {
            displayError(currentInstanceId, data.error);
            updateStatus(currentInstanceId, 'error', 'Error');
        }
    })
    .catch(error => {
        displayError(currentInstanceId, error.message);
        updateStatus(currentInstanceId, 'error', 'Error');
    });
}

function testAllInstances() {
    console.log('Iniciando pruebas masivas');
    const selectedInstanceIds = getSelectedInstanceIds();
    const instanceIdsToTest = selectedInstanceIds.length
        ? selectedInstanceIds
        : Array.from(document.querySelectorAll('.instance-card')).map(card => card.id.replace('instance-', ''));

    instanceIdsToTest.forEach(instanceId => {
        updateStatus(instanceId, 'testing', 'Probando...');
    });
    
    const number = document.getElementById('global-test-number').value;
    const text = document.getElementById('global-test-text').value;
    const testType = document.getElementById('test-type').value;
    
    console.log('Enviando peticiones masivas:', { number, text, testType, instanceIdsToTest });
    
    fetch('index.php?r=diagnostic/testAll', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `test_number=${encodeURIComponent(number)}&test_text=${encodeURIComponent(text)}&test_type=${encodeURIComponent(testType)}&instance_ids=${encodeURIComponent(instanceIdsToTest.join(','))}`
    })
    .then(response => {
        console.log('Respuesta masiva recibida:', response);
        return response.json();
    })
    .then(data => {
        console.log('Datos masivos procesados:', data);
        if (data.success) {
            data.results.forEach(result => {
                const instanceId = result.instance.id;
                if (result.success) {
                    displayResults(instanceId, result.result);
                    updateStatus(instanceId, 'success', 'Completado');
                } else {
                    displayError(instanceId, result.result.error || 'Error desconocido');
                    updateStatus(instanceId, 'error', 'Error');
                }
            });
        }
    })
    .catch(error => {
        console.error('Error en peticiones masivas:', error);
    });
}

function updateStatus(instanceId, status, text) {
    const statusEl = document.getElementById(`status-${instanceId}`);
    const dot = statusEl.querySelector('.status-dot');
    const textEl = statusEl.querySelector('.status-text');
    
    dot.className = `status-dot ${status}`;
    textEl.textContent = text;
}

function displayResults(instanceId, result) {
    const resultsEl = document.getElementById(`results-${instanceId}`);
    resultsEl.style.display = 'block';
    
    let html = '<div class="test-results">';
    
    // Instance info
    if (result.instance_slug) {
        html += `<div class="test-result info">
            <strong>Instancia:</strong> ${result.instance_slug}<br>
            <strong>URL:</strong> ${result.baseUrl || 'N/A'}<br>
            <strong>Tipo de prueba:</strong> ${result.test_type || 'single'}
        </div>`;
    }
    
    // Connection test
    if (result.connection) {
        const conn = result.connection;
        const connStatus = (conn.code === 200 || conn.code === 400) ? 'success' : 'error';
        html += `<div class="test-result ${connStatus}">
            <strong>Conexión:</strong> HTTP ${conn.code} (${conn.time}ms)`;
        if (conn.url) {
            html += `<br><strong>URL:</strong> ${conn.url}`;
        }
        if (conn.error) {
            html += `<br><strong>Error:</strong> ${conn.error}`;
        }
        if (conn.response) {
            html += `<br><strong>Respuesta:</strong> ${JSON.stringify(conn.response).substring(0, 150)}...`;
        }
        html += '</div>';
    }
    
    // Single send test
    if (result.send) {
        const send = result.send;
        const sendStatus = (send.code === 200 || send.code === 201) ? 'success' : 'error';
        html += `<div class="test-result ${sendStatus}">
            <strong>Envío Simple:</strong> HTTP ${send.code} (${send.time}ms)`;
        if (send.url) {
            html += `<br><strong>URL:</strong> ${send.url}`;
        }
        if (send.response) {
            html += `<br><strong>Respuesta:</strong> ${JSON.stringify(send.response).substring(0, 150)}...`;
        }
        html += '</div>';
    }
    
    // Burst test
    if (result.burst && Array.isArray(result.burst)) {
        html += `<div class="test-result info">
            <strong>📤 Ráfaga de Mensajes:</strong><br>`;
        result.burst.forEach((msg, index) => {
            const status = (msg.code === 200 || msg.code === 201) ? 'success' : 'error';
            html += `<div style="margin-left: 1rem; margin-bottom: 0.25rem;">
                <strong>Mensaje ${index + 1}:</strong> HTTP ${msg.code} (${msg.time}ms) ${status === 'success' ? '✅' : '❌'}
            </div>`;
        });
        html += '</div>';
    }
    
    // Media test
    if (result.media) {
        const media = result.media;
        const mediaStatus = (media.code === 200 || media.code === 201) ? 'success' : 'error';
        html += `<div class="test-result ${mediaStatus}">
            <strong>📎 Mensaje con Adjunto:</strong> HTTP ${media.code} (${media.time}ms)`;
        if (media.url) {
            html += `<br><strong>URL:</strong> ${media.url}`;
        }
        if (media.response) {
            html += `<br><strong>Respuesta:</strong> ${JSON.stringify(media.response).substring(0, 150)}...`;
        }
        html += '</div>';
    }
    
    html += '</div>';
    resultsEl.innerHTML = html;
}

function displayError(instanceId, error) {
    const resultsEl = document.getElementById(`results-${instanceId}`);
    resultsEl.style.display = 'block';
    resultsEl.innerHTML = `
        <div class="test-results">
            <div class="test-result error">
                <strong>Error:</strong> ${error}
            </div>
        </div>
    `;
}

function closeModal() {
    document.getElementById('test-modal').style.display = 'none';
    currentInstanceId = null;
}

function refreshInstances() {
    window.location.reload();
}

function toggleAllInstances(checked) {
    document.querySelectorAll('.instance-checkbox').forEach(checkbox => {
        checkbox.checked = checked;
    });
}

function getSelectedInstanceIds() {
    return Array.from(document.querySelectorAll('.instance-checkbox:checked'))
        .map(checkbox => checkbox.value);
}
</script>
