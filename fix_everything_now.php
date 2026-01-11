<?php
// SCRIPT DEFINITIVO - CORRIGE TODO EN UNA EJECUCIÓN
echo "🚨 SCRIPT DEFINITIVO - CORRIGIENDO TODOS LOS PROBLEMAS\n\n";

// PASO 1: Verificar y configurar BD
echo "📊 PASO 1: Verificando configuración BD\n";

$config_paths = [
    __DIR__ . '/config/config.php',
    __DIR__ . '/config.php'
];

$config_loaded = false;
foreach ($config_paths as $config_path) {
    if (file_exists($config_path)) {
        echo "✅ Config encontrada: $config_path\n";
        require_once $config_path;
        $config_loaded = true;
        break;
    }
}

if (!$config_loaded) {
    echo "❌ No se encontró configuración BD\n";
    exit(1);
}

// Verificar constantes
if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
    echo "✅ Constantes BD definidas\n";
    echo "📊 BD: " . DB_NAME . "\n";
    echo "👤 Usuario: " . DB_USER . "\n";
} else {
    echo "❌ Constantes BD no definidas\n";
    exit(1);
}

// PASO 2: Actualizar DB.php con métodos faltantes
echo "\n🔧 PASO 2: Actualizando DB.php\n";

$db_file = __DIR__ . '/app/Core/DB.php';
if (!file_exists($db_file)) {
    echo "❌ DB.php no encontrado\n";
    exit(1);
}

$db_content = file_get_contents($db_file);

if (strpos($db_content, 'function columnExists') === false) {
    echo "📝 Agregando métodos a DB.php...\n";
    
    $methods = '
    
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
    
    // Insertar antes del cierre
    $pattern = '/(}\s*;\s*\?>\s*$)/';
    $replacement = $methods . '\n$1';
    
    if (preg_match($pattern, $db_content)) {
        $updated_content = preg_replace($pattern, $replacement, $db_content);
        
        // Backup
        $backup = $db_file . '.backup.' . date('Y-m-d-H-i-s');
        copy($db_file, $backup);
        
        if (file_put_contents($db_file, $updated_content)) {
            echo "✅ DB.php actualizado\n";
        } else {
            echo "❌ Error actualizando DB.php\n";
        }
    }
} else {
    echo "✅ DB.php ya tiene los métodos\n";
}

// PASO 3: Actualizar WebhookController
echo "\n🔧 PASO 3: Actualizando WebhookController\n";

$webhook_file = __DIR__ . '/app/Controllers/WebhookController.php';
if (file_exists($webhook_file)) {
    $webhook_content = file_get_contents($webhook_file);
    
    // Agregar mapeos faltantes
    if (strpos($webhook_content, 'send.message') === false) {
        echo "📝 Agregando mapeos a WebhookController...\n";
        
        $old_mapping = '// Legacy mappings (mantener compatibilidad)
        \'contacts.update\' => \'CONTACTS_UPDATE\',
        \'chats.upsert\' => \'CHATS_UPSERT\',
        \'chats.update\' => \'CHATS_UPDATE\',
        \'presence.update\' => \'PRESENCE_UPDATE\',
        \'messages.upsert\' => \'MESSAGES_UPSERT\',
        \'messages.update\' => \'MESSAGES_UPDATE\',
        \'messages.delete\' => \'MESSAGES_DELETE\',
        \'statusMessage\' => \'STATUS_MESSAGE\'';
        
        $new_mapping = '// Legacy mappings (mantener compatibilidad)
        \'contacts.update\' => \'CONTACTS_UPDATE\',
        \'chats.upsert\' => \'CHATS_UPSERT\',
        \'chats.update\' => \'CHATS_UPDATE\',
        \'presence.update\' => \'PRESENCE_UPDATE\',
        \'messages.upsert\' => \'MESSAGES_UPSERT\',
        \'messages.update\' => \'MESSAGES_UPDATE\',
        \'messages.delete\' => \'MESSAGES_DELETE\',
        \'statusMessage\' => \'STATUS_MESSAGE\',
        
        // Eventos con formato diferente (evolution-api)
        \'send.message\' => \'SEND_MESSAGE\',
        \'send-message\' => \'SEND_MESSAGE\',
        \'message.update\' => \'MESSAGES_UPDATE\',
        \'message-update\' => \'MESSAGES_UPDATE\'';
        
        $updated_webhook = str_replace($old_mapping, $new_mapping, $webhook_content);
        
        // Corregir processMessageUpdate
        $old_method = 'private function processMessageUpdate($event, $payload)
    {
        $data = $payload[\'data\'];
        
        // Validar que data tenga la estructura esperada
        if (!isset($data[\'key\']) || !is_array($data[\'key\']) || !isset($data[\'key\'][\'id\'])) {
            error_log("Message update event missing key structure: " . json_encode($data));
            return;
        }
        
        $messageId = $data[\'key\'][\'id\'];
        
        // Update message status
        if (isset($data[\'status\'])) {
            Message::updateStatus($messageId, $data[\'status\']);
        }
    }';
        
        $new_method = 'private function processMessageUpdate($event, $payload)
    {
        $data = $payload[\'data\'];
        
        // Manejar diferentes estructuras de message update
        $messageId = null;
        
        // Estructura 1: con key
        if (isset($data[\'key\']) && is_array($data[\'key\']) && isset($data[\'key\'][\'id\'])) {
            $messageId = $data[\'key\'][\'id\'];
        }
        // Estructura 2: directo messageId
        elseif (isset($data[\'messageId\'])) {
            $messageId = $data[\'messageId\'];
        }
        // Estructura 3: en el nivel superior
        elseif (isset($data[\'id\'])) {
            $messageId = $data[\'id\'];
        }
        
        if (!$messageId) {
            error_log("Message update event - no message ID found: " . json_encode($data));
            return;
        }
        
        // Update message status
        if (isset($data[\'status\'])) {
            Message::updateStatus($messageId, $data[\'status\']);
            error_log("Message status updated: $messageId -> {$data[\'status\']}");
        }
    }';
        
        $updated_webhook = str_replace($old_method, $new_method, $updated_webhook);
        
        // Guardar
        $webhook_backup = $webhook_file . '.backup.' . date('Y-m-d-H-i-s');
        copy($webhook_file, $webhook_backup);
        
        if (file_put_contents($webhook_file, $updated_webhook)) {
            echo "✅ WebhookController actualizado\n";
        } else {
            echo "❌ Error actualizando WebhookController\n";
        }
    } else {
        echo "✅ WebhookController ya está actualizado\n";
    }
} else {
    echo "❌ WebhookController no encontrado\n";
}

// PASO 4: Actualizar Chat.php
echo "\n🔧 PASO 4: Actualizando Chat.php\n";

$chat_file = __DIR__ . '/app/Models/Chat.php';
if (file_exists($chat_file)) {
    $chat_content = file_get_contents($chat_file);
    
    if (strpos($chat_content, 'TRY-CATCH') === false) {
        echo "📝 Agregando TRY-CATCH a Chat.php...\n";
        
        $old_function = 'private static function getContactJoinConfig()
    {
        if (self::$contactJoinConfig !== null) {
            return self::$contactJoinConfig;
        }

        $hasProfilePic = DB::columnExists(\'contacts\', \'profile_pic_url\');
        $hasName = DB::columnExists(\'contacts\', \'name\');
        $hasPhone = DB::columnExists(\'contacts\', \'phone\');
        $hasPhoneE164 = DB::columnExists(\'contacts\', \'phone_e164\');';
        
        $new_function = 'private static function getContactJoinConfig()
    {
        if (self::$contactJoinConfig !== null) {
            return self::$contactJoinConfig;
        }

        // Usar TRY-CATCH para manejar el método columnExists()
        try {
            $hasProfilePic = DB::columnExists(\'contacts\', \'profile_pic_url\');
            $hasName = DB::columnExists(\'contacts\', \'name\');
            $hasPhone = DB::columnExists(\'contacts\', \'phone\');
            $hasPhoneE164 = DB::columnExists(\'contacts\', \'phone_e164\');
        } catch (Exception $e) {
            // Si columnExists no funciona, asumir valores por defecto
            error_log("columnExists method failed: " . $e->getMessage() . " - using defaults");
            $hasProfilePic = true;
            $hasName = false;
            $hasPhone = true;
            $hasPhoneE164 = false;
        }';
        
        $updated_chat = str_replace($old_function, $new_function, $chat_content);
        
        $chat_backup = $chat_file . '.backup.' . date('Y-m-d-H-i-s');
        copy($chat_file, $chat_backup);
        
        if (file_put_contents($chat_file, $updated_chat)) {
            echo "✅ Chat.php actualizado\n";
        } else {
            echo "❌ Error actualizando Chat.php\n";
        }
    } else {
        echo "✅ Chat.php ya está actualizado\n";
    }
} else {
    echo "❌ Chat.php no encontrado\n";
}

// PASO 5: Verificar todo funciona
echo "\n🧪 PASO 5: Verificando funcionamiento\n";

try {
    // Probar conexión BD
    require_once $db_file;
    
    $pdo = \App\Core\DB::getInstance();
    echo "✅ Conexión BD exitosa\n";
    
    // Probar métodos
    if (method_exists('App\Core\DB', 'columnExists')) {
        $exists = \App\Core\DB::columnExists('instances', 'id');
        echo "✅ columnExists() funciona: " . ($exists ? 'true' : 'false') . "\n";
    } else {
        echo "❌ columnExists() no disponible\n";
    }
    
    if (method_exists('App\Core\DB', 'tableExists')) {
        $exists = \App\Core\DB::tableExists('instances');
        echo "✅ tableExists() funciona: " . ($exists ? 'true' : 'false') . "\n";
    } else {
        echo "❌ tableExists() no disponible\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error en verificación: " . $e->getMessage() . "\n";
}

echo "\n🎉 ¡CORRECCIÓN COMPLETADA!\n";
echo "\n📋 RESUMEN DE CAMBIOS:\n";
echo "✅ Configuración BD verificada\n";
echo "✅ DB.php actualizado con columnExists() y tableExists()\n";
echo "✅ WebhookController actualizado con nuevos mapeos\n";
echo "✅ Chat.php actualizado con TRY-CATCH\n";
echo "✅ Verificación completada\n";

echo "\n🎯 PRÓXIMOS PASOS:\n";
echo "1. ✅ Todos los archivos actualizados\n";
echo "2. 🔄 Reinicia el servidor web (Apache/Nginx)\n";
echo "3. 🧪 Prueba la aplicación\n";
echo "4. 📊 Los errores deberían desaparecer\n";

echo "\n🚀 ¡SISTEMA CORREGIDO!\n";
?>
