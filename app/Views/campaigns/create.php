<?php
use App\Core\Auth;

$title = 'Campaigns - Create - ' . APP_NAME;
$errors = $errors ?? [];
$data = $data ?? [];
?>

<div class="campaign-create" style="padding: 2rem; max-width: 800px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="margin: 0; color: var(--text-primary);">New Campaign</h1>
        <a class="btn btn-secondary" href="<?= $viewHelper->url('campaigns/index') ?>&instance=<?= $viewHelper->escape($_GET['instance'] ?? '') ?>">Back</a>
    </div>

    <form method="post" action="<?= $viewHelper->url('campaigns/store') ?>&instance=<?= $viewHelper->escape($_GET['instance'] ?? '') ?>" style="background: var(--surface); border-radius: 0.5rem; padding: 1.5rem;">
        <div class="form-group">
            <label class="form-label" for="name">Campaign Name</label>
            <input class="form-input" id="name" name="name" type="text" required value="<?= $viewHelper->escape($data['name'] ?? '') ?>">
            <?php if (isset($errors['name'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['name']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="schedule_type">Schedule Type</label>
            <select class="form-input" id="schedule_type" name="schedule_type" required>
                <option value="once" <?= ($data['schedule_type'] ?? '') === 'once' ? 'selected' : '' ?>>Run Once</option>
                <option value="daily" <?= ($data['schedule_type'] ?? '') === 'daily' ? 'selected' : '' ?>>Daily</option>
                <option value="weekly" <?= ($data['schedule_type'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                <option value="monthly" <?= ($data['schedule_type'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
            </select>
        </div>

        <div class="form-group" id="date-fields" style="display: block;">
            <label class="form-label" for="start_date">Start Date</label>
            <input class="form-input" id="start_date" name="start_date" type="date" required value="<?= $viewHelper->escape($data['start_date'] ?? '') ?>" 
                   min="<?= date('Y-m-d') ?>" 
                   max="<?= date('Y-m-d', strtotime('+1 year')) ?>">
            <?php if (isset($errors['start_date'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['start_date']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group" id="time-fields" style="display: block;">
            <label class="form-label" for="start_time">Start Time</label>
            <input class="form-input" id="start_time" name="start_time" type="time" required value="<?= $viewHelper->escape($data['start_time'] ?? '') ?>">
            <?php if (isset($errors['start_time'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['start_time']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group" id="date-fields" style="display: block;">
            <label class="form-label" for="end_date">End Date (optional)</label>
            <input class="form-input" id="end_date" name="end_date" type="date" value="<?= $viewHelper->escape($data['end_date'] ?? '') ?>" 
                   min="<?= date('Y-m-d') ?>" 
                   max="<?= date('Y-m-d', strtotime('+1 year')) ?>">
            <?php if (isset($errors['end_date'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['end_date']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group" id="time-fields" style="display: block;">
            <label class="form-label" for="end_time">End Time (optional)</label>
            <input class="form-input" id="end_time" name="end_time" type="time" value="<?= $viewHelper->escape($data['end_time'] ?? '') ?>">
            <?php if (isset($errors['end_time'])): ?>
                <div style="color: var(--error); font-size: 0.875rem; margin-top: 0.25rem;">
                    <?= $viewHelper->escape($errors['end_time']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="timezone">Timezone</label>
            <select class="form-input" id="timezone" name="timezone">
                <option value="America/Caracas" <?= ($data['timezone'] ?? '') === 'America/Caracas' ? 'selected' : '' ?>>America/Caracas</option>
                <option value="America/Bogota" <?= ($data['timezone'] ?? '') === 'America/Bogota' ? 'selected' : '' ?>>America/Bogota</option>
                <option value="America/Mexico_City" <?= ($data['timezone'] ?? '') === 'America/Mexico_City' ? 'selected' : '' ?>>America/Mexico_City</option>
                <option value="America/Argentina/Buenos_Aires" <?= ($data['timezone'] ?? '') === 'America/Argentina/Buenos_Aires' ? 'selected' : '' ?>>America/Argentina/Buenos_Aires</option>
                <option value="America/Santiago" <?= ($data['timezone'] ?? '') === 'America/Santiago' ? 'selected' : '' ?>>America/Santiago</option>
                <option value="America/Lima" <?= ($data['timezone'] ?? '') === 'America/Lima' ? 'selected' : '' ?>>America/Lima</option>
                <option value="UTC" <?= ($data['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
            </select>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" <?= (!isset($data['is_active']) || (int)($data['is_active'] ?? 0) === 1) ? 'checked' : '' ?>>
                <span style="color: var(--text-primary);">Active</span>
            </label>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Create Campaign</button>
            <a class="btn btn-secondary" href="<?= $viewHelper->url('campaigns/index') ?>&instance=<?= $viewHelper->escape($_GET['instance'] ?? '') ?>">Cancel</a>
        </div>
    </form>
</div>

<style>
.campaign-create {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

.form-group {
    margin-bottom: 1rem;
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
    box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.2);
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
    font-size: 1rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-hover, #0056b3);
}

.btn-secondary {
    background: var(--surface);
    color: var(--text-primary);
    border: 1px solid var(--border);
}

.btn-secondary:hover {
    background: var(--surface-hover, #f8f9fa);
}

.error {
    color: var(--error);
    font-size: 0.875rem;
    margin-top: 0.25rem;
}
<script>
// Prevenir refresh automático
document.addEventListener('DOMContentLoaded', function() {
    // Eliminar cualquier temporizador existente
    if (window.refreshInterval) {
        clearInterval(window.refreshInterval);
        window.refreshInterval = null;
    }
    
    // Prevenir submit del formulario que cause refresh
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar y enviar con AJAX
            const formData = new FormData(form);
            
            fetch('index.php?r=campaigns/store&instance=<?= $viewHelper->escape($_GET['instance'] ?? '') ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    const successDiv = document.createElement('div');
                    successDiv.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #28a745;
                        color: white;
                        padding: 1rem 1.5rem;
                        border-radius: 0.5rem;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                        z-index: 1000;
                        font-size: 0.875rem;
                    `;
                    successDiv.innerHTML = `
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">✅ Campaña creada exitosamente!</div>
                        <div style="font-size: 0.875rem;">${data.message}</div>
                    `;
                    document.body.appendChild(successDiv);
                    
                    // Redirigir después de 2 segundos
                    setTimeout(() => {
                        window.location.href = 'index.php?r=campaigns/index&instance=<?= $viewHelper->escape($_GET['instance'] ?? '') ?>';
                    }, 2000);
                    
                } else {
                    // Mostrar errores
                    const errorDiv = document.createElement('div');
                    errorDiv.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #dc3545;
                        color: white;
                        padding: 1rem 1.5rem;
                        border-radius: 0.5rem;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                        z-index: 1000;
                        font-size: 0.875rem;
                    `;
                    
                    let errorHtml = '';
                    Object.keys(data.errors).forEach(field => {
                        errorHtml += `<div style="margin-bottom: 0.5rem;"><strong>${field}:</strong> ${data.errors[field]}</div>`;
                    });
                    
                    errorDiv.innerHTML = `
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">❌ Error al crear campaña</div>
                        <div style="font-size: 0.875rem;">${errorHtml}</div>
                    `;
                    document.body.appendChild(errorDiv);
                    
                    // Remover mensaje de éxito después de 5 segundos
                    setTimeout(() => {
                        if (successDiv.parentNode) {
                            successDiv.parentNode.removeChild(successDiv);
                        }
                    }, 5000);
                }
            });
        });
    }
});

// Función para mostrar calendario cuando se selecciona tipo de programación
function showScheduleFields(scheduleType) {
    // Ocultar todos los campos de programación
    document.querySelectorAll('.schedule-fields').forEach(field => {
        field.style.display = 'none';
    });
    
    // Mostrar campos según el tipo seleccionado
    const dateFields = document.getElementById('date-fields');
    const timeFields = document.getElementById('time-fields');
    
    if (scheduleType === 'once') {
        dateFields.style.display = 'block';
        timeFields.style.display = 'block';
    } else if (scheduleType === 'daily') {
        document.getElementById('daily-fields').style.display = 'block';
        dateFields.style.display = 'block';
        timeFields.style.display = 'block';
    } else if (scheduleType === 'weekly') {
        document.getElementById('weekly-fields').style.display = 'block';
        dateFields.style.display = 'block';
        timeFields.style.display = 'block';
    } else if (scheduleType === 'monthly') {
        document.getElementById('monthly-fields').style.display = 'block';
        dateFields.style.display = 'block';
        timeFields.style.display = 'block';
    }
}

// Escuchar cambios en el tipo de programación
document.getElementById('schedule_type').addEventListener('change', function() {
    showScheduleFields(this.value);
});

// Inicializar campos según el tipo seleccionado
document.addEventListener('DOMContentLoaded', function() {
    const scheduleType = document.getElementById('schedule_type').value;
    if (scheduleType) {
        showScheduleFields(scheduleType);
    }
});
</script>
