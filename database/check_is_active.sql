-- Verificar campo is_active en evo_instances
SELECT '=== Verificar is_active ===' as check;
DESCRIBE evo_instances;

SELECT '=== Datos con is_active ===' as check;
SELECT id, slug, is_active FROM evo_instances;

-- Actualizar is_active si es NULL o 0
UPDATE evo_instances SET is_active = 1 WHERE is_active IS NULL OR is_active = 0;

SELECT '=== Después de actualizar ===' as check;
SELECT id, slug, is_active FROM evo_instances;
