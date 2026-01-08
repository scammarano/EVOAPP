<?php
use App\Core\Auth;

$title = 'Contact Lists - ' . APP_NAME;
?>

<div class="contact-lists">
    <div class="page-header">
        <h1>📋 Listas de Contactos</h1>
        <div class="page-actions">
            <a href="<?= $viewHelper->url('contacts/sync/index') ?>&instance=<?= $viewHelper->escape($instance['slug']) ?>" class="btn btn-secondary">
                <span class="icon-sync"></span>
                Sincronizar Contactos
            </a>
            <a href="<?= $viewHelper->url('contacts/createList') ?>&instance=<?= $viewHelper->escape($instance['slug']) ?>" class="btn btn-primary">
                <span class="icon-plus"></span>
                Nueva Lista
            </a>
        </div>
    </div>

    <?php if (!empty($lists)): ?>
        <div class="lists-grid">
            <?php foreach ($lists as $list): ?>
                <div class="list-card">
                    <div class="list-header">
                        <h3><?= $viewHelper->escape($list['name']) ?></h3>
                        <div class="list-actions">
                            <a href="<?= $viewHelper->url('contacts/editList') ?>&instance=<?= $viewHelper->escape($instance['slug']) ?>&id=<?= $list['id'] ?>" class="btn-icon" title="Editar">
                                <span class="icon-edit"></span>
                            </a>
                            <button class="btn-icon btn-danger" onclick="deleteList(<?= $list['id'] ?>)" title="Eliminar">
                                <span class="icon-trash"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="list-description">
                        <?= $viewHelper->escape($list['description'] ?: 'Sin descripción') ?>
                    </div>
                    
                    <div class="list-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?= $list['contact_count'] ?? 0 ?></span>
                            <span class="stat-label">Contactos</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?= $list['active_contacts'] ?? 0 ?></span>
                            <span class="stat-label">Activos</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Estado:</span>
                            <span class="status-badge <?= $list['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $list['is_active'] ? 'Activa' : 'Inactiva' ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="list-footer">
                        <div class="created-info">
                            Creada: <?= $viewHelper->formatDate($list['created_at']) ?>
                        </div>
                        <div class="list-actions-footer">
                            <a href="<?= $viewHelper->url('contacts/viewList') ?>&instance=<?= $viewHelper->escape($instance['slug']) ?>&id=<?= $list['id'] ?>" class="btn btn-small">
                                <span class="icon-eye"></span>
                                Ver Contactos
                            </a>
                            <a href="<?= $viewHelper->url('contacts/addContacts') ?>&instance=<?= $viewHelper->escape($instance['slug']) ?>&list_id=<?= $list['id'] ?>" class="btn btn-small btn-primary">
                                <span class="icon-plus"></span>
                                Agregar Contactos
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total > $limit): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="btn btn-secondary">
                        ← Anterior
                    </a>
                <?php endif; ?>
                
                <span class="page-info">
                    Página <?= $page ?> de <?= ceil($total / $limit) ?>
                </span>
                
                <?php if ($page < ceil($total / $limit)): ?>
                    <a href="?page=<?= $page + 1 ?>" class="btn btn-secondary">
                        Siguiente →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <h3>No hay listas de contactos</h3>
            <p>Crea tu primera lista para organizar tus contactos.</p>
            <a href="<?= $viewHelper->url('contacts/createList') ?>&instance=<?= $viewHelper->escape($instance['slug']) ?>" class="btn btn-primary">
                <span class="icon-plus"></span>
                Crear Primera Lista
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
.contact-lists {
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

.lists-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.list-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1.5rem;
    transition: all 0.2s ease;
}

.list-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.list-header h3 {
    margin: 0;
    color: var(--text-primary);
    font-size: 1.25rem;
}

.list-actions {
    display: flex;
    gap: 0.5rem;
}

.list-description {
    color: var(--text-secondary);
    margin-bottom: 1rem;
    min-height: 3rem;
}

.list-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 1rem 0;
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.active {
    background: #22c55e;
    color: white;
}

.status-badge.inactive {
    background: #dc3545;
    color: white;
}

.list-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
}

.created-info {
    color: var(--text-secondary);
}

.list-actions-footer {
    display: flex;
    gap: 0.5rem;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-secondary);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    margin: 0 0 1rem 0;
    color: var(--text-primary);
}

.empty-state p {
    margin: 0 0 2rem 0;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border);
}

.page-info {
    color: var(--text-secondary);
    font-weight: 500;
}

@media (max-width: 768px) {
    .lists-grid {
        grid-template-columns: 1fr;
    }
    
    .list-stats {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .list-footer {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .list-actions-footer {
        justify-content: center;
    }
}
</style>

<script>
function deleteList(listId) {
    if (confirm('¿Estás seguro de que quieres eliminar esta lista? Esta acción no se puede deshacer.')) {
        fetch('index.php?r=contacts/deleteList', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: listId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the list card from DOM
                const listCard = document.querySelector(`[data-list-id="${listId}"]`);
                if (listCard) {
                    listCard.remove();
                } else {
                    // Fallback: reload page
                    window.location.reload();
                }
            } else {
                alert('Error al eliminar lista: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de red al eliminar lista');
        });
    }
}
</script>
