@echo off
echo ================================================
echo  FINDING HARDCODED DATABASE CREDENTIALS
echo ================================================
echo.

cd C:\xampp\htdocs\savepaws

echo Searching for files with hardcoded "localhost"...
echo ------------------------------------------------
findstr /S /I /N "localhost" *.php | findstr /V "db.php"
echo.

echo Searching for files with "new PDO"...
echo ------------------------------------------------
findstr /S /I /N "new PDO" *.php
echo.

echo Searching for files with hardcoded "root"...
echo ------------------------------------------------
findstr /S /I /N "root" *.php | findstr /V "db.php"
echo.

echo ================================================
echo  SEARCH COMPLETE
echo ================================================
echo.
echo Files listed above may have hardcoded credentials
echo that need to be updated for your hosted server.
echo.
pause
