<?php
// Mejoras finales: multimedia, estados, nombres e iconos de contactos
echo "🚀 MEJORAS FINALES - Multimedia, Contactos, Nombres e Iconos\n\n";

// PASO 1: Verificar y mejorar manejo de multimedia
echo "🖼️ PASO 1: Mejorando manejo de multimedia\n";

$media_handler_file = __DIR__ . '/media_handler.php';
if (!file_exists($media_handler_file)) {
    echo "📝 Creando MediaHandler mejorado...\n";
    
    $media_handler_content = '<?php
/**
 * MediaHandler Mejorado - Descarga y gestiona archivos multimedia
 */
class MediaHandler {
    private $mediaDir;
    private $baseUrl;
    
    public function __construct($mediaDir = null, $baseUrl = null) {
        $this->mediaDir = $mediaDir ?: __DIR__ . \'/media\';
        $this->baseUrl = $baseUrl ?: \'https://camcam.com.ve/evoappws/media/\';
        
        // Crear directorio si no existe
        if (!file_exists($this->mediaDir)) {
            mkdir($this->mediaDir, 0755, true);
        }
    }
    
    /**
     * Procesa y descarga media desde webhook
     */
    public function processMediaFromWebhook($webhookData) {
        if (!isset($webhookData[\'data\'][\'key\'][\'id\'])) {
            return null;
        }
        
        $messageId = $webhookData[\'data\'][\'key\'][\'id\'];
        $messageType = $webhookData[\'data\'][\'messageType\'] ?? \'text\';
        
        // Si es mensaje de texto, no hay media
        if ($messageType === \'text\') {
            return null;
        }
        
        // Extraer información del media
        $mediaInfo = $this->extractMediaInfo($webhookData);
        if (!$mediaInfo) {
            return null;
        }
        
        // Descargar media
        return $this->downloadMedia($messageId, $mediaInfo);
    }
    
    /**
     * Extrae información del media del webhook
     */
    private function extractMediaInfo($webhookData) {
        $data = $webhookData[\'data\'];
        
        // Buscar información del media en diferentes estructuras
        $mediaFields = [
            \'imageMessage\', \'videoMessage\', \'audioMessage\', 
            \'documentMessage\', \'stickerMessage\'
        ];
        
        foreach ($mediaFields as $field) {
            if (isset($data[$field])) {
                return [
                    'type' => str_replace('Message', '', $field),
                    'mimetype' => $data[$field]['mimetype'] ?? 'application/octet-stream',
                    'filename' => $data[$field]['fileName'] ?? null,
                    'caption' => $data[$field]['caption'] ?? null,
                    'fileSha256' => $data[$field]['fileSha256'] ?? null,
                    'mediaKey' => $data[$field]['mediaKey'] ?? null,
                    'directPath' => $data[$field]['directPath'] ?? null,
                    'url' => $data[$field]['url'] ?? null
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Descarga media desde Evolution API
     */
    private function downloadMedia($messageId, $mediaInfo) {
        try {
            // Construir URL para descargar media
            $instanceId = $_SESSION[\'instance_id\'] ?? \'default\';
            $downloadUrl = "https://api.evolution-api.com/v1/media/download/{$instanceId}";
            
            // Datos para la descarga
            $postData = [
                \'id\' => $messageId,
                \'type\' => $mediaInfo[\'type\']
            ];
            
            // Realizar descarga
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $downloadUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                \'Content-Type: application/json\',
                \'apikey: YOUR_EVOLUTION_API_KEY\'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                error_log("Media download failed: HTTP $httpCode");
                return null;
            }
            
            // Guardar archivo
            $filename = $this->generateFilename($messageId, $mediaInfo);
            $filepath = $this->mediaDir . $filename;
            
            if (file_put_contents($filepath, $response)) {
                return [
                    \'success\' => true,
                    \'filename\' => $filename,
                    \'filepath\' => $filepath,
                    \'url\' => $this->baseUrl . $filename,
                    \'type\' => $mediaInfo[\'type\'],
                    \'mimetype\' => $mediaInfo[\'mimetype\'],
                    \'caption\' => $mediaInfo[\'caption\'],
                    \'size\' => strlen($response)
                ];
            }
            
        } catch (Exception $e) {
            error_log("Media download error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Genera nombre de archivo único
     */
    private function generateFilename($messageId, $mediaInfo) {
        $extension = $this->getExtensionFromMimetype($mediaInfo[\'mimetype\']);
        return $messageId . \'_\' . time() . \'.\' . $extension;
    }
    
    /**
     * Obtiene extensión desde mimetype
     */
    private function getExtensionFromMimetype($mimetype) {
        $extensions = [
            \'image/jpeg\' => \'jpg\',
            \'image/png\' => \'png\',
            \'image/gif\' => \'gif\',
            \'video/mp4\' => \'mp4\',
            \'audio/mpeg\' => \'mp3\',
            \'audio/wav\' => \'wav\',
            \'application/pdf\' => \'pdf\',
            \'text/plain\' => \'txt\'
        ];
        
        return $extensions[$mimetype] ?? \'bin\';
    }
    
    /**
     * Sirve archivo multimedia
     */
    public function serveMedia($filename) {
        $filepath = $this->mediaDir . $filename;
        
        if (!file_exists($filepath)) {
            http_response_code(404);
            echo "Media not found";
            return;
        }
        
        $mimetype = mime_content_type($filepath);
        header(\'Content-Type: \' . $mimetype);
        header(\'Content-Length: \' . filesize($filepath));
        readfile($filepath);
    }
}
?>';
    
    if (file_put_contents($media_handler_file, $media_handler_content)) {
        echo "✅ MediaHandler mejorado creado\n";
    } else {
        echo "❌ Error al crear MediaHandler\n";
    }
} else {
    echo "✅ MediaHandler ya existe\n";
}

// PASO 2: Mejorar resolución de nombres y estados de contactos
echo "\n👤 PASO 2: Mejorando resolución de contactos\n";

$contact_resolver_file = __DIR__ . '/contact_resolver_enhanced.php';
if (!file_exists($contact_resolver_file)) {
    echo "📝 Creando ContactResolver mejorado...\n";
    
    $contact_resolver_content = '<?php
/**
 * ContactResolver Mejorado - Resuelve nombres, estados e iconos
 */
class ContactResolver {
    private $db;
    private $cache = [];
    private $cacheTimeout = 300; // 5 minutos
    
    public function __construct() {
        $this->db = \App\Core\DB::getInstance();
    }
    
    /**
     * Resuelve información completa del contacto
     */
    public function resolveContact($jid, $instanceId = null) {
        $cacheKey = $jid . \'_\' . ($instanceId ?? \'default\');
        
        // Verificar cache
        if (isset($this->cache[$cacheKey]) && (time() - $this->cache[$cacheKey][\'timestamp\']) < $this->cacheTimeout) {
            return $this->cache[$cacheKey][\'data\'];
        }
        
        $contactInfo = $this->fetchContactInfo($jid, $instanceId);
        
        // Guardar en cache
        $this->cache[$cacheKey] = [
            \'data\' => $contactInfo,
            \'timestamp\' => time()
        ];
        
        return $contactInfo;
    }
    
    /**
     * Obtiene información del contacto desde múltiples fuentes
     */
    private function fetchContactInfo($jid, $instanceId) {
        $contactInfo = [
            \'jid\' => $jid,
            \'name\' => null,
            \'pushname\' => null,
            \'profilePicUrl\' => null,
            \'phone\' => null,
            \'isBusiness\' => false,
            \'isEnterprise\' => false,
            \'presence\' => null,
            \'lastSeen\' => null
        ];
        
        // 1. Buscar en tabla contacts
        $contactInfo = array_merge($contactInfo, $this->getFromContactsTable($jid, $instanceId));
        
        // 2. Buscar en mensajes recientes
        if (!$contactInfo[\'name\']) {
            $contactInfo = array_merge($contactInfo, $this->getFromRecentMessages($jid, $instanceId));
        }
        
        // 3. Buscar en chats
        if (!$contactInfo[\'name\']) {
            $contactInfo = array_merge($contactInfo, $this->getFromChats($jid, $instanceId));
        }
        
        // 4. Extraer información del JID
        $contactInfo = array_merge($contactInfo, $this->extractFromJid($jid));
        
        // 5. Obtener presencia
        $contactInfo[\'presence\'] = $this->getPresence($jid, $instanceId);
        
        // 6. Obtener foto de perfil
        if (!$contactInfo[\'profilePicUrl\']) {
            $contactInfo[\'profilePicUrl\'] = $this->getProfilePicture($jid, $instanceId);
        }
        
        return $contactInfo;
    }
    
    /**
     * Obtiene información de la tabla contacts
     */
    private function getFromContactsTable($jid, $instanceId) {
        try {
            $query = "SELECT * FROM contacts WHERE jid = ? AND instance_id = ?";
            $result = $this->db->fetch($query, [$jid, $instanceId]);
            
            if ($result) {
                return [
                    \'name\' => $result[\'name\'],
                    \'pushname\' => $result[\'pushname\'],
                    \'profilePicUrl\' => $result[\'profile_pic_url\'],
                    \'phone\' => $result[\'phone\'],
                    \'isBusiness\' => $result[\'is_business\'] ?? false,
                    \'isEnterprise\' => $result[\'is_enterprise\'] ?? false
                ];
            }
        } catch (Exception $e) {
            error_log("Error fetching from contacts: " . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * Obtiene información de mensajes recientes
     */
    private function getFromRecentMessages($jid, $instanceId) {
        try {
            $query = "SELECT pushname, name FROM messages 
                     WHERE remote_jid = ? AND instance_id = ? 
                     AND (pushname IS NOT NULL OR name IS NOT NULL)
                     ORDER BY created_at DESC LIMIT 1";
            $result = $this->db->fetch($query, [$jid, $instanceId]);
            
            if ($result) {
                return [
                    \'name\' => $result[\'name\'],
                    \'pushname\' => $result[\'pushname\']
                ];
            }
        } catch (Exception $e) {
            error_log("Error fetching from messages: " . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * Obtiene información de chats
     */
    private function getFromChats($jid, $instanceId) {
        try {
            $query = "SELECT name FROM chats WHERE remote_jid = ? AND instance_id = ?";
            $result = $this->db->fetch($query, [$jid, $instanceId]);
            
            if ($result && $result[\'name\']) {
                return [\'name\' => $result[\'name\']];
            }
        } catch (Exception $e) {
            error_log("Error fetching from chats: " . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * Extrae información del JID
     */
    private function extractFromJid($jid) {
        $phone = null;
        $name = null;
        
        // Extraer número de teléfono del JID
        if (preg_match(\'/^(\d+)@s\.whatsapp\.net$/\', $jid, $matches)) {
            $phone = $matches[1];
            $name = \'+\' . $phone;
        }
        
        return [
            \'phone\' => $phone,
            \'name\' => $name
        ];
    }
    
    /**
     * Obtiene presencia del contacto
     */
    private function getPresence($jid, $instanceId) {
        try {
            $query = "SELECT last_known_presence FROM presence 
                     WHERE jid = ? AND instance_id = ? 
                     ORDER BY updated_at DESC LIMIT 1";
            $result = $this->db->fetch($query, [$jid, $instanceId]);
            
            return $result[\'last_known_presence\'] ?? null;
        } catch (Exception $e) {
            error_log("Error fetching presence: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Obtiene foto de perfil
     */
    private function getProfilePicture($jid, $instanceId) {
        try {
            // Intentar obtener desde Evolution API
            $instanceId = $instanceId ?? \'default\';
            $url = "https://api.evolution-api.com/v1/profile-picture/{$instanceId}/{$jid}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                \'apikey: YOUR_EVOLUTION_API_KEY\'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return $data[\'profilePictureUrl\'] ?? null;
            }
        } catch (Exception $e) {
            error_log("Error fetching profile picture: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Obtiene nombre para mostrar
     */
    public function getDisplayName($jid, $instanceId = null) {
        $contact = $this->resolveContact($jid, $instanceId);
        
        // Prioridad: pushname > name > phone > jid
        if ($contact[\'pushname\']) {
            return $contact[\'pushname\'];
        }
        
        if ($contact[\'name\']) {
            return $contact[\'name\'];
        }
        
        if ($contact[\'phone\']) {
            return \'+\' . $contact[\'phone\'];
        }
        
        return $jid;
    }
    
    /**
     * Actualiza información del contacto
     */
    public function updateContact($jid, $instanceId, $data) {
        try {
            // Verificar si existe
            $query = "SELECT id FROM contacts WHERE jid = ? AND instance_id = ?";
            $exists = $this->db->fetch($query, [$jid, $instanceId]);
            
            if ($exists) {
                // Actualizar
                $updateQuery = "UPDATE contacts SET 
                    name = COALESCE(?, name),
                    pushname = COALESCE(?, pushname),
                    profile_pic_url = COALESCE(?, profile_pic_url),
                    phone = COALESCE(?, phone),
                    is_business = COALESCE(?, is_business),
                    is_enterprise = COALESCE(?, is_enterprise),
                    updated_at = NOW()
                    WHERE jid = ? AND instance_id = ?";
                
                $this->db->q($updateQuery, [
                    $data[\'name\'] ?? null,
                    $data[\'pushname\'] ?? null,
                    $data[\'profilePicUrl\'] ?? null,
                    $data[\'phone\'] ?? null,
                    $data[\'isBusiness\'] ?? false,
                    $data[\'isEnterprise\'] ?? false,
                    $jid, $instanceId
                ]);
            } else {
                // Insertar
                $insertQuery = "INSERT INTO contacts 
                    (jid, instance_id, name, pushname, profile_pic_url, phone, is_business, is_enterprise, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                
                $this->db->q($insertQuery, [
                    $jid, $instanceId,
                    $data[\'name\'] ?? null,
                    $data[\'pushname\'] ?? null,
                    $data[\'profilePicUrl\'] ?? null,
                    $data[\'phone\'] ?? null,
                    $data[\'isBusiness\'] ?? false,
                    $data[\'isEnterprise\'] ?? false
                ]);
            }
            
            // Limpiar cache
            $this->clearCache($jid, $instanceId);
            
        } catch (Exception $e) {
            error_log("Error updating contact: " . $e->getMessage());
        }
    }
    
    /**
     * Limpia cache de contacto
     */
    private function clearCache($jid, $instanceId) {
        $cacheKey = $jid . \'_\' . ($instanceId ?? \'default\');
        unset($this->cache[$cacheKey]);
    }
}
?>';
    
    if (file_put_contents($contact_resolver_file, $contact_resolver_content)) {
        echo "✅ ContactResolver mejorado creado\n";
    } else {
        echo "❌ Error al crear ContactResolver\n";
    }
} else {
    echo "✅ ContactResolver ya existe\n";
}

// PASO 3: Actualizar WebhookController para usar mejoras
echo "\n🔧 PASO 3: Actualizando WebhookController con mejoras\n";

$webhook_file = __DIR__ . '/app/Controllers/WebhookController.php';
if (file_exists($webhook_file)) {
    echo "📖 Actualizando WebhookController...\n";
    
    $content = file_get_contents($webhook_file);
    
    // Agregar includes al principio
    if (strpos($content, 'require_once') === false) {
        $include_code = "
require_once __DIR__ . '/../../media_handler.php';
require_once __DIR__ . '/../../contact_resolver_enhanced.php';";
        
        $content = str_replace('<?php', '<?php' . $include_code, $content);
    }
    
    // Agregar manejo de multimedia en processWebhookEvent
    if (strpos($content, 'processMedia') === false) {
        $media_handler_code = "
        // Procesar multimedia si existe
        \$mediaHandler = new MediaHandler();
        \$mediaInfo = \$mediaHandler->processMediaFromWebhook(\$webhookData);
        if (\$mediaInfo) {
            error_log('Media processed: ' . json_encode(\$mediaInfo));
        }";
        
        $content = str_replace('// Procesar evento específico', $media_handler_code . "\n        // Procesar evento específico", $content);
    }
    
    // Agregar resolución de contactos
    if (strpos($content, 'ContactResolver') === false) {
        $contact_resolver_code = "
        // Resolver información de contacto
        \$contactResolver = new ContactResolver();
        if (isset(\$webhookData['data']['remoteJid'])) {
            \$contactInfo = \$contactResolver->resolveContact(\$webhookData['data']['remoteJid'], \$instanceId);
            error_log('Contact resolved: ' . json_encode(\$contactInfo));
        }";
        
        $content = str_replace('// Procesar evento específico', $contact_resolver_code . "\n        // Procesar evento específico", $content);
    }
    
    // Guardar cambios
    if (file_put_contents($webhook_file, $content)) {
        echo "✅ WebhookController actualizado con mejoras\n";
    } else {
        echo "❌ Error al actualizar WebhookController\n";
    }
} else {
    echo "❌ WebhookController no encontrado\n";
}

// PASO 4: Crear endpoint para servir media
echo "\n🌐 PASO 4: Creando endpoint para servir media\n";

$media_endpoint_file = __DIR__ . '/media_endpoint.php';
if (!file_exists($media_endpoint_file)) {
    $media_endpoint_content = '<?php
// Endpoint para servir archivos multimedia
require_once __DIR__ . \'/media_handler.php\';

\$mediaHandler = new MediaHandler();

// Obtener filename de la URL
\$filename = \$_GET[\'file\'] ?? null;

if (!\$filename) {
    http_response_code(400);
    echo "Filename required";
    exit;
}

// Validar seguridad del filename
if (strpos(\$filename, \'..\') !== false || !preg_match(\'/^[a-zA-Z0-9_\-\.]+$/\', \$filename)) {
    http_response_code(400);
    echo "Invalid filename";
    exit;
}

// Servir archivo
\$mediaHandler->serveMedia(\$filename);
?>';
    
    if (file_put_contents($media_endpoint_file, $media_endpoint_content)) {
        echo "✅ Endpoint de media creado\n";
    } else {
        echo "❌ Error al crear endpoint de media\n";
    }
} else {
    echo "✅ Endpoint de media ya existe\n";
}

echo "\n🎯 MEJORAS IMPLEMENTADAS:\n";
echo "========================\n";
echo "✅ MediaHandler mejorado - Descarga y gestiona archivos multimedia\n";
echo "✅ ContactResolver mejorado - Resuelve nombres, estados e iconos\n";
echo "✅ WebhookController actualizado - Integra mejoras\n";
echo "✅ Endpoint de media creado - Sirve archivos multimedia\n\n";

echo "🎯 PRÓXIMOS PASOS:\n";
echo "==================\n";
echo "1. 🔄 Reinicia el servidor web (si puedes)\n";
echo "2. 🧪 Prueba enviar/reibir mensajes con multimedia\n";
echo "3. 👤 Verifica que los nombres de contactos se resuelvan\n";
echo "4. 📱 Verifica que los estados de presencia funcionen\n";
echo "5. 🖼️ Verifica que las imágenes se descarguen\n\n";

echo "🌐 URLs de prueba:\n";
echo "==================\n";
echo "Media: https://camcam.com.ve/evoappws/media_endpoint.php?file=ejemplo.jpg\n\n";

echo "🚀 ¡MEJORAS FINALES IMPLEMENTADAS!\n";
?>';
    
    if (file_put_contents($enhance_file, $enhance_content)) {
        echo "✅ Script de mejoras creado\n";
    } else {
        echo "❌ Error al crear script de mejoras\n";
    }
} else {
    echo "✅ Script de mejoras ya existe\n";
}

echo "\n🎯 ¡EJECUTAR AHORA!\n";
echo "==================\n";
echo "🌐 https://camcam.com.ve/evoappws/enhance_multimedia_contacts.php\n\n";

echo "🚀 Este script implementará:\n";
echo "✅ Descarga automática de archivos multimedia\n";
echo "✅ Resolución de nombres de contactos\n";
echo "✅ Obtención de estados de presencia\n";
echo "✅ Descarga de iconos/fotos de perfil\n";
echo "✅ Integración con WebhookController\n";
echo "✅ Endpoint para servir archivos\n";
?>
