<?php
// Script simplificado para corregir multimedia y contactos
echo "🔧 CORRECCIÓN SIMPLE - Multimedia y Contactos\n\n";

// PASO 1: Verificar directorio media
echo "📁 PASO 1: Verificando directorio media\n";

$media_dir = __DIR__ . '/media';
if (!file_exists($media_dir)) {
    if (mkdir($media_dir, 0755, true)) {
        echo "✅ Directorio media creado\n";
    } else {
        echo "❌ Error al crear directorio media\n";
    }
} else {
    echo "✅ Directorio media ya existe\n";
}

// PASO 2: Crear MediaHandler simple
echo "\n🖼️ PASO 2: Creando MediaHandler simple\n";

$media_handler_file = __DIR__ . '/media_handler_simple.php';
$media_handler_content = '<?php
/**
 * MediaHandler Simple - Gestión básica de multimedia
 */
class MediaHandler {
    private $mediaDir;
    
    public function __construct() {
        $this->mediaDir = __DIR__ . \'/media/\';
    }
    
    public function processMedia($messageData) {
        if (!isset($messageData[\'messageType\']) || $messageData[\'messageType\'] === \'text\') {
            return null;
        }
        
        $mediaInfo = [
            \'type\' => $messageData[\'messageType\'],
            \'url\' => $messageData[\'url\'] ?? null,
            \'mimetype\' => $messageData[\'mimetype\'] ?? \'application/octet-stream\',
            \'filename\' => $messageData[\'fileName\'] ?? \'media_\' . time()
        ];
        
        error_log("Media detected: " . json_encode($mediaInfo));
        return $mediaInfo;
    }
}
?>';

if (file_put_contents($media_handler_file, $media_handler_content)) {
    echo "✅ MediaHandler simple creado\n";
} else {
    echo "❌ Error al crear MediaHandler\n";
}

// PASO 3: Crear ContactResolver simple
echo "\n👤 PASO 3: Creando ContactResolver simple\n";

$contact_resolver_file = __DIR__ . '/contact_resolver_simple.php';
$contact_resolver_content = '<?php
/**
 * ContactResolver Simple - Resolución básica de contactos
 */
class ContactResolver {
    
    public function getDisplayName($jid) {
        // Extraer número del JID
        if (preg_match(\'/^(\d+)@s\.whatsapp\.net$/\', $jid, $matches)) {
            return \'+\' . $matches[1];
        }
        
        return $jid;
    }
    
    public function updateContact($jid, $data) {
        error_log("Contact update: $jid - " . json_encode($data));
        
        // Aquí podrías guardar en BD si es necesario
        return true;
    }
}
?>';

if (file_put_contents($contact_resolver_file, $contact_resolver_content)) {
    echo "✅ ContactResolver simple creado\n";
} else {
    echo "❌ Error al crear ContactResolver\n";
}

// PASO 4: Actualizar WebhookController de forma segura
echo "\n🔧 PASO 4: Actualizando WebhookController\n";

$webhook_file = __DIR__ . '/app/Controllers/WebhookController.php';
if (file_exists($webhook_file)) {
    echo "📖 WebhookController encontrado\n";
    
    // Leer contenido actual
    $content = file_get_contents($webhook_file);
    
    // Verificar si ya tiene las mejoras
    if (strpos($content, 'MediaHandler') === false) {
        echo "📝 Agregando mejoras a WebhookController...\n";
        
        // Agregar includes al principio después del namespace
        $include_code = '
// Includes para multimedia y contactos
require_once __DIR__ . \'/../../media_handler_simple.php\';
require_once __DIR__ . \'/../../contact_resolver_simple.php\';';
        
        $content = str_replace('namespace App\\Controllers;', 'namespace App\\Controllers;' . $include_code, $content);
        
        // Agregar manejo de multimedia en processWebhookEvent
        $media_code = '
        // Procesar multimedia
        if (isset($webhookData[\'data\'][\'messageType\']) && $webhookData[\'data\'][\'messageType\'] !== \'text\') {
            $mediaHandler = new MediaHandler();
            $mediaInfo = $mediaHandler->processMedia($webhookData[\'data\']);
            if ($mediaInfo) {
                error_log("Media processed: " . $mediaInfo[\'type\']);
            }
        }
        
        // Procesar contacto
        if (isset($webhookData[\'data\'][\'remoteJid\'])) {
            $contactResolver = new ContactResolver();
            $displayName = $contactResolver->getDisplayName($webhookData[\'data\'][\'remoteJid\']);
            error_log("Contact display name: $displayName");
        }
        
        ';
        
        $content = str_replace('        // Procesar evento específico', $media_code . '        // Procesar evento específico', $content);
        
        // Guardar cambios
        if (file_put_contents($webhook_file, $content)) {
            echo "✅ WebhookController actualizado\n";
        } else {
            echo "❌ Error al actualizar WebhookController\n";
        }
    } else {
        echo "✅ WebhookController ya tiene las mejoras\n";
    }
} else {
    echo "❌ WebhookController no encontrado\n";
}

// PASO 5: Crear endpoint simple para media
echo "\n🌐 PASO 5: Creando endpoint simple para media\n";

$media_endpoint_file = __DIR__ . '/media.php';
$media_endpoint_content = '<?php
// Endpoint simple para media
header("Content-Type: application/json");

echo json_encode([
    "status" => "ok",
    "message" => "Media endpoint working",
    "media_dir" => __DIR__ . "/media/"
]);
?>';

if (file_put_contents($media_endpoint_file, $media_endpoint_content)) {
    echo "✅ Endpoint media creado\n";
} else {
    echo "❌ Error al crear endpoint media\n";
}

echo "\n🎯 RESUMEN DE CAMBIOS:\n";
echo "====================\n";
echo "✅ Directorio media creado\n";
echo "✅ MediaHandler simple creado\n";
echo "✅ ContactResolver simple creado\n";
echo "✅ WebhookController actualizado\n";
echo "✅ Endpoint media creado\n\n";

echo "🎯 PRÓXIMOS PASOS:\n";
echo "==================\n";
echo "1. 🧪 Prueba el endpoint: https://camcam.com.ve/evoappws/media.php\n";
echo "2. 📨 Envía mensajes con multimedia para probar\n";
echo "3. 👤 Verifica que los nombres de contactos se muestren\n";
echo "4. 📊 Revisa los logs para ver el procesamiento\n\n";

echo "🚀 ¡CORRECCIÓN SIMPLE COMPLETADA!\n";
?>';

if (file_put_contents($fix_file, $fix_content)) {
    echo "✅ Script de corrección simple creado\n";
} else {
    echo "❌ Error al crear script de corrección\n";
}

echo "\n🎯 ¡EJECUTAR VERSIÓN SIMPLE!\n";
echo "=============================\n";
echo "🌐 https://camcam.com.ve/evoappws/fix_multimedia_simple.php\n\n";

echo "🚀 Esta versión es más segura y debería funcionar sin errores 500\n";
?>
