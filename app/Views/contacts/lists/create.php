<?php
use App\Core\Auth;

$title = 'Create Contact List - ' . APP_NAME;
$errors = $errors ?? [];
$data = $data ?? [];
?>

<div class="create-list">
    <div class="page-header">
        <h1>📋 Nueva Lista de Contactos</h1>
        <div class="page-actions">
            <a href="<?= $viewHelper->url('contacts/lists') ?>&instance=<?= $viewHelper->escape($instance['slug']) ?>" class="btn btn-secondary">
                <span class="icon-back"></span>
                Volver a Listas
            </a>
        </div>
    </div>

    <div class="form-container">
        <form method="post" action="<?= $viewHelper->url('contacts/storeList') ?>&instance=<?= $viewHelper->escape($instance['slug']) ?>" class="list-form">
            <div class="form-group">
                <label class="form-label" for="name">Nombre de la Lista *</label>
                <input class="form-input" id="name" name="name" type="text" required 
                       value="<?= $viewHelper->escape($data['name'] ?? '') ?>"
                       placeholder="Ej: Clientes VIP, Leads Q4, etc.">
                <?php if (isset($errors['name'])): ?>
                    <div class="error-message"><?= $viewHelper->escape($errors['name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Descripción</label>
                <textarea class="form-textarea" id="description" name="description" rows="4" 
                          placeholder="Describe el propósito o tipo de contactos en esta lista..."><?= $viewHelper->escape($data['description'] ?? '') ?></textarea>
                <?php if (isset($errors['description'])): ?>
                    <div class="error-message"><?= $viewHelper->escape($errors['description']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" 
                           <?= (!isset($data['is_active']) || (int)($data['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
                    <span class="checkmark"></span>
                    Lista Activa
                </label>
                <small class="form-help">Las listas inactivas no se pueden usar para enviar campañas</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span class="icon-save"></span>
                    Crear Lista
                </button>
                <a href="<?= $viewHelper->url('contacts/lists') ?>&instance=<?= $viewHelper->escape($instance['slug']) ?>" class="btn btn-secondary">
                    <span class="icon-cancel"></span>
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    <div class="info-section">
        <div class="info-card">
            <h3>💡 ¿Qué son las Listas de Contactos?</h3>
            <div class="info-content">
                <p>Las listas de contactos te permiten organizar tus contactos en grupos específicos para:</p>
                <ul>
                    <li>📧 <strong>Envío de campañas dirigidas</strong></li>
                    <li>🎯 <strong>Segmentación de audiencias</strong></li>
                    <li>📊 <strong>Análisis por grupos demográficos</strong></li>
                    <li>🔄 <strong>Gestión eficiente de contactos</strong></li>
                </ul>
            </div>
        </div>

        <div class="info-card">
            <h3>🚀 Ejemplos de Uso</h3>
            <div class="examples-grid">
                <div class="example-item">
                    <strong>Clientes VIP</strong>
                    <p>Contactos premium para ofertas exclusivas</p>
                </div>
                <div class="example-item">
                    <strong>Leads Q4</strong>
                    <p>Prospectos del cuarto trimestre</p>
                </div>
                <div class="example-item">
                    <strong>Newsletter Mensual</strong>
                    <p>Suscriptores activos del mes</p>
                </div>
                <div class="example-item">
                    <strong>Soporte Técnico</strong>
                    <p>Equipo de soporte y técnicos</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.create-list {
    padding: 2rem;
    max-width: 800px;
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

.form-container {
    background: var(--surface);
    border-radius: 0.5rem;
    padding: 2rem;
    border: 1px solid var(--border);
    margin-bottom: 2rem;
}

.list-form {
    max-width: 500px;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.form-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border);
    border-radius: 0.25rem;
    background: var(--background);
    color: var(--text-primary);
    font-size: 1rem;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.2);
}

.form-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border);
    border-radius: 0.25rem;
    background: var(--background);
    color: var(--text-primary);
    font-size: 1rem;
    resize: vertical;
    min-height: 100px;
}

.form-textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.2);
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    font-weight: 500;
    color: var(--text-primary);
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid var(--border);
    border-radius: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.checkbox-label input[type="checkbox"]:checked + .checkmark {
    background: var(--primary);
    border-color: var(--primary);
}

.checkbox-label input[type="checkbox"]:checked + .checkmark::after {
    content: '✓';
    color: white;
    font-size: 0.875rem;
    font-weight: bold;
}

.form-help {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-style: italic;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.error-message {
    color: var(--error);
    font-size: 0.875rem;
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: #fee;
    border-radius: 0.25rem;
    border: 1px solid var(--error);
}

.info-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.info-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.info-card h3 {
    margin: 0 0 1rem 0;
    color: var(--text-primary);
    font-size: 1.125rem;
}

.info-content ul {
    margin: 0;
    padding-left: 1.5rem;
}

.info-content li {
    margin-bottom: 0.5rem;
    color: var(--text-secondary);
}

.examples-grid {
    display: grid;
    gap: 1rem;
}

.example-item {
    padding: 1rem;
    background: var(--background);
    border-radius: 0.25rem;
    border: 1px solid var(--border);
}

.example-item strong {
    display: block;
    color: var(--primary);
    margin-bottom: 0.5rem;
}

.example-item p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

@media (max-width: 768px) {
    .info-section {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .examples-grid {
        grid-template-columns: 1fr;
    }
}
</style>
