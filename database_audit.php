<?php
// Script de auditoría completa para EVOAPP
// Ejecutar: https://camcam.com.ve/evoappws/database_audit.php

echo "<h2>🔍 Auditoría Completa de Base de Datos</h2>";

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'grupoecc_evoappWS';
$user = 'grupoecc_toto';
$pass = 'Toto123*.A';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h3>📋 Verificando Conexión</h3>";
    echo "<p style='color: green;'>✅ Conexión exitosa a la base de datos</p>";
    
    // 1. Obtener TODAS las tablas de la base de datos
    echo "<h3>📊 Análisis de TODAS las Tablas</h3>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>📊 Encontradas <strong>" . count($allTables) . "</strong> tablas en total</p>";
    
    foreach ($allTables as $table) {
        echo "<h4>📋 Tabla: <strong>$table</strong></h4>";
        
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
            echo "<tr style='background: #f0f0f0;'><th>Columna</th><th>Tipo</th><th>Nulo</th><th>Extra</th></tr>";
            
            foreach ($columns as $column) {
                $type = $column['Type'];
                $null = $column['Null'] === 'YES' ? '✅' : '❌';
                $extra = !empty($column['Extra']) ? $column['Extra'] : '';
                
                echo "<tr>";
                echo "<td><strong>{$column['Field']}</strong></td>";
                echo "<td>$type</td>";
                echo "<td>$null</td>";
                echo "<td>$extra</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            // Verificar columnas críticas (solo para tablas principales)
            $mainTables = ['contacts', 'chats', 'instances', 'webhook_events', 'group_participants', 'contact_list_items', 'users', 'roles', 'permissions'];
            
            if (in_array($table, $mainTables)) {
                $criticalColumns = ['id', 'instance_id', 'name', 'remote_jid', 'phone_e164', 'created_at', 'updated_at'];
                $missingColumns = [];
                
                foreach ($criticalColumns as $criticalColumn) {
                    $found = false;
                    foreach ($columns as $column) {
                        if ($column['Field'] === $criticalColumn) {
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        $missingColumns[] = $criticalColumn;
                    }
                }
                
                if (!empty($missingColumns)) {
                    echo "<p style='color: red;'>⚠️ Columnas críticas faltantes: " . implode(', ', $missingColumns) . "</p>";
                } else {
                    echo "<p style='color: green;'>✅ Todas las columnas críticas existen</p>";
                }
            } else {
                echo "<p style='color: blue;'>ℹ️ Tabla adicional del sistema</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Error analizando tabla $table: " . $e->getMessage() . "</p>";
        }
    }
    
    // 2. Análisis de todos los archivos PHP
    echo "<h3>📁 Análisis de Archivos PHP del Sistema</h3>";
    
    function scanDirectory($dir, &$phpFiles) {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item[0] === '.') continue;
            
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                scanDirectory($path, $phpFiles);
            } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $phpFiles[] = $path;
            }
        }
    }
    
    $phpFiles = [];
    scanDirectory('.', $phpFiles);
    
    echo "<p>📁 Encontrados <strong>" . count($phpFiles) . "</strong> archivos PHP</p>";
    
    // Analizar archivos que usan Database/DB
    echo "<h4>🔍 Archivos que usan clases Database:</h4>";
    
    foreach ($phpFiles as $file) {
        $content = file_get_contents($file);
        
        if (strpos($content, 'Database') !== false || strpos($content, 'DB::') !== false) {
            echo "<p>📄 <strong>$file</strong> - ";
            
            if (strpos($content, 'App\Core\Database') !== false) {
                echo "✅ Usa <code>App\Core\Database</code>";
            }
            if (strpos($content, 'App\Core\DB') !== false) {
                echo "✅ Usa <code>App\Core\DB</code>";
            }
            if (strpos($content, 'DB::') !== false) {
                echo "✅ Usa <code>DB::</code>";
            }
            if (strpos($content, 'Database::getInstance') !== false) {
                echo "✅ Usa <code>Database::getInstance</code>";
            }
            
            echo "</p>";
        }
    }
    
    // 3. Verificar clases Database
    echo "<h3>🔍 Verificando Clases Database</h3>";
    
    $databaseFiles = [
        'app/Core/Database.php',
        'app/Core/DB.php',
        'app/Core/db.php'
    ];
    
    foreach ($databaseFiles as $file) {
        if (file_exists($file)) {
            echo "<p>📁 Encontrado: <strong>$file</strong></p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No encontrado: <strong>$file</strong></p>";
        }
    }
    
    // 4. Análisis de consultas SQL en archivos
    echo "<h3>🔍 Análisis de Consultas SQL</h3>";
    
    $sqlQueries = [];
    
    foreach ($phpFiles as $file) {
        $content = file_get_contents($file);
        
        // Buscar consultas SQL
        preg_match_all('/SELECT\s+.*?\s+FROM\s+(\w+)/i', $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $table) {
                if (!isset($sqlQueries[$table])) {
                    $sqlQueries[$table] = [];
                }
                $sqlQueries[$table][] = $file;
            }
        }
        
        // Buscar INSERT INTO
        preg_match_all('/INSERT\s+INTO\s+(\w+)/i', $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $table) {
                if (!isset($sqlQueries[$table])) {
                    $sqlQueries[$table] = [];
                }
                $sqlQueries[$table][] = $file;
            }
        }
        
        // Buscar UPDATE
        preg_match_all('/UPDATE\s+(\w+)/i', $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $table) {
                if (!isset($sqlQueries[$table])) {
                    $sqlQueries[$table] = [];
                }
                $sqlQueries[$table][] = $file;
            }
        }
    }
    
    echo "<h4>📊 Tablas referenciadas en consultas SQL:</h4>";
    
    foreach ($sqlQueries as $table => $files) {
        echo "<p><strong>$table</strong> - usada en: " . implode(', ', array_unique($files)) . "</p>";
    }
    
    // 5. Verificar Contact.php
    echo "<h3>📋 Análisis de Contact.php</h3>";
    
    $contactFile = 'app/Models/Contact.php';
    
    if (file_exists($contactFile)) {
        $content = file_get_contents($contactFile);
        
        echo "<h4>📝 Contenido de Contact.php:</h4>";
        
        // Verificar qué clase usa
        if (strpos($content, 'App\Core\Database') !== false) {
            echo "<p style='color: green;'>✅ Contact.php usa: <strong>App\Core\Database</strong> ✓</p>";
        } elseif (strpos($content, 'App\Core\DB') !== false) {
            echo "<p style='color: green;'>✅ Contact.php usa: <strong>App\Core\DB</strong> ✓</p>";
        } elseif (strpos($content, 'Database') !== false) {
            echo "<p style='color: green;'>✅ Contact.php usa: <strong>Database</strong> (genérica)</p>";
        } else {
            echo "<p style='color: red;'>❌ Contact.php no usa ninguna clase Database específica</p>";
        }
        
        // Verificar métodos getAll
        if (preg_match_all('/function\s+getAll\s*\(/', $content)) {
            echo "<p style='color: orange;'>⚠️ Se encontraron múltiples métodos <strong>getAll()</strong></p>";
        } else {
            echo "<p style='color: green;'>✅ No hay conflictos de método getAll</p>";
        }
    }
    
    // 4. Verificar archivos de scripts
    echo "<h3>📁 Análisis de Scripts</h3>";
    
    $scriptFiles = [
        'extract_all_data.php',
        'extract_all_data_fixed.php',
        'extract_all_data_clean.php',
        'extract_from_api.php',
        'extract_from_api_fixed.php',
        'extract_from_api_final.php'
    ];
    
    foreach ($scriptFiles as $script) {
        if (file_exists($script)) {
            echo "<p>📄 Encontrado: <strong>$script</strong></p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No encontrado: <strong>$script</strong></p>";
        }
    }
    
    // 5. Recomendaciones
    echo "<h3>🛠️ Recomendaciones</h3>";
    
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🔧 Para Corregir Contact.php:</h4>";
    echo "<ol>";
    echo "<li>1. Usar <code>App\Core\DB</code> en todos los métodos estáticos</li>";
    echo "<li>2. Eliminar métodos duplicados <code>getAll()</code></li>";
    echo "<li>3. Verificar que todos los métodos usen <code>DB::</code> o <code>$this->db</code></li>";
    echo "<li>4. Probar con <code>extract_from_api_final.php</code></li>";
    echo "</ol>";
    
    echo "<h4>🗑️ Para Scripts:</h4>";
    echo "<ol>";
    echo "<li>1. Usar solo <code>extract_from_api_final.php</code> (versión corregida)</li>";
    echo "<li>2. Eliminar scripts antiguos con errores</li>";
    echo "<li>3. Verificar schema antes de ejecutar queries</li>";
    echo "<li>4. Usar <code>try-catch</code> para manejar errores</li>";
    echo "</ol>";
    
    echo "<h4>📊 Para Base de Datos:</h4>";
    echo "<ol>";
    echo "<li>1. Asegurarse que todas las tablas tengan columnas críticas</li>";
    echo "<li>2. Crear script de migración para estandarizar schema</li>";
    echo "<li>3. Usar siempre <code>App\Core\DB</code> para consistencia</li>";
    echo "</ol>";
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error de Conexión</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2>❌ Error General</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
