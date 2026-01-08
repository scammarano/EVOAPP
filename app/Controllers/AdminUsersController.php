<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\User;
use App\Models\Role;
use App\Models\Instance;

class AdminUsersController
{
    public function index()
    {
        if (!Auth::hasPermission('users.manage')) {
            View::set('error', 'No tienes permisos para gestionar usuarios');
            View::render('error/403');
            return;
        }

        $page = (int)($_GET['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }

        $users = User::getAll($page, 20);
        $totalUsers = User::count();

        View::set('users', $users);
        View::set('page', $page);
        View::set('totalUsers', $totalUsers);
        View::render('admin/users/index');
    }

    public function create()
    {
        if (!Auth::hasPermission('users.manage')) {
            View::set('error', 'No tienes permisos para gestionar usuarios');
            View::render('error/403');
            return;
        }

        $roles = Role::getAll();
        $instances = Instance::getAll(true);

        View::set('roles', $roles);
        View::set('instances', $instances);
        View::set('user', null);
        View::set('selectedRoleId', null);
        View::set('selectedInstanceIds', []);
        View::render('admin/users/form');
    }

    public function store()
    {
        if (!Auth::hasPermission('users.manage')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $password = $_POST['password'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $roleId = (int)($_POST['role_id'] ?? 0);
        $instanceIds = $_POST['instances'] ?? [];

        $errors = [];
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }
        if (!$name) {
            $errors['name'] = 'Nombre requerido';
        }
        if (!$password) {
            $errors['password'] = 'Password requerido';
        }

        if (!empty($errors)) {
            $roles = Role::getAll();
            $instances = Instance::getAll(true);
            View::set('roles', $roles);
            View::set('instances', $instances);
            View::set('errors', $errors);
            View::set('user', ['email' => $email, 'name' => $name, 'is_active' => $isActive]);
            View::set('selectedRoleId', $roleId ?: null);
            View::set('selectedInstanceIds', array_map('intval', (array)$instanceIds));
            View::render('admin/users/form');
            return;
        }

        User::create([
            'email' => $email,
            'name' => $name,
            'password' => $password,
            'role_id' => $roleId ?: null,
            'instances' => $instanceIds,
        ]);

        if ($isActive === 0) {
            // User::create always sets is_active=1; if requested inactive, update after create
            $user = User::findByEmail($email);
            if ($user) {
                User::update($user['id'], ['is_active' => 0]);
            }
        }

        View::flash('success', 'Usuario creado correctamente');
        header('Location: index.php?r=admin/users');
        exit;
    }

    public function edit()
    {
        if (!Auth::hasPermission('users.manage')) {
            View::set('error', 'No tienes permisos para gestionar usuarios');
            View::render('error/403');
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $user = User::findById($id);
        if (!$user) {
            View::set('error', 'Usuario no encontrado');
            View::render('error/404');
            return;
        }

        $roles = Role::getAll();
        $instances = Instance::getAll(true);
        $selectedRoleId = User::getPrimaryRoleId($id);
        $selectedInstanceIds = User::getInstanceIds($id);

        View::set('roles', $roles);
        View::set('instances', $instances);
        View::set('user', $user);
        View::set('selectedRoleId', $selectedRoleId);
        View::set('selectedInstanceIds', $selectedInstanceIds);
        View::render('admin/users/form');
    }

    public function update()
    {
        if (!Auth::hasPermission('users.manage')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $user = User::findById($id);
        if (!$user) {
            View::set('error', 'Usuario no encontrado');
            View::render('error/404');
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $password = $_POST['password'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $roleId = (int)($_POST['role_id'] ?? 0);
        $instanceIds = $_POST['instances'] ?? [];

        $errors = [];
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }
        if (!$name) {
            $errors['name'] = 'Nombre requerido';
        }

        if (!empty($errors)) {
            $roles = Role::getAll();
            $instances = Instance::getAll(true);
            View::set('roles', $roles);
            View::set('instances', $instances);
            View::set('errors', $errors);
            View::set('user', ['id' => $id, 'email' => $email, 'name' => $name, 'is_active' => $isActive]);
            View::set('selectedRoleId', $roleId ?: null);
            View::set('selectedInstanceIds', array_map('intval', (array)$instanceIds));
            View::render('admin/users/form');
            return;
        }

        $data = [
            'email' => $email,
            'name' => $name,
            'is_active' => $isActive,
            'role_id' => $roleId ?: null,
            'instances' => $instanceIds,
        ];

        if ($password) {
            $data['password'] = $password;
        }

        User::update($id, $data);

        View::flash('success', 'Usuario actualizado correctamente');
        header('Location: index.php?r=admin/users');
        exit;
    }

    public function toggleActive()
    {
        if (!Auth::hasPermission('users.manage')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $user = User::findById($id);
        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'User not found']);
            return;
        }

        $newActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
        User::update($id, ['is_active' => $newActive]);

        header('Location: index.php?r=admin/users');
        exit;
    }
}
