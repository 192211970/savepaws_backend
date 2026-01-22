@echo off
REM ========================================
REM SavePaws PHP Files Cleanup Script
REM ========================================
REM This script removes duplicate and unused PHP files
REM Run this AFTER backing up your phpflies folder!

echo ========================================
echo SavePaws PHP Cleanup Script
echo ========================================
echo.
echo WARNING: This will DELETE unused files!
echo Make sure you have a BACKUP first!
echo.
pause

cd /d "%~dp0"

echo.
echo [1/4] Deleting duplicate files...
del /F /Q "get_don_ad.php" 2>nul
del /F /Q "get_don_us.php" 2>nul
del /F /Q "user_donation.php" 2>nul
echo Done.

echo.
echo [2/4] Deleting unused case filter files...
del /F /Q "all_critical_cases.php" 2>nul
del /F /Q "all_critical_accept.php" 2>nul
del /F /Q "all_critical_yet_to_accept.php" 2>nul
del /F /Q "all_standard_cases.php" 2>nul
del /F /Q "all_standard_accepted.php" 2>nul
del /F /Q "all_standard_yet_to_accept.php" 2>nul
echo Done.

echo.
echo [3/4] Moving image files to uploads folder...
if not exist "uploads\" mkdir uploads
move /Y "*.jpg" "uploads\" 2>nul
move /Y "*.avif" "uploads\" 2>nul
echo Done.

echo.
echo [4/4] Moving PDF to parent directory...
move /Y "OS - 1958.pdf" "..\..\" 2>nul
echo Done.

echo.
echo ========================================
echo Cleanup Complete!
echo ========================================
echo.
echo Files deleted:
echo - 3 duplicate files
echo - 6 unused case filter files
echo.
echo Files moved:
echo - Images moved to uploads/
echo - PDF moved to parent directory
echo.
echo NEXT STEP: Fix the missing file issue
echo Run: rename admin_all_cases.php admin_get_all_cases.php
echo.
pause
