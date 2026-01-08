# Fix Instances Tables

## 🎯 Objetivo
Eliminar la tabla incorrecta `instances` y crear todas las tablas relacionadas correctamente con `evo_instances`.

## 📋 Tablas a Eliminar (Incorrectas)
- ❌ `instances` (tabla incorrecta que no debería existir)
- ❌ Todas las tablas relacionadas que apunten a `instances`

## 📋 Tablas a Crear (Correctas)
- ✅ `instance_profiles` ←→ `evo_instances`
- ✅ `chats` ←→ `evo_instances`
- ✅ `messages` ←→ `evo_instances`
- ✅ `contacts` ←→ `evo_instances`
- ✅ `contact_lists` ←→ `evo_instances`
- ✅ `campaigns` ←→ `evo_instances`
- ✅ `webhook_events` ←→ `evo_instances`
- ✅ Todas las tablas relacionadas con FKs correctas

## 🔧 Ejecución

### Paso 1: Ejecutar script de corrección
```bash
mysql -u usuario -p nombre_db < fix_instances_tables.sql
```

### Paso 2: Verificar tablas creadas
```bash
mysql -u usuario -p nombre_db -e "SHOW TABLES;"
```

### Paso 3: Verificar relaciones
```bash
mysql -u usuario -p nombre_db -e "
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM 
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE 
    REFERENCED_TABLE_SCHEMA = 'nombre_db' 
    AND REFERENCED_TABLE_NAME = 'evo_instances';
"
```

## ⚠️ Importante

1. **Backup**: Hacer backup antes de ejecutar
2. **evo_instances**: Esta tabla NO se elimina, se asume que ya existe
3. **FKs**: Todas las nuevas tablas apuntan a `evo_instances`
4. **CASCADE**: Se usa ON DELETE CASCADE para mantener integridad

## 🎉 Resultado Esperado

Después de ejecutar el script:
- ✅ No existirá la tabla `instances`
- ✅ Todas las tablas relacionadas apuntarán a `evo_instances`
- ✅ El dashboard funcionará correctamente
- ✅ El modelo Instance funcionará sin errores
- ✅ Todas las relaciones estarán correctamente definidas

## 📊 Estructura Final

```
evo_instances (principal)
├── instance_profiles
├── chats → messages
├── contacts → contact_lists → contact_list_items
├── campaigns → campaign_messages → campaign_targets
├── campaigns → campaign_runs → campaign_run_items
├── contact_candidates
└── webhook_events
```
