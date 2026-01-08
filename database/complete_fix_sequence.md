# Secuencia Completa para Corregir Tablas

## 🎯 Objetivo Final
- ❌ Eliminar tabla `instances` incorrecta
- ✅ Crear tablas correctas relacionadas con `evo_instances`
- ✅ Recrear todas las Foreign Keys correctamente

## 📋 Secuencia de Ejecución

### Paso 1: Descubrir FKs existentes
```bash
mysql -u usuario -p nombre_db < find_fks.sql
```
*Esto mostrará qué FKs apuntan a `instances`*

### Paso 2: Eliminar FKs y tabla incorrecta
```bash
mysql -u usuario -p nombre_db < drop_instances_safe.sql
```
*Elimina FKs que bloquean y la tabla `instances`*

### Paso 3: Crear tablas faltantes
```bash
mysql -u usuario -p nombre_db < minimal_fix.sql
```
*Crea `instance_profiles`, `users`, y tablas de sistema*

### Paso 4: Recrear FKs correctas
```bash
mysql -u usuario -p nombre_db < recreate_fks.sql
```
*Recrea todas las FKs apuntando a `evo_instances`*

### Paso 5: Verificar resultado
```bash
mysql -u usuario -p nombre_db -e "SHOW TABLES;"
mysql -u usuario -p nombre_db -e "
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM 
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE 
    REFERENCED_TABLE_SCHEMA = DATABASE() 
    AND REFERENCED_TABLE_NAME = 'evo_instances';
"
```

## 🔄 Flujo de Datos

```
ANTES (Incorrecto):
instances ← user_instances
instances ← chats
instances ← contacts
instances ← campaigns
...

DESPUÉS (Correcto):
evo_instances ← user_instances
evo_instances ← chats
evo_instances ← contacts
evo_instances ← campaigns
evo_instances ← instance_profiles
```

## ✅ Resultado Esperado

Después de ejecutar toda la secuencia:

1. **No existe tabla `instances`** ❌
2. **Existe tabla `evo_instances`** ✅
3. **Todas las tablas apuntan a `evo_instances`** ✅
4. **Dashboard funciona sin errores** ✅
5. **Modelo Instance funciona correctamente** ✅

## ⚠️ Notas Importantes

- **Backup**: Hacer backup antes de empezar
- **Orden**: Ejecutar en secuencia exacta
- **Verificación**: Revisar cada paso antes de continuar
- **FKs**: El script `recreate_fks.sql` usa `IF EXISTS` implícito

## 🎉 Checklist Final

- [ ] Paso 1: Encontrar FKs
- [ ] Paso 2: Eliminar tabla `instances`
- [ ] Paso 3: Crear tablas faltantes
- [ ] Paso 4: Recrear FKs correctas
- [ ] Paso 5: Verificar estructura
- [ ] Probar dashboard

¡Al finalizar todo esto, el sistema estará completamente corregido! 🚀
