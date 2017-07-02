@echo off

where php.exe >nul 2>nul
if %errorlevel%==1 (
    @echo Not found php.exe
    @echo Download link for Windows http://windows.php.net/download#php-5.6
    goto end
) else (
    set RUN_COMMAND=php %~dp0gitget.phar %*
    goto run
)

:run
%RUN_COMMAND%

:end
