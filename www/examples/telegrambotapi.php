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

    'enableGPT' => 1, // 1 - включить ChatGPT команду /gpt; 0 - выключить
    'gptAllowedModelsArr' => ['gpt-4'=>'GPT-4', 'gpt-4-1106-preview'=>'GPT-4 Turbo', 'gpt-3.5-turbo'=>'GPT-3.5 Turbo'], // Массив моделей для gpt

    'enableWelcome' => 1, // 1 - включить приветствие новых участников; 0 - выключить
    'enableGoodbye' => 1, // 1 - включить удаление уведомления о выходе участника из группы; 0 - выключить
    'enableLinkBlocking' => 1, // 1 - включить блокирование ссылок; 0 - выключить
    'enableWallets' => 1, // wallets

    'superUsersIds' => ['000','000'], // id пользователей с привилегиями
    'AllowedChatIdArr' => [], // Массив чатов для которых работает данный бот. Пустотой массив - нет ограничений
    'waitMessage' => 'Запрос обрабатывается. Пожалуйста, подождите.', // Текст Пожалуйста, подождите

    'enableGPU' => 1, // 1, 0
    'textGPU' => 'The GPU is resting. Please try again later.',

    'enableStableDiffusion' => 1, // 1 Включить генерацию изображений через StableDiffusion если установлена сборка stable-diffusion-vg
    'SdNsfwChatIdArr' => [], // Массив чатов где разрешено nsfw для StableDiffusion
    'pathStableDiffusion' => 'D:/stable-diffusion-vg', // Путь к корню StableDiffusion
    'StableDiffusionAllowedModelsArr' => [0=>'stabilityai/stable-diffusion-2-1', 'SD1.5' => 'runwayml/stable-diffusion-v1-5', 'DreamShaper' => 'Lykon/DreamShaper', 'NeverEnding-Dream' => 'Lykon/NeverEnding-Dream'], // Массив моделей для StableDiffusion которые будут работать с huggingface.co

    'enableNFT' => 1, // 1 Включить NFT

    'enableAiAudio' => 1, // 1 Включить генерацию речи из текста
    'pathAiAudio' => 'D:/ai-audio-vg', // Путь к корню text-to-audio-vg
    'audioAllowedModelsArr' => [0=>'suno/bark-small'], // Массив моделей для audio
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
//$BotData = sTelegram::instance()->getBotData($bot_token);

/*$checkApi = sTelegram::instance()->checkApi($bot_token);
if(!empty($checkApi['error'])){
    echo json_encode($checkApi);
    exit;
}*/

// Если запускаем через консоль, а не используем Telegram Webhook
if(!empty($_SERVER['argv'][1]) && $_SERVER['argv'][1]=='console'){
    $removeWebhook = sTelegram::instance()->removeWebhook($bot_token); // Удаляем привязку к Telegram Webhook
    if(!empty($removeWebhook['error'])){ exit(json_encode($removeWebhook)); }
    // TODO Может требуется очистка services\telegram-ids
    $dataMessage = sTelegram::instance()->getUpdatesLastMessage($bot_token);
} else {
    // TODO Надо протестировать callback
    $dataMessage = sTelegram::instance()->getWebhookLastMessage($bot_token);
}

// callback_query для интерактива
$dataCallback = [];
if(!empty($dataMessage['callback_query'])){
    $dataCallback = $dataMessage;
    $dataMessage = $dataCallback['callback_query'];
    // Если ответил другой пользователь, то не обрабатываем
    if($dataCallback['callback_query']['message']['reply_to_message']['from']['id'] != $dataCallback['callback_query']['from']['id']){
        exit;
    }
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
if(!empty($dataMessage['message']['from']['is_bot']) && empty($dataCallback)){
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
$message_text = htmlspecialchars($message_text);

// Если интерактив
if(!empty($dataCallback['callback_query']['from']['id'])) {
    $from_id = $dataCallback['callback_query']['from']['id'];
}
if(!empty($dataCallback['callback_query']['data'])) {
    $message_text = $dataCallback['callback_query']['data'];
}

// Если это ответ на сообщение
$reply_to_message_text = '';
if(!empty($dataMessage['message']['reply_to_message']['text'])){
    $reply_to_message_text = $dataMessage['message']['reply_to_message']['text'];
}

// Если указан массив чатов для работы, если супер юзер то игнорируем
if(!empty($BotSettings['AllowedChatIdArr']) && !in_array($message_chat_id, $BotSettings['AllowedChatIdArr']) && !in_array($from_id, $BotSettings['superUsersIds'])){
    //sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Доступ к боту запрещен, используйте бот в другом чате.', '', $message_id);
    exit;
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
$messageTextLower = \modules\botservices\services\sPrompt::instance()->getMessageTextLower($message_text);

// Если узнаем id пользователя
$messageTextLower = \modules\botservices\services\sPrompt::instance()->removeBotName($messageTextLower, 'user_id');
if($messageTextLower=='/user_id'){
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'User_id: '.$from_id, '', $message_id);
    exit;
}

// Если узнаем $chat_id
$messageTextLower = preg_replace('/(.*)(\/chat_id@[^ ]*)(.*)/', '/chat_id $1$3', $messageTextLower); // Удаляем имя бота, например заменяеам /ai@Name_bot на /ai
if($messageTextLower=='/chat_id'){
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'chat_id: '.$message_chat_id, '', $message_id);
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
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Ответ от бота на сообщение тест. <b>Вы можете предусмотреть свои ответы на любые сообщения в формате HTML.</b>', '');
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

// Example of an interactive menu
$CommandName = '/menu';
$messageTextLower = \modules\botservices\services\sPrompt::instance()->removeBotName($message_text, $CommandName);
if (stripos($messageTextLower, $CommandName) !== false) {
    $messageTextLower = str_replace($CommandName, '', $messageTextLower);
    $messageTextLower = trim($messageTextLower);

    $InteractiveArrData = \modules\telegram\services\sInteractive::instance()->getExampleInteractiveArrData('simple'); // simple OR tree
    $InteractiveKeysStr = '';
    if(!empty($dataCallback['callback_query']['data'])){
        $InteractiveKeysStr = explode(' ', $dataCallback['callback_query']['data'])[0];
    }
    $InteractiveResData = \modules\telegram\services\sInteractive::instance()->getInteractive($CommandName, $InteractiveArrData, $InteractiveKeysStr);

    if(!empty($InteractiveResData['error'])){
        print_r($InteractiveResData);
        exit;
    }
    if(empty($InteractiveResData['outDataArr']['isFinish'])){
        if(empty($InteractiveResData['outDataArr']['editMarkup'])){
            sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $InteractiveResData['outDataArr']['select_text'], $InteractiveResData['outDataArr']['reply_markup'], $message_id);
        } else {
            sTelegram::instance()->editMessageText($bot_token, $dataCallback['callback_query']['message']['chat']['id'], $dataCallback['callback_query']['message']['message_id'], $InteractiveResData['outDataArr']['select_text'], $InteractiveResData['outDataArr']['reply_markup']);
            //sTelegram::instance()->editMessageReplyMarkup($bot_token, $dataCallback['callback_query']['message']['chat']['id'], $dataCallback['callback_query']['message']['message_id'], '', $InteractiveResData['outDataArr']['reply_markup']);
        }
        exit;
    } else {
        // isFinish
        // delete interactive message
        if(!empty($dataCallback['callback_query']['message']['message_id'])){
            sTelegram::instance()->removeMessage($bot_token, $dataCallback['callback_query']['message']['chat']['id'],  $dataCallback['callback_query']['message']['message_id']); // remove
        }
    }
    // change the message_id to the original
    if(!empty($dataCallback['callback_query']['message']['reply_to_message']['message_id'])){
        $message_id = $dataCallback['callback_query']['message']['reply_to_message']['message_id'];
    }
    // interactive data $InteractiveResData['outDataArr']['arrKeysValues']
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, json_encode($InteractiveResData['outDataArr']['arrKeysValues'],JSON_PRETTY_PRINT), '', $message_id);
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

    $model_id = 'gpt-3.5-turbo';
    $ChatGPTAnswerData = \modules\openai\services\sOpenAI::instance()->getChatGPTAnswer($api_gpt, $messageTextLower, $historyMessagesId, $model_id);
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

        $sendPhotoId = sTelegram::instance()->sendPhoto($bot_token, $message_chat_id, $fileName, $messageTextLower, $message_id);
    }

    sTelegram::instance()->removeMessage($bot_token, $message_chat_id,  $waitMessageData['MessageId']); // remove waitMessage
    exit;
}

// AI Gpt
$messageTextLower = \modules\botservices\services\sPrompt::instance()->removeBotName($message_text, 'gpt');
$pos3 = stripos($reply_to_message_text, '#gpt_'); // Проверка в #gpt_ при разговоре
if ((stripos($messageTextLower, '/gpt') !== false || $pos3 !== false ) && !empty($BotSettings['enableGPT'])) {
    $messageTextLower = \modules\botservices\services\sPrompt::instance()->removeCommand($messageTextLower);

    $exampleText = '';
    $exampleText .= '/gpt your question?'.PHP_EOL;
    //$exampleText .= 'prompt: Hello.'.PHP_EOL;
    //$exampleText .= 'model_id: gpt-4'.PHP_EOL;

    $AllowedModelsArr=$BotSettings['gptAllowedModelsArr'];

    /*$dir = __DIR__.'/uploads/gpt';
    if(!file_exists($dir)){
        if (!mkdir($dir, 0777, true)) {
            die('Не удалось создать директории...');
        }
    }*/

    // Interactive Bot

    $message_text_prompt = $message_text;

    // Если это интерактивный, то берем текст из предыдущего сообщения
    if(!empty($dataCallback['callback_query']['message']['reply_to_message']['text'])){
        $message_text_prompt = $dataCallback['callback_query']['message']['reply_to_message']['text'];
    }

    // Текст с ключами в массив с данными
    $PromptDataByMessage = \modules\botservices\services\sPrompt::instance()->getPromptDataByMessage($message_text_prompt, 'prompt', ['model_id','prompt']);
    $promptData=$PromptDataByMessage['promptData'];

    // Если пустой, отправляем пример
    if(empty($promptData['prompt'])){
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $exampleText, '', $message_id);
        exit;
    }

    // Получим id истории сообщений, если пользователь отвечает боту.
    $HistoryArr = explode('#gpt_',$reply_to_message_text);
    $historyMessagesId=0;
    if(!empty($HistoryArr[1])){
        $historyMessagesId = (int)(mb_substr($HistoryArr[1], 0, 10));
        $prompt = $promptData['prompt'];
        $model_id = ''; // модель из истории будет
    } else {

        $InteractiveArrData['TypeSelect'] = 'simple';

        // Choice 1
        if(empty($promptData['model_id'])){

            $select_data = [];
            foreach ($AllowedModelsArr as $AllowedModelKey => $AllowedModelRow){
                $select_data[] = ['select_value' => $AllowedModelKey, 'select_name' => $AllowedModelRow];
            }

            $InteractiveArrData['ElementsSelect'][] = [
                'columns' => 3,
                'select_value' => 'Value_Element_0',
                'select_name' => 'Model',
                'select_text' => 'Select model:',
                'select_key' => 'model_id',
                'select_data' => $select_data,
            ];

        }

        //$InteractiveArrData = \modules\telegram\services\sInteractive::instance()->getExampleInteractiveArrData('simple');
        $InteractiveKeysStr = '';
        if(!empty($dataCallback['callback_query']['data'])){
            $InteractiveKeysStr = explode(' ', $dataCallback['callback_query']['data'])[0];
        }
        $InteractiveResData = \modules\telegram\services\sInteractive::instance()->getInteractive('/gpt', $InteractiveArrData, $InteractiveKeysStr);

        if(!empty($InteractiveResData['error'])){
            print_r($InteractiveResData);
            exit;
        }
        if(empty($InteractiveResData['outDataArr']['isFinish'])){
            if(empty($InteractiveResData['outDataArr']['editMarkup'])){
                sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $InteractiveResData['outDataArr']['select_text'], $InteractiveResData['outDataArr']['reply_markup'], $message_id);
            } else {
                sTelegram::instance()->editMessageText($bot_token, $dataCallback['callback_query']['message']['chat']['id'], $dataCallback['callback_query']['message']['message_id'], $InteractiveResData['outDataArr']['select_text'], $InteractiveResData['outDataArr']['reply_markup']);
                //sTelegram::instance()->editMessageReplyMarkup($bot_token, $dataCallback['callback_query']['message']['chat']['id'], $dataCallback['callback_query']['message']['message_id'], '', $InteractiveResData['outDataArr']['reply_markup']);
            }
            exit;
        } else {
            // isFinish
            // delete interactive message
            if(!empty($dataCallback['callback_query']['message']['message_id'])){
                sTelegram::instance()->removeMessage($bot_token, $dataCallback['callback_query']['message']['chat']['id'],  $dataCallback['callback_query']['message']['message_id']); // remove
            }
        }
        // change the message_id to the original
        if(!empty($dataCallback['callback_query']['message']['reply_to_message']['message_id'])){
            $message_id = $dataCallback['callback_query']['message']['reply_to_message']['message_id'];
        }
        // change the message_id to the original
        /*if(!empty($dataCallback['callback_query']['message']['message_thread_id'])){
            $message_id = $dataCallback['callback_query']['message']['message_thread_id'];
        }*/

        // promptData add interactive data
        if(!empty($promptData) && !empty($InteractiveResData['outDataArr']['arrKeysValues'])){
            $promptData = array_merge($promptData, $InteractiveResData['outDataArr']['arrKeysValues']);
        }

        // Делаем проверки по параметрам

        // Если это требуемая модель, то применяем
        $model_id = reset($AllowedModelsArr);
        if(!empty($promptData['model_id'])){
            foreach ($AllowedModelsArr as $AllowedModelKey => $AllowedModelRow){
                if(mb_strtolower($promptData['model_id']) == mb_strtolower($AllowedModelKey) || mb_strtolower($promptData['model_id']) == mb_strtolower($AllowedModelRow)){
                    $model_id = mb_strtolower($AllowedModelRow);
                }
            }
        }

        $prompt = (!empty($promptData['prompt']))?$promptData['prompt']:'';
        $model_id = (!empty($promptData['model_id']))?$promptData['model_id']:'';

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
    /*$HistoryArr = explode('#gpt_',$reply_to_message_text);
    $historyMessagesId=0;
    if(!empty($HistoryArr[1])){
        $historyMessagesId = (int)(mb_substr($HistoryArr[1], 0, 10));
        $prompt = '';
        $model_id = '';
    } else {

    }*/

    // gpt
    $ChatGPTAnswerData = \modules\openai\services\sOpenAI::instance()->getChatGPTAnswer($api_gpt, $prompt, $historyMessagesId, $model_id);
    if(!empty($ChatGPTAnswerData['error'])){
        exit(json_encode($ChatGPTAnswerData));
    }

    if(!empty($ChatGPTAnswerData['answer'])){
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $ChatGPTAnswerData['answer'].' #gpt_'.$ChatGPTAnswerData['historyMessagesId'], '', $message_id);
    }

    sTelegram::instance()->removeMessage($bot_token, $message_chat_id,  $waitMessageData['MessageId']); // remove waitMessage
    exit;
}

$messageTextLower = preg_replace('/(.*)(\/sd_models@[^ ]*)(.*)/', '/sd_models $1$3', $messageTextLower); // Удаляем имя бота, например заменяеам /ai@Name_bot на /ai
$pos2 = stripos($messageTextLower, '/sd_models');
if ($pos2 !== false && !empty($BotSettings['enableStableDiffusion'])) {
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Allowed Models:'.PHP_EOL.implode(PHP_EOL, $BotSettings['StableDiffusionAllowedModelsArr']), '', $message_id);
    exit;
}

// StableDiffusion Рисует картинку по запросу
$messageTextLower = \modules\botservices\services\sPrompt::instance()->removeBotName($message_text, 'sd');
if (stripos($messageTextLower, '/sd') !== false && !empty($BotSettings['enableStableDiffusion'])) {

    if(empty($BotSettings['enableGPU'])){
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $BotSettings['textGPU'], '', $message_id);
        exit;
    }

    $messageTextLower = \modules\botservices\services\sPrompt::instance()->removeCommand($messageTextLower);

    // Подключаем нейросеть StableDiffusion
    $sStableDiffusion = new \modules\stablediffusion\services\sStableDiffusion();
    $sStableDiffusion->pathStableDiffusion = $BotSettings['pathStableDiffusion'];

    $exampleText = '';
    $exampleText .= '/sd beautiful (cyborg) with pink hair'.PHP_EOL;
    $exampleText .= PHP_EOL;
    $exampleText .= 'Примеры: 🔺 Простой запрос. 🔻 Продвинутый запрос:'.PHP_EOL;
    $exampleText .= PHP_EOL;
    $exampleText .= '/sd'.PHP_EOL;
    $exampleText .= 'model_id: Lykon/DreamShaper'.PHP_EOL;
    $exampleText .= 'img_width: 512'.PHP_EOL;
    $exampleText .= 'img_height: 768'.PHP_EOL;
    $exampleText .= 'img_num_inference_steps: 25'.PHP_EOL;
    $exampleText .= 'img_guidance_scale: 7.5'.PHP_EOL;
    $exampleText .= 'sampler: dpm++ sde karras'.PHP_EOL;
    $exampleText .= 'tags: #example'.PHP_EOL;
    $exampleText .= PHP_EOL;
    $exampleText .= 'prompt: 8k portrait of beautiful (cyborg) with pink hair'.PHP_EOL;
    $exampleText .= PHP_EOL;
    $exampleText .= 'negative_prompt: disfigured, kitsch, ugly, oversaturated, grain'.PHP_EOL;

    $AllowedModelsArr=$BotSettings['StableDiffusionAllowedModelsArr'];

    $dir = __DIR__.'/uploads/images';
    if(!file_exists($dir)){
        if (!mkdir($dir, 0777, true)) {
            die('Не удалось создать директории...');
        }
    }

    /*echo '<pre>';
    print_r($dataCallback);
    echo '</pre>';*/
    //exit;

    // Interactive Bot

    $message_text_prompt = $message_text;

    // Если это интерактивный, то берем текст из предыдущего сообщения
    if(!empty($dataCallback['callback_query']['message']['reply_to_message']['text'])){
        $message_text_prompt = $dataCallback['callback_query']['message']['reply_to_message']['text'];
    }

    // Текст с ключами в массив с данными
    $PromptDataByMessage = \modules\botservices\services\sPrompt::instance()->getPromptDataByMessage($message_text_prompt, 'prompt', ['model_id','img_width','img_height','img_num_inference_steps','img_guidance_scale', 'sampler', 'tags','prompt','negative_prompt','nft']);
    $promptData=$PromptDataByMessage['promptData'];

    if(!empty($promptData['prompt'])){
        $promptData['prompt'] = strip_tags($promptData['prompt']);
        $promptData['prompt'] = preg_replace("/[^A-Za-z0-9_ ->]/", '', $promptData['prompt']); //Supports English only
    }

    // Если пустой, отправляем пример
    if(empty($promptData['prompt'])){
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $exampleText, '', $message_id);
        exit;
    }

    /*echo '<pre>';
    print_r($promptData);
    echo '</pre>';
    exit;*/

    $InteractiveArrData['TypeSelect'] = 'simple';

    // Choice 1
    if(empty($promptData['model_id'])){

        $select_data = [];
        foreach ($AllowedModelsArr as $AllowedModelKey => $AllowedModelRow){
            $select_name = empty($AllowedModelKey)?$AllowedModelRow:$AllowedModelKey;
            $select_data[] = ['select_value' => $AllowedModelKey, 'select_name' => $select_name];
        }

        $InteractiveArrData['ElementsSelect'][] = [
            'columns' => 2,
            'select_value' => 'Value_Element_0',
            'select_name' => 'Модель',
            'select_text' => 'Выберите модель для генерации изображения:',
            'select_key' => 'model_id',
            'select_data' => $select_data,
        ];

    }

    // Choice 2
    if(empty($promptData['width_height']) && ( empty($promptData['img_width']) && empty($promptData['img_height']) )){

        $select_data = [];
        foreach ($AllowedModelsArr as $AllowedModelKey => $AllowedModelRow){
            $select_data[] = ['select_value' => $AllowedModelKey, 'select_name' => $AllowedModelKey];
        }

        $InteractiveArrData['ElementsSelect'][] = [
            'columns' => 3,
            'select_value' => 'Value_Element_0',
            'select_name' => 'Модель',
            'select_text' => 'Выберите размер изображения:',
            'select_key' => 'width_height',
            'select_data' => [
                ['select_value' => '512x512', 'select_name' => '512x512'],
                ['select_value' => '512x768', 'select_name' => '512x768'],
                ['select_value' => '768x512', 'select_name' => '768x512'],
            ]
        ];

    }

    // Choice 3
    if(empty($promptData['img_num_inference_steps'])){

        $select_data = [];
        for ($i = 1; $i <= 10; $i++) {
            $select_value = $i*5;
            $select_data[] = ['select_value' => $select_value, 'select_name' => $select_value];
        }

        $InteractiveArrData['ElementsSelect'][] = [
            'columns' => 3,
            'select_value' => 'Value_Element_0',
            'select_name' => 'Модель',
            'select_text' => 'Выберите количество шагов для шумоподавления:',
            'select_key' => 'img_num_inference_steps',
            'select_data' => $select_data,
        ];

    }

    // Choice 4
    if(empty($promptData['img_guidance_scale'])){
        $select_data = [];
        for ($i = 1; $i <= 10; $i++) {
            $select_value = 15/100*$i*10;
            $select_percent = (int)($i*10);
            $select_data[] = ['select_value' => $select_value, 'select_name' => $select_percent.'%'];
        }

        $InteractiveArrData['ElementsSelect'][] = [
            'columns' => 3,
            'select_value' => 'Value_Element_0',
            'select_name' => 'Модель',
            'select_text' => 'Выберите насколько сгенерированное изображение будет похоже на подсказку:',
            'select_key' => 'img_guidance_scale',
            'select_data' => $select_data,
        ];

    }

    // Choice 5
    if(empty($promptData['sampler'])){

        $InteractiveArrData['ElementsSelect'][] = [
            'columns' => 3,
            'select_value' => 'Value_Element_0',
            'select_name' => 'Модель',
            'select_text' => 'Выберите sampler:',
            'select_key' => 'sampler',
            'select_data' => [
                ['select_value' => 'euler', 'select_name' => 'euler'],
                ['select_value' => 'ddpm', 'select_name' => 'ddpm'],
                ['select_value' => 'dpm++ sde', 'select_name' => 'dpm++ sde'],
                ['select_value' => 'dpm++', 'select_name' => 'dpm++'],
                ['select_value' => 'karras', 'select_name' => 'karras'],
            ]
        ];

    }

    // Choice 6
    if(empty($promptData['negative_prompt'])){

        $InteractiveArrData['ElementsSelect'][] = [
            'columns' => 2,
            'select_value' => 'Value_Element_0',
            'select_name' => 'Модель',
            'select_text' => 'Выберите набор негативных подсказок:',
            'select_key' => 'negative_prompt',
            'select_data' => [
                ['select_value' => '', 'select_name' => 'Пропустить'],
                ['select_value' => 'worst quality, normal quality, low quality, low res, blurry, text, watermark, logo, banner, extra digits, cropped, jpeg artifacts, signature, username, error, sketch ,duplicate, ugly, monochrome, horror, geometry, mutation, disgusting', 'select_name' => 'Часто используемые'],
                ['select_value' => 'bad anatomy, bad hands, three hands, three legs, bad arms, missing legs, missing arms, poorly drawn face, bad face, fused face, cloned face, worst face, three crus, extra crus, fused crus, worst feet, three feet, fused feet, fused thigh, three thigh, fused thigh, extra thigh, worst thigh, missing fingers, extra fingers, ugly fingers, long fingers, horn, realistic photo, extra eyes, huge eyes, 2girl, amputation, disconnected limbs', 'select_name' => 'Для анимированного персонажа'],
                ['select_value' => 'bad anatomy, bad hands, three hands, three legs, bad arms, missing legs, missing arms, poorly drawn face, bad face, fused face, cloned face, worst face, three crus, extra crus, fused crus, worst feet, three feet, fused feet, fused thigh, three thigh, fused thigh, extra thigh, worst thigh, missing fingers, extra fingers, ugly fingers, long fingers, horn, extra eyes, huge eyes, 2girl, amputation, disconnected limbs, cartoon, cg, 3d, unreal, animate', 'select_name' => 'Для реалистичного персонажа'],
            ]
        ];

    }

    //$InteractiveArrData = \modules\telegram\services\sInteractive::instance()->getExampleInteractiveArrData('simple');
    $InteractiveKeysStr = '';
    if(!empty($dataCallback['callback_query']['data'])){
        $InteractiveKeysStr = explode(' ', $dataCallback['callback_query']['data'])[0];
    }
    $InteractiveResData = \modules\telegram\services\sInteractive::instance()->getInteractive('/sd', $InteractiveArrData, $InteractiveKeysStr);

    if(!empty($InteractiveResData['error'])){
        print_r($InteractiveResData);
        exit;
    }
    if(empty($InteractiveResData['outDataArr']['isFinish'])){
        if(empty($InteractiveResData['outDataArr']['editMarkup'])){
            sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $InteractiveResData['outDataArr']['select_text'], $InteractiveResData['outDataArr']['reply_markup'], $message_id);
        } else {
            sTelegram::instance()->editMessageText($bot_token, $dataCallback['callback_query']['message']['chat']['id'], $dataCallback['callback_query']['message']['message_id'], $InteractiveResData['outDataArr']['select_text'], $InteractiveResData['outDataArr']['reply_markup']);
            //sTelegram::instance()->editMessageReplyMarkup($bot_token, $dataCallback['callback_query']['message']['chat']['id'], $dataCallback['callback_query']['message']['message_id'], '', $InteractiveResData['outDataArr']['reply_markup']);
        }
        exit;
    } else {
        // isFinish
        // delete interactive message
        if(!empty($dataCallback['callback_query']['message']['message_id'])){
            sTelegram::instance()->removeMessage($bot_token, $dataCallback['callback_query']['message']['chat']['id'],  $dataCallback['callback_query']['message']['message_id']); // remove
        }
    }
    // change the message_id to the original
    if(!empty($dataCallback['callback_query']['message']['reply_to_message']['message_id'])){
        $message_id = $dataCallback['callback_query']['message']['reply_to_message']['message_id'];
    }
    // change the message_id to the original
    /*if(!empty($dataCallback['callback_query']['message']['message_thread_id'])){
        $message_id = $dataCallback['callback_query']['message']['message_thread_id'];
    }*/

    // promptData add interactive data
    if(!empty($promptData) && !empty($InteractiveResData['outDataArr']['arrKeysValues'])){
        $promptData = array_merge($promptData, $InteractiveResData['outDataArr']['arrKeysValues']);
    }

    // Делаем проверки по параметрам

    // Если это требуемая модель, то применяем
    $model_id = $AllowedModelsArr[0];
    if(!empty($promptData['model_id'])){
        foreach ($AllowedModelsArr as $AllowedModelKey => $AllowedModelRow){
            if(mb_strtolower($promptData['model_id']) == mb_strtolower($AllowedModelKey) || mb_strtolower($promptData['model_id']) == mb_strtolower($AllowedModelRow)){
                $model_id = mb_strtolower($AllowedModelRow);
            }
        }
    }

    // Проверим размер
    if(!empty($promptData['width_height'])){ // 512x768
        $width_height = explode('x', $promptData['width_height']);
        if(!empty($width_height[0]) && !empty($width_height[1])){
            $promptData['img_width'] = $width_height[0];
            $promptData['img_height'] = $width_height[1];
        }
    }
    $img_width = (!empty($promptData['img_width']) && (int)$promptData['img_width']>0 )?(int)$promptData['img_width']:512;
    $img_height = (!empty($promptData['img_height']) && (int)$promptData['img_height']>0 )?(int)$promptData['img_height']:512;
    // Если видюха не потянет, то 512
    $summWH = $img_width+$img_height;
    if( $summWH > 1280 ){
        $img_width = 512;
        $img_height = 512;
    }

    // The number of denoising steps, max 50
    $img_num_inference_steps = (isset($promptData['img_num_inference_steps']) && (int)$promptData['img_num_inference_steps']>=0 && (int)$promptData['img_num_inference_steps']<=50 )?(int)$promptData['img_num_inference_steps']:25;
    // Guidance scale controls how similar the generated image will be to the prompt, 15 - 100% prompt.
    $img_guidance_scale = (isset($promptData['img_guidance_scale']) && floatval($promptData['img_guidance_scale'])>=0 && floatval($promptData['img_guidance_scale'])<=15 )?floatval($promptData['img_guidance_scale']):7.5;
    $sampler = (!empty($promptData['sampler']))?$promptData['sampler']:'dpm++ sde karras';

    $prompt = (!empty($promptData['prompt']))?$promptData['prompt']:'';
    $negative_prompt = (!empty($promptData['negative_prompt']))?$promptData['negative_prompt']:'';

    $nsfw = false;
    if(!empty($BotSettings['SdNsfwChatIdArr']) && in_array($message_chat_id, $BotSettings['SdNsfwChatIdArr'])){
        $nsfw = true;
    }

    $sdData=[];
    $sdData['from_id'] = $from_id;
    $sdData['nsfw'] = $nsfw;
    $sdData['model_id']=$model_id;
    $sdData['img_width']=$img_width;
    $sdData['img_height']=$img_height;
    $sdData['img_num_inference_steps']=$img_num_inference_steps;
    $sdData['img_guidance_scale']=$img_guidance_scale;
    $sdData['model_lora_weights']='';
    $sdData['sampler']=$sampler;
    $sdData['prompt']=$prompt;
    $sdData['negative_prompt']=$negative_prompt;

    $waitMessageData = sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $BotSettings['waitMessage'], '', $message_id); // show waitMessage
    if(!empty($dataMessage['message']['photo'])){
        // Если генерация из изображения

        $file_id=$dataMessage['message']['photo'][array_key_last($dataMessage['message']['photo'])]['file_id'];
        $saveFileData = sTelegram::instance()->saveFile($bot_token, $file_id, __DIR__.'/uploads/received_files');
        if(!empty($saveFileData['error'])){
            sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'saveFile error', '', $message_id);
            exit;
        }
        $sdData['img_original']=$saveFileData['file'];
        $ImgData = $sStableDiffusion->getImg2Img($sdData);
    } else {
        // Если генерация из текста

        // Если пустой, отправляем пример
        if(empty($prompt)){
            sTelegram::instance()->removeMessage($bot_token, $message_chat_id,  $waitMessageData['MessageId']); // remove waitMessage
            sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $exampleText, '', $message_id);
            exit;
        }

        $ImgData = $sStableDiffusion->getTxt2Img($sdData);
    }
    sTelegram::instance()->removeMessage($bot_token, $message_chat_id,  $waitMessageData['MessageId']); // remove waitMessage


    if(!empty($ImgData['error'])){
        exit(json_encode($ImgData));
    }

    if(!empty($ImgData['resultData']['imgs'][0]['FilePath'])){

        // If the picture is black
        if(filesize($ImgData['resultData']['imgs'][0]['FilePath']) < 3000){
            sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Потенциальное содержимое NSFW. Смотрите описание группы.', '', $message_id);
            exit;
        }

        $resultText = '';
        $resultText .= '/sd'.PHP_EOL;
        $resultText .= 'model_id: '.$ImgData['resultData']['model_id'].PHP_EOL;
        $resultText .= 'img_width: '.$ImgData['resultData']['img_width'].PHP_EOL;
        $resultText .= 'img_height: '.$ImgData['resultData']['img_height'].PHP_EOL;
        $resultText .= 'img_num_inference_steps: '.$ImgData['resultData']['img_num_inference_steps'].PHP_EOL;
        $resultText .= 'img_guidance_scale: '.$ImgData['resultData']['img_guidance_scale'].PHP_EOL;
        $resultText .= 'sampler: '.$ImgData['resultData']['sampler'].PHP_EOL;
        if(!empty($promptData['tags'])){
            $resultText .= 'tags: '.$promptData['tags'].PHP_EOL;
        }
        $resultText .= PHP_EOL;
        $resultText .= 'prompt: '.$ImgData['resultData']['prompt'].PHP_EOL.PHP_EOL;
        $resultText .= 'negative_prompt: '.$ImgData['resultData']['negative_prompt'].PHP_EOL;

        $sendPhotoId = sTelegram::instance()->sendPhoto($bot_token, $message_chat_id, $ImgData['resultData']['imgs'][0]['FilePath'], $resultText, $message_id);

        // create NFT
        if(file_exists(__DIR__.'/../../backend/modules/nft/services/sNFT.php') && !empty($sendPhotoId) && !empty($BotSettings['enableNFT']) && !empty($BotSettings['enableNFT']) && !empty($promptData['nft']) && mb_strtolower($promptData['nft'])=='true' && $nsfw == false){
            \modules\nft\services\sNFT::instance()->addDataNFT(['ImgData' => $ImgData, 'MessageId' => $sendPhotoId['MessageId'], 'message_chat_id' => $message_chat_id, 'message_id' => $message_id, 'from_id' => $from_id]);
        }

        exit;
    }

}

// AI Audio
$messageTextLower = \modules\botservices\services\sPrompt::instance()->removeBotName($message_text, 'audio');
if (stripos($messageTextLower, '/audio') !== false && !empty($BotSettings['enableAiAudio'])) {

    if(empty($BotSettings['enableGPU'])){
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $BotSettings['textGPU'], '', $message_id);
        exit;
    }

    $messageTextLower = \modules\botservices\services\sPrompt::instance()->removeCommand($messageTextLower);

    // Подключаем нейросеть StableDiffusion
    $sAiAudio = new \modules\aiaudio\services\sAiAudio();
    $sAiAudio->pathAiAudio = $BotSettings['pathAiAudio'];

    $exampleText = '';
    $exampleText .= '/audio Example Text. Hello, welcome to Image Club'.PHP_EOL;
    //$exampleText .= 'prompt: Hello, welcome to Image Club.'.PHP_EOL;
    //$exampleText .= 'voice_preset: v2/en_speaker_0'.PHP_EOL;

    $AllowedModelsArr=$BotSettings['audioAllowedModelsArr'];

    $dir = __DIR__.'/uploads/audio';
    if(!file_exists($dir)){
        if (!mkdir($dir, 0777, true)) {
            die('Не удалось создать директории...');
        }
    }

    // Interactive Bot

    $message_text_prompt = $message_text;

    // Если это интерактивный, то берем текст из предыдущего сообщения
    if(!empty($dataCallback['callback_query']['message']['reply_to_message']['text'])){
        $message_text_prompt = $dataCallback['callback_query']['message']['reply_to_message']['text'];
    }

    // Текст с ключами в массив с данными
    $PromptDataByMessage = \modules\botservices\services\sPrompt::instance()->getPromptDataByMessage($message_text_prompt, 'prompt', ['voice_preset','prompt']);
    $promptData=$PromptDataByMessage['promptData'];

    // Если пустой, отправляем пример
    if(empty($promptData['prompt'])){
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $exampleText, '', $message_id);
        exit;
    }

    /*echo '<pre>';
    print_r($promptData);
    echo '</pre>';
    exit;*/

    $InteractiveArrData['TypeSelect'] = 'simple';

    // Choice 1
    /*if(empty($promptData['model_id'])){

        $select_data = [];
        foreach ($AllowedModelsArr as $AllowedModelKey => $AllowedModelRow){
            $select_name = empty($AllowedModelKey)?$AllowedModelRow:$AllowedModelKey;
            $select_data[] = ['select_value' => $AllowedModelKey, 'select_name' => $select_name];
        }

        $InteractiveArrData['ElementsSelect'][] = [
            'columns' => 2,
            'select_value' => 'Value_Element_0',
            'select_name' => 'Модель',
            'select_text' => 'Выберите модель для генерации:',
            'select_key' => 'model_id',
            'select_data' => $select_data,
        ];

    }*/

    // Choice 2
    if(empty($promptData['language'])){

        $select_data = [];
        foreach ($AllowedModelsArr as $AllowedModelKey => $AllowedModelRow){
            $select_data[] = ['select_value' => $AllowedModelKey, 'select_name' => $AllowedModelKey];
        }

        $InteractiveArrData['ElementsSelect'][] = [
            'columns' => 3,
            'select_value' => 'Value_Element_0',
            'select_name' => 'Язык',
            'select_text' => 'Выберите язык:',
            'select_key' => 'language',
            'select_data' => [
                ['select_value' => 'en', 'select_name' => 'English'],
                ['select_value' => 'de', 'select_name' => 'German'],
                ['select_value' => 'es', 'select_name' => 'Spanish'],
                ['select_value' => 'fr', 'select_name' => 'French'],
                ['select_value' => 'hi', 'select_name' => 'Hindi'],
                ['select_value' => 'it', 'select_name' => 'Italian'],
                ['select_value' => 'ja', 'select_name' => 'Japanese'],
                ['select_value' => 'ko', 'select_name' => 'Korean'],
                ['select_value' => 'pl', 'select_name' => 'Polish'],
                ['select_value' => 'pt', 'select_name' => 'Portuguese'],
                ['select_value' => 'ru', 'select_name' => 'Russian'],
                ['select_value' => 'tr', 'select_name' => 'Turkish'],
                ['select_value' => 'zh', 'select_name' => 'Chinese, simplified'],
            ]
        ];

    }

    // Choice 3
    if(empty($promptData['speaker'])){

        $select_data = [];
        for ($i = 0; $i <= 9; $i++) {
            $select_data[] = ['select_value' => 'speaker_'.$i, 'select_name' => 'Speaker '.$i];
        }

        $InteractiveArrData['ElementsSelect'][] = [
            'columns' => 3,
            'select_value' => 'Value_Element_0',
            'select_name' => 'Оператор',
            'select_text' => 'Выберите оператора:',
            'select_key' => 'speaker',
            'select_data' => $select_data,
        ];

    }

    //$InteractiveArrData = \modules\telegram\services\sInteractive::instance()->getExampleInteractiveArrData('simple');
    $InteractiveKeysStr = '';
    if(!empty($dataCallback['callback_query']['data'])){
        $InteractiveKeysStr = explode(' ', $dataCallback['callback_query']['data'])[0];
    }
    $InteractiveResData = \modules\telegram\services\sInteractive::instance()->getInteractive('/audio', $InteractiveArrData, $InteractiveKeysStr);

    if(!empty($InteractiveResData['error'])){
        print_r($InteractiveResData);
        exit;
    }
    if(empty($InteractiveResData['outDataArr']['isFinish'])){
        if(empty($InteractiveResData['outDataArr']['editMarkup'])){
            sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $InteractiveResData['outDataArr']['select_text'], $InteractiveResData['outDataArr']['reply_markup'], $message_id);
        } else {
            sTelegram::instance()->editMessageText($bot_token, $dataCallback['callback_query']['message']['chat']['id'], $dataCallback['callback_query']['message']['message_id'], $InteractiveResData['outDataArr']['select_text'], $InteractiveResData['outDataArr']['reply_markup']);
            //sTelegram::instance()->editMessageReplyMarkup($bot_token, $dataCallback['callback_query']['message']['chat']['id'], $dataCallback['callback_query']['message']['message_id'], '', $InteractiveResData['outDataArr']['reply_markup']);
        }
        exit;
    } else {
        // isFinish
        // delete interactive message
        if(!empty($dataCallback['callback_query']['message']['message_id'])){
            sTelegram::instance()->removeMessage($bot_token, $dataCallback['callback_query']['message']['chat']['id'],  $dataCallback['callback_query']['message']['message_id']); // remove
        }
    }
    // change the message_id to the original
    if(!empty($dataCallback['callback_query']['message']['reply_to_message']['message_id'])){
        $message_id = $dataCallback['callback_query']['message']['reply_to_message']['message_id'];
    }
    // change the message_id to the original
    /*if(!empty($dataCallback['callback_query']['message']['message_thread_id'])){
        $message_id = $dataCallback['callback_query']['message']['message_thread_id'];
    }*/

    // promptData add interactive data
    if(!empty($promptData) && !empty($InteractiveResData['outDataArr']['arrKeysValues'])){
        $promptData = array_merge($promptData, $InteractiveResData['outDataArr']['arrKeysValues']);
    }

    // Делаем проверки по параметрам

    // Если это требуемая модель, то применяем
    $model_id = $AllowedModelsArr[0];
    if(!empty($promptData['model_id'])){
        foreach ($AllowedModelsArr as $AllowedModelKey => $AllowedModelRow){
            if(mb_strtolower($promptData['model_id']) == mb_strtolower($AllowedModelKey) || mb_strtolower($promptData['model_id']) == mb_strtolower($AllowedModelRow)){
                $model_id = mb_strtolower($AllowedModelRow);
            }
        }
    }

    $prompt = (!empty($promptData['prompt']))?$promptData['prompt']:'';
    $voice_preset = (!empty($promptData['voice_preset']))?$promptData['voice_preset']:'';

    if(!empty($promptData['language']) && !empty($promptData['speaker'])){
        $voice_preset = $promptData['language'].'_'.$promptData['speaker'];
    }


    $audioData=[];
    $audioData['from_id'] = $from_id;
    $audioData['model_id']=$model_id;
    $audioData['prompt']=$prompt;
    $audioData['voice_preset']=$voice_preset;

    $waitMessageData = sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $BotSettings['waitMessage'], '', $message_id); // show waitMessage

    // Если генерация из текста

    // Если пустой, отправляем пример
    if(empty($prompt)){
        sTelegram::instance()->removeMessage($bot_token, $message_chat_id,  $waitMessageData['MessageId']); // remove waitMessage
        sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $exampleText, '', $message_id);
        exit;
    }

    $AiAudioData = $sAiAudio->getTxt2Audio($audioData);

    sTelegram::instance()->removeMessage($bot_token, $message_chat_id,  $waitMessageData['MessageId']); // remove waitMessage

    if(!empty($AiAudioData['error'])){
        exit(json_encode($AiAudioData));
    }

    if(!empty($AiAudioData['resultData']['files'][0]['FilePath'])){

        $resultText = '';
        $resultText .= '/audio'.PHP_EOL;
        $resultText .= 'prompt: '.$AiAudioData['resultData']['prompt'].PHP_EOL;
        $resultText .= 'voice_preset: '.$AiAudioData['resultData']['voice_preset'].PHP_EOL;

        $sendPhotoId = sTelegram::instance()->sendAudio($bot_token, $message_chat_id, $AiAudioData['resultData']['files'][0]['FilePath'], $resultText, $message_id);

        exit;
    }

}

$messageTextLower = \modules\botservices\services\sPrompt::instance()->removeBotName($message_text, 'new_wallets');
if (stripos($messageTextLower, '/new_wallets') !== false && !empty($BotSettings['enableWallets'])) {
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

$messageTextLower = \modules\botservices\services\sPrompt::instance()->removeBotName($message_text, 'new_wallet');
if (stripos($messageTextLower, '/new_wallet') !== false && !empty($BotSettings['enableWallets'])) {
    $messageTextLower = str_replace('/new_wallet', '', $messageTextLower);
    $messageTextLower = trim($messageTextLower);

    $countWallet = 1;
    if(!empty($messageTextLower)){
        if((int)$messageTextLower>1){
            $countWallet = (int)$messageTextLower;
        }
    }

    $waitMessageData = sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $BotSettings['waitMessage'], '', $message_id); // show waitMessage

    $WalletsData = \modules\crypto\services\sCrypto::instance()->createSeedWallet($countWallet);

    $ListWallets = '<b>Mnemonic Seed Phrases:</b> '.$WalletsData['seed'].''.PHP_EOL.PHP_EOL;

    $ListWallets .= '<b>Ethereum Accounts ('.$countWallet.'): </b>'.PHP_EOL.PHP_EOL;

    foreach ($WalletsData['accounts'] as $accountKey => $accountData){
        $ListWallets .= '<b>Account '.($accountKey+1).'</b>'.PHP_EOL;
        $ListWallets .= '<b>Address:</b> '.$accountData['address'].PHP_EOL;
        $ListWallets .= '<b>PrivateKey:</b> '.$accountData['privateKey'].PHP_EOL.PHP_EOL;
    }

    sTelegram::instance()->removeMessage($bot_token, $message_chat_id,  $waitMessageData['MessageId']); // remove waitMessage
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $ListWallets, '', $message_id);
    exit;
}

$messageTextLower = \modules\botservices\services\sPrompt::instance()->removeBotName($message_text, 'new_seed');
if (stripos($messageTextLower, '/new_seed') !== false && !empty($BotSettings['enableWallets'])) {
    $messageTextLower = str_replace('/new_seed', '', $messageTextLower);
    $messageTextLower = trim($messageTextLower);

    $countSeed = 1;
    if(!empty($messageTextLower)){
        if((int)$messageTextLower>1){
            $countSeed = (int)$messageTextLower;
        }
    }

    $waitMessageData = sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $BotSettings['waitMessage'], '', $message_id); // show waitMessage

    $ListSeed = '<b>Mnemonic Seed Phrases ('.$countSeed.'): </b>'.PHP_EOL.PHP_EOL;
    for ($i = 1; $i <= $countSeed; $i++) {
        $SeedData = \modules\crypto\services\sCrypto::instance()->generateSeedPhrase();
        $ListSeed .= '<b>'.$i.':</b> '.$SeedData['seed'].PHP_EOL;
    }

    sTelegram::instance()->removeMessage($bot_token, $message_chat_id,  $waitMessageData['MessageId']); // remove waitMessage
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $ListSeed, '', $message_id);
    exit;
}

$messageTextLower = \modules\botservices\services\sPrompt::instance()->removeBotName($message_text, 'token');
if (stripos($messageTextLower, '/token') !== false && !empty($BotSettings['enableWallets'])) {
    $messageTextLower = str_replace('/token', '', $messageTextLower);
    $messageTextLower = trim($messageTextLower);

    $countSeed = 1;
    if(!empty($messageTextLower)){
        if((int)$messageTextLower>1){
            $countSeed = (int)$messageTextLower;
        }
    }

    $waitMessageData = sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $BotSettings['waitMessage'], '', $message_id); // show waitMessage

    $ListSeed = '<b>Mnemonic Seed Phrases ('.$countSeed.'): </b>'.PHP_EOL.PHP_EOL;
    for ($i = 1; $i <= $countSeed; $i++) {
        $SeedData = \modules\crypto\services\sCrypto::instance()->generateSeedPhrase();
        $ListSeed .= '<b>'.$i.':</b> '.$SeedData['seed'].PHP_EOL;
    }

    sTelegram::instance()->removeMessage($bot_token, $message_chat_id,  $waitMessageData['MessageId']); // remove waitMessage
    sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $ListSeed, '', $message_id);
    exit;
}

// Если не предусмотрен ответ
//sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Ответ не предусмотрен', '', $message_id);
exit;

