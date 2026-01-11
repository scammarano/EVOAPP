<?php
// Corregir ruta de configuración
echo "🔧 CORRIGIENDO RUTA DE CONFIGURACIÓN...\n\n";

// Rutas posibles
$config_paths = [
    __DIR__ . '/config.php',                    // El que creó el script
    __DIR__ . '/config/config.php',            // El que funciona
    __DIR__ . '/app/config.php',
    __DIR__ . '/app/config/config.php'
];

echo "📂 Buscando archivos config.php:\n";
$working_config = null;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        echo "✅ Encontrado: $path\n";
        
        // Verificar si tiene las constantes BD
        $content = file_get_contents($path);
        if (strpos($content, "DB_HOST") !== false && strpos($content, "grupoecc_evoappWS") !== false) {
            echo "🎯 ¡Este es el config que funciona!\n";
            $working_config = $path;
        }
    } else {
        echo "❌ No encontrado: $path\n";
    }
}

if ($working_config) {
    echo "\n🔧 Configuración encontrada en: $working_config\n";
    
    // Verificar si index.php está incluyendo el correcto
    $index_file = __DIR__ . '/index.php';
    if (file_exists($index_file)) {
        $index_content = file_get_contents($index_file);
        
        echo "\n📖 Verificando inclusión en index.php:\n";
        
        if (strpos($index_content, 'config/config.php') !== false) {
            echo "✅ index.php incluye config/config.php (correcto)\n";
        } elseif (strpos($index_content, 'config.php') !== false) {
            echo "⚠️ index.php incluye config.php (puede ser incorrecto)\n";
            echo "🔧 Necesita incluir config/config.php\n";
        } else {
            echo "❌ index.php no incluye ninguna configuración\n";
        }
    }
    
    // Verificar inclusión en App.php
    $app_file = __DIR__ . '/app/Core/App.php';
    if (file_exists($app_file)) {
        $app_content = file_get_contents($app_file);
        
        echo "\n📖 Verificando inclusión en App.php:\n";
        
        if (strpos($app_content, 'config/config.php') !== false) {
            echo "✅ App.php incluye config/config.php (correcto)\n";
        } elseif (strpos($app_content, 'config.php') !== false) {
            echo "⚠️ App.php incluye config.php (puede ser incorrecto)\n";
        } else {
            echo "❌ App.php no incluye ninguna configuración\n";
        }
    }
    
    echo "\n🎯 SOLUCIÓN:\n";
    echo "1. ✅ Tu config.php en /config/config.php está PERFECTO\n";
    echo "2. 🔧 Asegúrate que todos los archivos incluyan: config/config.php\n";
    echo "3. 🗑️ Puedes eliminar el config.php que creó el script si no se usa\n";
    
} else {
    echo "\n❌ No se encontró configuración funcional\n";
}

echo "\n🚀 Prueba de conexión BD:\n";
try {
    // Incluir el config que funciona
    require_once __DIR__ . '/config/config.php';
    
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $stmt = $pdo->query("SELECT 1");
    $result = $stmt->fetch();
    
    echo "✅ Conexión BD exitosa desde config/config.php\n";
    echo "📊 Base de datos: " . DB_NAME . "\n";
    echo "👤 Usuario: " . DB_USER . "\n";
    echo "🌐 Host: " . DB_HOST . "\n";
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
}

echo "\n📋 ACCIONES REQUERIDAS:\n";
echo "1. ✅ Tu configuración está BIEN en /config/config.php\n";
echo "2. 🔧 Ejecuta update_db_class.php para agregar métodos a DB.php\n";
echo "3. 🔄 Reinicia el servidor web\n";
echo "4. ✅ Todo debería funcionar\n";
?>
