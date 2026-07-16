@echo off
setlocal

set "APP_DIR=%~dp0"
cd /d "%APP_DIR%"

if not exist artisan (
    echo Nao foi encontrado o ficheiro artisan em "%APP_DIR%".
    echo Execute este ficheiro a partir da pasta raiz do Nkama POS.
    pause
    exit /b 1
)

where php >nul 2>nul
if errorlevel 1 (
    echo PHP nao foi encontrado no PATH.
    echo Abra este ficheiro pelo terminal do Laragon ou configure o PATH do PHP.
    pause
    exit /b 1
)

echo A iniciar Jobs AGT...
echo.
echo - Fila AGT: run-agt-queue.bat
echo - Scheduler: run-agt-scheduler.bat
echo.

start "Nkama POS - AGT Queue" cmd /k "cd /d ""%APP_DIR%"" && run-agt-queue.bat"
start "Nkama POS - Laravel Scheduler" cmd /k "cd /d ""%APP_DIR%"" && run-agt-scheduler.bat"

echo Janelas iniciadas. Pode minimizar, mas nao feche enquanto o sistema estiver em uso.
pause
