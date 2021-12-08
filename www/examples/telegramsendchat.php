<?php
/** Пример автоматической отправки сообщений в телеграм канал */

// Подключим автозагрузчик composer, defines
use modules\telegram\services\sTelegram;
use Telegram\Bot\Api;

require_once __DIR__ .'/../system/defines.php';
require_once __DIR__ .'/../system/vendor/autoload.php';

// Получим токен бота из файла
$bot_token = file_get_contents(__DIR__.'/bot_token.txt');


// Отправка в чат или канал
$message_chat_id = '@NameChannelTest'; // Если публичный то через @ (@NameChannelTest) , а если частная группа то -100 и id чата (-1009999999999)
sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Текущая дата: '.date('d.m.Y H:i:s'));

echo json_encode(['error'=> 0, 'data' => 'success']);
exit;

