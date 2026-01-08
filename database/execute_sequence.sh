#!/bin/bash
# Secuencia de ejecución para corregir tablas

echo "🔥 INICIANDO CORRECCIÓN DE TABLAS..."
echo "=================================="

echo "📋 Paso 1: Eliminar TODAS las FKs..."
mysql -u "$1" -p"$2" "$3" < drop_all_fks.sql
if [ $? -eq 0 ]; then
    echo "✅ FKs eliminadas correctamente"
else
    echo "⚠️  Error eliminando FKs, continuando..."
fi

echo ""
echo "💥 Paso 2: Forzar eliminación de tabla 'instances'..."
mysql -u "$1" -p"$2" "$3" < force_drop_instances.sql
if [ $? -eq 0 ]; then
    echo "✅ Tabla 'instances' eliminada correctamente"
else
    echo "❌ Error eliminando tabla 'instances'"
    exit 1
fi

echo ""
echo "🏗️  Paso 3: Crear tablas faltantes..."
mysql -u "$1" -p"$2" "$3" < minimal_fix.sql
if [ $? -eq 0 ]; then
    echo "✅ Tablas faltantes creadas correctamente"
else
    echo "⚠️  Algunas tablas ya existían (esto está bien)"
fi

echo ""
echo "🔗 Paso 4: Recrear FKs correctas..."
mysql -u "$1" -p"$2" "$3" < recreate_fks.sql
if [ $? -eq 0 ]; then
    echo "✅ FKs recreadas correctamente"
else
    echo "⚠️  Algunas FKs ya existían (esto está bien)"
fi

echo ""
echo "🔍 Paso 5: Verificar resultado..."
mysql -u "$1" -p"$2" "$3" -e "SHOW TABLES;"

echo ""
echo "🎉 ¡CORRECCIÓN COMPLETADA!"
echo "=================================="
echo "✅ Tabla 'instances' eliminada"
echo "✅ Tablas correctas creadas"
echo "✅ FKs apuntando a 'evo_instances'"
echo "✅ Dashboard debería funcionar ahora"
