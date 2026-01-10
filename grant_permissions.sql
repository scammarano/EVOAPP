-- Dar permisos al usuario grupoecc para leer information_schema
-- Ejecutar como root o usuario con privilegios en MySQL

-- Paso 1: Dar permiso SELECT en information_schema
GRANT SELECT ON information_schema.* TO 'grupoecc'@'localhost';

-- Paso 2: Dar permiso SHOW DATABASES
GRANT SHOW DATABASES ON *.* TO 'grupoecc'@'localhost';

-- Paso 3: Refrescar privilegios
FLUSH PRIVILEGES;

-- Paso 4: Verificar permisos
SHOW GRANTS FOR 'grupoecc'@'localhost';

-- Nota: Si esto no funciona, el usuario puede necesitar permisos globales
-- Alternativa: GRANT SELECT ON *.* TO 'grupoecc'@'localhost';
