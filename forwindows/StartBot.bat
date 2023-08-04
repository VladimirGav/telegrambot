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
Every 2 seconds, the php file /telegrambot/www/examples/telegrambotapi.php is executed and processes all messages.
--------------------------------------
:start

echo VladimirGav telegrambot
echo https://github.com/VladimirGav/telegrambot

set php_path=%cd%\php

REM set php_zip_url=https://windows.php.net/downloads/releases/php-8.2.6-Win32-vs16-x64.zip
set php_zip_url=https://vladimirgav.github.io/files/php/php-8.2.6-Win32-vs16-x64.zip
set php_archive_name=php-8.2.6-Win32-vs16-x64.zip
set extract_folder=php

set cacert_url=https://curl.se/ca/cacert.pem
set cacert_path=./php/extras/ssl/cacert.pem

set FILE_PHP_INI_PATH=./php/php.ini

set folder_settings=.\..\backend\settings\
set file_bot_token=./../backend/settings/bot_token.txt
set file_api_gpt=./../backend/settings/api_gpt.txt

REM Create folder if it doesn't exist
if not exist %folder_settings% (
    MD %folder_settings%
	echo Settings folder created
)

REM Create files with Telegram Bot API KEY
if not exist %file_bot_token% (
set /p "bot_token_str=Enter Telegram Bot API KEY:"
echo !bot_token_str!>%file_bot_token%
    echo Telegram Bot API KEY saved to file %file_bot_token%
)
REM Create files with ChatGPT API KEY
if not exist %file_api_gpt% (
echo URL OpenAI  https://platform.openai.com/account/api-keys
set /p "api_gpt_str=Enter OpenAI API KEY:"
echo !api_gpt_str!>%file_api_gpt%
    echo Telegram Bot API KEY saved to file %file_api_gpt%
)

REM Install winget Git.Git
winget list --id Git.Git >nul 2>&1
if %ERRORLEVEL% EQU 0 (
	echo Git Ok!
) else (
	echo Install winget Git.Git
	winget install --id Git.Git -e --source winget
)

REM Install Microsoft Visual C++ Redistributable for Visual Studio
winget list --id Microsoft.VCRedist.2015+.x64 >nul 2>&1
if %ERRORLEVEL% EQU 0 (
	echo Microsoft Visual C++ Redistributable for Visual Studio Ok!
) else (
	echo Install Microsoft Visual C++ Redistributable for Visual Studio
	winget install Microsoft.VCRedist.2015+.x64
)

REM if there is no php, then install it
if exist "%php_path%" (
REM echo The folder php is exist.
echo PHP Ok!
) else (

REM download zip php and unzip in ./php
curl -o %php_archive_name% %php_zip_url%
powershell -Command "Expand-Archive -Path %php_archive_name% -DestinationPath ./%extract_folder%"
del %php_archive_name%

REM download cacert.pem in in ./php/extras/ssl/cacert.pem
curl -o %cacert_path% %cacert_url%

REM copy file /php/php.ini-development in ./php/php.ini
copy .\php\php.ini-development .\php\php.ini

REM add text in file ./php/php.ini
echo extension=curl >> %FILE_PHP_INI_PATH%
echo extension=gd >> %FILE_PHP_INI_PATH%
echo extension=mbstring >> %FILE_PHP_INI_PATH%
echo extension=openssl >> %FILE_PHP_INI_PATH%
echo extension=pdo_mysql >> %FILE_PHP_INI_PATH%

echo extension_dir = "%php_path%/ext" >> %FILE_PHP_INI_PATH%
echo curl.cainfo = "%php_path%/extras/ssl/cacert.pem" >> %FILE_PHP_INI_PATH%

echo PHP Ok!
)

REM Install nodejs
set nodejs_path=%cd%\nodejs\node-v18.16.0-win-x64
set nodejs_zip_url=https://vladimirgav.github.io/files/nodejs/node-v18.16.0-win-x64.zip
set nodejs_archive_name=node-v18.16.0-win-x64.zip
set nodejs_extract_folder=nodejs
set nodejs_project_path=%nodejs_path%\project
set nodejs_json_path=%cd%\nodejs\package.json

REM if there is no nodejs, then install it
if exist "%nodejs_path%" (
REM echo The folder nodejs is exist.
echo nodejs Ok!
) else (
REM download zip nodejs and unzip in ./nodejs
curl -o %nodejs_archive_name% %nodejs_zip_url%
powershell -Command "Expand-Archive -Path %nodejs_archive_name% -DestinationPath ./%nodejs_extract_folder%"
del %nodejs_archive_name%

md %nodejs_project_path%
powershell -Command "%nodejs_path%\npm --prefix %nodejs_project_path% install web3 bip39 ethereumjs-wallet fs fs-extra path puppeteer puppeteer-core request request-promise-native promise-poller puppeteer-extra puppeteer-extra-plugin-stealth"

echo nodejs Ok!
)

echo Start Telegram bot...
REM execute the file telegrambotapi.php every 2 seconds and check incoming messages to our bot
:loop
.\php\php ..\www\examples\telegrambotapi.php console %php_path%
timeout /t 2 /nobreak > nul
goto :loop

