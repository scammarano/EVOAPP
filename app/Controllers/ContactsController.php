<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Contact;
use App\Models\Instance;

class ContactsController
{
    public function index()
    {
        if (!Auth::hasPermission('contacts.view')) {
            View::set('error', 'No tienes permisos para ver contactos');
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
        $search = $_GET['search'] ?? '';
        $contacts = Contact::getAll($instance['id'], $page, 20, $search);
        $totalContacts = Contact::count($instance['id'], $search);
        $stats = Contact::getStats($instance['id']);

        View::set('instance', $instance);
        View::set('contacts', $contacts);
        View::set('search', $search);
        View::set('page', $page);
        View::set('totalContacts', $totalContacts);
        View::set('stats', $stats);
        
        View::render('contacts/index');
    }

    public function create()
    {
        if (!Auth::hasPermission('contacts.edit')) {
            View::set('error', 'No tienes permisos para editar contactos');
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

        View::set('instance', $instance);
        View::render('contacts/create');
    }

    public function store()
    {
        if (!Auth::hasPermission('contacts.edit')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $instanceSlug = $_POST['instance'] ?? '';
        $instance = Instance::findBySlug($instanceSlug);
        
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $data = [
            'instance_id' => $instance['id'],
            'phone_e164' => $_POST['phone_e164'] ?? '',
            'name' => $_POST['name'] ?? '',
            'company' => $_POST['company'] ?? '',
            'email' => $_POST['email'] ?? '',
            'birthday' => $_POST['birthday'] ?? '',
            'notes' => $_POST['notes'] ?? ''
        ];

        // Validate
        $errors = $this->validateContactData($data);
        if (!empty($errors)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => $errors]);
            return;
        }

        try {
            $contactId = Contact::createOrUpdate($data);
            
            // Log action
            Auth::logAction('create_contact', 'contact', $contactId, null, $data);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Contacto creado correctamente', 'contact_id' => $contactId]);

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al crear contacto: ' . $e->getMessage()]);
        }
    }

    public function edit()
    {
        if (!Auth::hasPermission('contacts.edit')) {
            View::set('error', 'No tienes permisos para editar contactos');
            View::render('error/403');
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $contact = Contact::findById($id);

        if (!$contact) {
            View::set('error', 'Contacto no encontrado');
            View::render('error/404');
            return;
        }

        $instance = Instance::findById($contact['instance_id']);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            View::set('error', 'Instancia no encontrada o sin permisos');
            View::render('error/404');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($id);
            return;
        }

        View::set('instance', $instance);
        View::set('contact', $contact);
        View::render('contacts/edit');
    }

    public function update($id)
    {
        if (!Auth::hasPermission('contacts.edit')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $contact = Contact::findById($id);
        if (!$contact) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Contact not found']);
            return;
        }

        $instance = Instance::findById($contact['instance_id']);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $data = [
            'instance_id' => $contact['instance_id'],
            'phone_e164' => $_POST['phone_e164'] ?? '',
            'name' => $_POST['name'] ?? '',
            'company' => $_POST['company'] ?? '',
            'email' => $_POST['email'] ?? '',
            'birthday' => $_POST['birthday'] ?? '',
            'notes' => $_POST['notes'] ?? ''
        ];

        // Validate
        $errors = $this->validateContactData($data, $id);
        if (!empty($errors)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => $errors]);
            return;
        }

        try {
            $contactId = Contact::createOrUpdate($data);
            
            // Log action
            Auth::logAction('update_contact', 'contact', $id, $contact, $data);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Contacto actualizado correctamente']);

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al actualizar contacto: ' . $e->getMessage()]);
        }
    }

    public function delete()
    {
        if (!Auth::hasPermission('contacts.edit')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $contact = Contact::findById($id);

        if (!$contact) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Contact not found']);
            return;
        }

        $instance = Instance::findById($contact['instance_id']);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        try {
            Contact::delete($id);
            
            // Log action
            Auth::logAction('delete_contact', 'contact', $id, $contact);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Contacto eliminado correctamente']);

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al eliminar contacto: ' . $e->getMessage()]);
        }
    }

    public function import()
    {
        if (!Auth::hasPermission('contacts.import')) {
            View::set('error', 'No tienes permisos para importar contactos');
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
            if (isset($_FILES['csv_file'])) {
                $this->processImport($instance);
            } else {
                View::set('error', 'No se seleccionó ningún archivo');
            }
        }

        View::set('instance', $instance);
        View::render('contacts/import');
    }

    public function export()
    {
        if (!Auth::hasPermission('contacts.export')) {
            View::set('error', 'No tienes permisos para exportar contactos');
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

        $search = $_GET['search'] ?? '';
        $csv = Contact::exportToCsv($instance['id'], $search);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="contacts_' . $instance['slug'] . '_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        echo $csv;
        exit;
    }

    public function candidates()
    {
        if (!Auth::hasPermission('contacts.view')) {
            View::set('error', 'No tienes permisos para ver contactos');
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

        // Get candidates from database
        $candidates = $this->getContactCandidates($instance['id']);

        View::set('instance', $instance);
        View::set('candidates', $candidates);
        View::render('contacts/candidates');
    }

    public function saveCandidates()
    {
        if (!Auth::hasPermission('contacts.edit')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $instanceSlug = $_POST['instance'] ?? '';
        $instance = Instance::findBySlug($instanceSlug);
        
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $selectedCandidates = $_POST['candidates'] ?? [];

        try {
            $saved = 0;
            foreach ($selectedCandidates as $candidateId) {
                $candidate = $this->getCandidateById($candidateId);
                if ($candidate && $candidate['instance_id'] == $instance['id']) {
                    $data = [
                        'instance_id' => $instance['id'],
                        'phone_e164' => $candidate['phone_e164'],
                        'name' => $candidate['name_guess']
                    ];
                    
                    Contact::createOrUpdate($data);
                    $this->updateCandidateStatus($candidateId, 'saved');
                    $saved++;
                }
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => "$saved contactos guardados correctamente"]);

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al guardar contactos: ' . $e->getMessage()]);
        }
    }

    private function processImport($instance)
    {
        $file = $_FILES['csv_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            View::set('error', 'Error al subir archivo: ' . $file['error']);
            return;
        }

        // Validate file type
        $allowedTypes = ['text/csv', 'text/plain', 'application/csv'];
        if (!in_array($file['type'], $allowedTypes)) {
            View::set('error', 'El archivo debe ser un CSV');
            return;
        }

        try {
            $imported = Contact::importFromCsv($instance['id'], $file['tmp_name']);
            
            // Log action
            Auth::logAction('import_contacts', 'contact', null, null, [
                'instance_id' => $instance['id'],
                'imported_count' => $imported
            ]);

            View::flash('success', "$imported contactos importados correctamente");
            header('Location: index.php?r=contacts/index&instance=' . urlencode($instance['slug']));
            exit;

        } catch (\Exception $e) {
            View::set('error', 'Error al importar contactos: ' . $e->getMessage());
        }
    }

    private function validateContactData($data, $excludeId = null)
    {
        $errors = [];

        // Phone validation
        if (empty($data['phone_e164'])) {
            $errors['phone_e164'] = 'El teléfono es obligatorio';
        } elseif (!preg_match('/^\+\d{10,15}$/', $data['phone_e164'])) {
            $errors['phone_e164'] = 'El teléfono debe estar en formato internacional (+1234567890)';
        }

        // Email validation (if provided)
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email no es válido';
        }

        // Birthday validation (if provided)
        if (!empty($data['birthday'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $data['birthday']);
            if (!$date || $date->format('Y-m-d') !== $data['birthday']) {
                $errors['birthday'] = 'La fecha de nacimiento no es válida';
            }
        }

        return $errors;
    }

    private function getContactCandidates($instanceId)
    {
        // This would typically query contact_candidates table
        // For now, return empty array
        return [];
    }

    private function getCandidateById($id)
    {
        // This would typically query contact_candidates table
        // For now, return null
        return null;
    }

    private function updateCandidateStatus($id, $status)
    {
        // This would typically update contact_candidates table
        // For now, do nothing
    }
}
