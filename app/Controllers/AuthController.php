<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\User;

class AuthController
{
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (Auth::login($email, $password)) {
                header('Location: index.php?r=dashboard/index');
                exit;
            } else {
                View::set('error', 'Credenciales incorrectas');
            }
        }
        
        View::render('auth/login');
    }
    
    public function logout()
    {
        Auth::logout();
    }
}
