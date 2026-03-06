@echo off
chcp 65001 >nul
cd /d "%~dp0"

echo.
echo === OpenStuff Timeline - Build + ZIP ===
echo.

echo [1/2] Building...
call npm run build
if errorlevel 1 (
    echo.
    echo ERROR: Build failed.
    pause
    exit /b 1
)

echo.
echo [2/2] Creating ZIP (Node.js - WordPress compatible)...
call npm run zip
if errorlevel 1 (
    echo.
    echo ERROR: ZIP failed.
    pause
    exit /b 1
)

echo.
echo Done! openstuff-timeline.zip created in parent folder.
echo.
pause
