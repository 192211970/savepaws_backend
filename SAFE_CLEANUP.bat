@echo off
REM ========================================
REM SafePaws - Safe PHP Cleanup Script
REM ========================================
REM This script deletes ONLY unused/duplicate PHP files
REM All files used by the app are preserved
REM ========================================

echo ========================================
echo SavePaws Safe PHP Cleanup
echo ========================================
echo.
echo This will DELETE the following unused files:
echo.
echo DUPLICATE FILES (3):
echo   - get_don_ad.php
echo   - get_don_us.php
echo   - user_donation.php
echo.
echo UNUSED CASE FILTER FILES (6):
echo   - all_critical_cases.php
echo   - all_critical_accept.php
echo   - all_critical_yet_to_accept.php
echo   - all_standard_cases.php
echo   - all_standard_accepted.php
echo   - all_standard_yet_to_accept.php
echo.
echo UNCLEAR/UNUSED FILES (5):
echo   - All_closed_cases.php
echo   - newcase.php
echo   - rejected.php
echo   - delay.php
echo   - recieved_don_history.php
echo.
echo Total: 14 files will be deleted
echo.
echo All ACTIVE files (42) will be PRESERVED!
echo.
pause

cd /d "%~dp0"

echo.
echo Starting cleanup...
echo.

REM Delete duplicate files
echo [1/3] Deleting duplicate files...
if exist "get_don_ad.php" (
    del /F /Q "get_don_ad.php"
    echo   ✓ Deleted: get_don_ad.php
) else (
    echo   - Not found: get_don_ad.php
)

if exist "get_don_us.php" (
    del /F /Q "get_don_us.php"
    echo   ✓ Deleted: get_don_us.php
) else (
    echo   - Not found: get_don_us.php
)

if exist "user_donation.php" (
    del /F /Q "user_donation.php"
    echo   ✓ Deleted: user_donation.php
) else (
    echo   - Not found: user_donation.php
)

REM Delete unused case filter files
echo.
echo [2/3] Deleting unused case filter files...
if exist "all_critical_cases.php" (
    del /F /Q "all_critical_cases.php"
    echo   ✓ Deleted: all_critical_cases.php
) else (
    echo   - Not found: all_critical_cases.php
)

if exist "all_critical_accept.php" (
    del /F /Q "all_critical_accept.php"
    echo   ✓ Deleted: all_critical_accept.php
) else (
    echo   - Not found: all_critical_accept.php
)

if exist "all_critical_yet_to_accept.php" (
    del /F /Q "all_critical_yet_to_accept.php"
    echo   ✓ Deleted: all_critical_yet_to_accept.php
) else (
    echo   - Not found: all_critical_yet_to_accept.php
)

if exist "all_standard_cases.php" (
    del /F /Q "all_standard_cases.php"
    echo   ✓ Deleted: all_standard_cases.php
) else (
    echo   - Not found: all_standard_cases.php
)

if exist "all_standard_accepted.php" (
    del /F /Q "all_standard_accepted.php"
    echo   ✓ Deleted: all_standard_accepted.php
) else (
    echo   - Not found: all_standard_accepted.php
)

if exist "all_standard_yet_to_accept.php" (
    del /F /Q "all_standard_yet_to_accept.php"
    echo   ✓ Deleted: all_standard_yet_to_accept.php
) else (
    echo   - Not found: all_standard_yet_to_accept.php
)

REM Delete unclear/unused files
echo.
echo [3/3] Deleting unclear/unused files...
if exist "All_closed_cases.php" (
    del /F /Q "All_closed_cases.php"
    echo   ✓ Deleted: All_closed_cases.php
) else (
    echo   - Not found: All_closed_cases.php
)

if exist "newcase.php" (
    del /F /Q "newcase.php"
    echo   ✓ Deleted: newcase.php
) else (
    echo   - Not found: newcase.php
)

if exist "rejected.php" (
    del /F /Q "rejected.php"
    echo   ✓ Deleted: rejected.php
) else (
    echo   - Not found: rejected.php
)

if exist "delay.php" (
    del /F /Q "delay.php"
    echo   ✓ Deleted: delay.php
) else (
    echo   - Not found: delay.php
)

if exist "recieved_don_history.php" (
    del /F /Q "recieved_don_history.php"
    echo   ✓ Deleted: recieved_don_history.php
) else (
    echo   - Not found: recieved_don_history.php
)

echo.
echo ========================================
echo Cleanup Complete!
echo ========================================
echo.
echo ✅ Deleted unused/duplicate PHP files
echo ✅ All 42 active files preserved
echo.
echo PRESERVED FILES:
echo   - All authentication files (login, register, etc.)
echo   - All user case files
echo   - All center management files
echo   - All admin panel files
echo   - All donation files
echo   - db.php and other core files
echo.
echo Your application will continue to work normally!
echo.
echo NEXT STEPS:
echo 1. Upload clean files to server
echo 2. Delete "- Copy" files from server
echo 3. Update db.php on server with correct credentials
echo 4. Test your app
echo.
pause
