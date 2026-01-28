@echo off
REM Script para configurar Swagger/OpenAPI desde cero
REM Proyecto: API Recetas DWES
REM Fecha: 28 de enero de 2026

echo ========================================================
echo   Configuracion de Swagger/OpenAPI - API Recetas DWES
echo ========================================================
echo.

REM Detectar si tenemos PHP disponible
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] PHP no encontrado en PATH
    echo.
    echo Por favor, asegurate de tener una de estas opciones:
    echo 1. Docker Desktop corriendo con el proyecto
    echo 2. PHP instalado localmente
    echo 3. Laravel Sail configurado
    echo.
    pause
    exit /b 1
)

echo [OK] PHP encontrado
echo.

echo ========================================
echo   Paso 1: Publicar assets de Swagger UI
echo ========================================
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --tag=assets --force

if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] No se pudieron publicar los assets
    pause
    exit /b 1
)

echo [OK] Assets publicados correctamente
echo.

echo ===============================================
echo   Paso 2: Generar documentacion OpenAPI JSON
echo ===============================================
php artisan l5-swagger:generate

if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] No se pudo generar la documentacion
    echo.
    echo Si el error es "Required @OA\PathItem() not found":
    echo - Revisa las anotaciones en app/Http/Controllers/Controller.php
    echo - Verifica que todos los metodos tengan @OA\Get, @OA\Post, etc.
    echo.
    pause
    exit /b 1
)

echo [OK] Documentacion generada correctamente
echo.

echo ==============================
echo   Paso 3: Verificar archivo JSON
echo ==============================
if exist "storage\api-docs\api-docs.json" (
    echo [OK] Archivo JSON creado: storage\api-docs\api-docs.json
    echo.
    echo Primeras lineas del JSON:
    echo ----------------------------------------
    type storage\api-docs\api-docs.json | more /E +1 | findstr /N "^" | findstr /B "[1-9]:"
    echo ----------------------------------------
) else (
    echo [ERROR] El archivo JSON no fue creado
    pause
    exit /b 1
)

echo.
echo ========================================
echo   Configuracion completada exitosamente!
echo ========================================
echo.
echo Accede a Swagger UI en:
echo   http://localhost/api/documentation
echo   http://localhost:8000/api/documentation
echo.
echo Acceso directo al JSON:
echo   http://localhost/storage/api-docs/api-docs.json
echo.
echo Para autenticarte:
echo   1. Obten un token: POST /api/auth/login
echo      Email: admin@demo.local
echo      Password: password
echo   2. Click en "Authorize" (candado verde)
echo   3. Pega el token completo
echo.
echo Listo para usar!
echo.
pause
