-- ACTUALIZAR CON TU URL REAL DE EVOLUTION API
-- Reemplaza 'http://localhost:8080' con tu URL real

-- Actualizar SCAMMARANO (la única con datos)
UPDATE evo_instances 
SET base_url = 'http://localhost:8080'  -- <-- CAMBIA ESTA URL
WHERE slug = 'SCAMMARANO';

-- Actualizar las otras instancias también
UPDATE evo_instances 
SET base_url = 'http://localhost:8080'  -- <-- CAMBIA ESTA URL
WHERE slug IN ('2CAMCARGO', 'CASAMIA', '2CAMSERVICES');

-- Verificar resultado
SELECT id, slug, 
       CASE 
           WHEN base_url IS NULL OR base_url = '' THEN 'VACÍO'
           ELSE base_url
       END as base_url_status,
       api_key
FROM evo_instances
ORDER BY id;

SELECT 'Base URLs actualizadas - REEMPLAZA con tu URL real' as resultado;
