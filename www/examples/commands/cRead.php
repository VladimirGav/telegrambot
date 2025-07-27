<?php

// AI speech
$aCmmand = 'read';
$NoCmmandsChatIdArr = $BotSettings['SpeechNoCmmandsChatIdArr'];
$aEnable = $BotSettings['enableAiSpeech'];
$tgData['messageTextLower'] = \modules\botservices\services\sPrompt::instance()->removeBotName($tgData['message_text'], $aCmmand);
if (
    (stripos($tgData['messageTextLower'], '/'.$aCmmand) !== false || (!empty($NoCmmandsChatIdArr) && in_array($tgData['message_chat_id'].'_'.$tgData['message_thread_id'], $NoCmmandsChatIdArr)))
    &&
    !empty($aEnable)
) {

    if(empty($BotSettings['enableGPU'])){
        \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $BotSettings['textGPU'], '', $tgData['message_id']);
        exit;
    }

    $tgData['messageTextLower'] = \modules\botservices\services\sPrompt::instance()->removeCommand($tgData['messageTextLower']);

    // Подключаем нейросеть StableDiffusion
    $sAiSpeech = new \modules\aivg\services\sAiSpeech();
    $sAiSpeech->pathAiSpeech = $BotSettings['pathAiSpeech'];

    $exampleText = '';
    $exampleText .= '/read Example Text. Hello, welcome to Image Club'.PHP_EOL;
    //$exampleText .= 'prompt: Hello, welcome to Image Club.'.PHP_EOL;

    $AllowedModelsArr=$BotSettings['speechAllowedModelsArr'];

    $dir = $tgData['DIR'].'/uploads/speech';
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
    // Если подпись, то будет как текст
    if(!empty($dataCallback['callback_query']['message']['reply_to_message']['caption'])){
        $message_text_prompt = $dataCallback['callback_query']['message']['reply_to_message']['caption'];
    }

    // Текст с ключами в массив с данными
    $PromptDataByMessage = \modules\botservices\services\sPrompt::instance()->getPromptDataByMessage($message_text_prompt, 'prompt', ['language', 'speaker', 'prompt']);
    $promptData=$PromptDataByMessage['promptData'];

    /*if(empty($promptData['prompt'])){
        $promptData['prompt'] = strip_tags($tgData['messageTextLower']);
        $promptData['prompt'] = str_replace(["\r", "\n"], ' ', $promptData['prompt']);
        print_r($promptData);
    }*/

    // Если пустой, отправляем пример
    if(empty($promptData['prompt'])){
        \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $exampleText.'4444444', '', $tgData['message_id']);
        exit;
    }

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
                //['select_value' => 'de', 'select_name' => 'German'],
                //['select_value' => 'es', 'select_name' => 'Spanish'],
                //['select_value' => 'fr', 'select_name' => 'French'],
                //['select_value' => 'hi', 'select_name' => 'Hindi'],
                //['select_value' => 'it', 'select_name' => 'Italian'],
                //['select_value' => 'ja', 'select_name' => 'Japanese'],
                //['select_value' => 'ko', 'select_name' => 'Korean'],
                //['select_value' => 'pl', 'select_name' => 'Polish'],
               // ['select_value' => 'pt', 'select_name' => 'Portuguese'],
                ['select_value' => 'ru', 'select_name' => 'Russian'],
                //['select_value' => 'tr', 'select_name' => 'Turkish'],
                //['select_value' => 'zh', 'select_name' => 'Chinese, simplified'],
            ]
        ];
    }

    // get voice
    $chat_id_input = 0;
    $message_id_input = 0;
    if(!empty($dataCallback['callback_query']['message']['message_id'])){
        $chat_id_input = $dataCallback['callback_query']['message']['chat']['id'];
        $message_id_input = $dataCallback['callback_query']['message']['reply_to_message']['message_id'];
    } else {
        $chat_id_input = $tgData['message_chat_id'];
        $message_id_input = $tgData['message_id'];
    }
    $MessageRowInput = \modules\telegram\services\sTelegram::instance()->getMessage($chat_id_input,$message_id_input);
    //print_r($MessageRowInput);
    $audiosData=[];
    if(empty($MessageRowInput['error'])){
        $audiosData = \modules\telegram\services\sTelegram::instance()->getFilesByMessage($tgData['bot_token'], $MessageRowInput['dataMessage'], 'audio', ['message','reply_to_message'], $tgData['DIR'].'/../../backend/modules/aivg/services/temp/inputfiles/');

        echo '<pre>';
        //print_r($dataMessage);
        //print_r($tgData);
        print_r($audiosData);
        echo '</pre>';
    }

    $InteractiveKeysStr = '';
    if(!empty($dataCallback['callback_query']['data'])){
        $InteractiveKeysStr = explode(' ', $dataCallback['callback_query']['data'])[0];
    }
    $InteractiveResData = \modules\telegram\services\sInteractive::instance()->getInteractive('/read', $InteractiveArrData, $InteractiveKeysStr);

    if(!empty($InteractiveResData['error'])){
        print_r($InteractiveResData);
        exit;
    }

    // Choice 3
    if(empty($promptData['speaker']) && empty($audiosData['filesData'][0]['file'])){
        if(!empty($InteractiveResData['outDataArr']['arrKeysValues']['language']) && $InteractiveResData['outDataArr']['arrKeysValues']['language']=='ru'){
            /*$select_data = [];
            for ($i = 0; $i <= 5; $i++) {
                $select_data[] = ['select_value' => $i, 'select_name' => 'Speaker '.$i];
            }*/

            $InteractiveArrData['ElementsSelect'][] = [
                'columns' => 3,
                'select_value' => 'Value_Element_0',
                'select_name' => 'Оператор',
                'select_text' => 'Выберите оператора:',
                'select_key' => 'speaker',
                'select_data' => [
                    ['select_value' => 0, 'select_name' => 'Misha'],
                    ['select_value' => 1, 'select_name' => 'Vova'],
                    ['select_value' => 2, 'select_name' => 'Alice'],
                ]
            ];
        }
    }

    //$InteractiveArrData = \modules\telegram\services\sInteractive::instance()->getExampleInteractiveArrData('simple');
    $InteractiveKeysStr = '';
    if(!empty($dataCallback['callback_query']['data'])){
        $InteractiveKeysStr = explode(' ', $dataCallback['callback_query']['data'])[0];
    }
    $InteractiveResData = \modules\telegram\services\sInteractive::instance()->getInteractive('/read', $InteractiveArrData, $InteractiveKeysStr);

    /*echo '<pre>';
    print_r($InteractiveResData);
    echo '</pre>';*/

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
    if(!empty($promptData['language'])){
        foreach ($AllowedModelsArr as $AllowedModelKey => $AllowedModelRow){
            if(mb_strtolower($promptData['language']) == mb_strtolower($AllowedModelKey) || mb_strtolower($promptData['language']) == mb_strtolower($AllowedModelRow)){
                $model_id = mb_strtolower($AllowedModelRow);
            }
        }
    }

    $prompt = (!empty($promptData['prompt']))?$promptData['prompt']:'';



    $speechData=[];
    $speechData['from_id'] = $tgData['from_id'];
    $speechData['model_id']=$model_id;
    $speechData['prompt']=$prompt;
    $speechData['speaker']=(!empty($promptData['speaker']))?$promptData['speaker']:'';
    if(!empty($audiosData['filesData'][0]['file'])){
        $speechData['ref_audio']=$audiosData['filesData'][0]['file'];
    }

    $waitMessageData = \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $BotSettings['waitMessage'], '', $tgData['message_id']); // show waitMessage

    // Если генерация из текста

    // Если пустой, отправляем пример
    if(empty($prompt)){
        \modules\telegram\services\sTelegram::instance()->removeMessage($tgData['bot_token'], $tgData['message_chat_id'],  $waitMessageData['MessageId']); // remove waitMessage
        \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $exampleText, '', $tgData['message_id']);
        exit;
    }

    $AiSpeechData = $sAiSpeech->getTxt2Speech($speechData);

    \modules\telegram\services\sTelegram::instance()->removeMessage($tgData['bot_token'], $tgData['message_chat_id'],  $waitMessageData['MessageId']); // remove waitMessage

    if(!empty($AiSpeechData['error'])){
        exit(json_encode($AiSpeechData));
    }

    if(!empty($AiSpeechData['resultData']['files'][0]['FilePath'])){
        echo $AiSpeechData['resultData']['files'][0]['FilePath'];

        $resultText = '';
        //$resultText .= '/read'.PHP_EOL;
        //$resultText .= 'prompt: '.$AiSpeechData['resultData']['prompt'].PHP_EOL;
        //$resultText .= 'language: '.$AiSpeechData['resultData']['model_id'].PHP_EOL;

        $sendPhotoId = \modules\telegram\services\sTelegram::instance()->sendAudio($tgData['bot_token'], $tgData['message_chat_id'], $AiSpeechData['resultData']['files'][0]['FilePath'], $resultText, $tgData['message_id']);

        exit;
    }

}