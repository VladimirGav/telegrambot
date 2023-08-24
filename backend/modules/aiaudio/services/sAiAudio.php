<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

namespace modules\aiaudio\services;

class sAiAudio
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

    public $pathAiAudio = 'D:/ai-audio-vg';

    public function getTxt2Audio($audioData){
        $audioData['type']='txt2audio';
        $AiAudioData = $this->getAiAudio($audioData);
        return $AiAudioData;
    }

    public function getAiAudio($audioData){
        // Получаем данные
        $from_id = (!empty($audioData['from_id']))?$audioData['from_id']:0;
        $prompt = (!empty($audioData['prompt']))?$audioData['prompt']:'';
        $prompt = mb_substr($prompt, 0, 320); // Max 320
        $voice_preset = (!empty($audioData['voice_preset']))?$audioData['voice_preset']:'';
        $model_id = (!empty($audioData['model_id']))?$audioData['model_id']:'suno/bark-small';
        $files_count = (!empty($audioData['files_count']))?$audioData['files_count']:1;

        $dirTemp = __DIR__.'/temp';
        if (!file_exists($dirTemp)) {
            mkdir($dirTemp, 0777, true);
        }
        $dirAudio = __DIR__.'/temp/audio';
        if (!file_exists($dirAudio)) {
            mkdir($dirAudio, 0777, true);
        }
        $dirData = __DIR__.'/temp/data';
        if (!file_exists($dirData)) {
            mkdir($dirData, 0777, true);
        }

        // Генерируем входные данные для SD
        $audio_id=time();
        $inputDataArr = [];
        $inputDataArr['from_id'] = $from_id;
        $inputDataArr['audio_id'] = $audio_id;
        $inputDataArr['files_count'] = $files_count;
        $inputDataArr['model_id'] = $model_id;

        $inputDataArr['prompt'] = $prompt;
        $inputDataArr['voice_preset'] = $voice_preset;
        $inputDataArr['files_dir'] = $dirAudio;

        $type = 'txt2audio';
        $file_data_json = $dirTemp.'/'.$type.'.json';
        file_put_contents($file_data_json, json_encode($inputDataArr, JSON_PRETTY_PRINT));

        // TODO под линукс надо потестировать, когда будет видюха
        // Определяемся, что будем запускать bat или sh
        //if(!empty(getenv('PHPBIN'))){
            // windows
            $shFile = $dirTemp.'/'.$type.'.bat';
            $shellText = '@echo off'.PHP_EOL;
            $shellText .= 'call '.$this->pathAiAudio.'/vladimirgav/programs/sdvenv/Scripts/activate.bat'.PHP_EOL;
            $shellText .= 'set XDG_CACHE_HOME='.$this->pathAiAudio.'/vladimirgav/programs/sdvenv/cache'.PHP_EOL;
            $shellText .= 'set PATH=%PATH%;'.$this->pathAiAudio.'/vladimirgav/programs/python/'.PHP_EOL;
        /*} else {
            // linux
            $shFile = $dirTemp.'/'.$type.'.sh';
            $shellText = '#!/bin/bash'.PHP_EOL;
            $shellText .= 'source '.$this->pathAiAudio.'/vladimirgav/programs/sdvenv/Scripts/activate'.PHP_EOL;
            $shellText .= 'export XDG_CACHE_HOME='.$this->pathAiAudio.'/vladimirgav/programs/sdvenv/cache'.PHP_EOL;
        }*/


        // Определяем тип генерации картинки
        $resultFile = $dirTemp.'/result.txt';
        if($type=='txt2audio'){
            $shellText .= 'python '.$this->pathAiAudio.'/vladimirgav/scripts/vgTxt2Audio.py '.$file_data_json.' > '.$resultFile.PHP_EOL;
            file_put_contents($shFile, $shellText);
        }

        // Выполняем скрипт
        $shellScript = $shFile.' &';
        $txt2audioSh = shell_exec($shellScript);

        // Получаем результат
        if(file_exists($resultFile)){
            $resultDataJson = file_get_contents($resultFile);
            if(!empty($resultDataJson)){
                $resultData = json_decode($resultDataJson, true);
                if(!empty($resultData['audio_id']) && !empty($resultData['files'])){
                    $resultData['files_dir'] = str_replace('\\', "/", $resultData['files_dir']);
                    foreach($resultData['files'] as $imgKey => $imgdata){
                        $resultData['files'][$imgKey]['FilePath'] = str_replace('\\', "/", $imgdata['FilePath']);
                    }
                    file_put_contents($dirData.'/'.$resultData['audio_id'].'.json', json_encode($resultData, JSON_PRETTY_PRINT));
                    return ['error' => 0, 'data' => 'Success', 'resultData'=>$resultData];
                }
            }
        }

        return ['error' => 1, 'data' => 'Unknown error'];
    }

}