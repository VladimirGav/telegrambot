<?php
// AI Gpt
$tgData['messageTextLower'] = \modules\botservices\services\sPrompt::instance()->removeBotName($tgData['message_text'], 'gpt');
$pos3 = stripos($tgData['reply_to_message_text'], '#gpt_'); // Проверка в #gpt_ при разговоре
if ((stripos($tgData['messageTextLower'], '/gpt') !== false || $pos3 !== false ) && !empty($BotSettings['enableGPT'])) {
    $tgData['messageTextLower'] = \modules\botservices\services\sPrompt::instance()->removeCommand($tgData['messageTextLower']);

    $exampleText = '';
    $exampleText .= '/gpt your question?'.PHP_EOL;
    //$exampleText .= 'prompt: Hello.'.PHP_EOL;
    //$exampleText .= 'model_id: gpt-4'.PHP_EOL;

    $AllowedModelsArr=$BotSettings['gptAllowedModelsArr'];

    /*$dir = $tgData['DIR'].'/uploads/gpt';
    if(!file_exists($dir)){
        if (!mkdir($dir, 0777, true)) {
            die('Не удалось создать директории...');
        }
    }*/

    // Interactive Bot

    $message_text_prompt = $tgData['message_text'];

    // Если это интерактивный, то берем текст из предыдущего сообщения
    if(!empty($dataCallback['callback_query']['message']['reply_to_message']['text'])){
        $message_text_prompt = $dataCallback['callback_query']['message']['reply_to_message']['text'];
    }

    // Текст с ключами в массив с данными
    $PromptDataByMessage = \modules\botservices\services\sPrompt::instance()->getPromptDataByMessage($message_text_prompt, 'prompt', ['model_id','prompt']);
    $promptData=$PromptDataByMessage['promptData'];

    // Если пустой, отправляем пример
    if(empty($promptData['prompt'])){
        \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $exampleText, '', $tgData['message_id']);
        exit;
    }

    // Получим id истории сообщений, если пользователь отвечает боту.
    $HistoryArr = explode('#gpt_',$tgData['reply_to_message_text']);
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
                \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $InteractiveResData['outDataArr']['select_text'], $InteractiveResData['outDataArr']['reply_markup'], $tgData['message_id']);
            } else {
                \modules\telegram\services\sTelegram::instance()->editMessageText($tgData['bot_token'], $dataCallback['callback_query']['message']['chat']['id'], $dataCallback['callback_query']['message']['message_id'], $InteractiveResData['outDataArr']['select_text'], $InteractiveResData['outDataArr']['reply_markup']);
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
        // change the message_id to the original
        /*if(!empty($dataCallback['callback_query']['message']['message_thread_id'])){
            $tgData['message_id'] = $dataCallback['callback_query']['message']['message_thread_id'];
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
    /*$HistoryArr = explode('#gpt_',$tgData['reply_to_message_text']);
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
        \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $ChatGPTAnswerData['answer'].' #gpt_'.$ChatGPTAnswerData['historyMessagesId'], '', $tgData['message_id']);
    }

    \modules\telegram\services\sTelegram::instance()->removeMessage($tgData['bot_token'], $tgData['message_chat_id'],  $waitMessageData['MessageId']); // remove waitMessage
    exit;
}