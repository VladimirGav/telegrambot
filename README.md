# Как создать телеграм-бота

## Возможности бота
### Пишем телеграм-боту и получаем ответы от обработчика:
- Приветствие новых участников в группе. Видео https://youtu.be/atu4ERvP26c
- Удаление уведомлений о выходе участников из группы. https://youtu.be/cZ5PfQ92AWA 
- Блокирование ссылок от участников в группе. https://youtu.be/z0uIJ15FPWg

OpenAI команды
- `/ai` - задаем любой вопрос. Отвечает ИИ OpenAI ChatGPT. Отвечая на сообщения бота можно вести диалог. Видео https://www.youtube.com/watch?v=V5s8zEvGr08
- `/img` - описание картинки. ИИ OpenAI рисует картинку в ответ. Видео https://youtu.be/km212I673hk

Stable Diffusion команды
- `/sd_models` - Получить список разрешенных моделей для бота от huggingface
- `/sd` - Запрос на создание изображения

Crypto команды
- `/new_wallets 3` - Создать 3 Ethereum кошелька (Address, PrivateKey). Видео https://youtu.be/U2uyD85Ysfw

Другие команды
- Любой запрос - Администратор может добавлять собственные ответы на запросы в файле [telegrambotapi.php](www/examples/telegrambotapi.php)
- `/user_id` - отправляет id отправителя. Видео https://youtu.be/z0uIJ15FPWg
- `/chat_id` - отправляет id текущего чата. Видео скоро

## Создание бота в Telegram
Видео инструкция https://youtu.be/OYy3Sq8wig0
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
Видео инструкция https://youtu.be/MjCKhhjmJBQ
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

## Установка и подключение Stable Diffusion
Видео инструкция 
1. Выполните установку https://github.com/VladimirGav/stable-diffusion-vg Видео инструкция https://youtu.be/dUGForWid64
2. Укажите путь к папке stable-diffusion-vg и список моделей в файле настроек Телеграм Бота.

## Персонализация (настроеки)
Возможности бота можно настроить индивидуально в файле /telegrambot/backend/settings/bot_settings.json. Файл создается автоматически, после первого успешного запуска бота.
- `enableChatGPT`: 1, // 1 - включить ChatGPT команду /ai; 0 - выключить
- `enableOpenAiImg`: 1, // 1 - включить OpenAi Img команду /img; 0 - выключить
- `enableWelcome`: 1, // 1 - включить приветствие новых участников; 0 - выключить
- `enableGoodbye`: 1, // 1 - включить удаление уведомления о выходе участника из группы; 0 - выключить
- `enableLinkBlocking`: 1, // 1 - включить блокирование ссылок; 0 - выключить
- `enableWallets`: 1, // wallets
- `superUsersIds`: ['000','000'], // id пользователей с привилегиями
- `AllowedChatIdArr`: [], // Массив чатов для которых работает данный бот. Пустотой массив - нет ограничений
- `waitMessage`: 'Пожалуйста, подождите', // Текст Пожалуйста, подождите
- `enableStableDiffusion`: 1, // 1 Включить генерацию изображений через StableDiffusion, если установлена сборка stable-diffusion-vg
- `pathStableDiffusion`: 'D:/stable-diffusion-vg', // Путь к корню StableDiffusion
- `StableDiffusionAllowedModelsArr`: [0=>'stabilityai/stable-diffusion-2-1', 'SD1.5: 'runwayml/stable-diffusion-v1-5'], // Массив моделей для StableDiffusion которые будут работать с huggingface.co

### Описание файлов
1. [backend/core/installComposer.php](backend/core/installComposer.php) - Класс для установки composer
2. [backend/composer/composer.json](backend/composer/composer.json) - Файл настроек для composer
3. [backend/modules/telegram/services/sTelegram.php](backend/modules/telegram/services/sTelegram.php) - Промежуточный класс между примерами и Telegram Bot API
4. [www/examples/telegrambotsettings.php](www/examples/telegrambotsettings.php) - Пример настройки связи между телеграм ботом и обработчиком на php
5. [www/examples/telegrambotapi.php](www/examples/telegrambotapi.php) - Пример обработчика сообщений бота на php
6. [www/examples/telegramsendchat.php](www/examples/telegramsendchat.php) - Пример отправки сообщений в канал/чат

Разработчик: VladimirGav