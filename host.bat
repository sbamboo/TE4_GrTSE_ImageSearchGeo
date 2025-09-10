@echo off
REM Save current working directory
set "OLD_DIR=%cd%"

REM Change to public directory
cd /d "%~dp0public"

REM Start PHP server (adjust command as needed)
php -S localhost:8080

REM After PHP exits, change back
cd /d "%OLD_DIR%"
