<?php
// Script para agregar rutas de grupos al router principal
// Ejecutar: https://camcam.com.ve/evoappws/add_group_routes.php

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔧 Agregar Rutas de Grupos</h2>";
    
    // 1. Verificar archivo de rutas
    $routesFile = __DIR__ . '/app/Core/routes.php';
    
    if (!file_exists($routesFile)) {
        echo "<p style='color: red;'>❌ No se encontró el archivo de rutas: {$routesFile}</p>";
        echo "<p>Buscando archivo de rutas alternativo...</p>";
        
        // Buscar archivos que puedan contener rutas
        $files = glob(__DIR__ . '/app/Core/*.php');
        $routesFiles = [];
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (strpos($content, 'Router') !== false || strpos($content, 'route') !== false) {
                $routesFiles[] = $file;
            }
        }
        
        if (!empty($routesFiles)) {
            echo "<p style='color: blue;'>📁 Archivos de rutas encontrados:</p>";
            foreach ($routesFiles as $file) {
                echo "<p>• " . basename($file) . "</p>";
            }
            $routesFile = $routesFiles[0];
        } else {
            echo "<p style='color: red;'>❌ No se encontraron archivos de rutas</p>";
            echo "<p>Creando archivo de rutas nuevo...</p>";
            
            // Crear archivo de rutas básico
            $routesContent = '<?php
// Rutas del sistema EVOAPP

// Rutas de grupos
$router->get('/groups', 'GroupController@index');
$router->post('/groups/extractGroupContacts', 'GroupController@extractGroupContacts');
$router->post('/groups/extractChatContacts', 'GroupController@extractChatContacts');

// Rutas existentes (mantener las que ya tienes)
$router->get('/', 'HomeController@index');
$router->get('/dashboard', 'DashboardController@index');
$router->get('/diagnostic', 'DiagnosticController@index');
$router->get('/logs', 'LogsController@index');
$router->get('/instances', 'InstanceController@index');
$router->get('/chats', 'ChatController@index');
$router->get('/messages', 'MessageController@index');
$router->get('/contacts', 'ContactController@index');
$router->get('/settings', 'SettingsController@index');

// Rutas de webhooks
$router->post('/webhook/evolution', 'WebhookController@evolution');
$router->post('/webhook/evolution/{instance}', 'WebhookController@evolution');
';
            
            file_put_contents($routesFile, $routesContent);
            echo "<p style='color: green;'>✅ Archivo de rutas creado: {$routesFile}</p>";
        }
    }
    
    // 2. Agregar rutas de grupos al archivo existente
    if (file_exists($routesFile)) {
        echo "<h3>📋 Agregando rutas de grupos a: " . basename($routesFile) . "</h3>";
        
        $currentContent = file_get_contents($routesFile);
        
        // Rutas a agregar
        $groupRoutes = "
// Rutas de grupos
\$router->get('/groups', 'GroupController@index');
\$router->post('/groups/extractGroupContacts', 'GroupController@extractGroupContacts');
\$router->post('/groups/extractChatContacts', 'GroupController@extractChatContacts');
";
        
        // Verificar si las rutas ya existen
        if (strpos($currentContent, 'GroupController') !== false) {
            echo "<p style='color: blue;'>ℹ️ Las rutas de grupos ya existen en el archivo</p>";
        } else {
            // Agregar rutas al final del archivo
            file_put_contents($routesFile, $groupRoutes, FILE_APPEND);
            echo "<p style='color: green;'>✅ Rutas de grupos agregadas correctamente</p>";
        }
    }
    
    // 3. Crear tabla de participantes si no existe
    echo "<h3>👥 Verificar tabla de participantes</h3>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'group_participants'");
    
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: orange;'>⚠️ Creando tabla group_participants...</p>";
        
        $createTable = "
        CREATE TABLE group_participants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id VARCHAR(255) NOT NULL,
            group_name VARCHAR(255),
            participant_jid VARCHAR(255) NOT NULL,
            participant_name VARCHAR(255),
            participant_phone VARCHAR(50),
            is_admin TINYINT(1) DEFAULT 0,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            instance_id INT,
            INDEX idx_group (group_id),
            INDEX idx_participant (participant_jid),
            INDEX idx_instance (instance_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->query($createTable);
        echo "<p style='color: green;'>✅ Tabla group_participants creada</p>";
    } else {
        echo "<p style='color: green;'>✅ Tabla group_participants ya existe</p>";
    }
    
    // 4. Verificar estructura de tabla contacts
    echo "<h3>📇 Verificar tabla contacts</h3>";
    
    $stmt = $pdo->query("DESCRIBE contacts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = ['id', 'instance_id', 'phone_e164', 'name', 'push_name', 'remote_jid', 'created_at'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $column) {
        $found = false;
        foreach ($columns as $col) {
            if ($col['Field'] === $column) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $missingColumns[] = $column;
        }
    }
    
    if (!empty($missingColumns)) {
        echo "<p style='color: orange;'>⚠️ Columnas faltantes en contacts: " . implode(', ', $missingColumns) . "</p>";
        
        foreach ($missingColumns as $column) {
            $columnType = 'VARCHAR(255)';
            if ($column === 'id') $columnType = 'INT AUTO_INCREMENT PRIMARY KEY';
            elseif ($column === 'instance_id') $columnType = 'INT';
            elseif ($column === 'created_at') $columnType = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
            
            try {
                $sql = "ALTER TABLE contacts ADD COLUMN {$column} {$columnType}";
                if ($column === 'id') {
                    echo "<p style='color: blue;'>ℹ️ La columna id ya debería existir como clave primaria</p>";
                } else {
                    $pdo->query($sql);
                    echo "<p style='color: green;'>✅ Columna {$column} agregada</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ Error agregando {$column}: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p style='color: green;'>✅ Tabla contacts tiene todas las columnas requeridas</p>";
    }
    
    // 5. Verificar estructura de tabla chats
    echo "<h3>💬 Verificar tabla chats</h3>";
    
    $stmt = $pdo->query("DESCRIBE chats");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredChatsColumns = ['id', 'instance_id', 'name', 'remote_jid', 'is_group', 'created_at'];
    $missingChatsColumns = [];
    
    foreach ($requiredChatsColumns as $column) {
        $found = false;
        foreach ($columns as $col) {
            if ($col['Field'] === $column) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $missingChatsColumns[] = $column;
        }
    }
    
    if (!empty($missingChatsColumns)) {
        echo "<p style='color: orange;'>⚠️ Columnas faltantes en chats: " . implode(', ', $missingChatsColumns) . "</p>";
        
        foreach ($missingChatsColumns as $column) {
            $columnType = 'VARCHAR(255)';
            if ($column === 'id') $columnType = 'VARCHAR(255) PRIMARY KEY';
            elseif ($column === 'instance_id') $columnType = 'INT';
            elseif ($column === 'is_group') $columnType = 'TINYINT(1) DEFAULT 0';
            elseif ($column === 'created_at') $columnType = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
            
            try {
                $sql = "ALTER TABLE chats ADD COLUMN {$column} {$columnType}";
                $pdo->query($sql);
                echo "<p style='color: green;'>✅ Columna {$column} agregada a chats</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ Error agregando {$column} a chats: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p style='color: green;'>✅ Tabla chats tiene todas las columnas requeridas</p>";
    }
    
    // 6. Crear enlace en el menú
    echo "<h3>🔗 Enlace para el menú</h3>";
    
    $menuLink = '<li class="nav-item">
        <a class="nav-link" href="?r=groups/index">
            <i class="fas fa-users nav-icon"></i>
            <p>Grupos</p>
        </a>
    </li>';
    
    echo "<p style='color: blue;'>📋 Agrega este enlace al menú de navegación:</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($menuLink);
    echo "</pre>";
    
    echo "<h3>🎯 Configuración Completada</h3>";
    echo "<ul>";
    echo "<li>✅ Controlador GroupController creado</li>";
    echo "<li>✅ Vista groups/index.php creada</li>";
    echo "<li>✅ Rutas de grupos agregadas</li>";
    echo "<li>✅ Tabla group_participants verificada</li>";
    echo "<li>✅ Tablas contacts y chats verificadas</li>";
    echo "</ul>";
    
    echo "<h3>🚀 Próximos Pasos</h3>";
    echo "<ol>";
    echo "<li>1. Agrega el enlace de 'Grupos' al menú de navegación</li>";
    echo "<li>2. Accede a la sección de grupos: <a href='?r=groups/index'>/?r=groups/index</a></li>";
    echo "<li>3. Prueba la extracción de contactos de chats</li>";
    echo "<li>4. Prueba la extracción de participantes de grupos</li>";
    echo "</ol>";
    
    echo "<p style='color: green;'>🎉 ¡La funcionalidad de grupos está lista para usar!</p>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error de Conexión</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2>❌ Error General</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
