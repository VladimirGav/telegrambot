<?php
// Пример chatGPT
$tgData['messageTextLower'] = preg_replace('/(.*)(\/ai@[^ ]*)(.*)/', '/ai $1$3', $tgData['messageTextLower']); // Удаляем имя бота, например заменяеам /ai@Name_bot на /ai
$pos2 = stripos($tgData['messageTextLower'], '/ai'); // Проверка /ai в тексте сообщения
$pos3 = stripos($tgData['reply_to_message_text'], '#ai_'); // Проверка в #ai_ при разговоре
if (($pos2 !== false || $pos3 !== false) && !empty($BotSettings['enableChatGPT'])) {
    $tgData['messageTextLower'] = str_replace('/ai', '', $tgData['messageTextLower']);

    // Если пустой, отправляем пример
    if(empty($tgData['messageTextLower'])){
        \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'Example: /ai Ты можешь отвечать на вопросы?', '', $tgData['message_id']);
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

    // Получим id истории сообщений, если пользователь отвечает боту.
    $HistoryArr = explode('#ai_',$tgData['reply_to_message_text']);
    $historyMessagesId=0;
    if(!empty($HistoryArr[1])){
        $historyMessagesId = (int)(mb_substr($HistoryArr[1], 0, 10));
    }

    $model_id = 'gpt-3.5-turbo';
    $ChatGPTAnswerData = \modules\openai\services\sOpenAI::instance()->getChatGPTAnswer($api_gpt, $tgData['messageTextLower'], $historyMessagesId, $model_id);
    if(!empty($ChatGPTAnswerData['error'])){
        exit(json_encode($ChatGPTAnswerData));
    }

    if(!empty($ChatGPTAnswerData['answer'])){
        \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $ChatGPTAnswerData['answer'].' #ai_'.$ChatGPTAnswerData['historyMessagesId'], '', $tgData['message_id']);
    }

    \modules\telegram\services\sTelegram::instance()->removeMessage($tgData['bot_token'], $tgData['message_chat_id'],  $waitMessageData['MessageId']); // remove waitMessage
    exit;
}