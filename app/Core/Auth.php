<?php
namespace App\Core;

use App\Models\User;

class Auth
{
    public static function requireLogin()
    {
        if (!self::isLoggedIn()) {
            if (self::isAjaxRequest()) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            } else {
                header('Location: index.php?r=auth/login');
                exit;
            }
        }
    }
    
    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public static function login($email, $password)
    {
        $user = User::findByEmail($email);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        if (!$user['is_active']) {
            return false;
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_roles'] = User::getRoles($user['id']);
        
        // Update last login
        User::updateLastLogin($user['id']);
        
        return true;
    }
    
    public static function logout()
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        header('Location: index.php?r=auth/login');
        exit;
    }
    
    public static function getCurrentUser()
    {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'roles' => $_SESSION['user_roles'] ?? []
        ];
    }
    
    public static function hasPermission($permission)
    {
        $user = self::getCurrentUser();
        if (!$user) {
            return false;
        }
        
        // Admin has all permissions
        if (in_array('admin', $user['roles'])) {
            return true;
        }
        
        // Check user permissions
        $userPermissions = User::getPermissions($user['id']);
        return in_array($permission, $userPermissions);
    }
    
    public static function canViewInstance($instanceId)
    {
        $user = self::getCurrentUser();
        if (!$user) {
            return false;
        }
        
        // Admin can view all instances
        if (in_array('admin', $user['roles'])) {
            return true;
        }
        
        // Check if user has access to this instance
        return User::canAccessInstance($user['id'], $instanceId);
    }
    
    public static function generateCsrfToken()
    {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    public static function validateCsrfToken($token)
    {
        return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    private static function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    public static function logAction($action, $entityType = null, $entityId = null, $before = null, $after = null)
    {
        $user = self::getCurrentUser();
        if (!$user) {
            return;
        }
        
        DB::q("
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, before_json, after_json, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ", [
            $user['id'],
            $action,
            $entityType,
            $entityId,
            $before ? json_encode($before) : null,
            $after ? json_encode($after) : null
        ]);
    }
}
