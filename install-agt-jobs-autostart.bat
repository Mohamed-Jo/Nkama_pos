@echo off
setlocal

set "APP_DIR=%~dp0"
set "TASK_QUEUE=Nkama POS AGT Queue"
set "TASK_SCHEDULER=Nkama POS Laravel Scheduler"

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

echo A instalar arranque automatico dos Jobs AGT...
echo Pasta: %APP_DIR%
echo.

schtasks /Create /TN "%TASK_QUEUE%" /TR "\"%APP_DIR%run-agt-queue.bat\"" /SC ONLOGON /RL HIGHEST /F
if errorlevel 1 (
    echo.
    echo Falha ao criar a tarefa "%TASK_QUEUE%".
    echo Execute este ficheiro como Administrador.
    pause
    exit /b 1
)

schtasks /Create /TN "%TASK_SCHEDULER%" /TR "\"%APP_DIR%run-agt-scheduler.bat\"" /SC ONLOGON /RL HIGHEST /F
if errorlevel 1 (
    echo.
    echo Falha ao criar a tarefa "%TASK_SCHEDULER%".
    echo Execute este ficheiro como Administrador.
    pause
    exit /b 1
)

echo.
echo Tarefas criadas com sucesso.
echo A iniciar agora...

schtasks /Run /TN "%TASK_QUEUE%" >nul
schtasks /Run /TN "%TASK_SCHEDULER%" >nul

echo.
echo Instalacao concluida.
echo Os Jobs AGT passam a iniciar automaticamente quando o utilizador entrar no Windows.
pause