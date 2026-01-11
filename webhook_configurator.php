<?php
// Configurador de Webhooks Evolution API - Activación Completa
require_once 'app/Core/DB.php';

// Inicializar conexión
App\Core\DB::init();

echo "<h1>🚀 Configurador Completo de Webhooks Evolution API</h1>";

// Obtener todas las instancias
$instances = App\Core\DB::fetchAll("SELECT * FROM evo_instances ORDER BY slug");

echo "<h2>📱 Instancias Disponibles</h2>";

foreach ($instances as $instance) {
    echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff;'>";
    echo "<h3>🏢 {$instance['slug']} (ID: {$instance['id']})</h3>";
    echo "<p><strong>API Key:</strong> " . substr($instance['api_key'], 0, 20) . "...</p>";
    echo "<p><strong>Base URL:</strong> {$instance['base_url']}</p>";
    echo "<p><strong>Webhook Enabled:</strong> " . ($instance['webhook_enabled'] ? '✅ Sí' : '❌ No') . "</p>";
    
    // Botón para configurar webhooks
    echo "<button onclick='configureWebhooks({$instance['id']}, \"{$instance['slug']}\", \"{$instance['api_key']}\", \"{$instance['base_url']}\")' 
            style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>
            🔧 Configurar Webhooks Completos
        </button>";
    
    // Botón para verificar estado
    echo "<button onclick='checkWebhookStatus({$instance['id']}, \"{$instance['slug']}\", \"{$instance['api_key']}\", \"{$instance['base_url']}\")' 
            style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>
            🔍 Verificar Estado
        </button>";
    
    echo "<div id='status-{$instance['id']}' style='margin-top: 10px;'></div>";
    echo "</div>";
}

?>
<script>
// Configuración completa de webhooks para una instancia
async function configureWebhooks(instanceId, slug, apiKey, baseUrl) {
    const statusDiv = document.getElementById(`status-${instanceId}`);
    statusDiv.innerHTML = '<p style="color: blue;">⚙️ Configurando webhooks...</p>';
    
    try {
        // URL del webhook local
        const webhookUrl = `https://camcam.com.ve/evoappws/index.php?r=webhook/evolution&instance=${slug}`;
        
        // Eventos completos Evolution API
        const events = [
            "QRCODE_UPDATED",
            "CONNECTION_UPDATE", 
            "MESSAGES_SET",
            "MESSAGES_UPSERT",
            "MESSAGES_UPDATE",
            "MESSAGES_DELETE",
            "SEND_MESSAGE",
            "CONTACTS_SET",
            "CONTACTS_UPSERT", 
            "CONTACTS_UPDATE",
            "PRESENCE_UPDATE",
            "CHATS_SET",
            "CHATS_UPSERT",
            "CHATS_UPDATE",
            "CHATS_DELETE",
            "GROUPS_UPSERT",
            "GROUPS_UPDATE",
            "GROUP_PARTICIPANTS_UPDATE"
        ];
        
        const webhookConfig = {
            url: webhookUrl,
            webhook_by_events: false, // Usar un solo endpoint para todos los eventos
            events: events
        };
        
        // Llamar a Evolution API para configurar webhook
        const response = await fetch(`${baseUrl}/webhook/instance`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'apikey': apiKey,
                'Authorization': `Bearer ${apiKey}`
            },
            body: JSON.stringify(webhookConfig)
        });
        
        const result = await response.json();
        
        if (response.ok && result) {
            // Actualizar base de datos local
            await updateLocalWebhookConfig(instanceId, webhookUrl, true);
            
            statusDiv.innerHTML = `
                <div style="background: #d4edda; padding: 10px; border-radius: 5px; margin-top: 10px;">
                    <h4 style="color: #155724;">✅ Webhooks Configurados Exitosamente</h4>
                    <p><strong>URL:</strong> ${webhookUrl}</p>
                    <p><strong>Eventos Activados:</strong> ${events.length} eventos</p>
                    <p><strong>Respuesta API:</strong> ${JSON.stringify(result, null, 2)}</p>
                </div>
            `;
        } else {
            throw new Error(result.error || 'Error en la configuración');
        }
        
    } catch (error) {
        statusDiv.innerHTML = `
            <div style="background: #f8d7da; padding: 10px; border-radius: 5px; margin-top: 10px;">
                <h4 style="color: #721c24;">❌ Error en Configuración</h4>
                <p>${error.message}</p>
            </div>
        `;
    }
}

// Verificar estado actual del webhook
async function checkWebhookStatus(instanceId, slug, apiKey, baseUrl) {
    const statusDiv = document.getElementById(`status-${instanceId}`);
    statusDiv.innerHTML = '<p style="color: blue;">🔍 Verificando estado...</p>';
    
    try {
        const response = await fetch(`${baseUrl}/webhook/find/${slug}`, {
            method: 'GET',
            headers: {
                'apikey': apiKey,
                'Authorization': `Bearer ${apiKey}`
            }
        });
        
        const result = await response.json();
        
        if (response.ok) {
            statusDiv.innerHTML = `
                <div style="background: #d1ecf1; padding: 10px; border-radius: 5px; margin-top: 10px;">
                    <h4 style="color: #0c5460;">📊 Estado Actual del Webhook</h4>
                    <p><strong>Enabled:</strong> ${result.enabled ? '✅ Sí' : '❌ No'}</p>
                    <p><strong>URL:</strong> ${result.url || 'No configurada'}</p>
                    <p><strong>Webhook by Events:</strong> ${result.webhookByEvents ? '✅ Sí' : '❌ No'}</p>
                    <p><strong>Eventos Configurados:</strong> ${result.events ? result.events.length : 0}</p>
                    ${result.events ? '<p><strong>Lista de Eventos:</strong><br>' + result.events.join('<br>') + '</p>' : ''}
                </div>
            `;
        } else {
            throw new Error(result.error || 'Error al verificar estado');
        }
        
    } catch (error) {
        statusDiv.innerHTML = `
            <div style="background: #f8d7da; padding: 10px; border-radius: 5px; margin-top: 10px;">
                <h4 style="color: #721c24;">❌ Error al Verificar</h4>
                <p>${error.message}</p>
            </div>
        `;
    }
}

// Actualizar configuración local del webhook
async function updateLocalWebhookConfig(instanceId, webhookUrl, enabled) {
    try {
        const response = await fetch('update_webhook_config.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                instance_id: instanceId,
                webhook_url: webhookUrl,
                webhook_enabled: enabled
            })
        });
        
        return await response.json();
    } catch (error) {
        console.error('Error updating local config:', error);
        return { success: false, error: error.message };
    }
}

// Configurar webhooks por eventos (webhook_by_events = true)
async function configureWebhookByEvents(instanceId, slug, apiKey, baseUrl) {
    const statusDiv = document.getElementById(`status-${instanceId}`);
    statusDiv.innerHTML = '<p style="color: blue;">⚙️ Configurando webhooks por eventos...</p>';
    
    try {
        const baseWebhookUrl = `https://camcam.com.ve/evoappws/webhook/`;
        
        // Eventos completos con URLs específicas
        const events = [
            "QRCODE_UPDATED",
            "CONNECTION_UPDATE", 
            "MESSAGES_SET",
            "MESSAGES_UPSERT",
            "MESSAGES_UPDATE",
            "MESSAGES_DELETE",
            "SEND_MESSAGE",
            "CONTACTS_SET",
            "CONTACTS_UPSERT", 
            "CONTACTS_UPDATE",
            "PRESENCE_UPDATE",
            "CHATS_SET",
            "CHATS_UPSERT",
            "CHATS_UPDATE",
            "CHATS_DELETE",
            "GROUPS_UPSERT",
            "GROUPS_UPDATE",
            "GROUP_PARTICIPANTS_UPDATE"
        ];
        
        const webhookConfig = {
            url: baseWebhookUrl,
            webhook_by_events: true, // Usar URLs específicas por evento
            events: events
        };
        
        const response = await fetch(`${baseUrl}/webhook/instance`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'apikey': apiKey,
                'Authorization': `Bearer ${apiKey}`
            },
            body: JSON.stringify(webhookConfig)
        });
        
        const result = await response.json();
        
        if (response.ok && result) {
            statusDiv.innerHTML = `
                <div style="background: #d4edda; padding: 10px; border-radius: 5px; margin-top: 10px;">
                    <h4 style="color: #155724;">✅ Webhooks por Eventos Configurados</h4>
                    <p><strong>Base URL:</strong> ${baseWebhookUrl}</p>
                    <p><strong>Eventos Activados:</strong> ${events.length} eventos</p>
                    <p><strong>URLs Específicas:</strong></p>
                    <ul>
                        ${events.map(event => `<li>${baseWebhookUrl}${event.toLowerCase().replace('_', '-')}</li>`).join('')}
                    </ul>
                </div>
            `;
        } else {
            throw new Error(result.error || 'Error en la configuración');
        }
        
    } catch (error) {
        statusDiv.innerHTML = `
            <div style="background: #f8d7da; padding: 10px; border-radius: 5px; margin-top: 10px;">
                <h4 style="color: #721c24;">❌ Error en Configuración</h4>
                <p>${error.message}</p>
            </div>
        `;
    }
}
</script>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}

h1 {
    color: #333;
    text-align: center;
    margin-bottom: 30px;
}

h2 {
    color: #007bff;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

h3 {
    color: #495057;
    margin-top: 0;
}

button {
    transition: background-color 0.3s;
}

button:hover {
    opacity: 0.8;
}

pre {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
    max-height: 200px;
}
</style>

<?php
echo "<hr>";
echo "<h2>📚 Documentación de Endpoints Creados</h2>";
echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px;'>";
echo "<h3>🔧 Endpoints Principales:</h3>";
echo "<ul>";
echo "<li><strong>POST /webhook/evolution</strong> - Webhook principal para todos los eventos</li>";
echo "<li><strong>GET /webhook/find/[instance]</strong> - Verificar configuración de webhook</li>";
echo "<li><strong>POST /webhook/[event-name]</strong> - Webhooks específicos por evento</li>";
echo "</ul>";

echo "<h3>📡 Eventos Soportados (Completos):</h3>";
echo "<ul>";
echo "<li>✅ QRCODE_UPDATED - Actualización de código QR</li>";
echo "<li>✅ CONNECTION_UPDATE - Estado de conexión</li>";
echo "<li>✅ MESSAGES_SET - Carga inicial de mensajes</li>";
echo "<li>✅ MESSAGES_UPSERT - Nuevos mensajes</li>";
echo "<li>✅ MESSAGES_UPDATE - Actualización de mensajes</li>";
echo "<li>✅ MESSAGES_DELETE - Eliminación de mensajes</li>";
echo "<li>✅ SEND_MESSAGE - Mensajes enviados</li>";
echo "<li>✅ CONTACTS_SET - Carga inicial de contactos</li>";
echo "<li>✅ CONTACTS_UPSERT - Actualización de contactos</li>";
echo "<li>✅ CONTACTS_UPDATE - Actualización individual de contacto</li>";
echo "<li>✅ PRESENCE_UPDATE - Estado de presencia (online, typing, etc.)</li>";
echo "<li>✅ CHATS_SET - Carga inicial de chats</li>";
echo "<li>✅ CHATS_UPSERT - Nuevos chats</li>";
echo "<li>✅ CHATS_UPDATE - Actualización de chats</li>";
echo "<li>✅ CHATS_DELETE - Eliminación de chats</li>";
echo "<li>✅ GROUPS_UPSERT - Creación de grupos</li>";
echo "<li>✅ GROUPS_UPDATE - Actualización de grupos</li>";
echo "<li>✅ GROUP_PARTICIPANTS_UPDATE - Cambios en participantes de grupos</li>";
echo "</ul>";

echo "<h3>🎯 Características Implementadas:</h3>";
echo "<ul>";
echo "<li>🔄 <strong>Procesamiento en tiempo real</strong> de todos los eventos</li>";
echo "<li>📊 <strong>Almacenamiento completo</strong> en base de datos</li>";
echo "<li>🔀 <strong>Forwarding</strong> a URLs externas configuradas</li>";
echo "<li>🛡️ <strong>Validación de tokens</strong> de webhook</li>";
echo "<li>📈 <strong>Estadísticas y monitoreo</strong> de eventos</li>";
echo "<li>🏗️ <strong>Manejo de errores</strong> y logging</li>";
echo "<li>👥 <strong>Gestión de grupos</strong> y participantes</li>";
echo "<li>📱 <strong>Presencia</strong> y estado de conexión</li>";
echo "</ul>";
echo "</div>";
?>
