<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Instance;
use App\Models\Contact;
use App\Models\ContactList;

class ContactsSyncController
{
    public function index()
    {
        if (!Auth::hasPermission('contacts.view')) {
            View::set('error', 'No tienes permisos para ver sincronización de contactos');
            View::render('error/403');
            return;
        }

        $instanceSlug = $_GET['instance'] ?? '';
        if (!$instanceSlug) {
            header('Location: index.php?r=dashboard/index');
            exit;
        }
        
        $instance = Instance::findBySlug($instanceSlug);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            View::set('error', 'Instancia no encontrada o sin permisos');
            View::render('error/404');
            return;
        }

        View::set('instance', $instance);
        View::render('contacts/sync/index');
    }

    public function syncContacts()
    {
        header('Content-Type: application/json');
        
        if (!Auth::hasPermission('contacts.manage')) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $instanceSlug = $_POST['instance'] ?? '';
        $instance = Instance::findBySlug($instanceSlug);
        
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            echo json_encode(['success' => false, 'error' => 'Instance not found']);
            return;
        }

        try {
            $client = Instance::evoClient($instance);
            
            // Sync contacts
            $contactsResponse = $client->getContacts($instanceSlug);
            $syncedContacts = 0;
            $updatedContacts = 0;
            
            if ($contactsResponse['success'] && isset($contactsResponse['data'])) {
                foreach ($contactsResponse['data'] as $contactData) {
                    $result = Contact::createOrUpdateFromWhatsApp($contactData, $instance['id']);
                    if ($result['created']) {
                        $syncedContacts++;
                    } elseif ($result['updated']) {
                        $updatedContacts++;
                    }
                }
            }
            
            // Sync groups
            $groupsResponse = $client->getGroups($instanceSlug);
            $syncedGroups = 0;
            $updatedGroups = 0;
            
            if ($groupsResponse['success'] && isset($groupsResponse['data'])) {
                foreach ($groupsResponse['data'] as $groupData) {
                    $result = Contact::createOrUpdateGroupFromWhatsApp($groupData, $instance['id']);
                    if ($result['created']) {
                        $syncedGroups++;
                    } elseif ($result['updated']) {
                        $updatedGroups++;
                    }
                }
            }
            
            // Log action
            Auth::logAction('sync_contacts', 'contact', null, [
                'instance_id' => $instance['id'],
                'synced_contacts' => $syncedContacts,
                'updated_contacts' => $updatedContacts,
                'synced_groups' => $syncedGroups,
                'updated_groups' => $updatedGroups
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Sincronización completada',
                'stats' => [
                    'synced_contacts' => $syncedContacts,
                    'updated_contacts' => $updatedContacts,
                    'synced_groups' => $syncedGroups,
                    'updated_groups' => $updatedGroups
                ]
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error al sincronizar: ' . $e->getMessage()
            ]);
        }
    }

    public function lists()
    {
        if (!Auth::hasPermission('contacts.view')) {
            View::set('error', 'No tienes permisos para ver listas de contactos');
            View::render('error/403');
            return;
        }

        $instanceSlug = $_GET['instance'] ?? '';
        if (!$instanceSlug) {
            header('Location: index.php?r=dashboard/index');
            exit;
        }
        
        $instance = Instance::findBySlug($instanceSlug);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            View::set('error', 'Instancia no encontrada o sin permisos');
            View::render('error/404');
            return;
        }

        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $lists = ContactList::getByInstance($instance['id'], $offset, $limit);
        $total = ContactList::countByInstance($instance['id']);

        View::set('instance', $instance);
        View::set('lists', $lists);
        View::set('total', $total);
        View::set('page', $page);
        View::set('limit', $limit);
        View::render('contacts/lists/index');
    }

    public function createList()
    {
        if (!Auth::hasPermission('contacts.manage')) {
            View::set('error', 'No tienes permisos para gestionar listas de contactos');
            View::render('error/403');
            return;
        }

        $instanceSlug = $_GET['instance'] ?? '';
        $instance = Instance::findBySlug($instanceSlug);
        
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            View::set('error', 'Instancia no encontrada o sin permisos');
            View::render('error/404');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->storeList();
            return;
        }

        View::set('instance', $instance);
        View::render('contacts/lists/create');
    }

    public function storeList()
    {
        header('Content-Type: application/json');
        
        if (!Auth::hasPermission('contacts.manage')) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $instanceSlug = $_POST['instance'] ?? '';
        $instance = Instance::findBySlug($instanceSlug);
        
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            echo json_encode(['success' => false, 'error' => 'Instance not found']);
            return;
        }

        $data = [
            'instance_id' => $instance['id'],
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Validate
        $errors = $this->validateListData($data);
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            return;
        }

        try {
            $listId = ContactList::create($data);
            
            // Log action
            Auth::logAction('create_contact_list', 'contact_list', $listId, null, $data);
            
            echo json_encode([
                'success' => true,
                'message' => 'Lista creada correctamente',
                'list_id' => $listId
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error al crear lista: ' . $e->getMessage()
            ]);
        }
    }

    public function editList()
    {
        if (!Auth::hasPermission('contacts.manage')) {
            View::set('error', 'No tienes permisos para gestionar listas de contactos');
            View::render('error/403');
            return;
        }

        $instanceSlug = $_GET['instance'] ?? '';
        $instance = Instance::findBySlug($instanceSlug);
        
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            View::set('error', 'Instancia no encontrada o sin permisos');
            View::render('error/404');
            return;
        }

        $listId = (int)($_GET['id'] ?? 0);
        $list = ContactList::findById($listId);
        
        if (!$list || $list['instance_id'] != $instance['id']) {
            View::set('error', 'Lista no encontrada');
            View::render('error/404');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->updateList($listId);
            return;
        }

        View::set('instance', $instance);
        View::set('list', $list);
        View::render('contacts/lists/edit');
    }

    public function updateList($id)
    {
        header('Content-Type: application/json');
        
        if (!Auth::hasPermission('contacts.manage')) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $list = ContactList::findById($id);
        if (!$list) {
            echo json_encode(['success' => false, 'error' => 'List not found']);
            return;
        }

        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Validate
        $errors = $this->validateListData($data, $id);
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            return;
        }

        try {
            ContactList::update($id, $data);
            
            // Log action
            Auth::logAction('update_contact_list', 'contact_list', $id, $list, $data);
            
            echo json_encode([
                'success' => true,
                'message' => 'Lista actualizada correctamente'
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error al actualizar lista: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteList()
    {
        header('Content-Type: application/json');
        
        if (!Auth::hasPermission('contacts.manage')) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $listId = (int)($_POST['id'] ?? 0);
        $list = ContactList::findById($listId);
        
        if (!$list) {
            echo json_encode(['success' => false, 'error' => 'List not found']);
            return;
        }

        try {
            ContactList::delete($listId);
            
            // Log action
            Auth::logAction('delete_contact_list', 'contact_list', $listId, $list);
            
            echo json_encode([
                'success' => true,
                'message' => 'Lista eliminada correctamente'
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error al eliminar lista: ' . $e->getMessage()
            ]);
        }
    }

    private function validateListData($data, $excludeId = null)
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'El nombre de la lista es obligatorio';
        } elseif (strlen($data['name']) > 100) {
            $errors['name'] = 'El nombre no puede exceder 100 caracteres';
        }

        if (!empty($data['description']) && strlen($data['description']) > 500) {
            $errors['description'] = 'La descripción no puede exceder 500 caracteres';
        }

        // Check uniqueness
        $existing = ContactList::findByName($data['name'], $excludeId);
        if ($existing) {
            $errors['name'] = 'Ya existe una lista con este nombre';
        }

        return $errors;
    }
}
