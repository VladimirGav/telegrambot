<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

namespace modules\aivg\services;

class sAiSpeech
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

    public $pathAiSpeech = 'D:/ai-vladimir-gav';

    public function getTxt2Speech($speechData){
        $speechData['type']='txt2speech';
        $AiSpeechData = $this->getAiSpeech($speechData);
        return $AiSpeechData;
    }

    public function getAiSpeech($speechData){
        // Получаем данные
        $from_id = (!empty($speechData['from_id']))?$speechData['from_id']:0;
        $prompt = (!empty($speechData['prompt']))?$speechData['prompt']:'';
        $prompt = mb_substr($prompt, 0, 3000); // Max 320
        $ref_audio = (!empty($speechData['ref_audio']))?$speechData['ref_audio']:'sdroot/vladimirgav/inputdata/speech/voice/basic_ref_en.wav';
        $ref_text = (!empty($speechData['ref_text']))?$speechData['ref_text']:'Some call me nature, others call me mother nature.';
        $model_id = (!empty($speechData['model_id']))?$speechData['model_id']:'en';
        $speaker = (!empty($speechData['speaker']))?'_'.$speechData['speaker']:'';
        $ckpt_file_url = "https://huggingface.co/SWivid/F5-TTS/resolve/main/F5TTS_v1_Base/model_1250000.safetensors";
        $vocab_file_url = "https://huggingface.co/SWivid/F5-TTS/resolve/main/F5TTS_v1_Base/vocab.txt";
        if($model_id=='ru'){
            $ref_audio = (!empty($speechData['ref_audio']))?$speechData['ref_audio']:'sdroot/vladimirgav/inputdata/speech/voice/basic_ref_'.$model_id.''.$speaker.'.wav';
            $ref_text = (!empty($speechData['ref_text']))?$speechData['ref_text']:'VladimirGav, используй мой голос для генерации речи';
            if($speaker==2){
                $ref_text = (!empty($speechData['ref_text']))?$speechData['ref_text']:'Как обучить сотрудников быстро и эффективно.';
            }

            $ckpt_file_url = "https://huggingface.co/Misha24-10/F5-TTS_RUSSIAN/resolve/main/F5TTS_v1_Base/model_240000_inference.safetensors";
            $vocab_file_url = "https://huggingface.co/Misha24-10/F5-TTS_RUSSIAN/resolve/main/F5TTS_v1_Base/vocab.txt";
        }
        $files_count = (!empty($speechData['files_count']))?$speechData['files_count']:1;

        $dirTemp = __DIR__.'/temp';
        if (!file_exists($dirTemp)) {
            mkdir($dirTemp, 0777, true);
        }
        $dirSpeech = __DIR__.'/temp/speech';
        if (!file_exists($dirSpeech)) {
            mkdir($dirSpeech, 0777, true);
        }
        $dirData = __DIR__.'/temp/dataSpeech';
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
        $inputDataArr['ckpt_file_url'] = $ckpt_file_url;
        $inputDataArr['vocab_file_url'] = $vocab_file_url;

        $inputDataArr['prompt'] = $prompt;

        $inputDataArr['ref_audio'] = $ref_audio;
        $inputDataArr['ref_text'] = $ref_text;

        $inputDataArr['files_dir'] = $dirSpeech;
        $inputDataArr['result_json_file'] = $dirData.'/'.$audio_id.'.json';

        $type = 'txt2speech';
        $file_data_json = $dirTemp.'/'.$type.'.json';
        file_put_contents($file_data_json, json_encode($inputDataArr, JSON_PRETTY_PRINT));

        // TODO под линукс надо потестировать, когда будет видюха
        // Определяемся, что будем запускать bat или sh
        //if(!empty(getenv('PHPBIN'))){
            // windows
            $shFile = $dirTemp.'/'.$type.'.bat';
            $shellText = '@echo off'.PHP_EOL;
            $shellText .= 'call '.$this->pathAiSpeech.'/vladimirgav/programsSpeech/sdvenv/Scripts/activate.bat'.PHP_EOL;
            $shellText .= 'set XDG_CACHE_HOME='.$this->pathAiSpeech.'/vladimirgav/programsSpeech/sdvenv/cache'.PHP_EOL;
            $shellText .= 'set PATH=%PATH%;'.$this->pathAiSpeech.'/vladimirgav/programsSpeech/python/'.PHP_EOL;
        /*} else {
            // linux
            $shFile = $dirTemp.'/'.$type.'.sh';
            $shellText = '#!/bin/bash'.PHP_EOL;
            $shellText .= 'source '.$this->pathAiSpeech.'/vladimirgav/programsSpeech/sdvenv/Scripts/activate'.PHP_EOL;
            $shellText .= 'export XDG_CACHE_HOME='.$this->pathAiSpeech.'/vladimirgav/programsSpeech/sdvenv/cache'.PHP_EOL;
        }*/


        // Определяем тип генерации картинки
        $resultFile = $dirTemp.'/resultFull.txt';
        if($type=='txt2speech'){
            $shellText .= 'set PATH='.$this->pathAiSpeech.'/vladimirgav/programsSadTalker/ffmpeg;%PATH%'.PHP_EOL;
            $shellText .= 'python '.$this->pathAiSpeech.'/vladimirgav/scriptsSpeech/infer_cli.py --jsondata '.$file_data_json.' > '.$resultFile.PHP_EOL;
            file_put_contents($shFile, $shellText);
        }

        // Выполняем скрипт
        $shellScript = $shFile.' &';
        $txt2speechSh = shell_exec($shellScript);

        // Получаем результат
        if(file_exists($inputDataArr['result_json_file'])){
            return ['error' => 0, 'data' => 'Success', 'resultData'=>json_decode(file_get_contents($inputDataArr['result_json_file']), true)];
        }

        // Получаем результат
        /*if(file_exists($resultFile)){
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
        }*/

        return ['error' => 1, 'data' => 'Unknown error'];
    }

}