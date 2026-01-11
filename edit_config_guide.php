<?php
// Guía para editar configuración de BD
echo "🔧 GUÍA PARA EDITAR CONFIGURACIÓN DE BASE DE DATOS\n\n";

$config_file = __DIR__ . '/config.php';

if (file_exists($config_file)) {
    echo "📂 Archivo encontrado: $config_file\n";
    echo "📖 Contenido actual:\n";
    echo "================================\n";
    readfile($config_file);
    echo "================================\n\n";
    
    echo "🔧 PASOS PARA EDITAR:\n";
    echo "1. Abre el archivo: $config_file\n";
    echo "2. Reemplaza los valores placeholder con tus credenciales reales:\n\n";
    
    echo "📝 EJEMPLO DE CONFIGURACIÓN CORRECTA:\n";
    echo "<?php\n";
    echo "// Configuración de Base de Datos\n";
    echo "define(\"DB_HOST\", \"localhost\");           // Tu servidor BD\n";
    echo "define(\"DB_NAME\", \"grupoecc_evoapp\");      // Nombre BD real\n";
    echo "define(\"DB_USER\", \"grupoecc_evoapp\");       // Usuario BD real\n";
    echo "define(\"DB_PASS\", \"TU_PASSWORD_REAL\");       // Password BD real\n\n";
    echo "// Configuración de la Aplicación\n";
    echo "define(\"APP_URL\", \"https://camcam.com.ve/evoappws/\");\n";
    echo "define(\"APP_NAME\", \"EVOAPP\");\n\n";
    echo "// Zona horaria\n";
    echo "date_default_timezone_set(\"America/Bogota\");\n";
    echo "?>\n\n";
    
    echo "🎯 TUS CREDENCIALES REALES:\n";
    echo "• DB_HOST: Servidor de tu base de datos (usualmente localhost)\n";
    echo "• DB_NAME: Nombre exacto de tu base de datos\n";
    echo "• DB_USER: Usuario de la base de datos\n";
    echo "• DB_PASS: Contraseña del usuario\n\n";
    
    echo "🔍 Si no conoces tus credenciales:\n";
    echo "1. Revisa tu panel de hosting (cPanel, Plesk, etc.)\n";
    echo "2. Busca la sección \"Base de Datos\" o \"MySQL® Databases\"\n";
    echo "3. Anota el nombre de BD, usuario y contraseña\n";
    echo "4. El host usualmente es \"localhost\" o una IP\n\n";
    
    echo "⚠️ IMPORTANTE:\n";
    echo "• No uses comillas en los valores\n";
    echo "• Mantén el formato exacto\n";
    echo "• Guarda el archivo después de editar\n";
    echo "• Reinicia el servidor web después\n\n";
    
    echo "🚀 Después de editar, ejecuta:\n";
    echo "https://camcam.com.ve/evoappws/update_db_class.php\n";
    
} else {
    echo "❌ Archivo config.php no encontrado\n";
    echo "📍 Debe estar en: " . __DIR__ . "/config.php\n";
}
?>
