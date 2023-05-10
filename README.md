# Как создать телеграм-бота

## Возможности бота
### Пишем телеграм-боту и получаем ответы от обработчика:
- Любой запрос - Администратор может добавлять собственные ответы на запросы в файле [telegrambotapi.php](www/examples/telegrambotapi.php)
- /ai - задаем любой вопрос. Отвечает ИИ OpenAI ChatGPT.
- /img - описание картинки. ИИ OpenAI рисует картинку в ответ.
- /user_id - отправляет id отправителя
- Приветствие новых участников
- Удаление уведомления о выходе участников из группы
- Блокирование ссылок в сообщениях

`! Каждую из этих возможностей бота можно настроить индивидуально в файле /telegrambot/backend/settings/bot_settings.json , где 1 - вкл, 0 - выкл. Файл создается автоматически, после первого успешного запуска бота.`

## Создание бота в Telegram
1. Заходим в Telegram и добавляем @BotFather
2. Пишем `/start`
3. Пишем `/newbot` - Новый бот
4. Пишем `Любое название` - Имя бота
5. Пишем `NameYouBot` - Логин бота, должен заканчиваться на bot
Если все в порядке приходит Telegram Bot API KEY примерно такой `7345887:AAElClcpnLz8fGX2vEEaa`

## Установка обработчика ответов на Windows 10, 11
Видео инструкция https://youtu.be/OYy3Sq8wig0
1. Скачиваем zip архив репозитория VladimirGav/telegrambot на компьютер и распаковываем.
2. Запускаем файл forwindows/StartBot.bat и при первом запуске вводим API ключи и все.
Пока консоль запущена, она будет обрабатывать сообщения, вы можете свернуть консоль.

## Установка обработчика ответов для других Windows
Видео инструкция https://youtu.be/OYy3Sq8wig0
1. Скачиваем zip архив репозитория VladimirGav/telegrambot на компьютер и распаковываем.
- Устанавливаем вручную 64-bit Git for Windows Setup из https://git-scm.com/download/win
- Устанавливаем вручную Microsoft Visual C++ Redistributable https://aka.ms/vs/17/release/vc_redist.x64.exe
2. Запускаем файл forwindows/StartBotWinOther.bat и при первом запуске вводим API ключи и все.
Пока консоль запущена, она будет обрабатывать сообщения, вы можете свернуть консоль.

## Установка обработчика ответов на хостинг
Видео инструкция https://youtu.be/D8sZ51KYVJY
1. Загружаем папку [www/examples](www/examples) в корень сайта.
2. Загружаем папку [backend](backend) за пределы корня сайта.
3. Запускаем файл  [www/examples/telegrambotsettings.php](www/examples/telegrambotsettings.php) , вводим токен бота и URL адрес (только https) к обработчику сообщений [www/examples/telegrambotapi.php](www/examples/telegrambotapi.php)

### Автоматическая публикация сообщений в телеграм канал
1. Назначьте телеграм-бота администратором в чате/канале
2. Укажите имя канала `@NameYouChannel` в файле [www/examples/telegramsendchat.php](www/examples/telegramsendchat.php) и выполните его.

## Ключи API
- Все ключи с API хранятся в папке /telegrambot/backend/settings , вы всегда можете их отредактировать.
- OpenAI API KEY вы можете получить по ссылке https://platform.openai.com/account/api-keys

### Описание файлов
1. [backend/core/installComposer.php](backend/core/installComposer.php) - Класс для установки composer
2. [backend/composer/composer.json](backend/composer/composer.json) - Файл настроек для composer
3. [backend/modules/telegram/services/sTelegram.php](backend/modules/telegram/services/sTelegram.php) - Промежуточный класс между примерами и Telegram Bot API
4. [www/examples/telegrambotsettings.php](www/examples/telegrambotsettings.php) - Пример настройки связи между телеграм ботом и обработчиком на php
5. [www/examples/telegrambotapi.php](www/examples/telegrambotapi.php) - Пример обработчика сообщений бота на php
6. [www/examples/telegramsendchat.php](www/examples/telegramsendchat.php) - Пример отправки сообщений в канал/чат

Разработчик: VladimirGav