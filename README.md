## Как создать телеграм-бота
### Создание бота в telegram
1. Заходим в Telegram и добавляем @BotFather
2. Пишем `/start`
3. Пишем `/newbot` - Новый бот
4. Пишем `Любое название`"` - Имя бота
5. Пишем `NameYouBot` - Логин бота, должен заканчиваться на bot
Если все в порядке приходит токен бота примерно такой `7345887:AAElClcpnLz8fGX2vEEaa`

### Задаем телеграм-боту url адрес для обработки входящих сообщений и отправки ответов
1. Загружаем папку [www/examples](www/examples) в корень сайта.
2. Загружаем папку [backend](backend) за пределы корня сайта.
3. Запускаем файл  [www/examples/telegrambotsettings.php](www/examples/telegrambotsettings.php) , вводим токен бота и URL адрес (только https) к обработчику сообщений [www/examples/telegrambotapi.php](www/examples/telegrambotapi.php)
4. Пишем телеграм-боту и получаем ответы от обработчика.

### Автоматическая публикация сообщений в телеграм канал
1. Назначьте телеграм-бота администратором в чате/канале
2. Укажите имя канала `@NameYouChannel` в файле [www/examples/telegramsendchat.php](www/examples/telegramsendchat.php) и выполните его.

### Описание файлов
1. [backend/core/installCopmposer.php](backend/core/installCopmposer.php) - Класс для установки composer
2. [backend/composer/composer.json](backend/composer/composer.json) - Файл настроек для composer
3. [backend/modules/telegram/services/sTelegram.php](backend/modules/telegram/services/sTelegram.php) - Промежуточный класс между примерами и Telegram Bot API
4. [www/examples/telegrambotsettings.php](www/examples/telegrambotsettings.php) - Пример настройки связи между телеграм ботом и обработчиком на php
5. [www/examples/telegrambotapi.php](www/examples/telegrambotapi.php) - Пример обработчика сообщений бота на php
6. [www/examples/telegramsendchat.php](www/examples/telegramsendchat.php) - Пример отправки сообщений в канал/чат