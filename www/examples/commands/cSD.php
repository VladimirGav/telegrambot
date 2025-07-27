<?php

$tgData['messageTextLower'] = preg_replace('/(.*)(\/sd_models@[^ ]*)(.*)/', '/sd_models $1$3', $tgData['messageTextLower']); // Удаляем имя бота, например заменяеам /ai@Name_bot на /ai
$pos2 = stripos($tgData['messageTextLower'], '/sd_models');
if ($pos2 !== false && !empty($BotSettings['enableStableDiffusion'])) {
    \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'Allowed Models:'.PHP_EOL.implode(PHP_EOL, $BotSettings['StableDiffusionAllowedModelsArr']), '', $tgData['message_id']);
    exit;
}

// StableDiffusion Рисует картинку по запросу
$aCmmand = 'sd';
$NoCmmandsChatIdArr = $BotSettings['SdNoCmmandsChatIdArr'];
$aEnable = $BotSettings['enableStableDiffusion'];
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

    $dir = $tgData['DIR'].'/uploads/images';
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

    $message_text_prompt = $tgData['message_text'];

    // Если это интерактивный, то берем текст из предыдущего сообщения
    if(!empty($dataCallback['callback_query']['message']['reply_to_message']['text'])){
        $message_text_prompt = $dataCallback['callback_query']['message']['reply_to_message']['text'];
    }

    // Текст с ключами в массив с данными
    $PromptDataByMessage = \modules\botservices\services\sPrompt::instance()->getPromptDataByMessage($message_text_prompt, 'prompt', ['model_id','img_width','img_height','img_num_inference_steps','img_guidance_scale', 'sampler', 'tags','prompt','negative_prompt','nft']);
    $promptData=$PromptDataByMessage['promptData'];

    if(!empty($promptData['prompt'])){
        $promptData['prompt'] = strip_tags($promptData['prompt']);
        if(!\modules\botservices\services\sPrompt::instance()->isEnglishText($promptData['prompt'])){
            \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'Use only English', '', $tgData['message_id']);
            exit;
        }
        $promptData['prompt'] = preg_replace("/[^A-Za-z0-9_ ->]/", '', $promptData['prompt']); //Supports English only
    }

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
    if(!empty($BotSettings['SdNsfwChatIdArr']) && in_array($tgData['message_chat_id'], $BotSettings['SdNsfwChatIdArr'])){
        $nsfw = true;
    }

    $sdData=[];
    $sdData['from_id'] = $tgData['from_id'];
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

    $waitMessageData = \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $BotSettings['waitMessage'], '', $tgData['message_id']); // show waitMessage
    if(!empty($dataMessage['message']['photo'])){
        // Если генерация из изображения

        $file_id=$dataMessage['message']['photo'][array_key_last($dataMessage['message']['photo'])]['file_id'];
        $saveFileData = \modules\telegram\services\sTelegram::instance()->saveFile($tgData['bot_token'], $file_id, $tgData['DIR'].'/uploads/received_files');
        if(!empty($saveFileData['error'])){
            \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'saveFile error', '', $tgData['message_id']);
            exit;
        }
        $sdData['img_original']=$saveFileData['file'];
        $ImgData = $sStableDiffusion->getImg2Img($sdData);
    } else {
        // Если генерация из текста

        // Если пустой, отправляем пример
        if(empty($prompt)){
            \modules\telegram\services\sTelegram::instance()->removeMessage($tgData['bot_token'], $tgData['message_chat_id'],  $waitMessageData['MessageId']); // remove waitMessage
            \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $exampleText, '', $tgData['message_id']);
            exit;
        }

        $ImgData = $sStableDiffusion->getTxt2Img($sdData);
    }
    \modules\telegram\services\sTelegram::instance()->removeMessage($tgData['bot_token'], $tgData['message_chat_id'],  $waitMessageData['MessageId']); // remove waitMessage


    if(!empty($ImgData['error'])){
        exit(json_encode($ImgData));
    }

    if(!empty($ImgData['resultData']['imgs'][0]['FilePath'])){

        // If the picture is black
        if(filesize($ImgData['resultData']['imgs'][0]['FilePath']) < 3000){
            \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], 'Потенциальное содержимое NSFW. Смотрите описание группы.', '', $tgData['message_id']);
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

        $sendPhotoId = \modules\telegram\services\sTelegram::instance()->sendPhoto($tgData['bot_token'], $tgData['message_chat_id'], $ImgData['resultData']['imgs'][0]['FilePath'], $resultText, $tgData['message_id']);

        // create NFT
        if(file_exists($tgData['DIR'].'/../../backend/modules/nft/services/sNFT.php') && !empty($sendPhotoId) && !empty($BotSettings['enableNFT']) && !empty($BotSettings['enableNFT']) && !empty($promptData['nft']) && mb_strtolower($promptData['nft'])=='true' && $nsfw == false){
            \modules\nft\services\sNFT::instance()->addDataNFT(['ImgData' => $ImgData, 'MessageId' => $sendPhotoId['MessageId'], 'message_chat_id' => $tgData['message_chat_id'], 'message_id' => $tgData['message_id'], 'from_id' => $tgData['from_id']]);
        }

        exit;
    }

}