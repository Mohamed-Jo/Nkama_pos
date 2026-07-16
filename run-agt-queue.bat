@echo off
setlocal

set "APP_DIR=%~dp0"
cd /d "%APP_DIR%"

if not exist artisan (
    echo Nao foi encontrado o ficheiro artisan em "%APP_DIR%".
    exit /b 1
)

where php >nul 2>nul
if errorlevel 1 (
    echo PHP nao foi encontrado no PATH.
    exit /b 1
)

echo Nkama POS - AGT Queue
echo Pasta: %APP_DIR%
echo.

:loop
php artisan queue:work database --queue=agt,default --tries=1 --timeout=300 --sleep=3
echo.
echo O worker AGT parou com codigo %ERRORLEVEL%. A reiniciar em 10 segundos...
timeout /t 10 /nobreak >nul
goto loop