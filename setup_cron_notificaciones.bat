@echo off
REM ============================================================
REM  CRM QUANTUN Digital — Instalar cron de notificaciones
REM  Ejecutar como Administrador (clic derecho → "Ejecutar como admin")
REM ============================================================

SET PHP=C:\xampp\php\php.exe
SET SCRIPT=C:\xampp\htdocs\CRM-QUANTUN-Digital\api\enviar_notif_renovacion.php
SET TASK=CRM_QUANTUN_Notificaciones
SET LOG=C:\xampp\htdocs\CRM-QUANTUN-Digital\logs\notif_cron.log

REM Crear carpeta de logs si no existe
IF NOT EXIST "C:\xampp\htdocs\CRM-QUANTUN-Digital\logs" (
    mkdir "C:\xampp\htdocs\CRM-QUANTUN-Digital\logs"
)

REM Eliminar tarea antigua si existe
schtasks /delete /tn "%TASK%" /f >nul 2>&1

REM Crear la tarea: cada 5 minutos, todos los días, empieza hoy
schtasks /create ^
  /tn "%TASK%" ^
  /tr "\"%PHP%\" \"%SCRIPT%\"" ^
  /sc MINUTE /mo 5 ^
  /ru SYSTEM ^
  /rl HIGHEST ^
  /f

IF %ERRORLEVEL% EQU 0 (
    echo.
    echo [OK] Tarea programada creada: %TASK%
    echo      Se ejecuta cada 5 minutos automaticamente.
    echo.
    echo Ejecutando primera vez ahora...
    schtasks /run /tn "%TASK%"
    echo [OK] Primera ejecucion lanzada.
) ELSE (
    echo.
    echo [ERROR] No se pudo crear la tarea. Ejecuta este script como Administrador.
)

pause
