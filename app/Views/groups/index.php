<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <h4>Error</h4>
        <p><?php echo htmlspecialchars($error); ?></p>
    </div>
<?php else: ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-users"></i> 
                            Gestión de Grupos - <?php echo htmlspecialchars($instance->slug); ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <!-- Botones de extracción masiva -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-user-friends"></i> 
                                            Extraer Contactos de Chats
                                        </h5>
                                        <p class="card-text">
                                            Extraer todos los contactos de los chats abiertos y guardarlos en la base de datos.
                                        </p>
                                        <button class="btn btn-primary btn-sm" onclick="extractChatContacts()">
                                            <i class="fas fa-download"></i> Extraer Contactos de Chats
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-users"></i> 
                                            Extraer Participantes de Grupos
                                        </h5>
                                        <p class="card-text">
                                            Obtener todos los participantes de los grupos y guardarlos como contactos.
                                        </p>
                                        <button class="btn btn-success btn-sm" onclick="showGroupList()">
                                            <i class="fas fa-list"></i> Ver Grupos Disponibles
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Lista de Grupos -->
                        <div class="row">
                            <div class="col-12">
                                <h5><i class="fas fa-users"></i> Grupos Disponibles</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nombre</th>
                                                <th>Remote JID</th>
                                                <th>Participantes</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($groups as $group): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($group['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($group['name'] ?? 'Sin nombre'); ?></td>
                                                    <td><?php echo htmlspecialchars($group['remote_jid']); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" onclick="viewParticipants('<?php echo $group['id']; ?>')">
                                                            <i class="fas fa-eye"></i> Ver
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success" onclick="extractGroupContacts('<?php echo $group['id']; ?>', '<?php echo htmlspecialchars($group['name'] ?? 'Sin nombre'); ?>')">
                                                            <i class="fas fa-download"></i> Extraer
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            
                                            <?php foreach ($apiGroups as $group): ?>
                                                <tr class="table-info">
                                                    <td><?php echo htmlspecialchars($group['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($group['name'] ?? 'Sin nombre'); ?></td>
                                                    <td><?php echo htmlspecialchars($group['remoteJid'] ?? ''); ?></td>
                                                    <td>
                                                        <span class="badge badge-info">API</span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success" onclick="extractGroupContacts('<?php echo $group['id']; ?>', '<?php echo htmlspecialchars($group['name'] ?? 'Sin nombre'); ?>')">
                                                            <i class="fas fa-download"></i> Extraer
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Estadísticas -->
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-users"></i> Grupos en BD
                                        </h5>
                                        <h3><?php echo count($groups); ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-cloud"></i> Grupos en API
                                        </h5>
                                        <h3><?php echo count($apiGroups); ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-database"></i> Total
                                        </h5>
                                        <h3><?php echo count($groups) + count($apiGroups); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver participantes -->
    <div class="modal fade" id="participantsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Participantes del Grupo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="participantsContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p>Cargando participantes...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-success" id="extractAllBtn">
                        <i class="fas fa-download"></i> Extraer Todos
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const instanceId = <?php echo $instance->id; ?>;
        
        function extractChatContacts() {
            if (confirm('¿Estás seguro de extraer todos los contactos de los chats?')) {
                const btn = event.target;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Extrayendo...';
                
                fetch('?r=group/extractChatContacts', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `instance_id=${instanceId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error de conexión: ' + error.message);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-download"></i> Extraer Contactos de Chats';
                });
            }
        }
        
        function extractGroupContacts(groupId, groupName) {
            if (confirm(`¿Estás seguro de extraer todos los participantes del grupo "${groupName}"?`)) {
                const btn = event.target;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Extrayendo...';
                
                fetch('?r=group/extractGroupContacts', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `instance_id=${instanceId}&group_id=${groupId}&group_name=${encodeURIComponent(groupName)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error de conexión: ' + error.message);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-download"></i> Extraer';
                });
            }
        }
        
        function viewParticipants(groupId) {
            const modal = new bootstrap.Modal(document.getElementById('participantsModal'));
            const content = document.getElementById('participantsContent');
            
            content.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p>Cargando participantes...</p>
                </div>
            `;
            
            modal.show();
            
            // Cargar participantes desde la API
            fetch(`/group/${groupId}/participants`)
                .then(response => response.json())
                .then(data => {
                    if (data.data && data.data.length > 0) {
                        let html = '<div class="table-responsive"><table class="table table-striped">';
                        html += '<thead><tr><th>Nombre</th><th>JID</th><th>Teléfono</th><th>Admin</th></tr></thead><tbody>';
                        
                        data.data.forEach(participant => {
                            const name = participant.pushName || participant.name || 'Sin nombre';
                            const jid = participant.id || '';
                            const phone = jid.replace(/@.*$/, '');
                            const isAdmin = participant.isAdmin ? 'Sí' : 'No';
                            
                            html += `<tr>
                                <td>${name}</td>
                                <td><small>${jid}</small></td>
                                <td>${phone}</td>
                                <td><span class="badge ${isAdmin ? 'bg-success' : 'bg-secondary'}">${isAdmin}</span></td>
                            </tr>`;
                        });
                        
                        html += '</tbody></table></div>';
                        
                        // Actualizar botón de extracción
                        document.getElementById('extractAllBtn').onclick = function() {
                            extractGroupContacts(groupId, 'Grupo seleccionado');
                        };
                        
                    } else {
                        html = '<p class="text-muted">No se encontraron participantes</p>';
                    }
                    
                    content.innerHTML = html;
                })
                .catch(error => {
                    content.innerHTML = '<p class="text-danger">Error cargando participantes: ' + error.message + '</p>';
                });
        }
        
        function showGroupList() {
            // Scroll a la tabla de grupos
            document.querySelector('.table-responsive').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
<?php endif; ?>
