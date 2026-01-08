# Recrear Base de Datos EVOAPP

## Pasos a seguir:

1. **Ejecutar script de reset:**
   ```sql
   mysql -u usuario -p nombre_db < reset_database.sql
   ```

2. **Crear estructura completa:**
   ```sql
   mysql -u usuario -p nombre_db < evoapp_schema.sql
   ```

3. **Crear tabla de perfiles:**
   ```sql
   mysql -u usuario -p nombre_db < instance_profiles.sql
   ```

4. **Verificar tablas creadas:**
   ```sql
   SHOW TABLES;
   ```

## Orden de ejecución:
1. reset_database.sql (elimina todo)
2. evoapp_schema.sql (crea estructura principal)
3. instance_profiles.sql (crea tabla de perfiles)

## Notas importantes:
- Hacer backup antes de ejecutar
- Ejecutar en orden exacto
- Verificar que no haya errores en cada paso

## Tablas que se crearán:
- ✅ evo_instances (instancias de WhatsApp)
- ✅ instance_profiles (perfiles de instancias)
- ✅ users, roles, permissions (sistema de usuarios)
- ✅ user_instances (permisos por instancia)
- ✅ chats, messages (conversaciones)
- ✅ chat_reads (estados de lectura)
- ✅ contacts, contact_lists, contact_list_items (contactos)
- ✅ campaigns, campaign_messages, campaign_targets (campañas)
- ✅ campaign_runs, campaign_run_items (ejecución)
- ✅ webhook_events (eventos de webhook)
- ✅ audit_log, cron_log (logs del sistema)
