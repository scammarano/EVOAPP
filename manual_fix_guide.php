<?php
// Guía MANUAL para corregir todos los problemas
echo "🔧 GUÍA MANUAL DE CORRECCIÓN - PASO A PASO\n\n";

echo "📋 PROBLEMAS IDENTIFICADOS:\n";
echo "❌ DB_HOST no definido\n";
echo "❌ columnExists() no existe\n";
echo "❌ send.message no reconocido\n";
echo "❌ messageId no encontrado\n";
echo "❌ campaigns SQL error\n\n";

echo "🎯 SOLUCIÓN MANUAL - PASO A PASO:\n\n";

echo "📂 PASO 1: Verificar archivos en el servidor\n";
echo "=====================================\n";
echo "Conéctate a tu servidor via FTP/SSH y verifica:\n\n";

$files_to_check = [
    __DIR__ . '/config/config.php',
    __DIR__ . '/config.php',
    __DIR__ . '/app/Core/DB.php',
    __DIR__ . '/app/Controllers/WebhookController.php',
    __DIR__ . '/app/Models/Chat.php',
    __DIR__ . '/fix_everything_now.php',
    __DIR__ . '/update_db_class.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ Existe: $file\n";
        echo "📏 Tamaño: " . filesize($file) . " bytes\n";
        echo "📅 Modificado: " . date('Y-m-d H:i:s', filemtime($file)) . "\n\n";
    } else {
        echo "❌ No existe: $file\n\n";
    }
}

echo "📂 PASO 2: Verificar configuración BD\n";
echo "=================================\n";

$config_file = __DIR__ . '/config/config.php';
if (file_exists($config_file)) {
    echo "✅ config/config.php encontrado\n";
    $content = file_get_contents($config_file);
    
    if (strpos($content, 'DB_HOST') !== false) {
        echo "✅ Tiene constantes BD\n";
    } else {
        echo "❌ No tiene constantes BD\n";
    }
    
    if (strpos($content, 'grupoecc_evoappWS') !== false) {
        echo "✅ Tiene credenciales correctas\n";
    } else {
        echo "❌ No tiene credenciales correctas\n";
    }
} else {
    echo "❌ config/config.php no encontrado\n";
}

echo "\n📂 PASO 3: Verificar métodos en DB.php\n";
echo "==================================\n";

$db_file = __DIR__ . '/app/Core/DB.php';
if (file_exists($db_file)) {
    $db_content = file_get_contents($db_file);
    
    if (strpos($db_content, 'function columnExists') !== false) {
        echo "✅ Tiene método columnExists()\n";
    } else {
        echo "❌ NO tiene método columnExists()\n";
    }
    
    if (strpos($db_content, 'function tableExists') !== false) {
        echo "✅ Tiene método tableExists()\n";
    } else {
        echo "❌ NO tiene método tableExists()\n";
    }
} else {
    echo "❌ DB.php no encontrado\n";
}

echo "\n📂 PASO 4: Verificar WebhookController\n";
echo "====================================\n";

$webhook_file = __DIR__ . '/app/Controllers/WebhookController.php';
if (file_exists($webhook_file)) {
    $webhook_content = file_get_contents($webhook_file);
    
    if (strpos($webhook_content, 'send.message') !== false) {
        echo "✅ Tiene mapeo send.message\n";
    } else {
        echo "❌ NO tiene mapeo send.message\n";
    }
    
    if (strpos($webhook_content, 'messageId') !== false) {
        echo "✅ Tiene manejo de messageId\n";
    } else {
        echo "❌ NO tiene manejo de messageId\n";
    }
} else {
    echo "❌ WebhookController.php no encontrado\n";
}

echo "\n📂 PASO 5: Verificar Chat.php\n";
echo "============================\n";

$chat_file = __DIR__ . '/app/Models/Chat.php';
if (file_exists($chat_file)) {
    $chat_content = file_get_contents($chat_file);
    
    if (strpos($chat_content, 'TRY-CATCH') !== false) {
        echo "✅ Tiene TRY-CATCH\n";
    } else {
        echo "❌ NO tiene TRY-CATCH\n";
    }
} else {
    echo "❌ Chat.php no encontrado\n";
}

echo "\n🎯 ACCIONES REQUERIDAS:\n";
echo "======================\n\n";

echo "🔧 Si los archivos NO existen en el servidor:\n";
echo "1. Sube estos archivos via FTP:\n";
echo "   - fix_everything_now.php\n";
echo "   - update_db_class.php\n";
echo "   - check_config.php\n\n";

echo "🌐 Ejecuta en el navegador:\n";
echo "   https://camcam.com.ve/evoappws/fix_everything_now.php\n\n";

echo "🔧 Si los archivos SÍ existen pero no funcionan:\n";
echo "1. Verifica permisos (chmod 644 para PHP, 755 para carpetas)\n";
echo "2. Reinicia servidor web: sudo systemctl restart apache2\n";
echo "3. Limpia cache si usas OPcache\n\n";

echo "🔧 Si nada funciona, hazlo manualmente:\n\n";

echo "📝 1. Agrega métodos a DB.php manualmente:\n";
echo "   Abre app/Core/DB.php\n";
echo "   Antes del cierre de la clase, agrega:\n\n";

$methods_to_add = '
public static function columnExists($table, $column)
{
    try {
        $result = self::fetch("
            SELECT COUNT(*) as count
            FROM information_schema.columns 
            WHERE table_schema = DATABASE() 
            AND table_name = ? 
            AND column_name = ?
        ", [$table, $column]);
        
        return $result[\'count\'] > 0;
    } catch (Exception $e) {
        error_log("columnExists error: " . $e->getMessage());
        return false;
    }
}

public static function tableExists($table)
{
    try {
        $result = self::fetch("
            SELECT COUNT(*) as count
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = ?
        ", [$table]);
        
        return $result[\'count\'] > 0;
    } catch (Exception $e) {
        error_log("tableExists error: " . $e->getMessage());
        return false;
    }
}';

echo $methods_to_add;
echo "\n\n";

echo "📝 2. Agrega TRY-CATCH a Chat.php manualmente:\n";
echo "   Reemplaza la línea de columnExists() con:\n\n";

$try_catch_code = '
try {
    $hasProfilePic = DB::columnExists(\'contacts\', \'profile_pic_url\');
    $hasName = DB::columnExists(\'contacts\', \'name\');
    $hasPhone = DB::columnExists(\'contacts\', \'phone\');
    $hasPhoneE164 = DB::columnExists(\'contacts\', \'phone_e164\');
} catch (Exception $e) {
    error_log("columnExists method failed: " . $e->getMessage() . " - using defaults");
    $hasProfilePic = true;
    $hasName = false;
    $hasPhone = true;
    $hasPhoneE164 = false;
}';

echo $try_catch_code;
echo "\n\n";

echo "📝 3. Agrega mapeos a WebhookController manualmente:\n";
echo "   En EVENT_MAPPING, agrega:\n\n";

$mappings_code = '
\'send.message\' => \'SEND_MESSAGE\',
\'send-message\' => \'SEND_MESSAGE\',
\'message.update\' => \'MESSAGES_UPDATE\',
\'message-update\' => \'MESSAGES_UPDATE\'';

echo $mappings_code;
echo "\n\n";

echo "🎯 Después de hacer los cambios manuales:\n";
echo "1. Reinicia el servidor web\n";
echo "2. Limpia caches\n";
echo "3. Prueba la aplicación\n\n";

echo "🚀 Si todo falla, contacta a tu administrador de sistemas\n";
echo "para que ejecute los comandos directamente en el servidor.\n";
?>
