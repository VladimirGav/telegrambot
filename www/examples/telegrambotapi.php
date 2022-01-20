<?php
/** Пример обработки сообщений телеграм бота */

// Подключим автозагрузчик composer, defines
use modules\telegram\services\sTelegram;
use Telegram\Bot\Api;

require_once __DIR__ .'/../system/defines.php';
require_once __DIR__ .'/../system/vendor/autoload.php';

// Получим токен бота из файла
$bot_token = file_get_contents(__DIR__.'/bot_token.txt');


// Подключаемся к апи
$telegram = new Api($bot_token);
$dataMessage = $telegram->getWebhookUpdates();

if(empty($dataMessage['message']['message_id'])){
    echo json_encode(['error'=> 1, 'data' => 'message_id empty']);
    exit;
}
if(empty($dataMessage['message']['chat']['id'])){
    echo json_encode(['error'=> 1, 'data' => 'chat_id empty']);
    exit;
}
if(empty($dataMessage['message']['text'])){
    echo json_encode(['error'=> 1, 'data' => 'text empty']);
    exit;
}

// Получим данные от пользователя
$message_id = $dataMessage['message']['message_id']; // Id сообщения
$message_chat_id = $dataMessage['message']['chat']['id']; // Id чата
$message_text = $dataMessage['message']['text']; // Текст сообщения

// К нижнему регистру
$messageTextLower = mb_strtolower($message_text);

// Если первое сообщение
if($messageTextLower=='/start'){
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Привет, я бот');
    exit;
}

// Если пользователь напишет Тест, то выведем ответ
if($messageTextLower=='тест'){
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Ответ от бота на сообщение тест. <b>Вы можете предусмотреть свои ответы на любые сообщения в формате HTML.</b>');
    exit;
}

// Если пользователь напишет привет
if($messageTextLower=='привет'){
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Привет');
    exit;
}

// пример ответа
if($messageTextLower=='пример ответа'){
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Ответ на сообщение', '', $message_id);
    exit;
}

if($messageTextLower=='chat_id'){
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'chat_id: '.$message_chat_id, '', $message_id);
    exit;
}

// Пример отправки аудио файла
if($messageTextLower=='мелодия'){
    $InputFile = \Telegram\Bot\FileUpload\InputFile::create(__DIR__.'/audio.mp3');
    $telegram = new \Telegram\Bot\Api($bot_token);
    $response = $telegram->sendAudio([
        'chat_id' => $message_chat_id,
        'audio' => $InputFile,
    ]);
    exit;
}

// пример кнопки
if($messageTextLower=='пример кнопки'){
    $inline_keyboard=[];
    $inline_keyboard[][] = ["text"=>'telegram кнопка', "url"=>'https://telegram.org/'];
    $keyboard=["inline_keyboard"=>$inline_keyboard];
    $reply_markup = json_encode($keyboard);
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Сообщение с кнопкой', $reply_markup);
    exit;
}

// Если не предусмотрен ответ
sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Ответ не предусмотрен');
exit;

