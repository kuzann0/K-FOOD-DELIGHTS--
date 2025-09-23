@echo off
ECHO Starting K-Food Delights WebSocket Servers...

REM Kill any existing PHP processes running WebSocket servers
taskkill /F /FI "IMAGENAME eq php.exe" /FI "WINDOWTITLE eq WebSocket*" 2>NUL

REM Start the WebSocket servers
START "WebSocket Order Server" /MIN php ..\websocket\server.php order
START "WebSocket Payment Server" /MIN php ..\websocket\server.php payment

ECHO WebSocket servers started successfully!
ECHO Press any key to stop the servers...
PAUSE >NUL

REM Kill the WebSocket servers when exiting
taskkill /F /FI "IMAGENAME eq php.exe" /FI "WINDOWTITLE eq WebSocket*"