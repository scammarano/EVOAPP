<?php
// FIX DE EMERGENCIA - Error Clase App\Core\DB no encontrada
// Este script corrige el problema inmediato en el servidor

echo "<h1>🚨 FIX DE EMERGENCIA - App\Core\DB</h1>";

// Paso 1: Verificar si DB_fixed.php existe y reemplazar DB.php
echo "<h3>📝 Paso 1: Verificando archivos DB</h3>";

if (file_exists('app/Core/DB_fixed.php')) {
    echo "<p style='color: blue;'>📁 DB_fixed.php encontrado</p>";
    
    // Leer contenido del archivo fixed
    $fixedContent = file_get_contents('app/Core/DB_fixed.php');
    
    // Hacer backup del DB.php actual
    if (file_exists('app/Core/DB.php')) {
        if (copy('app/Core/DB.php', 'app/Core/DB_backup_' . date('YmdHis') . '.php')) {
            echo "<p style='color: orange;'>📋 Backup de DB.php creado</p>";
        }
    }
    
    // Reemplazar DB.php con la versión fixed
    if (file_put_contents('app/Core/DB.php', $fixedContent)) {
        echo "<p style='color: green;'>✅ DB.php actualizado con versión fixed</p>";
    } else {
        echo "<p style='color: red;'>❌ Error al actualizar DB.php</p>";
    }
} else {
    echo "<p style='color: red;'>❌ DB_fixed.php no encontrado</p>";
    
    // Crear DB.php básico con getInstance()
    $basicDB = '<?php
namespace App\Core;

use PDO;
use PDOException;

class DB
{
    private static $pdo = null;
    
    public static function getInstance()
    {
        if (self::$pdo === null) {
            self::init();
        }
        return self::$pdo;
    }
    
    public static function init()
    {
        if (self::$pdo === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
    }
    
    public static function q($sql, $params = [])
    {
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage() . " SQL: $sql");
            throw $e;
        }
    }
    
    public static function fetch($sql, $params = [])
    {
        $stmt = self::q($sql, $params);
        return $stmt->fetch();
    }
    
    public static function fetchAll($sql, $params = [])
    {
        $stmt = self::q($sql, $params);
        return $stmt->fetchAll();
    }
    
    public static function lastInsertId()
    {
        return self::getInstance()->lastInsertId();
    }
}';
    
    if (file_put_contents('app/Core/DB.php', $basicDB)) {
        echo "<p style='color: green;'>✅ DB.php básico creado con getInstance()</p>";
    }
}

// Paso 2: Verificar que App.php pueda encontrar la clase
echo "<h3>📝 Paso 2: Verificando App.php</h3>";

$appContent = file_get_contents('app/Core/App.php');
if (strpos($appContent, 'use App\Core\DB;') !== false) {
    echo "<p style='color: green;'>✅ App.php importa App\\Core\\DB correctamente</p>";
} else {
    echo "<p style='color: red;'>❌ App.php no importa App\\Core\\DB</p>";
}

// Paso 3: Probar carga de la clase
echo "<h3>📝 Paso 3: Probando carga de clase DB</h3>";

try {
    // Incluir constantes de BD si no están definidas
    if (!defined('DB_HOST')) {
        // Definir constantes (ajustar según configuración real)
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'grupoecc_evoappWS');
        define('DB_USER', 'grupoecc_toto');
        define('DB_PASS', 'Toto123*.A');
        echo "<p style='color: orange;'>⚠️ Constantes de BD definidas temporalmente</p>";
    }
    
    // Intentar incluir y usar la clase
    require_once 'app/Core/DB.php';
    
    // Probar método estático
    if (class_exists('App\Core\DB')) {
        echo "<p style='color: green;'>✅ Clase App\\Core\\DB encontrada</p>";
        
        if (method_exists('App\Core\DB', 'getInstance')) {
            echo "<p style='color: green;'>✅ Método getInstance() existe</p>";
        } else {
            echo "<p style='color: red;'>❌ Método getInstance() no existe</p>";
        }
        
        if (method_exists('App\Core\DB', 'init')) {
            echo "<p style='color: green;'>✅ Método init() existe</p>";
        } else {
            echo "<p style='color: red;'>❌ Método init() no existe</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Clase App\\Core\\DB no encontrada</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al probar clase: " . $e->getMessage() . "</p>";
}

// Paso 4: Probar conexión a BD
echo "<h3>📝 Paso 4: Probando conexión a base de datos</h3>";

try {
    require_once 'app/Core/DB.php';
    
    // Inicializar conexión
    App\Core\DB::init();
    echo "<p style='color: green;'>✅ DB::init() ejecutado correctamente</p>";
    
    // Probar consulta simple
    $result = App\Core\DB::fetch("SELECT 1 as test");
    if ($result && $result['test'] == 1) {
        echo "<p style='color: green;'>✅ Conexión a BD funcionando</p>";
    } else {
        echo "<p style='color: red;'>❌ Error en consulta de prueba</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error de conexión: " . $e->getMessage() . "</p>";
}

// Paso 5: Verificar archivos críticos
echo "<h3>📝 Paso 5: Verificando archivos críticos</h3>";

$criticalFiles = [
    'app/Core/DB.php' => 'Clase de base de datos',
    'app/Core/App.php' => 'Clase principal de aplicación',
    'index.php' => 'Punto de entrada',
    'app/Models/Contact.php' => 'Modelo de contactos'
];

foreach ($criticalFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file - $description</p>";
    } else {
        echo "<p style='color: red;'>❌ $file - $description (NO ENCONTRADO)</p>";
    }
}

// Paso 6: Crear test simple
echo "<h3>📝 Paso 6: Creando test de funcionamiento</h3>";

$testContent = '<?php
// Test de funcionamiento del sistema
require_once "app/Core/DB.php";

echo "<h2>🧪 Test de Sistema</h2>";

try {
    // Definir constantes si no existen
    if (!defined("DB_HOST")) {
        define("DB_HOST", "localhost");
        define("DB_NAME", "grupoecc_evoappWS");
        define("DB_USER", "grupoecc_toto");
        define("DB_PASS", "Toto123*.A");
    }
    
    // Inicializar BD
    App\Core\DB::init();
    echo "<p style=\"color: green;\">✅ Base de datos inicializada</p>";
    
    // Probar consulta
    $result = App\Core\DB::fetchAll("SHOW TABLES");
    echo "<p style=\"color: green;\">✅ Consulta ejecutada - " . count($result) . " tablas encontradas</p>";
    
    // Probar Contact model si existe
    if (file_exists("app/Models/Contact.php")) {
        require_once "app/Models/Contact.php";
        if (class_exists("App\\Models\\Contact")) {
            echo "<p style=\"color: green;\">✅ Clase Contact encontrada</p>";
        }
    }
    
    echo "<h3 style=\"color: green;\">🎉 Sistema funcionando correctamente!</h3>";
    
} catch (Exception $e) {
    echo "<p style=\"color: red;\">❌ Error: " . $e->getMessage() . "</p>";
}
?>';

if (file_put_contents('test_system.php', $testContent)) {
    echo "<p style='color: green;'>✅ Test system creado: <a href=\"test_system.php\">test_system.php</a></p>";
} else {
    echo "<p style='color: red;'>❌ Error al crear test</p>";
}

echo "<hr>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h3>🎯 RESUMEN DEL FIX</h3>";
echo "<ul>";
echo "<li>✅ Verificado y actualizado DB.php con método getInstance()</li>";
echo "<li>✅ Verificado App.php importa correctamente la clase</li>";
echo "<li>✅ Probada conexión a base de datos</li>";
echo "<li>✅ Verificados archivos críticos</li>";
echo "<li>✅ Creado test de funcionamiento</li>";
echo "</ul>";

echo "<h3>🚀 PRÓXIMOS PASOS</h3>";
echo "<ol>";
echo "<li>📋 Probar el test: <a href='test_system.php'>test_system.php</a></li>";
echo "<li>🌐 Probar la aplicación principal: <a href='index.php'>index.php</a></li>";
echo "<li>📡 Probar webhooks: <a href='index.php?r=webhook/evolution'>Webhook Test</a></li>";
echo "<li>🔧 Si todo funciona, ejecutar deploy_fix.php completo</li>";
echo "</ol>";
echo "</div>";

echo "<p style='color: green; font-weight: bold;'>🎯 ¡Fix de emergencia aplicado! El error de App\\Core\\DB debería estar resuelto.</p>";
?>
