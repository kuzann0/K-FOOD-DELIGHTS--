@echo off
echo Starting KFoodDelights WebSocket Server...

REM Check if PHP is in PATH
where php >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo PHP not found in PATH. Please ensure PHP is installed and added to system PATH.
    pause
    exit /b 1
)

REM Check if Composer dependencies are installed
if not exist vendor\autoload.php (
    echo Installing Composer dependencies...
    composer install
    if %ERRORLEVEL% neq 0 (
        echo Failed to install dependencies.
        pause
        exit /b 1
    )
)

REM Start the WebSocket server
php -d extension=zip websocket_server_main.php
pause