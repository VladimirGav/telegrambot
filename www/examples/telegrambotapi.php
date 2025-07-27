<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

// Устанавливаем и подключаем Composer
$DIR_ = __DIR__;
require_once $DIR_.'/../../backend/defines.php';

// Настройки по умолчанию, редактируйте в файле /telegrambot/backend/settings/bot_settings.json
$BotSettings=[
    'enableChatGPT' => 1, // 1 - включить ChatGPT команду /ai; 0 - выключить
    'enableOpenAiImg' => 1, // 1 - включить OpenAi Img команду /img; 0 - выключить

    'enableGPT' => 1, // 1 - включить ChatGPT команду /gpt; 0 - выключить
    'gptNoCmmandsChatIdArr' => ['000_0'], // Массив чатов_тем ['message_chat_id_message_thread_id', '123_0'] в которых эта комада выполняется каждого текста. Пустотой массив - только по команде
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
    'SdNoCmmandsChatIdArr' => ['000_0'], // Массив чатов_тем ['message_chat_id_message_thread_id', '123_0'] в которых эта комада выполняется каждого текста. Пустотой массив - только по команде
    'SdNsfwChatIdArr' => [], // Массив чатов где разрешено nsfw для StableDiffusion
    'pathStableDiffusion' => 'D:/stable-diffusion-vg', // Путь к корню StableDiffusion
    'StableDiffusionAllowedModelsArr' => [0=>'stabilityai/stable-diffusion-2-1', 'SD1.5' => 'runwayml/stable-diffusion-v1-5', 'DreamShaper' => 'Lykon/DreamShaper', 'NeverEnding-Dream' => 'Lykon/NeverEnding-Dream'], // Массив моделей для StableDiffusion которые будут работать с huggingface.co

    'enableNFT' => 1, // 1 Включить NFT

    'enableAiAudio' => 1, // 1 Включить генерацию речи из текста
    'pathAiAudio' => 'D:/ai-audio-vg', // Путь к корню text-to-audio-vg
    'audioAllowedModelsArr' => [0=>'suno/bark-small'], // Массив моделей для audio

    'enableAiSpeech' => 1, // 1 Включить генерацию речи из текста
    'SpeechNoCmmandsChatIdArr' => ['000_0'], // Массив чатов_тем ['message_chat_id_message_thread_id', '123_0'] в которых эта комада выполняется каждого текста. Пустотой массив - только по команде
    'pathAiSpeech' => 'D:/ai-vladimir-gav', // Путь к корню text-to aivg
    'speechAllowedModelsArr' => [0=>'en', 1=>'ru'], // Массив моделей для aivg

    'enableAiFake' => 1, // 1 Включить генерацию речи из текста
    'FakeNoCmmandsChatIdArr' => ['000_0'], // Массив чатов_тем ['message_chat_id_message_thread_id', '123_0'] в которых эта комада выполняется каждого текста. Пустотой массив - только по команде
    'pathAiFake' => 'D:/ai-vladimir-gav', // Путь к корню text-to aivg
    'fakeAllowedModelsArr' => [0=>'0'], // Массив моделей для aivg
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
//$BotData = \modules\telegram\services\sTelegram::instance()->getBotData($bot_token);

/*$checkApi = \modules\telegram\services\sTelegram::instance()->checkApi($bot_token);
if(!empty($checkApi['error'])){
    echo json_encode($checkApi);
    exit;
}*/

// Если запускаем через консоль, а не используем Telegram Webhook
if(!empty($_SERVER['argv'][1]) && $_SERVER['argv'][1]=='console'){
    $removeWebhook = \modules\telegram\services\sTelegram::instance()->removeWebhook($bot_token); // Удаляем привязку к Telegram Webhook
    if(!empty($removeWebhook['error'])){ exit(json_encode($removeWebhook)); }
    // TODO Может требуется очистка services\telegram-ids
    $dataMessage = \modules\telegram\services\sTelegram::instance()->getUpdatesLastMessage($bot_token);
} else {
    // TODO Надо протестировать callback
    $dataMessage = \modules\telegram\services\sTelegram::instance()->getWebhookLastMessage($bot_token);
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
    \modules\telegram\services\sTelegram::instance()->removeMessage($bot_token, $dataMessage['message']['chat']['id'],  $dataMessage['message']['message_id']);
    \modules\telegram\services\sTelegram::instance()->sendMessage($bot_token, $dataMessage['message']['chat']['id'],  'Привет '.$member_username.'! Добро пожаловать в группу!');
    exit;
}

// Если вышел участник, то удалим сообщение о выходе
if(!empty($dataMessage['message']['left_chat_member']['id']) && !empty($BotSettings['enableGoodbye'])){
    \modules\telegram\services\sTelegram::instance()->removeMessage($bot_token, $dataMessage['message']['chat']['id'],  $dataMessage['message']['message_id']);
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

$message_thread_id = 0;
if(!empty($dataMessage['message']['message_thread_id'])){
    $message_thread_id = $dataMessage['message']['message_thread_id'];
}

// К нижнему регистру
$messageTextLower = \modules\botservices\services\sPrompt::instance()->getMessageTextLower($message_text);

$tgData = [];
$tgData['DIR'] = __DIR__;
$tgData['from_id'] = $from_id;
$tgData['message_id'] = $message_id;
$tgData['message_text'] = $message_text;
$tgData['messageTextLower'] = $messageTextLower;
$tgData['message_chat_id'] = $message_chat_id;
$tgData['message_thread_id'] = $message_thread_id;
$tgData['reply_to_message_text'] = $reply_to_message_text;
$tgData['bot_token'] = $bot_token;

// Если указан массив чатов для работы, если супер юзер то игнорируем
if(!empty($BotSettings['AllowedChatIdArr']) && !in_array($tgData['message_chat_id'], $BotSettings['AllowedChatIdArr']) && !in_array($tgData['from_id'], $BotSettings['superUsersIds'])){
    //\modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'Доступ к боту запрещен, используйте бот в другом чате.', '', $tgData['message_id']);
    exit;
}

// Если ссылки запрещены, то удлаляем сообщение
if(!empty($BotSettings['enableLinkBlocking'])){
    $AllowedMessages = \modules\telegram\services\sTelegram::instance()->checkAllowedMessages($dataMessage, ['mention', 'url'], $BotSettings['superUsersIds']);
    if(!empty($AllowedMessages['error'])){
        \modules\telegram\services\sTelegram::instance()->removeMessage($tgData['bot_token'], $tgData['message_chat_id'],  $tgData['message_id']);
        $member_username='<a href="tg://user?id='.$tgData['from_id'].'">'.$dataMessage['message']['from']['first_name'].'</a>';
        \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'],  $member_username.', размещение ссылок запрещено.', '');
    }
}

// Если узнаем id пользователя
$tgData['messageTextLower'] = \modules\botservices\services\sPrompt::instance()->removeBotName($tgData['messageTextLower'], 'user_id');
if($tgData['messageTextLower']=='/user_id'){
    \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'User_id: '.$tgData['from_id'], '', $tgData['message_id']);
    exit;
}

// Если узнаем $chat_id
$tgData['messageTextLower'] = preg_replace('/(.*)(\/chat_id@[^ ]*)(.*)/', '/chat_id $1$3', $tgData['messageTextLower']); // Удаляем имя бота, например заменяеам /ai@Name_bot на /ai
if($tgData['messageTextLower']=='/chat_id'){
    \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'chat_id: '.$tgData['message_chat_id'], '', $tgData['message_id']);
    exit;
}

// Если узнаем $message_thread_id
$tgData['messageTextLower'] = preg_replace('/(.*)(\/thread_id@[^ ]*)(.*)/', '/thread_id $1$3', $tgData['messageTextLower']); // Удаляем имя бота, например заменяеам /ai@Name_bot на /ai
if($tgData['messageTextLower']=='/thread_id'){
    \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'thread_id: '.$tgData['message_thread_id'], '', $tgData['message_id']);
    exit;
}

// Если первое сообщение
$tgData['messageTextLower'] = preg_replace('/(.*)(\/start@[^ ]*)(.*)/', '/start $1$3', $tgData['messageTextLower']); // Удаляем имя бота, например заменяеам /ai@Name_bot на /ai
if($tgData['messageTextLower']=='/start'){
    \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'Привет, я бот', '', $tgData['message_id']);
    exit;
}

// Если пользователь напишет Тест, то выведем ответ
if($tgData['messageTextLower']=='тест'){
    \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'Ответ от бота на сообщение тест. <b>Вы можете предусмотреть свои ответы на любые сообщения в формате HTML.</b>', '');
    exit;
}

// Если пользователь напишет привет
if($tgData['messageTextLower']=='привет'){
    \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'Привет', '', $tgData['message_id']);
    exit;
}

// пример ответа
if($tgData['messageTextLower']=='пример ответа'){
    \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'Ответ на сообщение', '', $tgData['message_id']);
    exit;
}

if($tgData['messageTextLower']=='chat_id'){
    \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'chat_id: '.$tgData['message_chat_id'], '', $tgData['message_id']);
    exit;
}

// Пример отправки аудио файла
if($tgData['messageTextLower']=='мелодия'){
    \modules\telegram\services\sTelegram::instance()->sendAudio($tgData['bot_token'], $tgData['message_chat_id'], $tgData['DIR'].'/audio.mp3', '', $tgData['message_id']);
    exit;
}

// пример кнопки
if($tgData['messageTextLower']=='пример кнопки'){
    $inline_keyboard=[];
    $inline_keyboard[][] = ["text"=>'telegram кнопка', "url"=>'https://telegram.org/'];
    $keyboard=["inline_keyboard"=>$inline_keyboard];
    $reply_markup = json_encode($keyboard);
    \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'Сообщение с кнопкой', $reply_markup);
    exit;
}

// Загрузка остальных команд
$commandsFiles = new DirectoryIterator($DIR_.'/commands');
foreach ($commandsFiles as $commandsFile) {
    if ($commandsFile->isFile()) { // Проверяем, что текущий элемент является файлом
        $commandsFilePath = $commandsFile->getPathname(); // Получаем полный путь к файлу
        $commandsFileExtension = pathinfo($commandsFilePath, PATHINFO_EXTENSION); // Получаем расширение файла

        if (strtolower($commandsFileExtension) === 'php') { // Проверяем, что файл имеет расширение PHP
            require_once($commandsFilePath); // Подключаем файл
        }
    }
}

// Если не предусмотрен ответ
//\modules\telegram\services\sTelegram::instance()->sendMessage($bot_token, $message_chat_id, 'Ответ не предусмотрен', '', $message_id);
exit;

