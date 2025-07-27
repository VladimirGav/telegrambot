<?php

// AI Fake
$aCmmand = 'fake';
$NoCmmandsChatIdArr = $BotSettings['FakeNoCmmandsChatIdArr'];
$aEnable = $BotSettings['enableAiFake'];
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
    $sAiFaceTalking = new \modules\aivg\services\sAiFaceTalking();
    $sAiFaceTalking->pathAiFaceTalking = $BotSettings['pathAiFake'];

    $exampleText = '';
    $exampleText .= '/fake Example Text.'.PHP_EOL;
    //$exampleText .= 'prompt: Hello, welcome to Image Club.'.PHP_EOL;

    $AllowedModelsArr=$BotSettings['fakeAllowedModelsArr'];

    $dir = $tgData['DIR'].'/uploads/fake';
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
    $PromptDataByMessage = \modules\botservices\services\sPrompt::instance()->getPromptDataByMessage($message_text_prompt, 'prompt', ['typesize', 'size', 'prompt']);
    $promptData=$PromptDataByMessage['promptData'];

    /*if(empty($promptData['prompt'])){
        $promptData['prompt'] = strip_tags($tgData['messageTextLower']);
        $promptData['prompt'] = str_replace(["\r", "\n"], ' ', $promptData['prompt']);
        print_r($promptData);
    }*/

    // Если пустой, отправляем пример
    /*if(empty($promptData['prompt'])){
        \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $exampleText, '', $tgData['message_id']);
        exit;
    }*/

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
    $imgData=[];
    if(empty($MessageRowInput['error'])){
        $audiosData = \modules\telegram\services\sTelegram::instance()->getFilesByMessage($tgData['bot_token'], $MessageRowInput['dataMessage'], 'audio', ['message','reply_to_message'], $tgData['DIR'].'/../../backend/modules/aivg/services/temp/inputfiles/');
        if($audiosData['error']==2){
            \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $audiosData['data'], '', $tgData['message_id']);
            exit;
        }
        if(empty($audiosData['filesData'][0]['file'])){
            \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'Добавьте аудио', '', $tgData['message_id']);
            exit;
        }
        $imgData = \modules\telegram\services\sTelegram::instance()->getFilesByMessage($tgData['bot_token'], $MessageRowInput['dataMessage'], 'image', ['message','reply_to_message'], $tgData['DIR'].'/../../backend/modules/aivg/services/temp/inputfiles/');
        if($imgData['error']==2){
            \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $imgData['data'], '', $tgData['message_id']);
            exit;
        }
        if(empty($imgData['filesData'][0]['file'])){
            \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'Добавьте фото', '', $tgData['message_id']);
            exit;
        }
    }

    $InteractiveArrData['TypeSelect'] = 'simple';

    // Choice 2
    //if(empty($promptData['type'])){

        $select_data = [];
        foreach ($AllowedModelsArr as $AllowedModelKey => $AllowedModelRow){
            $select_data[] = ['select_value' => $AllowedModelKey, 'select_name' => $AllowedModelKey];
        }

        $InteractiveArrData['ElementsSelect'][] = [
            'columns' => 3,
            'select_value' => 'Value_Element_0',
            'select_name' => 'Type',
            'select_text' => 'Type:',
            'select_key' => 'typesize',
            'select_data' => [
                ['select_value' => 'full', 'select_name' => 'full'],
                ['select_value' => 'crop', 'select_name' => 'crop'],
                ['select_value' => 'extcrop', 'select_name' => 'extcrop'],
                ['select_value' => 'resize', 'select_name' => 'resize'],
                ['select_value' => 'extfull', 'select_name' => 'extfull'],
            ]
        ];
    //}

    // Choice 3
    //if(empty($promptData['size'])){
        // Choice 3
        $InteractiveArrData['ElementsSelect'][] = [
            'columns' => 3,
            'select_value' => 'Value_Element_0',
            'select_name' => 'Size',
            'select_text' => 'Size:',
            'select_key' => 'size',
            'select_data' => [
                ['select_value' => 256, 'select_name' => '256'],
                ['select_value' => 512, 'select_name' => '512'],
            ]
        ];
    //}

    //$InteractiveArrData = \modules\telegram\services\sInteractive::instance()->getExampleInteractiveArrData('simple');
    $InteractiveKeysStr = '';
    if(!empty($dataCallback['callback_query']['data'])){
        $InteractiveKeysStr = explode(' ', $dataCallback['callback_query']['data'])[0];
    }
    $InteractiveResData = \modules\telegram\services\sInteractive::instance()->getInteractive('/fake', $InteractiveArrData, $InteractiveKeysStr);

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

    // promptData add interactive data
    if(!empty($InteractiveResData['outDataArr']['arrKeysValues'])){
        $promptData = array_merge($promptData, $InteractiveResData['outDataArr']['arrKeysValues']);
    }

    // Делаем проверки по параметрам

    $model_id = '';
    // Если это требуемая модель, то применяем
    /*$model_id = $AllowedModelsArr[0];
    if(!empty($promptData['type'])){
        foreach ($AllowedModelsArr as $AllowedModelKey => $AllowedModelRow){
            if(mb_strtolower($promptData['type']) == mb_strtolower($AllowedModelKey) || mb_strtolower($promptData['type']) == mb_strtolower($AllowedModelRow)){
                $model_id = mb_strtolower($AllowedModelRow);
            }
        }
    }*/

    $prompt = (!empty($promptData['prompt']))?$promptData['prompt']:'';

    /*echo '<pre>';
    print_r($promptData);
    echo '</pre>';*/

    $FakeData=[];
    $FakeData['from_id'] = $tgData['from_id'];
    $FakeData['model_id']=$model_id;
    $FakeData['prompt']=$prompt;
    $FakeData['typesize']=$promptData['typesize'];
    $FakeData['size']=$promptData['size'];
    $FakeData['path_audio']=$audiosData['filesData'][0]['file'];
    $FakeData['path_img']=$imgData['filesData'][0]['file'];

    $waitMessageData = \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $BotSettings['waitMessage'], '', $tgData['message_id']); // show waitMessage

    // Если генерация из текста

    // Если пустой, отправляем пример
    /*if(empty($prompt)){
        \modules\telegram\services\sTelegram::instance()->removeMessage($tgData['bot_token'], $tgData['message_chat_id'],  $waitMessageData['MessageId']); // remove waitMessage
        \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $exampleText, '', $tgData['message_id']);
        exit;
    }*/

    $AiFaceTalkingData = $sAiFaceTalking->getFaceTalking($FakeData);

    \modules\telegram\services\sTelegram::instance()->removeMessage($tgData['bot_token'], $tgData['message_chat_id'],  $waitMessageData['MessageId']); // remove waitMessage

    if(!empty($AiFaceTalkingData['error'])){
        exit(json_encode($AiFaceTalkingData));
    }

    if(!empty($AiFaceTalkingData['resultData']['files'][0]['FilePath'])){
        echo $AiFaceTalkingData['resultData']['files'][0]['FilePath'];

        $resultText = '';
        //$resultText .= '/fake'.PHP_EOL;
        //$resultText .= 'prompt: '.$AiFaceTalkingData['resultData']['prompt'].PHP_EOL;
        //$resultText .= 'type: '.$AiFaceTalkingData['resultData']['model_id'].PHP_EOL;

        $sendPhotoId = \modules\telegram\services\sTelegram::instance()->sendVideo($tgData['bot_token'], $tgData['message_chat_id'], $AiFaceTalkingData['resultData']['files'][0]['FilePath'], $resultText, $tgData['message_id']);

        exit;
    }

}