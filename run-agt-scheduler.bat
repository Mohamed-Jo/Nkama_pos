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

echo Nkama POS - Laravel Scheduler
echo Pasta: %APP_DIR%
echo.

:loop
php artisan schedule:work
echo.
echo O scheduler parou com codigo %ERRORLEVEL%. A reiniciar em 10 segundos...
timeout /t 10 /nobreak >nul
goto loop