<?php
// Script para corregir TODOS los problemas críticos
echo "🚨 CORRIGIENDO TODOS LOS PROBLEMAS CRÍTICOS...\n\n";

// PASO 1: Verificar y crear configuración BD
echo "📊 PASO 1: Configuración de Base de Datos\n";

$config_file = __DIR__ . '/config.php';
if (!file_exists($config_file)) {
    echo "📝 Creando config.php...\n";
    
    $config_content = '<?php
// Configuración de Base de Datos - ACTUALIZAR CON TUS CREDENCIALES REALES
define("DB_HOST", "localhost");
define("DB_NAME", "grupoecc_evoapp");
define("DB_USER", "grupoecc_evoapp");
define("DB_PASS", "CAMBIA_ESTA_PASSWORD");

// Configuración de la Aplicación
define("APP_URL", "https://camcam.com.ve/evoappws/");
define("APP_NAME", "EVOAPP");

// Zona horaria
date_default_timezone_set("America/Bogota");
?>';
    
    if (file_put_contents($config_file, $config_content)) {
        echo "✅ config.php creado\n";
        echo "⚠️ ¡IMPORTANTE! Edita el archivo con tus credenciales reales\n";
    } else {
        echo "❌ Error al crear config.php\n";
    }
} else {
    echo "✅ config.php ya existe\n";
}

// PASO 2: Actualizar clase DB con métodos faltantes
echo "\n🔧 PASO 2: Actualizando clase DB\n";

$db_file = __DIR__ . '/app/Core/DB.php';
$current_content = file_get_contents($db_file);

if (strpos($current_content, 'function columnExists') === false) {
    echo "📝 Agregando métodos faltantes a DB.php...\n";
    
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
    
    // Insertar métodos antes del cierre de la clase
    $pattern = '/(}\s*;\s*\?>\s*$)/';
    $replacement = $methods_to_add . '\n$1';
    
    if (preg_match($pattern, $current_content)) {
        $updated_content = preg_replace($pattern, $replacement, $current_content);
        
        // Backup
        $backup_file = $db_file . '.backup.' . date('Y-m-d-H-i-s');
        copy($db_file, $backup_file);
        
        if (file_put_contents($db_file, $updated_content)) {
            echo "✅ DB.php actualizado con métodos faltantes\n";
        } else {
            echo "❌ Error al actualizar DB.php\n";
        }
    }
} else {
    echo "✅ DB.php ya tiene los métodos\n";
}

// PASO 3: Corregir WebhookController
echo "\n🔧 PASO 3: Corrigiendo WebhookController\n";

$webhook_file = __DIR__ . '/app/Controllers/WebhookController.php';
$webhook_content = file_get_contents($webhook_file);

// Verificar si tiene las correcciones
if (strpos($webhook_content, 'send.message') === false) {
    echo "📝 Actualizando WebhookController...\n";
    
    // Agregar mapeos faltantes
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
    
    // Backup y actualización
    $webhook_backup = $webhook_file . '.backup.' . date('Y-m-d-H-i-s');
    copy($webhook_file, $webhook_backup);
    
    if (file_put_contents($webhook_file, $updated_webhook)) {
        echo "✅ WebhookController actualizado\n";
    } else {
        echo "❌ Error al actualizar WebhookController\n";
    }
} else {
    echo "✅ WebhookController ya está actualizado\n";
}

// PASO 4: Corregir Chat.php
echo "\n🔧 PASO 4: Corrigiendo Chat.php\n";

$chat_file = __DIR__ . '/app/Models/Chat.php';
$chat_content = file_get_contents($chat_file);

if (strpos($chat_content, 'TRY-CATCH') === false) {
    echo "📝 Actualizando Chat.php con TRY-CATCH...\n";
    
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
    
    // Backup y actualización
    $chat_backup = $chat_file . '.backup.' . date('Y-m-d-H-i-s');
    copy($chat_file, $chat_backup);
    
    if (file_put_contents($chat_file, $updated_chat)) {
        echo "✅ Chat.php actualizado\n";
    } else {
        echo "❌ Error al actualizar Chat.php\n";
    }
} else {
    echo "✅ Chat.php ya está actualizado\n";
}

echo "\n🎉 ¡CORRECCIONES COMPLETADAS!\n";
echo "\n📋 PASOS FINALES:\n";
echo "1. ✅ Edita config.php con tus credenciales REALES de BD\n";
echo "2. ✅ Reinicia el servidor web (Apache/Nginx)\n";
echo "3. ✅ Limpia caches si es necesario\n";
echo "4. ✅ Prueba la aplicación\n";
echo "\n🚀 Los errores críticos deberían estar resueltos ahora.\n";
?>
