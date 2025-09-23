@echo off
echo Starting K-Food Delights WebSocket Server...
cd /d %~dp0
php ..\websocket\server.php
pause