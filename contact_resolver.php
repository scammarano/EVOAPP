<?php
// Contact Resolver - Mejora la resolución de nombres de contactos
class ContactResolver
{
    private static $cache = [];
    private static $cacheTimeout = 300; // 5 minutos
    
    /**
     * Obtiene información del contacto con múltiples estrategias
     */
    public static function getContactInfo($instanceId, $remoteJid)
    {
        $cacheKey = "{$instanceId}_{$remoteJid}";
        
        // Verificar cache
        if (isset(self::$cache[$cacheKey]) && (time() - self::$cache[$cacheKey]['timestamp']) < self::$cacheTimeout) {
            return self::$cache[$cacheKey]['data'];
        }
        
        $contactInfo = [
            'name' => null,
            'pushname' => null,
            'phone' => null,
            'profile_pic_url' => null,
            'is_group' => strpos($remoteJid, '@g.us') !== false,
            'display_name' => $remoteJid // fallback
        ];
        
        // Estrategia 1: Buscar en tabla contacts (si existe)
        $contactInfo = self::resolveFromContactsTable($instanceId, $remoteJid, $contactInfo);
        
        // Estrategia 2: Buscar en mensajes recientes
        if (empty($contactInfo['name']) && empty($contactInfo['pushname'])) {
            $contactInfo = self::resolveFromRecentMessages($instanceId, $remoteJid, $contactInfo);
        }
        
        // Estrategia 3: Extraer de chats
        if (empty($contactInfo['name']) && empty($contactInfo['pushname'])) {
            $contactInfo = self::resolveFromChats($instanceId, $remoteJid, $contactInfo);
        }
        
        // Estrategia 4: Formatear número telefónico
        if (empty($contactInfo['name']) && empty($contactInfo['pushname']) && !$contactInfo['is_group']) {
            $contactInfo['display_name'] = self::formatPhoneNumber($remoteJid);
        }
        
        // Guardar en cache
        self::$cache[$cacheKey] = [
            'data' => $contactInfo,
            'timestamp' => time()
        ];
        
        return $contactInfo;
    }
    
    /**
     * Resuelve desde tabla contacts
     */
    private static function resolveFromContactsTable($instanceId, $remoteJid, $contactInfo)
    {
        try {
            // Intentar diferentes columnas que puedan existir
            $queries = [
                "SELECT pushname, profile_pic_url, phone FROM contacts WHERE instance_id = ? AND remote_jid = ?",
                "SELECT name, profile_pic_url, phone FROM contacts WHERE instance_id = ? AND remote_jid = ?",
                "SELECT contact_name, profile_pic_url, phone FROM contacts WHERE instance_id = ? AND remote_jid = ?"
            ];
            
            foreach ($queries as $query) {
                $result = DB::fetch($query, [$instanceId, $remoteJid]);
                if ($result) {
                    $contactInfo['pushname'] = $result['pushname'] ?? $result['name'] ?? $result['contact_name'] ?? null;
                    $contactInfo['profile_pic_url'] = $result['profile_pic_url'] ?? null;
                    $contactInfo['phone'] = $result['phone'] ?? null;
                    
                    if (!empty($contactInfo['pushname'])) {
                        $contactInfo['name'] = $contactInfo['pushname'];
                        $contactInfo['display_name'] = $contactInfo['pushname'];
                    }
                    break;
                }
            }
        } catch (Exception $e) {
            error_log("Error resolving from contacts table: " . $e->getMessage());
        }
        
        return $contactInfo;
    }
    
    /**
     * Resuelve desde mensajes recientes
     */
    private static function resolveFromRecentMessages($instanceId, $remoteJid, $contactInfo)
    {
        try {
            // Buscar mensajes recientes de este contacto
            $messages = DB::fetchAll("
                SELECT raw_json, body_text, participant_jid 
                FROM messages 
                WHERE instance_id = ? AND remote_jid = ? 
                ORDER BY ts DESC 
                LIMIT 5
            ", [$instanceId, $remoteJid]);
            
            foreach ($messages as $message) {
                // Extraer del JSON del mensaje
                $rawJson = json_decode($message['raw_json'], true);
                
                // Buscar pushname en diferentes lugares del JSON
                $pushname = null;
                
                if (isset($rawJson['data']['pushname'])) {
                    $pushname = $rawJson['data']['pushname'];
                } elseif (isset($rawJson['data']['notifyName'])) {
                    $pushname = $rawJson['data']['notifyName'];
                } elseif (isset($rawJson['data']['participant'])) {
                    // Para grupos, buscar participant
                    $participantJid = $rawJson['data']['participant'];
                    $pushname = self::extractParticipantName($rawJson, $participantJid);
                }
                
                if (!empty($pushname)) {
                    $contactInfo['pushname'] = $pushname;
                    $contactInfo['name'] = $pushname;
                    $contactInfo['display_name'] = $pushname;
                    break;
                }
            }
        } catch (Exception $e) {
            error_log("Error resolving from recent messages: " . $e->getMessage());
        }
        
        return $contactInfo;
    }
    
    /**
     * Resuelve desde tabla chats
     */
    private static function resolveFromChats($instanceId, $remoteJid, $contactInfo)
    {
        try {
            $chat = DB::fetch("
                SELECT title, name 
                FROM chats 
                WHERE instance_id = ? AND remote_jid = ?
            ", [$instanceId, $remoteJid]);
            
            if ($chat) {
                $chatName = $chat['title'] ?? $chat['name'] ?? null;
                if (!empty($chatName) && $chatName !== $remoteJid) {
                    $contactInfo['name'] = $chatName;
                    $contactInfo['display_name'] = $chatName;
                }
            }
        } catch (Exception $e) {
            error_log("Error resolving from chats: " . $e->getMessage());
        }
        
        return $contactInfo;
    }
    
    /**
     * Extrae nombre de participante de grupo
     */
    private static function extractParticipantName($rawJson, $participantJid)
    {
        // Buscar en diferentes estructuras posibles
        if (isset($rawJson['data']['participants'])) {
            foreach ($rawJson['data']['participants'] as $participant) {
                if (isset($participant['userJid']) && $participant['userJid'] === $participantJid) {
                    return $participant['name'] ?? null;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Formatea número telefónico para display
     */
    private static function formatPhoneNumber($remoteJid)
    {
        // Eliminar sufijo de WhatsApp
        $phone = str_replace(['@s.whatsapp.net', '@c.us', '@g.us'], '', $remoteJid);
        
        // Formatear como número de teléfono
        if (strlen($phone) >= 10) {
            // Formato internacional: +58 414 240 2478
            if (strpos($phone, '58') === 0) {
                return '+' . substr($phone, 0, 2) . ' ' . substr($phone, 2, 3) . ' ' . substr($phone, 5, 3) . ' ' . substr($phone, 8);
            }
            // Formato local: 0414-240-2478
            return substr($phone, 0, 4) . '-' . substr($phone, 4, 3) . '-' . substr($phone, 7);
        }
        
        return $phone;
    }
    
    /**
     * Actualiza información de contacto
     */
    public static function updateContactInfo($instanceId, $remoteJid, $contactData)
    {
        try {
            // Verificar si existe tabla contacts
            if (!self::tableExists('contacts')) {
                return false;
            }
            
            $pushname = $contactData['pushname'] ?? $contactData['name'] ?? null;
            $profilePicUrl = $contactData['profile_pic_url'] ?? null;
            
            if (empty($pushname)) {
                return false;
            }
            
            // Intentar actualizar o insertar
            $existing = DB::fetch("SELECT id FROM contacts WHERE instance_id = ? AND remote_jid = ?", [$instanceId, $remoteJid]);
            
            if ($existing) {
                // Actualizar
                DB::q("
                    UPDATE contacts 
                    SET pushname = ?, profile_pic_url = ?, updated_at = NOW() 
                    WHERE instance_id = ? AND remote_jid = ?
                ", [$pushname, $profilePicUrl, $instanceId, $remoteJid]);
            } else {
                // Insertar
                DB::q("
                    INSERT INTO contacts (instance_id, remote_jid, pushname, profile_pic_url, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ", [$instanceId, $remoteJid, $pushname, $profilePicUrl]);
            }
            
            // Limpiar cache
            $cacheKey = "{$instanceId}_{$remoteJid}";
            unset(self::$cache[$cacheKey]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error updating contact info: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si existe tabla
     */
    private static function tableExists($table)
    {
        try {
            if (method_exists('App\Core\DB', 'tableExists')) {
                return DB::tableExists($table);
            }
            
            // Fallback
            $result = DB::fetch("
                SELECT COUNT(*) as count
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = ?
            ", [$table]);
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Limpia cache
     */
    public static function clearCache()
    {
        self::$cache = [];
    }
    
    /**
     * Obtiene estadísticas de resolución
     */
    public static function getResolutionStats()
    {
        return [
            'cache_size' => count(self::$cache),
            'cache_timeout' => self::$cacheTimeout,
            'resolved_contacts' => array_filter(self::$cache, function($item) {
                return !empty($item['data']['name']) || !empty($item['data']['pushname']);
            })
        ];
    }
}
?>
