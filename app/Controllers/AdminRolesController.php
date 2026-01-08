<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Role;
use App\Models\Permission;

class AdminRolesController
{
    public function index()
    {
        if (!Auth::hasPermission('users.manage')) {
            View::set('error', 'No tienes permisos para gestionar roles');
            View::render('error/403');
            return;
        }

        $roles = Role::getAll();
        View::set('roles', $roles);
        View::render('admin/roles/index');
    }

    public function create()
    {
        if (!Auth::hasPermission('users.manage')) {
            View::set('error', 'No tienes permisos para gestionar roles');
            View::render('error/403');
            return;
        }

        $permissions = Permission::getAll();

        View::set('role', null);
        View::set('permissions', $permissions);
        View::set('selectedPermissionIds', []);
        View::render('admin/roles/form');
    }

    public function store()
    {
        if (!Auth::hasPermission('users.manage')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $permissionIds = $_POST['permissions'] ?? [];

        $errors = [];
        if (!$name) {
            $errors['name'] = 'Nombre requerido';
        }

        if (!empty($errors)) {
            $permissions = Permission::getAll();
            View::set('errors', $errors);
            View::set('role', ['name' => $name, 'description' => $description]);
            View::set('permissions', $permissions);
            View::set('selectedPermissionIds', array_map('intval', (array)$permissionIds));
            View::render('admin/roles/form');
            return;
        }

        $roleId = Role::create(['name' => $name, 'description' => $description]);
        Role::setPermissions($roleId, (array)$permissionIds);

        View::flash('success', 'Rol creado correctamente');
        header('Location: index.php?r=admin/roles');
        exit;
    }

    public function edit()
    {
        if (!Auth::hasPermission('users.manage')) {
            View::set('error', 'No tienes permisos para gestionar roles');
            View::render('error/403');
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $role = Role::findById($id);
        if (!$role) {
            View::set('error', 'Rol no encontrado');
            View::render('error/404');
            return;
        }

        $permissions = Permission::getAll();
        $selectedPermissionIds = Role::getPermissionIds($id);

        View::set('role', $role);
        View::set('permissions', $permissions);
        View::set('selectedPermissionIds', $selectedPermissionIds);
        View::render('admin/roles/form');
    }

    public function update()
    {
        if (!Auth::hasPermission('users.manage')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $role = Role::findById($id);
        if (!$role) {
            View::set('error', 'Rol no encontrado');
            View::render('error/404');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $permissionIds = $_POST['permissions'] ?? [];

        $errors = [];
        if (!$name && $role['name'] !== 'admin') {
            $errors['name'] = 'Nombre requerido';
        }

        if (!empty($errors)) {
            $permissions = Permission::getAll();
            View::set('errors', $errors);
            View::set('role', ['id' => $id, 'name' => $name ?: $role['name'], 'description' => $description]);
            View::set('permissions', $permissions);
            View::set('selectedPermissionIds', array_map('intval', (array)$permissionIds));
            View::render('admin/roles/form');
            return;
        }

        $data = [
            'description' => $description,
        ];

        if ($role['name'] !== 'admin') {
            $data['name'] = $name;
        }

        Role::update($id, $data);
        Role::setPermissions($id, (array)$permissionIds);

        View::flash('success', 'Rol actualizado correctamente');
        header('Location: index.php?r=admin/roles');
        exit;
    }
}
