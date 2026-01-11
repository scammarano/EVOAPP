<?php
// Verificar configuración de base de datos y constantes
echo "🔍 Verificando configuración del sistema...\n\n";

// Verificar constantes de BD
echo "📊 Constantes de Base de Datos:\n";
$constants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($constants as $const) {
    if (defined($const)) {
        $value = $const === 'DB_PASS' ? '***' : constant($const);
        echo "✅ $const = $value\n";
    } else {
        echo "❌ $const = NO DEFINIDA\n";
    }
}

echo "\n📂 Archivo de configuración:\n";
$config_files = [
    __DIR__ . '/config.php',
    __DIR__ . '/config/config.php',
    __DIR__ . '/app/config.php',
    __DIR__ . '/app/config/config.php'
];

foreach ($config_files as $file) {
    if (file_exists($file)) {
        echo "✅ Encontrado: $file\n";
    } else {
        echo "❌ No encontrado: $file\n";
    }
}

echo "\n🔍 Verificando inclusión de configuración:\n";
// Buscar en archivos principales
$main_files = [
    __DIR__ . '/index.php',
    __DIR__ . '/app/Core/App.php'
];

foreach ($main_files as $file) {
    if (file_exists($file)) {
        echo "📖 Analizando: $file\n";
        $content = file_get_contents($file);
        
        if (strpos($content, 'config.php') !== false) {
            echo "✅ Incluye config.php\n";
        } else {
            echo "❌ No incluye config.php\n";
        }
        
        if (strpos($content, 'DB_') !== false) {
            echo "✅ Referencia a DB_\n";
        } else {
            echo "❌ No referencia a DB_\n";
        }
    }
}

echo "\n🔧 Creando configuración si es necesario...\n";

// Crear archivo de configuración si no existe
$config_file = __DIR__ . '/config.php';
if (!file_exists($config_file)) {
    echo "📝 Creando config.php...\n";
    
    $config_content = '<?php
// Configuración de Base de Datos
define("DB_HOST", "localhost");
define("DB_NAME", "grupoecc_evoapp");
define("DB_USER", "grupoecc_evoapp");
define("DB_PASS", "tu_password_aqui");

// Configuración de la Aplicación
define("APP_URL", "https://camcam.com.ve/evoappws/");
define("APP_NAME", "EVOAPP");

// Zona horaria
date_default_timezone_set("America/Bogota");
?>';
    
    if (file_put_contents($config_file, $config_content)) {
        echo "✅ config.php creado\n";
        echo "⚠️ DEBES EDITAR el archivo con tus credenciales reales\n";
    } else {
        echo "❌ Error al crear config.php\n";
    }
} else {
    echo "✅ config.php ya existe\n";
}

echo "\n🎯 Próximos pasos:\n";
echo "1. Si se creó config.php, edítalo con tus credenciales\n";
echo "2. Si ya existe, verifica que tenga las constantes DB_\n";
echo "3. Reinicia el servidor web\n";
echo "4. Ejecuta update_db_class.php nuevamente\n";
?>
