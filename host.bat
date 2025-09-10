@echo off
REM Bypass Ctrl+C Prompt
if "%~1"=="-FIXED_CTRL_C" (
   REM Remove the -FIXED_CTRL_C parameter
   SHIFT
) ELSE (
   REM Run the batch with <NUL and -FIXED_CTRL_C
   CALL <NUL %0 -FIXED_CTRL_C %*
   GOTO :EOF
)

REM Save current working directory
set "OLD_DIR=%cd%"

REM Change to public directory
cd /d "%~dp0public"

REM Start PHP server (adjust command as needed)
php -S localhost:8080

REM After PHP exits, change back
cd /d "%OLD_DIR%"
