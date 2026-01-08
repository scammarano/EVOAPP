<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Instance;

class InstancesController
{
    public function index()
    {
        if (!Auth::hasPermission('instances.view')) {
            View::set('error', 'No tienes permisos para ver instancias');
            View::render('error/403');
            return;
        }

        $user = Auth::getCurrentUser();
        $instances = Instance::getAccessibleInstances($user['id']);

        View::set('instances', $instances);
        View::render('instances/index');
    }

    public function create()
    {
        if (!Auth::hasPermission('instances.manage')) {
            View::set('error', 'No tienes permisos para gestionar instancias');
            View::render('error/403');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

        View::render('instances/create');
    }

    public function store()
    {
        if (!Auth::hasPermission('instances.manage')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $data = [
            'slug' => $_POST['slug'] ?? '',
            'description' => $_POST['description'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'api_key' => $_POST['api_key'] ?? '',
            'base_url' => $_POST['base_url'] ?? '',
            'webhook_token' => $_POST['webhook_token'] ?? '',
            'webhook_enabled' => isset($_POST['webhook_enabled']) ? 1 : 0,
            'forward_webhook_url' => $_POST['forward_webhook_url'] ?? '',
            'forward_webhook_enabled' => isset($_POST['forward_webhook_enabled']) ? 1 : 0
        ];

        // Validate
        $errors = $this->validateInstanceData($data);
        if (!empty($errors)) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            } else {
                View::set('errors', $errors);
                View::set('data', $data);
                View::render('instances/create');
                return;
            }
        }

        try {
            $instanceId = Instance::create($data);
            
            // Log action
            Auth::logAction('create_instance', 'instance', $instanceId, null, $data);

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Instancia creada correctamente', 'instance_id' => $instanceId]);
            } else {
                View::flash('success', 'Instancia creada correctamente');
                header('Location: index.php?r=instances/index');
                exit;
            }

        } catch (\Exception $e) {
            $error = 'Error al crear instancia: ' . $e->getMessage();
            
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $error]);
            } else {
                View::set('error', $error);
                View::set('data', $data);
                View::render('instances/create');
            }
        }
    }

    public function edit()
    {
        if (!Auth::hasPermission('instances.manage')) {
            View::set('error', 'No tienes permisos para gestionar instancias');
            View::render('error/403');
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $instance = Instance::findById($id);

        if (!$instance) {
            View::set('error', 'Instancia no encontrada');
            View::render('error/404');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($id);
            return;
        }

        View::set('instance', $instance);
        View::render('instances/edit');
    }

    public function update($id)
    {
        if (!Auth::hasPermission('instances.manage')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $instance = Instance::findById($id);
        if (!$instance) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Instance not found']);
            return;
        }

        $data = [
            'slug' => $_POST['slug'] ?? '',
            'description' => $_POST['description'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'api_key' => $_POST['api_key'] ?? '',
            'base_url' => $_POST['base_url'] ?? '',
            'webhook_token' => $_POST['webhook_token'] ?? '',
            'webhook_enabled' => isset($_POST['webhook_enabled']) ? 1 : 0,
            'forward_webhook_url' => $_POST['forward_webhook_url'] ?? '',
            'forward_webhook_enabled' => isset($_POST['forward_webhook_enabled']) ? 1 : 0
        ];

        // Validate
        $errors = $this->validateInstanceData($data, $id);
        if (!empty($errors)) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            } else {
                View::set('errors', $errors);
                View::set('instance', array_merge($instance, $data));
                View::render('instances/edit');
                return;
            }
        }

        try {
            Instance::update($id, $data);
            
            // Log action
            Auth::logAction('update_instance', 'instance', $id, $instance, $data);

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Instancia actualizada correctamente']);
            } else {
                View::flash('success', 'Instancia actualizada correctamente');
                header('Location: index.php?r=instances/index');
                exit;
            }

        } catch (\Exception $e) {
            $error = 'Error al actualizar instancia: ' . $e->getMessage();
            
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $error]);
            } else {
                View::set('error', $error);
                View::set('instance', array_merge($instance, $data));
                View::render('instances/edit');
            }
        }
    }

    public function delete()
    {
        if (!Auth::hasPermission('instances.manage')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $instance = Instance::findById($id);

        if (!$instance) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Instance not found']);
            return;
        }

        try {
            Instance::delete($id);
            
            // Log action
            Auth::logAction('delete_instance', 'instance', $id, $instance);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Instancia eliminada correctamente']);

        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error al eliminar instancia: ' . $e->getMessage()]);
        }
    }

    public function view()
    {
        $slug = $_GET['slug'] ?? '';
        
        if (!$slug) {
            header('Location: index.php?r=dashboard/index');
            exit;
        }

        $instance = Instance::findBySlug($slug);
        if (!$instance || !Auth::canViewInstance($instance['id'])) {
            View::set('error', 'Instancia no encontrada o sin permisos');
            View::render('error/404');
            return;
        }

        // Redirect to inbox with this instance
        header('Location: index.php?r=inbox/index&instance=' . urlencode($slug));
        exit;
    }

    private function validateInstanceData($data, $excludeId = null)
    {
        $errors = [];

        // Slug validation
        if (empty($data['slug'])) {
            $errors['slug'] = 'El slug es obligatorio';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['slug'])) {
            $errors['slug'] = 'El slug solo puede contener letras, números, guiones y guiones bajos';
        } else {
            // Check uniqueness
            $existing = Instance::findBySlug($data['slug']);
            if ($existing && (!$excludeId || $existing['id'] != $excludeId)) {
                $errors['slug'] = 'El slug ya está en uso';
            }
        }

        // API Key validation
        if (empty($data['api_key'])) {
            $errors['api_key'] = 'La API key es obligatoria';
        }

        // Base URL validation (if provided)
        if (!empty($data['base_url'])) {
            if (!filter_var($data['base_url'], FILTER_VALIDATE_URL)) {
                $errors['base_url'] = 'La URL base no es válida';
            }
        }

        // Forward webhook URL validation (if provided)
        if (!empty($data['forward_webhook_url'])) {
            if (!filter_var($data['forward_webhook_url'], FILTER_VALIDATE_URL)) {
                $errors['forward_webhook_url'] = 'La URL de forward webhook no es válida';
            }
        }

        return $errors;
    }

    private function isAjaxRequest()
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return stripos($accept, 'application/json') !== false;
    }
}
