<?php
// Enhanced Chat Model - Versión mejorada con resolución de contactos
namespace App\Models;

use App\Core\DB;

class EnhancedChat
{
    /**
     * Obtiene chats con nombres de contactos resueltos
     */
    public static function getChatsByInstance($instanceId, $page = 1, $limit = 25)
    {
        $offset = ($page - 1) * $limit;
        
        // Obtener chats básicos
        $chats = DB::fetchAll("
            SELECT c.*,
                   (SELECT COUNT(*) FROM messages m WHERE m.chat_id = c.id) as message_count,
                   (SELECT MAX(m.ts) FROM messages m WHERE m.chat_id = c.id) as last_message_time
            FROM chats c
            WHERE c.instance_id = ?
            ORDER BY c.last_message_at DESC
            LIMIT ? OFFSET ?
        ", [$instanceId, $limit, $offset]);
        
        // Resolver nombres de contactos para cada chat
        foreach ($chats as &$chat) {
            $contactInfo = self::resolveContactName($instanceId, $chat['remote_jid'], $chat);
            $chat['contact_name'] = $contactInfo['display_name'];
            $chat['contact_info'] = $contactInfo;
        }
        
        return $chats;
    }
    
    /**
     * Obtiene chat específico con contacto resuelto
     */
    public static function findById($id)
    {
        $chat = DB::fetch("
            SELECT c.*,
                   (SELECT COUNT(*) FROM messages m WHERE m.chat_id = c.id) as message_count
            FROM chats c
            WHERE c.id = ?
        ", [$id]);
        
        if ($chat) {
            $contactInfo = self::resolveContactName($chat['instance_id'], $chat['remote_jid'], $chat);
            $chat['contact_name'] = $contactInfo['display_name'];
            $chat['contact_info'] = $contactInfo;
        }
        
        return $chat;
    }
    
    /**
     * Resuelve nombre de contacto usando múltiples estrategias
     */
    private static function resolveContactName($instanceId, $remoteJid, $chat = null)
    {
        $contactInfo = [
            'display_name' => $remoteJid,
            'pushname' => null,
            'profile_pic_url' => null,
            'is_group' => strpos($remoteJid, '@g.us') !== false,
            'source' => 'jid' // fuente de la información
        ];
        
        // Estrategia 1: Usar nombre del chat si está disponible
        if ($chat && !empty($chat['title']) && $chat['title'] !== $remoteJid) {
            $contactInfo['display_name'] = $chat['title'];
            $contactInfo['source'] = 'chat_title';
            return $contactInfo;
        }
        
        // Estrategia 2: Buscar en tabla contacts
        $contactInfo = self::resolveFromContactsTable($instanceId, $remoteJid, $contactInfo);
        
        // Estrategia 3: Buscar en mensajes recientes
        if ($contactInfo['source'] === 'jid') {
            $contactInfo = self::resolveFromRecentMessages($instanceId, $remoteJid, $contactInfo);
        }
        
        // Estrategia 4: Formatear número si es individual
        if ($contactInfo['source'] === 'jid' && !$contactInfo['is_group']) {
            $contactInfo['display_name'] = self::formatPhoneNumber($remoteJid);
            $contactInfo['source'] = 'formatted_phone';
        }
        
        return $contactInfo;
    }
    
    /**
     * Resuelve desde tabla contacts
     */
    private static function resolveFromContactsTable($instanceId, $remoteJid, $contactInfo)
    {
        try {
            // Intentar diferentes consultas según la estructura de la tabla
            $queries = [
                "SELECT pushname, profile_pic_url FROM contacts WHERE instance_id = ? AND remote_jid = ?",
                "SELECT name as pushname, profile_pic_url FROM contacts WHERE instance_id = ? AND remote_jid = ?",
                "SELECT contact_name as pushname, profile_pic_url FROM contacts WHERE instance_id = ? AND remote_jid = ?"
            ];
            
            foreach ($queries as $query) {
                $result = DB::fetch($query, [$instanceId, $remoteJid]);
                if ($result && !empty($result['pushname'])) {
                    $contactInfo['display_name'] = $result['pushname'];
                    $contactInfo['pushname'] = $result['pushname'];
                    $contactInfo['profile_pic_url'] = $result['profile_pic_url'];
                    $contactInfo['source'] = 'contacts_table';
                    break;
                }
            }
        } catch (Exception $e) {
            error_log("Error resolving from contacts: " . $e->getMessage());
        }
        
        return $contactInfo;
    }
    
    /**
     * Resuelve desde mensajes recientes
     */
    private static function resolveFromRecentMessages($instanceId, $remoteJid, $contactInfo)
    {
        try {
            $messages = DB::fetchAll("
                SELECT raw_json 
                FROM messages 
                WHERE instance_id = ? AND remote_jid = ? 
                ORDER BY ts DESC 
                LIMIT 3
            ", [$instanceId, $remoteJid]);
            
            foreach ($messages as $message) {
                $rawJson = json_decode($message['raw_json'], true);
                
                // Buscar en diferentes campos del JSON
                $nameFields = [
                    'data.pushname',
                    'data.notifyName',
                    'data.senderName',
                    'data.participantName'
                ];
                
                foreach ($nameFields as $field) {
                    $value = self::getNestedValue($rawJson, $field);
                    if (!empty($value)) {
                        $contactInfo['display_name'] = $value;
                        $contactInfo['pushname'] = $value;
                        $contactInfo['source'] = 'recent_messages';
                        return $contactInfo;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error resolving from messages: " . $e->getMessage());
        }
        
        return $contactInfo;
    }
    
    /**
     * Obtiene valor anidado de array
     */
    private static function getNestedValue($array, $key)
    {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    /**
     * Formatea número telefónico
     */
    private static function formatPhoneNumber($remoteJid)
    {
        // Eliminar sufijos de WhatsApp
        $phone = preg_replace('/@(s\.whatsapp\.net|c\.us|g\.us)$/', '', $remoteJid);
        
        // Formatear según longitud
        if (strlen($phone) >= 10) {
            // Venezuela: +58 414 240 2478
            if (strpos($phone, '58') === 0 && strlen($phone) === 12) {
                return '+' . substr($phone, 0, 2) . ' ' . substr($phone, 2, 3) . ' ' . substr($phone, 5, 3) . ' ' . substr($phone, 8);
            }
            // Formato local: 0414-240-2478
            if (strlen($phone) === 10) {
                return substr($phone, 0, 4) . '-' . substr($phone, 4, 3) . '-' . substr($phone, 7);
            }
        }
        
        return $phone;
    }
    
    /**
     * Actualiza o crea chat con información de contacto
     */
    public static function createOrUpdate($data)
    {
        // Verificar si ya existe
        $existing = DB::fetch("
            SELECT id FROM chats 
            WHERE instance_id = ? AND remote_jid = ?
        ", [$data['instance_id'], $data['remote_jid']]);
        
        if ($existing) {
            // Actualizar
            $sql = "
                UPDATE chats SET 
                    title = ?, 
                    unread_messages = COALESCE(?, unread_messages),
                    last_message_at = ?,
                    updated_at = NOW()
                WHERE id = ?
            ";
            
            DB::q($sql, [
                $data['title'] ?? null,
                $data['unread_messages'] ?? null,
                $data['last_message_at'] ?? date('Y-m-d H:i:s'),
                $existing['id']
            ]);
            
            return $existing['id'];
        } else {
            // Insertar
            $sql = "
                INSERT INTO chats (
                    instance_id, remote_jid, title, is_group, 
                    unread_messages, last_message_at, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ";
            
            DB::q($sql, [
                $data['instance_id'],
                $data['remote_jid'],
                $data['title'] ?? null,
                $data['is_group'] ?? 0,
                $data['unread_messages'] ?? 0,
                $data['last_message_at'] ?? date('Y-m-d H:i:s')
            ]);
            
            return DB::lastInsertId();
        }
    }
    
    /**
     * Incrementa mensajes no leídos
     */
    public static function incrementUnread($chatId)
    {
        DB::q("
            UPDATE chats 
            SET unread_messages = unread_messages + 1, updated_at = NOW() 
            WHERE id = ?
        ", [$chatId]);
    }
    
    /**
     * Marca mensajes como leídos
     */
    public static function markAsRead($chatId)
    {
        DB::q("
            UPDATE chats 
            SET unread_messages = 0, updated_at = NOW() 
            WHERE id = ?
        ", [$chatId]);
    }
}
?>
