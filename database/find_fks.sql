-- Encontrar todas las FKs que apuntan a la tabla 'instances'
SELECT 
    TABLE_NAME as tabla_hija,
    COLUMN_NAME as columna_fk,
    REFERENCED_TABLE_NAME as tabla_padre,
    REFERENCED_COLUMN_NAME as columna_padre,
    CONSTRAINT_NAME as nombre_fk
FROM 
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE 
    REFERENCED_TABLE_SCHEMA = DATABASE() 
    AND REFERENCED_TABLE_NAME = 'instances'
ORDER BY 
    TABLE_NAME, COLUMN_NAME;
