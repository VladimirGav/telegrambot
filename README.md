## Как создать телеграм-бота
### Создание бота в telegram
1. Заходим в Telegram и добавляем @BotFather
2. Пишем `/start`
3. Пишем `/newbot` - Новый бот
4. Пишем `Любое название`"` - Имя бота
5. Пишем `NameYouBot` - Логин бота, должен заканчиваться на bot
Если все в порядке приходит токен бота примерно такой `7345887:AAElClcpnLz8fGX2vEEaa`

### Задаем телеграм-боту url адрес для обработки входящих сообщений и отправки ответов
1. Загружаем содержимое [www](www) на хостинг.
2. Запускаем файл  [www/examples/telegrambotsettings.php](www/examples/telegrambotsettings.php) , вводим токен бота и URL адрес к обработчику сообщений [www/examples/telegrambotapi.php](www/examples/telegrambotapi.php)
3. Пишем телеграм-боту и получаем ответы от обработчика.

### Автоматическая публикация сообщений в телеграм канал
1. Назначьте телеграм-бота администратором в чате/канале
2. Укажите имя канала `@NameYouChannel` в файле [www/examples/telegramsendchat.php](www/examples/telegramsendchat.php) и выполните его.

### Описание файлов
1. `irazasyed/telegram-bot-sdk` - Используем Telegram Bot API - PHP SDK
2. [www/system/modules/telegram/services/sTelegram.php](www/system/modules/telegram/services/sTelegram.php) - Промежуточный класс между примерами и Telegram Bot API
3. [www/examples/telegrambotsettings.php](www/examples/telegrambotsettings.php) - Пример настройки связи между телеграм ботом и обработчиком на php
4. [www/examples/telegrambotapi.php](www/examples/telegrambotapi.php) - Пример обработчика сообщений бота на php
5. [www/examples/telegramsendchat.php](www/examples/telegramsendchat.php) - Пример отправки сообщений в канал/чат