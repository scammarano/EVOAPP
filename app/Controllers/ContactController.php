<?php

namespace App\Controllers;

use App\Models\Contact;
use App\Models\Instance;
use App\Models\User;

class ContactController
{
    private $contactModel;
    private $instanceModel;
    
    public function __construct()
    {
        $this->contactModel = new Contact();
        $this->instanceModel = new Instance();
    }
    
    /**
     * Listar contactos
     */
    public function index()
    {
        try {
            $search = $_GET['search'] ?? '';
            $page = $_GET['page'] ?? 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $contacts = $this->contactModel->getAll($limit, $offset, $search);
            
            include __DIR__ . '/../Views/contacts/index_new.php';
        } catch (\Exception $e) {
            error_log("Error in contact index: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar los contactos';
            header('Location: /contacts');
            exit;
        }
    }
    
    /**
     * Extraer contactos de chats
     */
    public function extractFromChats()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }
            
            // Validar CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new \Exception('Token CSRF inválido');
            }
            
            // Obtener instancias activas
            $instances = $this->instanceModel->getAll();
            $totalExtracted = 0;
            
            foreach ($instances as $instance) {
                $extracted = $this->extractContactsFromInstanceChats($instance);
                $totalExtracted += $extracted;
            }
            
            $_SESSION['success'] = "Se extrajeron {$totalExtracted} contactos de los chats exitosamente";
            
        } catch (\Exception $e) {
            error_log("Error extracting from chats: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: /contacts');
        exit;
    }
    
    /**
     * Extraer contactos de grupos
     */
    public function extractFromGroups()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }
            
            // Validar CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new \Exception('Token CSRF inválido');
            }
            
            // Obtener instancias activas
            $instances = $this->instanceModel->getAll();
            $totalExtracted = 0;
            
            foreach ($instances as $instance) {
                $extracted = $this->extractContactsFromInstanceGroups($instance);
                $totalExtracted += $extracted;
            }
            
            $_SESSION['success'] = "Se extrajeron {$totalExtracted} contactos de los grupos exitosamente";
            
        } catch (\Exception $e) {
            error_log("Error extracting from groups: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: /contacts');
        exit;
    }
    
    /**
     * Extraer contactos de chats de una instancia específica
     */
    private function extractContactsFromInstanceChats($instance)
    {
        try {
            // Llamar a Evolution API para obtener chats
            $apiUrl = EVO_BASE_URL . '/chat/fetchAll/' . $instance['slug'];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $instance['api_key']
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new \Exception('Error al obtener chats de la API');
            }
            
            $chats = json_decode($response, true);
            $extractedCount = 0;
            
            if (isset($chats['response']) && is_array($chats['response'])) {
                foreach ($chats['response'] as $chat) {
                    // Extraer información del contacto
                    $contactData = $this->parseChatContact($chat);
                    
                    if ($contactData) {
                        // Guardar contacto si no existe
                        if ($this->contactModel->createIfNotExists($contactData)) {
                            $extractedCount++;
                        }
                    }
                }
            }
            
            return $extractedCount;
            
        } catch (\Exception $e) {
            error_log("Error extracting from instance chats: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Extraer contactos de grupos de una instancia específica
     */
    private function extractContactsFromInstanceGroups($instance)
    {
        try {
            // Llamar a Evolution API para obtener grupos
            $apiUrl = EVO_BASE_URL . '/group/fetchAll/' . $instance['slug'];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $instance['api_key']
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new \Exception('Error al obtener grupos de la API');
            }
            
            $groups = json_decode($response, true);
            $extractedCount = 0;
            
            if (isset($groups['response']) && is_array($groups['response'])) {
                foreach ($groups['response'] as $group) {
                    // Extraer miembros del grupo
                    if (isset($group['participants']) && is_array($group['participants'])) {
                        foreach ($group['participants'] as $participant) {
                            $contactData = $this->parseGroupParticipant($participant, $group['groupName'] ?? 'Unknown Group');
                            
                            if ($contactData) {
                                // Guardar contacto si no existe
                                if ($this->contactModel->createIfNotExists($contactData)) {
                                    $extractedCount++;
                                }
                            }
                        }
                    }
                }
            }
            
            return $extractedCount;
            
        } catch (\Exception $e) {
            error_log("Error extracting from instance groups: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Parsear información de contacto desde chat
     */
    private function parseChatContact($chat)
    {
        try {
            $phone = $chat['remoteJid'] ?? '';
            
            // Limpiar número de teléfono (quitar @s.whatsapp.net)
            $phone = str_replace('@s.whatsapp.net', '', $phone);
            $phone = str_replace('@g.us', '', $phone);
            
            if (empty($phone)) {
                return null;
            }
            
            // Formatear número E164
            $phone = $this->formatPhoneNumber($phone);
            
            return [
                'name' => $chat['pushname'] ?? $chat['name'] ?? null,
                'phone' => $phone,
                'phone_e164' => $phone,
                'profile_pic_url' => $chat['profilePicUrl'] ?? null,
                'source' => 'chat_extraction',
                'instance_id' => $chat['instance_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            error_log("Error parsing chat contact: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Parsear información de participante de grupo
     */
    private function parseGroupParticipant($participant, $groupName)
    {
        try {
            $phone = $participant['id'] ?? '';
            
            // Limpiar número de teléfono
            $phone = str_replace('@s.whatsapp.net', '', $phone);
            $phone = str_replace('@g.us', '', $phone);
            
            if (empty($phone)) {
                return null;
            }
            
            // Formatear número E164
            $phone = $this->formatPhoneNumber($phone);
            
            return [
                'name' => $participant['pushname'] ?? $participant['name'] ?? null,
                'phone' => $phone,
                'phone_e164' => $phone,
                'profile_pic_url' => $participant['profilePicUrl'] ?? null,
                'source' => 'group_extraction',
                'group_name' => $groupName,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            error_log("Error parsing group participant: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Formatear número de teléfono a formato E164
     */
    private function formatPhoneNumber($phone)
    {
        // Eliminar caracteres no numéricos excepto +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Si no empieza con +, agregar código de país (Venezuela por defecto)
        if (!str_starts_with($phone, '+')) {
            if (strlen($phone) === 10) {
                $phone = '+58' . $phone;
            } else {
                $phone = '+' . $phone;
            }
        }
        
        return $phone;
    }
    
    /**
     * Crear nuevo contacto
     */
    public function create()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->store();
                return;
            }
            
            include __DIR__ . '/../Views/contacts/create.php';
        } catch (\Exception $e) {
            error_log("Error in contact create: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el formulario';
            header('Location: /contacts');
            exit;
        }
    }
    
    /**
     * Guardar contacto
     */
    private function store()
    {
        try {
            // Validar CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new \Exception('Token CSRF inválido');
            }
            
            // Validar datos requeridos
            if (empty($_POST['phone'])) {
                throw new \Exception('El teléfono es requerido');
            }
            
            $contactData = [
                'name' => trim($_POST['name'] ?? ''),
                'phone' => trim($_POST['phone']),
                'email' => trim($_POST['email'] ?? ''),
                'company' => trim($_POST['company'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'notes' => trim($_POST['notes'] ?? ''),
                'source' => 'manual'
            ];
            
            // Formatear teléfono
            $contactData['phone'] = $this->formatPhoneNumber($contactData['phone']);
            $contactData['phone_e164'] = $contactData['phone'];
            
            if ($this->contactModel->create($contactData)) {
                $_SESSION['success'] = 'Contacto creado correctamente';
                header('Location: /contacts');
                exit;
            } else {
                throw new \Exception('Error al crear el contacto');
            }
            
        } catch (\Exception $e) {
            error_log("Error storing contact: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: /contacts/create');
            exit;
        }
    }
    
    /**
     * Editar contacto
     */
    public function edit($id)
    {
        try {
            $contact = $this->contactModel->getById($id);
            
            if (!$contact) {
                $_SESSION['error'] = 'Contacto no encontrado';
                header('Location: /contacts');
                exit;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->update($id);
                return;
            }
            
            include __DIR__ . '/../Views/contacts/edit.php';
        } catch (\Exception $e) {
            error_log("Error in contact edit: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el contacto';
            header('Location: /contacts');
            exit;
        }
    }
    
    /**
     * Actualizar contacto
     */
    private function update($id)
    {
        try {
            // Validar CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new \Exception('Token CSRF inválido');
            }
            
            $contactData = [
                'name' => trim($_POST['name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'company' => trim($_POST['company'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'notes' => trim($_POST['notes'] ?? '')
            ];
            
            if ($this->contactModel->update($id, $contactData)) {
                $_SESSION['success'] = 'Contacto actualizado correctamente';
                header('Location: /contacts');
                exit;
            } else {
                throw new \Exception('Error al actualizar el contacto');
            }
            
        } catch (\Exception $e) {
            error_log("Error updating contact: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header("Location: /contacts/edit/{$id}");
            exit;
        }
    }
    
    /**
     * Eliminar contacto
     */
    public function delete($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }
            
            // Validar CSRF
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new \Exception('Token CSRF inválido');
            }
            
            if ($this->contactModel->delete($id)) {
                $_SESSION['success'] = 'Contacto eliminado correctamente';
            } else {
                throw new \Exception('Error al eliminar el contacto');
            }
            
        } catch (\Exception $e) {
            error_log("Error deleting contact: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: /contacts');
        exit;
    }
}