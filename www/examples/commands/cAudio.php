<?php

// AI Audio
$tgData['messageTextLower'] = \modules\botservices\services\sPrompt::instance()->removeBotName($tgData['message_text'], 'audio');
if (stripos($tgData['messageTextLower'], '/audio') !== false && !empty($BotSettings['enableAiAudio'])) {

    if(empty($BotSettings['enableGPU'])){
        \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $BotSettings['textGPU'], '', $tgData['message_id']);
        exit;
    }

    $tgData['messageTextLower'] = \modules\botservices\services\sPrompt::instance()->removeCommand($tgData['messageTextLower']);

    // Подключаем нейросеть StableDiffusion
    $sAiAudio = new \modules\aiaudio\services\sAiAudio();
    $sAiAudio->pathAiAudio = $BotSettings['pathAiAudio'];

    $exampleText = '';
    $exampleText .= '/audio Example Text. Hello, welcome to Image Club'.PHP_EOL;
    //$exampleText .= 'prompt: Hello, welcome to Image Club.'.PHP_EOL;
    //$exampleText .= 'voice_preset: v2/en_speaker_0'.PHP_EOL;

    $AllowedModelsArr=$BotSettings['audioAllowedModelsArr'];

    $dir = $tgData['DIR'].'/uploads/audio';
    if(!file_exists($dir)){
        if (!mkdir($dir, 0777, true)) {
            die('Не удалось создать директории...');
        }
    }

    // Interactive Bot

    $message_text_prompt = $tgData['message_text'];

    // Если это интерактивный, то берем текст из предыдущего сообщения
    if(!empty($dataCallback['callback_query']['message']['reply_to_message']['text'])){
        $message_text_prompt = $dataCallback['callback_query']['message']['reply_to_message']['text'];
    }

    // Текст с ключами в массив с данными
    $PromptDataByMessage = \modules\botservices\services\sPrompt::instance()->getPromptDataByMessage($message_text_prompt, 'prompt', ['voice_preset','prompt']);
    $promptData=$PromptDataByMessage['promptData'];

    // Если пустой, отправляем пример
    if(empty($promptData['prompt'])){
        \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $exampleText, '', $tgData['message_id']);
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
    $audioData['from_id'] = $tgData['from_id'];
    $audioData['model_id']=$model_id;
    $audioData['prompt']=$prompt;
    $audioData['voice_preset']=$voice_preset;

    $waitMessageData = \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $BotSettings['waitMessage'], '', $tgData['message_id']); // show waitMessage

    // Если генерация из текста

    // Если пустой, отправляем пример
    if(empty($prompt)){
        \modules\telegram\services\sTelegram::instance()->removeMessage($tgData['bot_token'], $tgData['message_chat_id'],  $waitMessageData['MessageId']); // remove waitMessage
        \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $exampleText, '', $tgData['message_id']);
        exit;
    }

    $AiAudioData = $sAiAudio->getTxt2Audio($audioData);

    \modules\telegram\services\sTelegram::instance()->removeMessage($tgData['bot_token'], $tgData['message_chat_id'],  $waitMessageData['MessageId']); // remove waitMessage

    if(!empty($AiAudioData['error'])){
        exit(json_encode($AiAudioData));
    }

    if(!empty($AiAudioData['resultData']['files'][0]['FilePath'])){

        $resultText = '';
        $resultText .= '/audio'.PHP_EOL;
        $resultText .= 'prompt: '.$AiAudioData['resultData']['prompt'].PHP_EOL;
        $resultText .= 'voice_preset: '.$AiAudioData['resultData']['voice_preset'].PHP_EOL;

        $sendPhotoId = \modules\telegram\services\sTelegram::instance()->sendAudio($tgData['bot_token'], $tgData['message_chat_id'], $AiAudioData['resultData']['files'][0]['FilePath'], $resultText, $tgData['message_id']);

        exit;
    }

}