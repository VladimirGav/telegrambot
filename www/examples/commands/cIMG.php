<?php
// АИ Рисуем картинку по запросу
$tgData['messageTextLower'] = preg_replace('/(.*)(\/img@[^ ]*)(.*)/', '/img $1$3', $tgData['messageTextLower']); // Удаляем имя бота, например заменяеам /ai@Name_bot на /ai
$pos2 = stripos($tgData['messageTextLower'], '/img');

if ($pos2 !== false && !empty($BotSettings['enableOpenAiImg'])) {
    $tgData['messageTextLower'] = str_replace('/img', '', $tgData['messageTextLower']);

    $dir = $tgData['DIR'].'/uploads/images';
    if(!file_exists($dir)){
        if (!mkdir($dir, 0777, true)) {
            die('Не удалось создать директории...');
        }
    }

    // Если пустой, отправляем пример
    if(empty($tgData['messageTextLower'])){
        \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'Example: /img Рыжая лиса в лесу', '', $tgData['message_id']);
        exit;
    }

    // Получим токен бота из файла
    if(!file_exists(_FILE_api_gpt_)){
        \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'OpenAI API KEY is empty', '', $tgData['message_id']);
        exit;
    }
    $api_gpt = trim(file_get_contents(_FILE_api_gpt_));
    if(empty($api_gpt)){
        \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'OpenAI API KEY is empty', '', $tgData['message_id']);
        exit;
    }

    $waitMessageData = \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $BotSettings['waitMessage'], '', $tgData['message_id']); // show waitMessage

    // gpt-3.5-turbo
    $ImgData = \modules\openai\services\sOpenAI::instance()->getImg($api_gpt, $tgData['messageTextLower'], '256x256');
    if(!empty($ImgData['error'])){
        exit(json_encode($ImgData));
    }

    if(!empty($ImgData['url'])){
        // save img
        $fileName = $dir.'/'.time().'.png';
        file_put_contents($fileName, file_get_contents($ImgData['url']));

        $sendPhotoId = \modules\telegram\services\sTelegram::instance()->sendPhoto($tgData['bot_token'], $tgData['message_chat_id'], $fileName, $tgData['messageTextLower'], $tgData['message_id']);
    }

    \modules\telegram\services\sTelegram::instance()->removeMessage($tgData['bot_token'], $tgData['message_chat_id'],  $waitMessageData['MessageId']); // remove waitMessage
    exit;
}