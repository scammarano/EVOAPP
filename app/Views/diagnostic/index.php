<?php
use App\Core\Auth;
$title = 'Diagnostic - ' . APP_NAME;

// VERSIÓN DE LA VISTA
$view_version = '1.5';
$view_modified = '2025-01-10 16:30:00';
$view_features = ['Send Mode Selector', 'Enhanced UI', 'State Persistence'];
?>

<div class="diagnostic-container">
    <!-- PANEL DE VERSIONES - ESQUINA SUPERIOR DERECHA -->
    <div class="version-panel" style="position: fixed; top: 10px; right: 10px; background: #ff6b6b; border: 3px solid #ff0000; border-radius: 8px; padding: 10px; font-size: 11px; z-index: 9999; box-shadow: 0 2px 10px rgba(255,0,0,0.5); min-width: 300px;">
        <h4 style="margin: 0 0 6px 0; color: #ffffff; font-size: 12px;">📋 VERSIONES DEL SISTEMA:</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
            <div style="background: white; padding: 5px; border-radius: 4px;">
                <strong>🎛️ Controlador:</strong><br>
                v<?= $controller_version ?? 'N/A' ?><br>
                <small><?= $controller_modified ?? 'N/A' ?></small><br>
                <small><?= implode(', ', $controller_features ?? []) ?></small>
            </div>
            <div style="background: white; padding: 5px; border-radius: 4px;">
                <strong>📄 Vista:</strong><br>
                v<?= $view_version ?><br>
                <small><?= $view_modified ?></small><br>
                <small><?= implode(', ', $view_features) ?></small>
            </div>
            <div style="background: white; padding: 5px; border-radius: 4px;">
                <strong>🔧 MessageSender:</strong><br>
                v<?= \App\Core\MessageSender::MODEL_VERSION ?? 'N/A' ?><br>
                <small><?= \App\Core\MessageSender::LAST_MODIFIED ?? 'N/A' ?></small><br>
                <small><?= implode(', ', \App\Core\MessageSender::FEATURES ?? []) ?></small>
            </div>
        </div>
        <div style="margin-top: 8px; background: yellow; padding: 5px; border-radius: 4px; text-align: center; font-weight: bold;">
            ⚠️ SI VES ESTE PANEL - ARCHIVO ACTUALIZADO ⚠️
        </div>
    </div>
    
    <div class="diagnostic-header">
        <div class="diagnostic-logo" aria-hidden="true">EVO</div>
        <div>
            <h1>🔍 Diagnóstico de Instancias</h1>
            <p>Prueba la conexión y envío de mensajes de todas las instancias Evolution API</p>
        </div>
    </div>
    
    <div class="diagnostic-actions">
        <div class="test-config">
            <div class="form-group">
                <label>Número para todas las pruebas:</label>
                <input type="text" id="global-test-number" value="+584143238051" placeholder="+584143238051">
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
            <div class="form-group">
                <label>Modo de envío con adjunto:</label>
                <select id="send-mode">
                    <option value="media_only">📎 Solo imagen + caption</option>
                    <option value="media_with_text">📎 Imagen + caption + texto</option>
                </select>
            </div>
            <div class="form-group media-only">
                <label>Archivo adjunto:</label>
                <input type="file" id="global-test-media" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
            </div>
            <div class="form-group media-only">
                <label>Caption del adjunto:</label>
                <input type="text" id="global-test-caption" placeholder="Texto del adjunto">
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
                        <label>Último webhook recibido:</label>
                        <span><?= $instance['last_webhook_at'] ? date('d/m H:i', strtotime($instance['last_webhook_at'])) : 'Sin eventos' ?></span>
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
                
                <div class="test-results" id="results-<?= $instance['id'] ?>" data-empty="true">
                    <div class="results-screen is-idle">
                        <div class="screen-header">
                            <span class="screen-dot"></span>
                            Resultado de pruebas
                        </div>
                        <div class="screen-body">
                            Ejecuta una prueba para ver el resultado aquí.
                        </div>
                    </div>
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
                    <input type="text" id="test-number" value="+584143238051" placeholder="+584143238051">
                </div>
                <div class="form-group">
                    <label>Mensaje de prueba:</label>
                    <textarea id="test-text" rows="3">Mensaje de prueba - EVOAPP Diagnostic</textarea>
                </div>
                <div class="form-group">
                    <label>Modo de envío:</label>
                    <select id="test-send-mode">
                        <option value="media_only">📎 Solo imagen + caption</option>
                        <option value="media_with_text">📎 Imagen + caption + texto</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Archivo adjunto:</label>
                    <input type="file" id="test-media" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                </div>
                <div class="form-group">
                    <label>Caption del adjunto:</label>
                    <input type="text" id="test-caption" placeholder="Texto del adjunto">
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
    padding: 1.5rem;
    color: #212529;
}

.diagnostic-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.diagnostic-header h1 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.diagnostic-logo {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: linear-gradient(135deg, #1b7edb, #4aa3ff);
    color: #fff;
    font-weight: 700;
    letter-spacing: 1px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 18px rgba(0,0,0,0.12);
    font-size: 0.9rem;
}

.diagnostic-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.test-config {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1rem;
    padding: 0.85rem;
    background: #f8f9fa;
    border-radius: 10px;
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
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1rem;
}

.instance-card {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
    color: #212529;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.instance-header {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
}

.instance-header h3 {
    margin: 0;
    color: var(--text-primary);
    font-size: 1rem;
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
    display: grid;
    gap: 0.35rem;
}

.info-item {
    display: grid;
    gap: 0.15rem;
    font-size: 0.85rem;
}

.info-item label {
    font-weight: 500;
    color: var(--text-secondary);
}

.instance-actions {
    display: flex;
    gap: 0.5rem;
}

.test-results {
    margin-top: 0.25rem;
}

.results-screen {
    border: 1px solid #d8e2f0;
    border-radius: 10px;
    background: #f9fbff;
    overflow: hidden;
    font-size: 0.82rem;
    color: #212529;
}

.results-screen.is-idle .screen-body {
    color: #6c757d;
}

.screen-header {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.5rem 0.75rem;
    background: #e9f0fb;
    border-bottom: 1px solid #d8e2f0;
    font-weight: 600;
}

.screen-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #6c757d;
}

.results-screen.is-success .screen-dot {
    background: #28a745;
}

.results-screen.is-error .screen-dot {
    background: #dc3545;
}

.screen-body {
    padding: 0.75rem;
}

.test-results .test-result {
    margin-bottom: 0.5rem;
    padding: 0.5rem 0.6rem;
    border-radius: 6px;
    font-size: 0.85rem;
}

.test-result.info {
    background: #eaf2ff;
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

.media-only {
    display: none;
}

.media-only.is-visible {
    display: block;
}

.send-mode-group {
    display: none;
}

.send-mode-group.is-visible {
    display: block;
}

.text-with-media {
    display: none;
}

.text-with-media.is-visible {
    display: block;
}
</style>

<script>
window.evoappDisablePolling = true;
</script>

<script>
let currentInstanceId = null;
const diagnosticStorageKey = 'evoapp-diagnostic-state';

function getDiagnosticState() {
    return {
        number: document.getElementById('global-test-number')?.value || '',
        text: document.getElementById('global-test-text')?.value || '',
        testType: document.getElementById('test-type')?.value || 'single',
        caption: document.getElementById('global-test-caption')?.value || '',
        sendMode: document.getElementById('send-mode')?.value || 'media_only',
        selectedInstanceIds: getSelectedInstanceIds()
    };
}

function saveDiagnosticState() {
    try {
        const state = getDiagnosticState();
        sessionStorage.setItem(diagnosticStorageKey, JSON.stringify(state));
    } catch (error) {
        console.warn('No se pudo guardar el estado de diagnóstico:', error);
    }
}

function restoreDiagnosticState() {
    try {
        const raw = sessionStorage.getItem(diagnosticStorageKey);
        if (!raw) return;
        const state = JSON.parse(raw);
        if (state.number) {
            document.getElementById('global-test-number').value = state.number;
        }
        if (state.text) {
            document.getElementById('global-test-text').value = state.text;
        }
        if (state.testType) {
            document.getElementById('test-type').value = state.testType;
        }
        if (state.caption) {
            document.getElementById('global-test-caption').value = state.caption;
        }
        if (state.sendMode) {
            document.getElementById('send-mode').value = state.sendMode;
        }
        if (Array.isArray(state.selectedInstanceIds)) {
            document.querySelectorAll('.instance-checkbox').forEach(checkbox => {
                checkbox.checked = state.selectedInstanceIds.includes(checkbox.value);
            });
        }
    } catch (error) {
        console.warn('No se pudo restaurar el estado de diagnóstico:', error);
    }
}

function initDiagnosticStateHandlers() {
    restoreDiagnosticState();
    ['global-test-number', 'global-test-text', 'test-type', 'global-test-caption'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', saveDiagnosticState);
            el.addEventListener('change', saveDiagnosticState);
        }
    });
    document.querySelectorAll('.instance-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', saveDiagnosticState);
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Enter' && event.target.closest('.diagnostic-actions')) {
            event.preventDefault();
        }
    });
}

function updateMediaFieldsVisibility() {
    const isMedia = document.getElementById('test-type')?.value === 'media';
    document.querySelectorAll('.media-only').forEach(element => {
        element.classList.toggle('is-visible', isMedia);
    });
}

function updateSendModeVisibility() {
    const isTextWithMedia = document.getElementById('send-mode')?.value === 'text_with_media';
    document.querySelectorAll('.text-with-media').forEach(element => {
        element.classList.toggle('is-visible', isTextWithMedia);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initDiagnosticStateHandlers();
    updateMediaFieldsVisibility();
    updateSendModeVisibility();
});

function testInstance(instanceId) {
    console.log('Iniciando prueba para instancia:', instanceId);
    updateStatus(instanceId, 'testing', 'Probando...');
    
    const number = document.getElementById('global-test-number').value;
    const text = document.getElementById('global-test-text').value;
    const testType = document.getElementById('test-type').value;
    const caption = document.getElementById('global-test-caption').value;
    const sendMode = document.getElementById('send-mode').value;
    const mediaFile = document.getElementById('global-test-media').files[0] || null;
    
    console.log('Enviando petición:', { instanceId, number, text, testType, sendMode });

    let requestBody;
    let requestHeaders = {};

    if (testType === 'media') {
        requestBody = new FormData();
        requestBody.append('instance_id', instanceId);
        requestBody.append('test_number', number);
        requestBody.append('test_text', text);
        requestBody.append('test_type', testType);
        requestBody.append('test_caption', caption);
        requestBody.append('send_mode', sendMode);
        if (mediaFile) {
            requestBody.append('test_media', mediaFile);
        }
    } else {
        requestHeaders['Content-Type'] = 'application/x-www-form-urlencoded';
        requestBody = `instance_id=${instanceId}&test_number=${encodeURIComponent(number)}&test_text=${encodeURIComponent(text)}&test_type=${testType}&send_mode=${sendMode}`;
    }

    fetch('index.php?r=diagnostic/testInstance', {
        method: 'POST',
        headers: requestHeaders,
        body: requestBody
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
    const caption = document.getElementById('test-caption').value;
    const mediaFile = document.getElementById('test-media').files[0] || null;
    const testType = mediaFile ? 'media' : 'single';
    const sendMode = document.getElementById('test-send-mode').value;
    
    updateStatus(currentInstanceId, 'testing', 'Enviando...');
    closeModal();
    
    let requestBody;
    let requestHeaders = {};

    if (testType === 'media') {
        requestBody = new FormData();
        requestBody.append('instance_id', currentInstanceId);
        requestBody.append('test_number', number);
        requestBody.append('test_text', text);
        requestBody.append('test_type', testType);
        requestBody.append('test_caption', caption);
        requestBody.append('send_mode', sendMode);
        if (mediaFile) {
            requestBody.append('test_media', mediaFile);
        }
    } else {
        requestHeaders['Content-Type'] = 'application/x-www-form-urlencoded';
        requestBody = `instance_id=${currentInstanceId}&test_number=${encodeURIComponent(number)}&test_text=${encodeURIComponent(text)}&send_mode=${sendMode}`;
    }

    fetch('index.php?r=diagnostic/testInstance', {
        method: 'POST',
        headers: requestHeaders,
        body: requestBody
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
    const caption = document.getElementById('global-test-caption').value;
    const mediaFile = document.getElementById('global-test-media').files[0] || null;
    
    console.log('Enviando peticiones masivas:', { number, text, testType, instanceIdsToTest });

    let requestBody;
    let requestHeaders = {};

    if (testType === 'media') {
        requestBody = new FormData();
        requestBody.append('test_number', number);
        requestBody.append('test_text', text);
        requestBody.append('test_type', testType);
        requestBody.append('test_caption', caption);
        requestBody.append('instance_ids', instanceIdsToTest.join(','));
        if (mediaFile) {
            requestBody.append('test_media', mediaFile);
        }
    } else {
        requestHeaders['Content-Type'] = 'application/x-www-form-urlencoded';
        requestBody = `test_number=${encodeURIComponent(number)}&test_text=${encodeURIComponent(text)}&test_type=${encodeURIComponent(testType)}&instance_ids=${encodeURIComponent(instanceIdsToTest.join(','))}`;
    }

    fetch('index.php?r=diagnostic/testAll', {
        method: 'POST',
        headers: requestHeaders,
        body: requestBody
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
    resultsEl.dataset.empty = 'false';
    
    let html = '';
    
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
    
    renderResultsPanel(resultsEl, html, 'is-success');
}

function displayError(instanceId, error) {
    const resultsEl = document.getElementById(`results-${instanceId}`);
    resultsEl.dataset.empty = 'false';
    const html = `<div class="test-result error"><strong>Error:</strong> ${error}</div>`;
    renderResultsPanel(resultsEl, html, 'is-error');
}

function renderResultsPanel(resultsEl, contentHtml, statusClass) {
    resultsEl.innerHTML = `
        <div class="results-screen ${statusClass}">
            <div class="screen-header">
                <span class="screen-dot"></span>
                Resultado de pruebas
            </div>
            <div class="screen-body">${contentHtml}</div>
        </div>
    `;
}

function closeModal() {
    document.getElementById('test-modal').style.display = 'none';
    currentInstanceId = null;
}

function refreshInstances() {
    saveDiagnosticState();
    window.location.reload();
}

function toggleAllInstances(checked) {
    document.querySelectorAll('.instance-checkbox').forEach(checkbox => {
        checkbox.checked = checked;
    });
    saveDiagnosticState();
}

function getSelectedInstanceIds() {
    return Array.from(document.querySelectorAll('.instance-checkbox:checked'))
        .map(checkbox => checkbox.value);
}
</script>
