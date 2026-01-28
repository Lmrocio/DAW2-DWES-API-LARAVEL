#!/bin/bash
# Script para generar documentación Swagger con atributos PHP
# Ejecutar en tu máquina (no en este entorno)

set -e

echo "=========================================="
echo "Configuración de Swagger con Atributos PHP"
echo "=========================================="
echo ""

# Paso 1: Limpiar caché
echo "Paso 1: Limpiando caché de configuración..."
php artisan config:clear

if [ $? -eq 0 ]; then
    echo "✅ Caché limpiado"
else
    echo "❌ Error al limpiar caché"
    exit 1
fi

echo ""

# Paso 2: Generar documentación Swagger
echo "Paso 2: Generando documentación OpenAPI..."
php artisan l5-swagger:generate

if [ $? -eq 0 ]; then
    echo "✅ Documentación generada"
else
    echo "❌ Error al generar documentación"
    exit 1
fi

echo ""

# Paso 3: Verificar que el archivo fue creado
echo "Paso 3: Verificando archivo generado..."
if [ -f "storage/api-docs/api-docs.json" ]; then
    SIZE=$(du -h storage/api-docs/api-docs.json | cut -f1)
    echo "✅ Archivo creado (Tamaño: $SIZE)"
    echo ""
    echo "Contenido (primeras 20 líneas):"
    head -20 storage/api-docs/api-docs.json
else
    echo "❌ Archivo no creado"
    exit 1
fi

echo ""
echo "=========================================="
echo "✅ Swagger configurado correctamente"
echo "=========================================="
echo ""
echo "Accede a: http://localhost/api/documentation"
echo ""
