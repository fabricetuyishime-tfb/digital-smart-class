@echo off
title Digital Smart Class — Command Prompt Setup & Runner
color 0b

echo =====================================================================
echo           DIGITAL SMART CLASS — COMMAND PROMPT MANAGER
echo   Stack: PHP + HTML5 + CSS3 + MySQL (Apache / XAMPP / Native CMD)
echo =====================================================================
echo.

:: 1. Check if PHP is installed and available in PATH
where php >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] PHP is installed and available in Command Prompt.
    php -v | findstr /i "PHP 8" || php -v | findstr /i "PHP 7"
) else (
    echo [NOTICE] PHP is not in system PATH.
    echo If using XAMPP, you can use: C:\xampp\php\php.exe
)

echo.
echo =====================================================================
echo AVAILABLE ACTIONS:
echo   [1] Start Built-in PHP Development Server (localhost:8000)
echo   [2] Import MySQL Database (digital_smart_class.sql)
echo   [3] Copy Project to XAMPP htdocs (C:\xampp\htdocs\digital-smart-class)
echo   [4] Exit
echo =====================================================================
echo.

set /p choice="Enter your choice (1, 2, 3, or 4): "

if "%choice%"=="1" (
    echo.
    echo Starting PHP Local Server on http://localhost:8000 ...
    echo Open your browser and navigate to: http://localhost:8000
    echo Press Ctrl+C in this CMD window anytime to stop the server.
    echo.
    where php >nul 2>&1
    if %errorlevel% equ 0 (
        php -S localhost:8000
    ) else if exist "C:\xampp\php\php.exe" (
        C:\xampp\php\php.exe -S localhost:8000
    ) else (
        echo Error: PHP executable not found. Please install XAMPP or PHP.
        pause
    )
    goto end
)

if "%choice%"=="2" (
    echo.
    echo Importing database\digital_smart_class.sql into MySQL...
    where mysql >nul 2>&1
    if %errorlevel% equ 0 (
        mysql -u root -e "CREATE DATABASE IF NOT EXISTS digital_smart_class CHARACTER SET utf8mb4;"
        mysql -u root digital_smart_class < database\digital_smart_class.sql
        echo [OK] Database imported successfully!
    ) else if exist "C:\xampp\mysql\bin\mysql.exe" (
        C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS digital_smart_class CHARACTER SET utf8mb4;"
        C:\xampp\mysql\bin\mysql.exe -u root digital_smart_class < database\digital_smart_class.sql
        echo [OK] Database imported successfully into XAMPP MySQL!
    ) else (
        echo Could not auto-detect mysql command.
        echo Please import database\digital_smart_class.sql via phpMyAdmin (http://localhost/phpmyadmin)
    )
    pause
    goto end
)

if "%choice%"=="3" (
    echo.
    echo Copying project files to C:\xampp\htdocs\digital-smart-class ...
    if not exist "C:\xampp\htdocs\digital-smart-class" mkdir "C:\xampp\htdocs\digital-smart-class"
    xcopy /E /I /Y . "C:\xampp\htdocs\digital-smart-class"
    echo.
    echo [OK] Files copied! You can now visit: http://localhost/digital-smart-class/
    pause
    goto end
)

:end
