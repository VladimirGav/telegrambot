<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

namespace modules\aivg\services;

class sAiFaceTalking
{

    /**
     * @var
     */
    protected static $instance;

    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public $pathAiFaceTalking = 'D:/ai-vladimir-gav';

    public function getFaceTalking($fakeData){
        $fakeData['type']='FaceTalking';
        $AiFaceTalkingData = $this->getAiFaceTalking($fakeData);
        return $AiFaceTalkingData;
    }

    public function getAiFaceTalking($fakeData){
        // Получаем данные
        $from_id = (!empty($fakeData['from_id']))?$fakeData['from_id']:0;
        $prompt = (!empty($fakeData['prompt']))?$fakeData['prompt']:'';
        $prompt = mb_substr($prompt, 0, 3000); // Max 320
        $path_audio = (!empty($fakeData['path_audio']))?$fakeData['path_audio']:'sdroot/vladimirgav/inputdata/speech/voice/basic_ref_en.wav';
        $path_img = (!empty($fakeData['path_img']))?$fakeData['path_img']:'sdroot/vladimirgav/inputdata/img2img.png';
        $model_id = (!empty($fakeData['model_id']))?$fakeData['model_id']:'';
        $files_count = (!empty($fakeData['files_count']))?$fakeData['files_count']:1;
        $size = (!empty($fakeData['size']))?$fakeData['size']:'512';
        $typesize = (!empty($fakeData['typesize']))?$fakeData['typesize']:'full';

        $dirTemp = __DIR__.'/temp';
        if (!file_exists($dirTemp)) {
            mkdir($dirTemp, 0777, true);
        }
        $dirFake = __DIR__.'/temp/fake';
        if (!file_exists($dirFake)) {
            mkdir($dirFake, 0777, true);
        }
        $dirData = __DIR__.'/temp/dataFake';
        if (!file_exists($dirData)) {
            mkdir($dirData, 0777, true);
        }

        // Генерируем входные данные для SD
        $video_id=time();
        $inputDataArr = [];
        $inputDataArr['from_id'] = $from_id;
        $inputDataArr['video_id'] = $video_id;
        $inputDataArr['files_count'] = $files_count;

        $inputDataArr['model_id'] = $model_id;

        $inputDataArr['prompt'] = $prompt;

        $inputDataArr['path_audio'] = $path_audio;
        $inputDataArr['path_img'] = $path_img;

        $inputDataArr['files_dir'] = $dirFake;
        $inputDataArr['result_json_file'] = $dirData.'/'.$video_id.'.json';

        $inputDataArr['size'] = $size;
        $inputDataArr['type'] = $typesize;

        $type = 'FaceTalking';
        $file_data_json = $dirTemp.'/'.$type.'.json';
        file_put_contents($file_data_json, json_encode($inputDataArr, JSON_PRETTY_PRINT));

        // TODO под линукс надо потестировать, когда будет видюха
        // Определяемся, что будем запускать bat или sh
        //if(!empty(getenv('PHPBIN'))){
            // windows
            $shFile = $dirTemp.'/'.$type.'.bat';
            $shellText = '@echo off'.PHP_EOL;
            $shellText .= 'call '.$this->pathAiFaceTalking.'/vladimirgav/programsSadTalker/sdvenv/Scripts/activate.bat'.PHP_EOL;
            $shellText .= 'set XDG_CACHE_HOME='.$this->pathAiFaceTalking.'/vladimirgav/programsSadTalker/sdvenv/cache'.PHP_EOL;
            $shellText .= 'set PATH=%PATH%;'.$this->pathAiFaceTalking.'/vladimirgav/programsSadTalker/python/'.PHP_EOL;
        /*} else {
            // linux
            $shFile = $dirTemp.'/'.$type.'.sh';
            $shellText = '#!/bin/bash'.PHP_EOL;
            $shellText .= 'source '.$this->pathAiFaceTalking.'/vladimirgav/programsSadTalker/sdvenv/Scripts/activate'.PHP_EOL;
            $shellText .= 'export XDG_CACHE_HOME='.$this->pathAiFaceTalking.'/vladimirgav/programsSadTalker/sdvenv/cache'.PHP_EOL;
        }*/


        // Определяем тип генерации картинки
        $resultFile = $dirTemp.'/resultTalkingFace.txt';
        if($type=='FaceTalking'){
            $shellText .= 'set PATH='.$this->pathAiFaceTalking.'/vladimirgav/programsSadTalker/ffmpeg;%PATH%'.PHP_EOL;
            $shellText .= 'python '.$this->pathAiFaceTalking.'/vladimirgav/scriptsSadTalker/inference.py --jsondata '.$file_data_json.' > '.$resultFile.PHP_EOL;
            file_put_contents($shFile, $shellText);
        }

        // Выполняем скрипт
        $shellScript = $shFile.' &';
        $FaceTalkingSh = shell_exec($shellScript);

        // Получаем результат
        if(file_exists($inputDataArr['result_json_file'])){
            return ['error' => 0, 'data' => 'Success', 'resultData'=>json_decode(file_get_contents($inputDataArr['result_json_file']), true)];
        }

        return ['error' => 1, 'data' => 'Unknown error'];
    }

}