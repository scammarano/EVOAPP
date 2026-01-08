-- Verificar datos existentes
SELECT '=== evo_instances ===' as tabla;
SELECT COUNT(*) as total FROM evo_instances;
SELECT * FROM evo_instances LIMIT 5;

SELECT '=== users ===' as tabla;
SELECT COUNT(*) as total FROM users;
SELECT * FROM users LIMIT 5;

SELECT '=== user_instances ===' as tabla;
SELECT COUNT(*) as total FROM user_instances;
SELECT * FROM user_instances LIMIT 5;
