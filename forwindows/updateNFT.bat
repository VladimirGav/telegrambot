@echo off
Setlocal EnableDelayedExpansion

goto start
--------------------------------------
VladimirGav
GitHub Website: https://vladimirgav.github.io/
GitHub: https://github.com/VladimirGav
Copyright (c)
--------------------------------------
Source https://github.com/VladimirGav/telegrambot
This file allows you to run the software for processing incoming messages from your Telegram Bot in OS Windows.
Every 2 seconds, the php file /telegrambot/www/examples/updatenft.php is executed and processes all messages.
--------------------------------------
:start

echo updateNFT...
REM execute the file updatenft.php every 2 seconds and check incoming messages to our bot
:loop
.\php\php ..\www\examples\updatenft.php console %php_path%
timeout /t 3 /nobreak > nul
goto :loop

