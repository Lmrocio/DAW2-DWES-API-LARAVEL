@echo off
REM Script para generar documentación Swagger con atributos PHP (Windows)
REM Ejecutar en tu máquina desde PowerShell o CMD

echo ==========================================
echo Configuracion de Swagger con Atributos PHP
echo ==========================================
echo.

REM Paso 1: Limpiar caché
echo Paso 1: Limpiando cache de configuracion...
php artisan config:clear

if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Error al limpiar cache
    exit /b 1
)
echo [OK] Cache limpiado
echo.

REM Paso 2: Generar documentación Swagger
echo Paso 2: Generando documentacion OpenAPI...
php artisan l5-swagger:generate

if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Error al generar documentacion
    exit /b 1
)
echo [OK] Documentacion generada
echo.

REM Paso 3: Verificar que el archivo fue creado
echo Paso 3: Verificando archivo generado...
if exist "storage\api-docs\api-docs.json" (
    for %%F in ("storage\api-docs\api-docs.json") do set SIZE=%%~zF
    echo [OK] Archivo creado (Tamaño: %SIZE% bytes)
    echo.
    echo Contenido (primeras lineas):
    type storage\api-docs\api-docs.json | findstr /N "^" | findstr /B "[1-9]:" | findstr /E "^[1-9]: | ^[1-2][0-9]:"
) else (
    echo [ERROR] Archivo no creado
    exit /b 1
)

echo.
echo ==========================================
echo [OK] Swagger configurado correctamente
echo ==========================================
echo.
echo Accede a: http://localhost/api/documentation
echo.
pause
