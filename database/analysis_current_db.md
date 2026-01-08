# Análisis de Base de Datos Actual

## 📋 **Estado Actual de la BD**

### ✅ **Tablas Correctas (ya existen):**
- `evo_instances` ← **Tabla principal correcta**
- `audit_log` ← Con FKs correctas
- `campaigns` ← Sin FK a `instances`
- `campaign_messages` ← Sin FKs
- `campaign_runs` ← Sin FKs
- `campaign_run_items` ← Sin FKs
- `chats` ← Sin FK a `instances`
- `chat_reads` ← Sin FKs
- `contacts` ← Sin FK a `instances`
- `contact_lists` ← Sin FKs
- `contact_list_items` ← Sin FKs
- `contact_candidates` ← Sin FK a `instances`
- `cron_log` ← Sin FKs
- `messages` ← Sin FK a `instances`
- `webhook_events` ← Sin FK a `instances`

### ❌ **Tabla Incorrecta (debe eliminarse):**
- `instances` ← **Tabla duplicada/incorrecta**

### ⚠️ **Tablas Faltantes:**
- `users` ← No existe
- `roles` ← No existe
- `permissions` ← No existe
- `role_permissions` ← No existe
- `user_roles` ← No existe
- `user_instances` ← No existe
- `instance_profiles` ← No existe

## 🔍 **Problemas Identificados:**

### **1. Tabla `instances` Incorrecta:**
```sql
CREATE TABLE `instances` (
  `id` int(11) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `api_key` varchar(255) NOT NULL,
  `base_url` varchar(255) DEFAULT NULL,
  `webhook_token` varchar(255) DEFAULT NULL,
  `webhook_enabled` tinyint(1) DEFAULT 1,
  `forward_webhook_url` varchar(255) DEFAULT NULL,
  `forward_webhook_enabled` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `last_webhook_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
```

### **2. Datos Existentes:**
- **4 instancias** en `evo_instances` (SCAMMARANO, 2CAMCARGO, CASAMIA, 2CAMSERVICES)
- **3 instancias** en `instances` (main, 2CAMCARGO, SCAMMARANO)
- **Datos duplicados** entre ambas tablas

### **3. FKs Faltantes:**
- La mayoría de tablas no tienen FKs definidas
- `chats`, `messages`, `contacts` no apuntan a `evo_instances`
- Sistema de usuarios no existe

## 🎯 **Solución Requerida:**

### **Paso 1: Eliminar tabla incorrecta**
```sql
DROP TABLE IF EXISTS `instances`;
```

### **Paso 2: Crear tablas faltantes**
- `users`, `roles`, `permissions`, `role_permissions`, `user_roles`
- `user_instances`, `instance_profiles`

### **Paso 3: Agregar FKs faltantes**
- `chats.instance_id` → `evo_instances.id`
- `messages.instance_id` → `evo_instances.id`
- `contacts.instance_id` → `evo_instances.id`
- etc.

## 📊 **Impacto en el Sistema:**

### **Dashboard:**
- ✅ Ya tiene datos en `evo_instances`
- ❌ Modelo `Instance::getStats()` falla por FKs faltantes
- ❌ Vista de dashboard no muestra estadísticas

### **Inbox:**
- ✅ Tiene datos de chats y mensajes
- ❌ No puede relacionar con instancias correctamente

### **Campaigns:**
- ✅ Tiene datos de campañas
- ❌ No puede relacionar con instancias correctamente

## 🚀 **Plan de Acción:**

1. **Backup actual** ← Ya tienes el dump
2. **Eliminar `instances`** ← Tabla incorrecta
3. **Crear tablas de usuarios** ← Sistema completo
4. **Agregar FKs faltantes** ← Relaciones correctas
5. **Probar dashboard** ← Debería funcionar

## ⚡ **Ventaja:**
- **No perder datos existentes** en `evo_instances`
- **Solo eliminar tabla duplicada**
- **Agregar funcionalidad faltante**
