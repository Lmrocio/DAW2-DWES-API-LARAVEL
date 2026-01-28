#!/bin/bash
# Script para configurar Swagger/OpenAPI desde cero
# Proyecto: API Recetas DWES
# Fecha: 28 de enero de 2026

echo "🚀 Configuración de Swagger/OpenAPI - API Recetas DWES"
echo "======================================================"
echo ""

# Colores para output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Detectar si estamos usando Docker/Sail
if [ -f "./vendor/bin/sail" ]; then
    echo -e "${BLUE}ℹ Detectado Laravel Sail${NC}"
    PHP_CMD="./vendor/bin/sail artisan"
elif command -v docker-compose &> /dev/null && docker-compose ps | grep -q "Up"; then
    echo -e "${BLUE}ℹ Detectado Docker Compose${NC}"
    PHP_CMD="docker-compose exec laravel.test php artisan"
elif command -v php &> /dev/null; then
    echo -e "${BLUE}ℹ Detectado PHP local${NC}"
    PHP_CMD="php artisan"
else
    echo -e "${RED}❌ No se encontró PHP. Asegúrate de tener Docker corriendo o PHP instalado.${NC}"
    exit 1
fi

echo ""
echo "📦 Paso 1: Publicar assets de Swagger UI"
echo "----------------------------------------"
$PHP_CMD vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --tag=assets --force

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Assets publicados correctamente${NC}"
else
    echo -e "${RED}❌ Error al publicar assets${NC}"
    exit 1
fi

echo ""
echo "📝 Paso 2: Generar documentación OpenAPI JSON"
echo "---------------------------------------------"
$PHP_CMD l5-swagger:generate

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Documentación generada correctamente${NC}"
    echo -e "${BLUE}📁 Archivo creado: storage/api-docs/api-docs.json${NC}"
else
    echo -e "${RED}❌ Error al generar documentación${NC}"
    echo -e "${YELLOW}⚠ Si el error es 'Required @OA\PathItem() not found', revisa las anotaciones en los controladores${NC}"
    exit 1
fi

echo ""
echo "🔗 Paso 3: Verificar archivo JSON"
echo "---------------------------------"
if [ -f "storage/api-docs/api-docs.json" ]; then
    FILE_SIZE=$(du -h storage/api-docs/api-docs.json | cut -f1)
    echo -e "${GREEN}✅ Archivo encontrado (Tamaño: $FILE_SIZE)${NC}"
    echo -e "${BLUE}📄 Primeras líneas del JSON:${NC}"
    head -15 storage/api-docs/api-docs.json
else
    echo -e "${RED}❌ El archivo JSON no fue creado${NC}"
    exit 1
fi

echo ""
echo "🌐 Paso 4: Información de acceso"
echo "--------------------------------"
echo -e "${GREEN}✅ Configuración completada exitosamente!${NC}"
echo ""
echo -e "${BLUE}🔗 Accede a Swagger UI en:${NC}"
echo "   http://localhost/api/documentation"
echo "   http://localhost:8000/api/documentation"
echo ""
echo -e "${BLUE}📄 Acceso directo al JSON:${NC}"
echo "   http://localhost/storage/api-docs/api-docs.json"
echo ""
echo -e "${YELLOW}🔐 Para autenticarte:${NC}"
echo "   1. Obtén un token: POST /api/auth/login"
echo "      Email: admin@demo.local"
echo "      Password: password"
echo "   2. Click en 'Authorize' (candado verde)"
echo "   3. Pega el token completo"
echo ""
echo -e "${GREEN}¡Listo para usar!${NC} 🎉"
