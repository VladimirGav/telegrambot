<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

// Устанавливаем и подключаем Composer
require_once __DIR__.'/../../backend/defines.php';

// Настройки по умолчанию, редактируйте в файле /telegrambot/backend/settings/bot_settings.json
$BotSettings=[
    'enableChatGPT' => 1, // 1 - включить ChatGPT команду /ai; 0 - выключить
    'enableOpenAiImg' => 1, // 1 - включить OpenAi Img команду /img; 0 - выключить
    'enableWelcome' => 1, // 1 - включить приветствие новых участников; 0 - выключить
    'enableGoodbye' => 1, // 1 - включить удаление уведомления о выходе участника из группы; 0 - выключить
    'enableLinkBlocking' => 1, // 1 - включить блокирование ссылок; 0 - выключить
    'enableStableDiffusion' => 1, // 1 Включить генерацию изображений через StableDiffusion если установлена сборка VladimirGavSD
    'enableWallets' => 1, // wallets
    'superUsersIds' => ['000','000'], // id пользователей с привилегиями
    'waitMessage' => 'Запрос обрабатывается. Пожалуйста, подождите.', // Текст Пожалуйста, подождите
];

// Подгружаем файл с индивидуальными настройками бота /telegrambot/backend/settings/bot_settings.json
if(file_exists(_FILE_bot_settings_)){
    $BotSettings = json_decode(file_get_contents(_FILE_bot_settings_), true);
} else {
    // Если индивидуальных настроек нет, то создадим их
    $dirSettings = dirname(_FILE_bot_settings_);
    if(!is_dir($dirSettings)) { mkdir($dirSettings, 0777, true); }
    file_put_contents(_FILE_bot_settings_, json_encode($BotSettings, JSON_PRETTY_PRINT));
}

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

// Если вышел участник, то удалим сообщение о выходе
if(!empty($dataMessage['message']['left_chat_member']['id']) && !empty($BotSettings['enableGoodbye'])){
    sTelegram::instance()->removeMessage($bot_token, $dataMessage['message']['chat']['id'],  $dataMessage['message']['message_id']);
}

// Если бот, то игнорируем сообщение
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
    // Если подпись, то будет как текст
    if(!empty($dataMessage['message']['caption'])){
        $message_text = $dataMessage['message']['caption'];
    }
}

// Если это ответ на сообщение
$reply_to_message_text = '';
if(!empty($dataMessage['message']['reply_to_message']['text'])){
    $reply_to_message_text = $dataMessage['message']['reply_to_message']['text'];
}

// Если ссылки запрещены, то удлаляем сообщение
if(!empty($BotSettings['enableLinkBlocking'])){
    $AllowedMessages = sTelegram::instance()->checkAllowedMessages($dataMessage, ['mention', 'url'], $BotSettings['superUsersIds']);
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

// Если узнаем id пользователя
$messageTextLower = preg_replace('/(.*)(\/user_id@[^ ]*)(.*)/', '/user_id $1$3', $messageTextLower); // Удаляем имя бота, например заменяеам /ai@Name_bot на /ai
if($messageTextLower=='/user_id'){
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'User_id: '.$from_id, '', $message_id);
    exit;
}

// Если первое сообщение
$messageTextLower = preg_replace('/(.*)(\/start@[^ ]*)(.*)/', '/start $1$3', $messageTextLower); // Удаляем имя бота, например заменяеам /ai@Name_bot на /ai
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
$messageTextLower = preg_replace('/(.*)(\/ai@[^ ]*)(.*)/', '/ai $1$3', $messageTextLower); // Удаляем имя бота, например заменяеам /ai@Name_bot на /ai
$pos2 = stripos($messageTextLower, '/ai'); // Проверка /ai в тексте сообщения
$pos3 = stripos($reply_to_message_text, '#ai_'); // Проверка в #ai_ при разговоре
if (($pos2 !== false || $pos3 !== false) && !empty($BotSettings['enableChatGPT'])) {
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

    $waitMessageData = sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $BotSettings['waitMessage'], '', $message_id); // show waitMessage

    // Получим id истории сообщений, если пользователь отвечает боту.
    $HistoryArr = explode('#ai_',$reply_to_message_text);
    $historyMessagesId=0;
    if(!empty($HistoryArr[1])){
        $historyMessagesId = (int)(mb_substr($HistoryArr[1], 0, 10));
    }

    // gpt-3.5-turbo
    $ChatGPTAnswerData = \modules\openai\services\sOpenAI::instance()->getChatGPTAnswer($api_gpt, $messageTextLower, $historyMessagesId);
    if(!empty($ChatGPTAnswerData['error'])){
        exit(json_encode($ChatGPTAnswerData));
    }

    if(!empty($ChatGPTAnswerData['answer'])){
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $ChatGPTAnswerData['answer'].' #ai_'.$ChatGPTAnswerData['historyMessagesId'], '', $message_id);
    }

    sTelegram::instance()->removeMessage($bot_token, $message_chat_id,  $waitMessageData['MessageId']); // remove waitMessage
    exit;
}

// АИ Рисуем картинку по запросу
$messageTextLower = preg_replace('/(.*)(\/img@[^ ]*)(.*)/', '/img $1$3', $messageTextLower); // Удаляем имя бота, например заменяеам /ai@Name_bot на /ai
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

    $waitMessageData = sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $BotSettings['waitMessage'], '', $message_id); // show waitMessage

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
    }

    sTelegram::instance()->removeMessage($bot_token, $message_chat_id,  $waitMessageData['MessageId']); // remove waitMessage
    exit;
}

// StableDiffusion Рисует картинку по запросу
$messageTextLower = preg_replace('/(.*)(\/sd@[^ ]*)(.*)/', '/sd $1$3', $messageTextLower); // Удаляем имя бота, например заменяеам /ai@Name_bot на /ai
$pos2 = stripos($messageTextLower, '/sd');
if ($pos2 !== false && !empty($BotSettings['enableStableDiffusion'])) {
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

    $promptArr = explode('negative',$messageTextLower);
    $prompt = $promptArr [0];
    $n_prompt='';
    if(!empty($promptArr [1])){
        $n_prompt = $promptArr [1];
    }

    $waitMessageData = sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $BotSettings['waitMessage'], '', $message_id); // show waitMessage
    if(!empty($dataMessage['message']['photo'])){
        // Если генерация из изображения

        $file_id=$dataMessage['message']['photo'][array_key_last($dataMessage['message']['photo'])]['file_id'];
        $saveFileData = sTelegram::instance()->saveFile($bot_token, $file_id, __DIR__.'/uploads/received_files');
        if(!empty($saveFileData['error'])){
            sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'saveFile error', '', $message_id);
            exit;
        }
        $ImgData = \modules\stablediffusion\services\sStableDiffusion::instance()->getImg2Img($model_id, $prompt, $n_prompt, $saveFileData['file']);
    } else {
        // Если генерация из текста

        // Если пустой, отправляем пример
        if(empty($messageTextLower)){
            sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Example: /sd stabilityai/stable-diffusion-2-1-base fox in the forest negative water', '', $message_id);
            exit;
        }

        $ImgData = \modules\stablediffusion\services\sStableDiffusion::instance()->getTxt2Img($model_id, $prompt, $n_prompt);
    }
    sTelegram::instance()->removeMessage($bot_token, $message_chat_id,  $waitMessageData['MessageId']); // remove waitMessage


    if(!empty($ImgData['error'])){
        exit(json_encode($ImgData));
    }

    if(!empty($ImgData['resultData']['imgs'][0]['FilePath'])){
        sTelegram::instance()->sendPhoto($bot_token, $message_chat_id, $ImgData['resultData']['imgs'][0]['FilePath'], 'model: '.$model_id.'; prompt: '.$prompt.'; negative: '.$n_prompt, $message_id);
        exit;
    }

}

$messageTextLower = preg_replace('/(.*)(\/new_wallets@[^ ]*)(.*)/', '/new_wallets $1$3', $messageTextLower); // Удаляем имя бота, например заменяеам /ai@Name_bot на /ai
$pos2 = stripos($messageTextLower, '/new_wallets');
if ($pos2 !== false && !empty($BotSettings['enableWallets'])) {
    $messageTextLower = str_replace('/new_wallets', '', $messageTextLower);
    $messageTextLower = trim($messageTextLower);

    $countWallets = 1;
    if(!empty($messageTextLower)){
        if((int)$messageTextLower>1){
            $countWallets = (int)$messageTextLower;
        }
    }

    $waitMessageData = sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $BotSettings['waitMessage'], '', $message_id); // show waitMessage

    $ListWallets = '<b>Ethereum wallets ('.$countWallets.'): </b>'.PHP_EOL.PHP_EOL;
    for ($i = 1; $i <= $countWallets; $i++) {
        $WalletData = \modules\crypto\services\sCrypto::instance()->createWallet();
        $ListWallets .= '<b>Wallet '.$i.'</b>'.PHP_EOL;
        $ListWallets .= '<b>Address:</b> '.$WalletData['address'].PHP_EOL;
        $ListWallets .= '<b>PrivateKey:</b> '.$WalletData['privateKey'].PHP_EOL.PHP_EOL;
    }

    sTelegram::instance()->removeMessage($bot_token, $message_chat_id,  $waitMessageData['MessageId']); // remove waitMessage
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $ListWallets, '', $message_id);
    exit;
}
// Men's wallets

// Если не предусмотрен ответ
//sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Ответ не предусмотрен', '', $message_id);
exit;

