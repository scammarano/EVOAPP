<?php

/**
 * 🗄️ Script de Migración de Base de Datos
 * Convierte estructura PHP actual a migraciones Laravel
 */

// Configuración de la base de datos actual
$oldConfig = [
    'host' => 'localhost',
    'database' => 'grupoecc_evoappWS',
    'username' => 'grupoecc_toto',
    'password' => 'Toto123*.A'
];

// Conexión a la base de datos actual
try {
    $pdo = new PDO(
        "mysql:host={$oldConfig['host']};dbname={$oldConfig['database']}",
        $oldConfig['username'],
        $oldConfig['password']
    );
    
    echo "🔗 Conectado a la base de datos actual\n";
    
    // Obtener estructura de tablas
    $tables = [
        'users', 'roles', 'permissions', 'user_roles', 'user_permissions',
        'evo_instances', 'user_instances', 'instance_profiles',
        'chats', 'messages', 'chat_reads',
        'contacts', 'contact_lists', 'contact_list_items',
        'campaigns', 'campaign_contacts', 'campaign_messages',
        'audit_logs', 'cron_logs'
    ];
    
    $migrationFiles = [];
    
    foreach ($tables as $table) {
        echo "📋 Analizando tabla: $table\n";
        
        // Obtener estructura de la tabla
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener datos de la tabla
        $stmt = $pdo->query("SELECT * FROM $table LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generar migración Laravel
        $migrationContent = generateMigration($table, $columns, $sampleData);
        $migrationFiles[] = $migrationContent;
    }
    
    // Crear archivos de migración
    createMigrationFiles($migrationFiles);
    
    echo "✅ Migraciones generadas exitosamente\n";
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
}

function generateMigration($tableName, $columns, $sampleData) {
    $timestamp = date('Y_m_d_His');
    $className = 'Create' . ucfirst(str_replace('_', '', $tableName)) . 'Table';
    
    $migration = "<?php\n\n";
    $migration .= "use Illuminate\Database\Migrations\Migration;\n";
    $migration .= "use Illuminate\Database\Schema\Blueprint;\n";
    $migration .= "use Illuminate\Support\Facades\Schema;\n\n";
    $migration .= "return new class extends Migration\n";
    $migration .= "{\n";
    $migration .= "    /**\n";
    $migration .= "     * Run the migrations.\n";
    $migration .= "     */\n";
    $migration .= "    public function up()\n";
    $migration .= "    {\n";
    $migration .= "        Schema::create('$tableName', function (Blueprint \$table) {\n";
    
    foreach ($columns as $column) {
        $migration .= generateColumnDefinition($column);
    }
    
    $migration .= "        });\n";
    $migration .= "    }\n\n";
    
    $migration .= "    /**\n";
    $migration .= "     * Reverse the migrations.\n";
    $migration .= "     */\n";
    $migration .= "    public function down()\n";
    $migration .= "    {\n";
    $migration .= "        Schema::dropIfExists('$tableName');\n";
    $migration .= "    }\n";
    $migration .= "};\n";
    
    return [
        'filename' => "{$timestamp}_create_{$tableName}_table.php",
        'content' => $migration
    ];
}

function generateColumnDefinition($column) {
    $name = $column['Field'];
    $type = convertColumnType($column['Type']);
    $nullable = $column['Null'] === 'YES' ? '->nullable()' : '';
    $default = $column['Default'] ? "->default('{$column['Default']}')" : '';
    $autoIncrement = strpos($column['Extra'], 'auto_increment') !== false ? '->autoIncrement()' : '';
    
    return "            \$table->$type('$name')$nullable$default$autoIncrement;\n";
}

function convertColumnType($mysqlType) {
    if (strpos($mysqlType, 'int') !== false) {
        return 'integer';
    } elseif (strpos($mysqlType, 'varchar') !== false) {
        return 'string';
    } elseif (strpos($mysqlType, 'text') !== false) {
        return 'text';
    } elseif (strpos($mysqlType, 'datetime') !== false) {
        return 'dateTime';
    } elseif (strpos($mysqlType, 'timestamp') !== false) {
        return 'timestamp';
    } elseif (strpos($mysqlType, 'tinyint(1)') !== false) {
        return 'boolean';
    } elseif (strpos($mysqlType, 'decimal') !== false) {
        return 'decimal';
    }
    
    return 'string';
}

function createMigrationFiles($migrations) {
    $directory = 'database/migrations';
    
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    foreach ($migrations as $migration) {
        $filepath = $directory . '/' . $migration['filename'];
        file_put_contents($filepath, $migration['content']);
        echo "📄 Creado: {$migration['filename']}\n";
    }
}

echo "\n🎯 Script completado. Revisa la carpeta database/migrations/\n";
?>
