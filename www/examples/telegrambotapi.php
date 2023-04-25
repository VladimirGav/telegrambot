<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

// Устанавливаем и подключаем Composer
require_once __DIR__.'/../../backend/defines.php';

/** Пример обработки сообщений телеграм бота */

use modules\telegram\services\sTelegram;

// Получим токен бота из файла
if(!file_exists(_FILE_bot_token_)){
    exit(_FILE_bot_token_.' is empty');
}
$bot_token = trim(file_get_contents(_FILE_bot_token_));

// Подключаемся к апи
$telegram = new \Telegram\Bot\Api($bot_token);

// Если запускаем через консоль, а не используем Telegram Webhook
if(!empty($_SERVER['argv'][1]) && $_SERVER['argv'][1]=='console'){
    $removeWebhook = sTelegram::instance()->removeWebhook($bot_token); // Удаляем привязку к Telegram Webhook
    if(!empty($removeWebhook['error'])){ exit(json_encode($removeWebhook)); }
    $dataMessage = sTelegram::instance()->getUpdatesLastMessage($bot_token);
} else {
    $dataMessage = sTelegram::instance()->getWebhookLastMessage($bot_token);
}

if(empty($dataMessage['message']['message_id'])){
    //echo json_encode(['error'=> 1, 'data' => 'message_id empty']);
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

// Удаляем имя бота, например заменяеам /ai@Name_bot на /ai
$messageTextLower = preg_replace('/(.*)(\/ai@[^ ]*)(.*)/', '/ai $1$3', $messageTextLower);
$messageTextLower = preg_replace('/(.*)(\/img@[^ ]*)(.*)/', '/img $1$3', $messageTextLower);

// Если первое сообщение
if($messageTextLower=='/start'){
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Привет, я бот', '', $message_id);
    exit;
}

// Если пользователь напишет Тест, то выведем ответ
if($messageTextLower=='тест'){
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Ответ от бота на сообщение тест. <b>Вы можете предусмотреть свои ответы на любые сообщения в формате HTML.</b>', '', $message_id);
    exit;
}

// Если пользователь напишет привет
if($messageTextLower=='привет'){
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Привет', '', $message_id);
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
    sTelegram::instance()->sendAudio($bot_token, $message_chat_id, __DIR__.'/audio.mp3', '', $message_id);
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

// Пример chatGPT
$pos2 = stripos($message_text, '/ai');
if ($pos2 !== false) {
    // Удаляем все лишнее
    $message_text = trim($message_text);
    $message_text = str_replace('/ai', '', $message_text);
    $message_text = str_replace('  ', ' ', $message_text);
    $message_text  = mb_strtolower($message_text);

    // Если пустой, отправляем пример
    if(empty($message_text)){
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Example: /ai Ты можешь отвечать на вопросы?', '', $message_id);
        exit;
    }

    // Получим токен бота из файла
    if(!file_exists(_FILE_api_gpt_)){
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'OpenAI API KEY is empty', '', $message_id);
        exit;
    }
    $api_gpt = trim(file_get_contents(_FILE_api_gpt_));
    if(empty($api_gpt)){
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'OpenAI API KEY is empty', '', $message_id);
        exit;
    }

    // gpt-3.5-turbo
    $ChatGPTAnswerData = \modules\openai\services\sOpenAI::instance()->getChatGPTAnswer($api_gpt, $message_text);
    if(!empty($ChatGPTAnswerData['error'])){
        exit(json_encode($ChatGPTAnswerData));
    }

    if(!empty($ChatGPTAnswerData['answer'])){
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $ChatGPTAnswerData['answer'], '', $message_id);
        exit;
    }
}

// АИ Рисуем картинку по запросу
$pos2 = stripos($message_text, '/img');
if ($pos2 !== false) {
    $dir = __DIR__.'/uploads/images';
    if(!file_exists($dir)){
        if (!mkdir($dir, 0777, true)) {
            die('Не удалось создать директории...');
        }
    }

    // Удаляем все лишнее
    $message_text = trim($message_text);
    $message_text = str_replace('/img', '', $message_text);
    $message_text = str_replace('  ', ' ', $message_text);
    $message_text  = mb_strtolower($message_text);

    // Если пустой, отправляем пример
    if(empty($message_text)){
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Example: /img Рыжая лиса в лесу', '', $message_id);
        exit;
    }

    // Получим токен бота из файла
    if(!file_exists(_FILE_api_gpt_)){
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'OpenAI API KEY is empty', '', $message_id);
        exit;
    }
    $api_gpt = trim(file_get_contents(_FILE_api_gpt_));
    if(empty($api_gpt)){
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'OpenAI API KEY is empty', '', $message_id);
        exit;
    }

    // gpt-3.5-turbo
    $ImgData = \modules\openai\services\sOpenAI::instance()->getImg($api_gpt, $message_text);
    if(!empty($ImgData['error'])){
        exit(json_encode($ImgData));
    }

    if(!empty($ImgData['url'])){
        // save img
        $fileName = $dir.'/'.time().'.png';
        file_put_contents($fileName, file_get_contents($ImgData['url']));

        sTelegram::instance()->sendPhoto($bot_token, $message_chat_id, $fileName, $message_text, $message_id);
        exit;
    }

}

// Если не предусмотрен ответ
sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Ответ не предусмотрен', '', $message_id);
exit;

