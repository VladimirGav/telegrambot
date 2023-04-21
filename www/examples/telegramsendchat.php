<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

// Устанавливаем и подключаем Composer
require_once __DIR__.'/../../backend/defines.php';

/** Пример автоматической отправки сообщений в телеграм канал */

use modules\telegram\services\sTelegram;

// Получим токен бота из файла
if(!file_exists(_FILE_bot_token_)){
    exit(_FILE_bot_token_.' is empty');
}
$bot_token = trim(file_get_contents(_FILE_bot_token_));


// Отправка в чат или канал
$message_chat_id = '@NameChannelTest'; // Если публичный то через @ (@NameChannelTest) , а если частная группа то -100 и id чата (-1009999999999)
sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Текущая дата: '.date('d.m.Y H:i:s'));

echo json_encode(['error'=> 0, 'data' => 'success']);
exit;

