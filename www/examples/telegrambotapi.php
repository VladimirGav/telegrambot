<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

$BotSettings=[
    'enableChatGPT' => 1, // 1 - включить ChatGPT команду /ai; 0 - выключить
    'enableOpenAiImg' => 1, // 1 - включить OpenAi Img команду /img; 0 - выключить
    'enableWelcome' => 1, // 1 - включить приветствие новых участников; 0 - выключить
    'enableLinkBlocking' => 1, // 1 - включить блокирование ссылок; 0 - выключить
];

$superUsersIds = ['000']; // id пользователи с привилегиями

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

/*$checkApi = sTelegram::instance()->checkApi($bot_token);
if(!empty($checkApi['error'])){
    echo json_encode($checkApi);
    exit;
}*/

// Если запускаем через консоль, а не используем Telegram Webhook
if(!empty($_SERVER['argv'][1]) && $_SERVER['argv'][1]=='console'){
    $removeWebhook = sTelegram::instance()->removeWebhook($bot_token); // Удаляем привязку к Telegram Webhook
    if(!empty($removeWebhook['error'])){ exit(json_encode($removeWebhook)); }
    $dataMessage = sTelegram::instance()->getUpdatesLastMessage($bot_token);
} else {
    $dataMessage = sTelegram::instance()->getWebhookLastMessage($bot_token);
}

// Если новый участник, то удалим сообщение о вступлении и отправим приветствие
if(!empty($dataMessage['message']['new_chat_member']['id']) && !empty($BotSettings['enableWelcome'])){
    $member_username='';
    if(!empty($dataMessage['message']['new_chat_member']['first_name'])){
        $member_username='<a href="tg://user?id='.$dataMessage['message']['new_chat_member']['id'].'">'.$dataMessage['message']['new_chat_member']['first_name'].'</a>';
    }
    sTelegram::instance()->removeMessage($bot_token, $dataMessage['message']['chat']['id'],  $dataMessage['message']['message_id']);
    sTelegram::instance()->sendMessage($bot_token, $dataMessage['message']['chat']['id'],  'Привет '.$member_username.'! Добро пожаловать в группу!');
    exit;
}

if(!empty($dataMessage['message']['from']['is_bot'])){
    echo json_encode(['error'=> 1, 'data' => 'is_bot']);
    exit;
}
if(empty($dataMessage['message']['message_id'])){
    //echo json_encode(['error'=> 1, 'data' => 'message_id empty']);
    exit;
}
if(empty($dataMessage['message']['chat']['id'])){
    echo json_encode(['error'=> 1, 'data' => 'chat_id empty']);
    exit;
}
/*if(empty($dataMessage['message']['text'])){
    echo json_encode(['error'=> 1, 'data' => 'text empty']);
    exit;
}*/

// Получим данные от пользователя
$from_id = $dataMessage['message']['from']['id'];
$message_id = $dataMessage['message']['message_id']; // Id сообщения
$message_chat_id = $dataMessage['message']['chat']['id']; // Id чата
$message_text = ''; // Текст сообщения
if(!empty($dataMessage['message']['text'])){
    $message_text = $dataMessage['message']['text'];
} else {
    if(!empty($dataMessage['message']['caption'])){
        $message_text = $dataMessage['message']['caption'];
    }
}

// Если ссылки запрещены, то удлаляем сообщение
if(!empty($BotSettings['enableLinkBlocking'])){
    $AllowedMessages = sTelegram::instance()->checkAllowedMessages($dataMessage, ['mention', 'url'], $superUsersIds);
    if(!empty($AllowedMessages['error'])){
        sTelegram::instance()->removeMessage($bot_token, $message_chat_id,  $message_id);
        $member_username='<a href="tg://user?id='.$from_id.'">'.$dataMessage['message']['from']['first_name'].'</a>';
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id,  $member_username.', размещение ссылок запрещено.', '');
    }
}

// К нижнему регистру
$messageTextLower = mb_strtolower($message_text);
$messageTextLower = str_replace('  ', ' ', $messageTextLower);
$messageTextLower = trim($messageTextLower);

// Удаляем имя бота, например заменяеам /ai@Name_bot на /ai
$messageTextLower = preg_replace('/(.*)(\/ai@[^ ]*)(.*)/', '/ai $1$3', $messageTextLower);
$messageTextLower = preg_replace('/(.*)(\/img@[^ ]*)(.*)/', '/img $1$3', $messageTextLower);
$messageTextLower = preg_replace('/(.*)(\/sd@[^ ]*)(.*)/', '/sd $1$3', $messageTextLower);

// Если узнаем id пользователя
if($messageTextLower=='/user_id'){
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'User_id: '.$from_id, '', $message_id);
    exit;
}

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
$pos2 = stripos($messageTextLower, '/ai');
if ($pos2 !== false && !empty($BotSettings['enableChatGPT'])) {
    $messageTextLower = str_replace('/ai', '', $messageTextLower);

    // Если пустой, отправляем пример
    if(empty($messageTextLower)){
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
    $ChatGPTAnswerData = \modules\openai\services\sOpenAI::instance()->getChatGPTAnswer($api_gpt, $messageTextLower);
    if(!empty($ChatGPTAnswerData['error'])){
        exit(json_encode($ChatGPTAnswerData));
    }

    if(!empty($ChatGPTAnswerData['answer'])){
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $ChatGPTAnswerData['answer'], '', $message_id);
        exit;
    }
}

// АИ Рисуем картинку по запросу
$pos2 = stripos($messageTextLower, '/img');
if ($pos2 !== false && !empty($BotSettings['enableOpenAiImg'])) {
    $messageTextLower = str_replace('/img', '', $messageTextLower);

    $dir = __DIR__.'/uploads/images';
    if(!file_exists($dir)){
        if (!mkdir($dir, 0777, true)) {
            die('Не удалось создать директории...');
        }
    }

    // Если пустой, отправляем пример
    if(empty($messageTextLower)){
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
    $ImgData = \modules\openai\services\sOpenAI::instance()->getImg($api_gpt, $messageTextLower, '256x256');
    if(!empty($ImgData['error'])){
        exit(json_encode($ImgData));
    }

    if(!empty($ImgData['url'])){
        // save img
        $fileName = $dir.'/'.time().'.png';
        file_put_contents($fileName, file_get_contents($ImgData['url']));

        sTelegram::instance()->sendPhoto($bot_token, $message_chat_id, $fileName, $messageTextLower, $message_id);
        exit;
    }

}

// StableDiffusion Рисует картинку по запросу
$pos2 = stripos($messageTextLower, '/sd');
if ($pos2 !== false) {
    $messageTextLower = str_replace('/sd', '', $messageTextLower);
    $messageTextLower = trim($messageTextLower);

    $AllowedModelsArr=[
        mb_strtolower('stabilityai/stable-diffusion-2-1-base'),
        mb_strtolower('XpucT/Deliberate'),
        mb_strtolower('darkstorm2150/Protogen_Nova_Official_Release'),
        mb_strtolower('nitrosocke/Ghibli-Diffusion'),
    ];
    $model_id = $AllowedModelsArr[0];

    $MessageArr = explode(' ', $messageTextLower);
    if(!empty($MessageArr[0]) && $MessageArr[0]=='models'){
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'models: '.implode(PHP_EOL, $AllowedModelsArr), '', $message_id);
        exit;
    }
    // Проверим первое слово, если это требуемая модель, то применяем
    if(!empty($MessageArr[0]) && in_array($MessageArr[0], $AllowedModelsArr)){
        $model_id = $MessageArr[0];
        $messageTextLower = str_replace($model_id, '', $messageTextLower);
    }

    $dir = __DIR__.'/uploads/images';
    if(!file_exists($dir)){
        if (!mkdir($dir, 0777, true)) {
            die('Не удалось создать директории...');
        }
    }

    if(!empty($dataMessage['message']['photo'])){
        // Если генерация из изображения

        $file_id=$dataMessage['message']['photo'][array_key_last($dataMessage['message']['photo'])]['file_id'];
        $saveFileData = sTelegram::instance()->saveFile($bot_token, $file_id, __DIR__.'/uploads/received_files');
        if(!empty($saveFileData['error'])){
            sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'saveFile error', '', $message_id);
            exit;
        }
        $ImgData = \modules\stablediffusion\services\sStableDiffusion::instance()->getImg2Img($model_id, $messageTextLower, $saveFileData['file']);
    } else {
        // Если генерация из текста

        // Если пустой, отправляем пример
        if(empty($messageTextLower)){
            sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Example: /sd stabilityai/stable-diffusion-2-1-base fox in the forest', '', $message_id);
            exit;
        }

        $ImgData = \modules\stablediffusion\services\sStableDiffusion::instance()->getTxt2Img($model_id, $messageTextLower);
    }


    if(!empty($ImgData['error'])){
        exit(json_encode($ImgData));
    }

    if(!empty($ImgData['resultData']['imgs'][0]['FilePath'])){
        sTelegram::instance()->sendPhoto($bot_token, $message_chat_id, $ImgData['resultData']['imgs'][0]['FilePath'], 'model: '.$model_id.'; promt: '.$messageTextLower, $message_id);
        exit;
    }

}

// Если не предусмотрен ответ
//sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Ответ не предусмотрен', '', $message_id);
exit;

