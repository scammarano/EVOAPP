# EVOAPP Database Setup

## 📋 Overview
Este sistema de base de datos está diseñado para soportar múltiples instancias de WhatsApp con gestión completa de campañas, contactos y mensajes.

## 🗄️ Tablas Principales

### Instancias y Permisos
- **evo_instances**: Instancias de WhatsApp (principal)
- **instance_profiles**: Perfiles y avatares de instancias
- **users**: Usuarios del sistema
- **roles, permissions, user_roles**: Sistema de permisos
- **user_instances**: ACL por instancia

### Mensajería y Chat
- **chats**: Conversaciones de WhatsApp
- **messages**: Mensajes individuales
- **chat_reads**: Estados de lectura por usuario

### Contactos
- **contacts**: Contactos sincronizados
- **contact_lists**: Listas de distribución
- **contact_list_items**: Miembros de listas
- **contact_candidates**: Candidatos para importación

### Campañas
- **campaigns**: Configuración de campañas
- **campaign_messages**: Contenido de campañas
- **campaign_targets**: Destinatarios de campañas
- **campaign_runs**: Ejecuciones de campañas
- **campaign_run_items**: Resultados individuales

### Sistema
- **webhook_events**: Eventos de webhook
- **audit_log**: Registro de auditoría
- **cron_log**: Logs de tareas programadas

## 🔧 Setup Rápido

### Opción 1: Script Completo
```bash
mysql -u usuario -p nombre_db < reset_and_recreate.sql
```

### Opción 2: Scripts Separados
```bash
# 1. Resetear base de datos
mysql -u usuario -p nombre_db < reset_database.sql

# 2. Crear estructura principal
mysql -u usuario -p nombre_db < evoapp_schema.sql

# 3. Crear tabla de perfiles
mysql -u usuario -p nombre_db < instance_profiles.sql

# 4. Verificar
mysql -u usuario -p nombre_db -e "SHOW TABLES;"
```

## 📊 Relaciones Importantes

```
evo_instances (1) ←→ instance_profiles (1)
evo_instances (1) ←→ user_instances (N)
evo_instances (1) ←→ chats (N) ←→ messages (N)
evo_instances (1) ←→ contacts (N)
evo_instances (1) ←→ contact_lists (N) ←→ contact_list_items (N)
evo_instances (1) ←→ campaigns (N) ←→ campaign_messages (N)
evo_instances (1) ←→ campaigns (N) ←→ campaign_targets (N)
evo_instances (1) ←→ campaigns (N) ←→ campaign_runs (N) ←→ campaign_run_items (N)
```

## ⚠️ Notas Importantes

1. **Backup**: Siempre hacer backup antes de resetear
2. **Permisos**: El usuario MySQL necesita CREATE, ALTER, DROP, REFERENCES
3. **Charset**: Todas las tablas usan utf8mb4_unicode_ci
4. **Engine**: InnoDB con transacciones ACID

## 🚀 Ready for Production

La base de datos está lista para soportar:
- ✅ Múltiples instancias de WhatsApp
- ✅ Sistema de usuarios y permisos
- ✅ Gestión completa de campañas
- ✅ Contactos y listas de distribución
- ✅ Mensajería en tiempo real
- ✅ Webhooks y auditoría completa
