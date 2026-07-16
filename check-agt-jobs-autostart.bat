@echo off
setlocal

set "TASK_QUEUE=Nkama POS AGT Queue"
set "TASK_SCHEDULER=Nkama POS Laravel Scheduler"

echo Estado das tarefas automaticas dos Jobs AGT
echo.

schtasks /Query /TN "%TASK_QUEUE%" /V /FO LIST
echo.
schtasks /Query /TN "%TASK_SCHEDULER%" /V /FO LIST
echo.

pause
