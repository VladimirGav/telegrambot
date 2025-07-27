<?php
// Example of an interactive menu
$CommandName = '/menu';
$tgData['messageTextLower'] = \modules\botservices\services\sPrompt::instance()->removeBotName($tgData['message_text'], $CommandName);
if (stripos($tgData['messageTextLower'], $CommandName) !== false) {
    $tgData['messageTextLower'] = str_replace($CommandName, '', $tgData['messageTextLower']);
    $tgData['messageTextLower'] = trim($tgData['messageTextLower']);

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
            \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $InteractiveResData['outDataArr']['select_text'], $InteractiveResData['outDataArr']['reply_markup'], $tgData['message_id']);
        } else {
            \modules\telegram\services\sTelegram::instance()->editMessageTextTemp($tgData['bot_token'], $dataCallback['callback_query']['message']['chat']['id'], $dataCallback['callback_query']['message']['message_id'], $InteractiveResData['outDataArr']['select_text'], $InteractiveResData['outDataArr']['reply_markup'], $dataCallback['callback_query']['message']['reply_to_message']['message_id']);
            //\modules\telegram\services\sTelegram::instance()->editMessageText($tgData['bot_token'], $dataCallback['callback_query']['message']['chat']['id'], $dataCallback['callback_query']['message']['message_id'], $InteractiveResData['outDataArr']['select_text'], $InteractiveResData['outDataArr']['reply_markup']);
            //\modules\telegram\services\sTelegram::instance()->editMessageReplyMarkup($tgData['bot_token'], $dataCallback['callback_query']['message']['chat']['id'], $dataCallback['callback_query']['message']['message_id'], '', $InteractiveResData['outDataArr']['reply_markup']);
        }
        exit;
    } else {
        // isFinish
        // delete interactive message
        if(!empty($dataCallback['callback_query']['message']['message_id'])){
            \modules\telegram\services\sTelegram::instance()->removeMessage($tgData['bot_token'], $dataCallback['callback_query']['message']['chat']['id'],  $dataCallback['callback_query']['message']['message_id']); // remove
        }
    }
    // change the message_id to the original
    if(!empty($dataCallback['callback_query']['message']['reply_to_message']['message_id'])){
        $tgData['message_id'] = $dataCallback['callback_query']['message']['reply_to_message']['message_id'];
    }
    // interactive data $InteractiveResData['outDataArr']['arrKeysValues']
    \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], json_encode($InteractiveResData['outDataArr']['arrKeysValues'],JSON_PRETTY_PRINT), '', $tgData['message_id']);
    exit;
}