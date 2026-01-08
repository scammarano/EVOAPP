-- Test simple query
SELECT '=== Test simple ===' as test;

-- Query exacta que usa el código
SELECT * FROM evo_instances WHERE is_active = 1 ORDER BY slug;

-- Query sin filtro is_active
SELECT * FROM evo_instances ORDER BY slug;
