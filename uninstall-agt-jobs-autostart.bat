@echo off
setlocal

set "TASK_QUEUE=Nkama POS AGT Queue"
set "TASK_SCHEDULER=Nkama POS Laravel Scheduler"

echo A parar e remover tarefas automaticas dos Jobs AGT...
echo.

schtasks /End /TN "%TASK_QUEUE%" >nul 2>nul
schtasks /End /TN "%TASK_SCHEDULER%" >nul 2>nul

schtasks /Delete /TN "%TASK_QUEUE%" /F >nul 2>nul
schtasks /Delete /TN "%TASK_SCHEDULER%" /F >nul 2>nul

echo Concluido. As tarefas automaticas foram removidas, se existiam.
pause