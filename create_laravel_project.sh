#!/bin/bash

# 🚀 Script para crear proyecto Laravel con AdminLTE

echo "🚀 Creando proyecto EVOAPP con Laravel + Bootstrap + AdminLTE..."

# Variables
PROJECT_NAME="evoapp-laravel"
LARAVEL_VERSION="11.0"
DB_NAME="evoapp_laravel"
DB_USER="root"
DB_PASS=""

# 1. Crear nuevo proyecto Laravel
echo "📦 Instalando Laravel $LARAVEL_VERSION..."
composer create-project laravel/laravel="$LARAVEL_VERSION" $PROJECT_NAME

cd $PROJECT_NAME

# 2. Instalar dependencias adicionales
echo "📦 Instalando dependencias..."
composer require laravel/ui laravel/sanctum barryvdh/laravel-debugbar laravel/telescope pusher/pusher-php-server

# 3. Instalar AdminLTE
echo "🎨 Instalando AdminLTE..."
npm install admin-lte@3.2 bootstrap@5.3 @fortawesome/fontawesome-free@6.5 chart.js

# 4. Configurar base de datos
echo "🗄️ Configurando base de datos..."
cp .env.example .env

# Actualizar .env con configuración de base de datos
sed -i "s/DB_DATABASE=laravel/DB_DATABASE=$DB_NAME/" .env
sed -i "s/DB_USERNAME=root/DB_USERNAME=$DB_USER/" .env
if [ ! -z "$DB_PASS" ]; then
    sed -i "s/DB_PASSWORD=/DB_PASSWORD=$DB_PASS/" .env
fi

# 5. Generar key de aplicación
echo "🔑 Generando application key..."
php artisan key:generate

# 6. Instalar autenticación Laravel UI
echo "🔐 Configurando autenticación..."
php artisan ui bootstrap --auth
npm install && npm run build

# 7. Crear estructura de carpetas
echo "📁 Creando estructura de carpetas..."
mkdir -p app/Http/Controllers/{Auth,Dashboard,Instances,Inbox,Campaigns,Contacts}
mkdir -p app/Services
mkdir -p database/seeders/Development
mkdir -p resources/views/{layouts,dashboard,instances,inbox,campaigns,contacts,auth}

# 8. Crear layout base con AdminLTE
echo "🎨 Creando layout base..."
cat > resources/views/layouts/app.blade.php << 'EOF'
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>EVOAPP - @yield('title', 'Dashboard')</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    
    <!-- AdminLTE Theme style -->
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">
    
    <!-- Custom styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        @include('layouts.navbar')
        
        <!-- Main Sidebar Container -->
        @include('layouts.sidebar')
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            @include('layouts.header')
            
            <!-- Main content -->
            <section class="content">
                @yield('content')
            </section>
        </div>
        
        <!-- Footer -->
        @include('layouts.footer')
    </div>
    
    <!-- jQuery -->
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    
    <!-- Bootstrap 4 -->
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    
    <!-- AdminLTE App -->
    <script src="{{ asset('dist/js/adminlte.min.js') }}"></script>
    
    <!-- Custom scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
    
    @stack('scripts')
</body>
</html>
EOF

# 9. Crear componentes del layout
echo "🧩 Creando componentes del layout..."

# Navbar
cat > resources/views/layouts/navbar.blade.php << 'EOF'
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('dashboard.index') }}" class="nav-link"><strong>EVOAPP</strong></a>
        </li>
    </ul>
    
    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-user"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">{{ Auth::user()->name }}</span>
                <div class="dropdown-divider"></div>
                <a href="{{ route('logout') }}" class="dropdown-item">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </li>
    </ul>
</nav>
EOF

# Sidebar
cat > resources/views/layouts/sidebar.blade.php << 'EOF'
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route('dashboard.index') }}" class="brand-link">
        <img src="{{ asset('img/logo.png') }}" alt="EVOAPP Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">EVOAPP</span>
    </a>
    
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                <li class="nav-item">
                    <a href="{{ route('dashboard.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('instances.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-server"></i>
                        <p>Instancias</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('inbox.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-comments"></i>
                        <p>Inbox</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('campaigns.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-bullhorn"></i>
                        <p>Campañas</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('contacts.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-address-book"></i>
                        <p>Contactos</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
EOF

# 10. Configurar rutas principales
echo "🛣️ Configurando rutas..."
cat > routes/web.php << 'EOF'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthController;

Route::get('/', function () {
    return redirect()->route('dashboard.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    
    // Rutas de instancias
    Route::prefix('instances')->group(function () {
        Route::get('/', [InstanceController::class, 'index'])->name('instances.index');
        Route::get('/create', [InstanceController::class, 'create'])->name('instances.create');
        Route::post('/', [InstanceController::class, 'store'])->name('instances.store');
    });
    
    // Rutas de inbox
    Route::prefix('inbox')->group(function () {
        Route::get('/', [InboxController::class, 'index'])->name('inbox.index');
        Route::get('/chats', [InboxController::class, 'chats'])->name('inbox.chats');
        Route::get('/messages/{chat}', [InboxController::class, 'messages'])->name('inbox.messages');
        Route::post('/send', [InboxController::class, 'send'])->name('inbox.send');
    });
});

Auth::routes();
EOF

echo "✅ Proyecto Laravel creado exitosamente!"
echo ""
echo "📁 Ubicación: $(pwd)"
echo "🗄️ Base de datos: $DB_NAME"
echo ""
echo "🚀 Siguientes pasos:"
echo "1. cd $PROJECT_NAME"
echo "2. Configurar base de datos en .env"
echo "3. php artisan migrate"
echo "4. php artisan serve"
echo ""
echo "🎯 ¡Listo para comenzar el desarrollo!"
