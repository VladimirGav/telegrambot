<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

namespace modules\stablediffusion\services;

class sStableDiffusion
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

    public $pathStableDiffusion = 'D:/stable-diffusion-vg';

    public function getTxt2Img($sdData){
        $sdData['type']='txt2img';
        $SdImg = $this->getSdImg($sdData);
        return $SdImg;
    }

    public function getImg2Img($sdData){
        $sdData['type']='img2img';
        $SdImg = $this->getSdImg($sdData);
        return $SdImg;
    }

    public function getSdImg($sdData){
        // Получаем данные
        $from_id = (!empty($sdData['from_id']))?$sdData['from_id']:0;
        $nsfw = (!empty($sdData['nsfw']))?$sdData['nsfw']:false;
        $type = (!empty($sdData['type']))?mb_strtolower($sdData['type']):'txt2img'; // txt2img, img2img
        $prompt = (!empty($sdData['prompt']))?$sdData['prompt']:'';
        //$prompt = mb_substr($prompt, 0, 320); // Max 320
        $negative_prompt = (!empty($sdData['negative_prompt']))?$sdData['negative_prompt']:'';
        //$negative_prompt = mb_substr($negative_prompt, 0, 320); // Max 320
        $model_id = (!empty($sdData['model_id']))?$sdData['model_id']:'stabilityai/stable-diffusion-2-1-base';
        $model_lora_weights = (!empty($sdData['model_lora_weights']))?$sdData['model_lora_weights']:'';
        $imgs_count = (!empty($sdData['imgs_count']))?$sdData['model_id']:1;
        $img_width = (!empty($sdData['img_width']) && (int)$sdData['img_width'] > 0)?$sdData['img_width']:512;
        $img_height = (!empty($sdData['img_height']) && (int)$sdData['img_height'] > 0)?$sdData['img_height']:512;
        $img_num_inference_steps = (isset($sdData['img_num_inference_steps']) && (int)$sdData['img_num_inference_steps'] >= 0 && (int)$sdData['img_num_inference_steps'] <= 50)?(int)$sdData['img_num_inference_steps']:25;
        $img_guidance_scale = (isset($sdData['img_guidance_scale']) && floatval($sdData['img_guidance_scale']) >= 0 && floatval($sdData['img_guidance_scale']) <= 15)?floatval($sdData['img_guidance_scale']):7.5;
        $sampler = (!empty($sdData['sampler']))?$sdData['sampler']:'';

        // Проверим типы на доступность
        if(!in_array($type, ['txt2img', 'img2img'])){
            return ['error' => 1, 'data' => 'available types: txt2img, img2img'];
        }

        // Если генерируем картинку из картинки, то проверяем
        if($type=='img2img'){
            if(empty($sdData['img_original'])){
                return ['error' => 1, 'data' => 'img_original is empty'];
            }
            if(!file_exists($sdData['img_original'])){
                return ['error' => 1, 'data' => 'img_original is no exist'];
            }
            $fileInfo = pathinfo($sdData['img_original']);
            if(!in_array($fileInfo['extension'], ['jpg','png'])){
                return ['error' => 1, 'data' => 'img_original is not jpg, png'];
            }
        }

        $dirTemp = __DIR__.'/temp';
        if (!file_exists($dirTemp)) {
            mkdir($dirTemp, 0777, true);
        }
        $dirImgs = __DIR__.'/temp/imgs';
        if (!file_exists($dirImgs)) {
            mkdir($dirImgs, 0777, true);
        }
        $dirData = __DIR__.'/temp/data';
        if (!file_exists($dirData)) {
            mkdir($dirData, 0777, true);
        }

        // Генерируем входные данные для SD
        $img_id=time();
        $inputDataArr = [];
        $inputDataArr['from_id'] = $from_id;
        $inputDataArr['nsfw'] = $nsfw;
        $inputDataArr['img_id'] = $img_id;
        $inputDataArr['imgs_count'] = $imgs_count;
        $inputDataArr['model_id'] = $model_id;
        $inputDataArr['model_lora_weights'] = $model_lora_weights;
        $inputDataArr['img_width'] = $img_width;
        $inputDataArr['img_height'] = $img_height;
        $inputDataArr['img_num_inference_steps'] = $img_num_inference_steps;
        $inputDataArr['img_guidance_scale'] = $img_guidance_scale;
        $inputDataArr['sampler'] = $sampler;

        $inputDataArr['prompt'] = $prompt;
        $inputDataArr['negative_prompt'] = $negative_prompt;
        $inputDataArr['imgs_dir'] = $dirImgs;
        if(!empty($sdData['img_original'])){
            $inputDataArr['img_original'] = $sdData['img_original'];
        }

        $file_data_json = $dirTemp.'/'.$type.'.json';
        file_put_contents($file_data_json, json_encode($inputDataArr, JSON_PRETTY_PRINT));

        // TODO под линукс надо потестировать, когда будет видюха
        // Определяемся, что будем запускать bat или sh
        //if(!empty(getenv('PHPBIN'))){
            // windows
            $shFile = $dirTemp.'/'.$type.'.bat';
            $shellText = '@echo off'.PHP_EOL;
            $shellText .= 'call '.$this->pathStableDiffusion.'/vladimirgav/programs/sdvenv/Scripts/activate.bat'.PHP_EOL;
            $shellText .= 'set XDG_CACHE_HOME='.$this->pathStableDiffusion.'/vladimirgav/programs/sdvenv/cache'.PHP_EOL;
            $shellText .= 'set PATH=%PATH%;'.$this->pathStableDiffusion.'/vladimirgav/programs/python/'.PHP_EOL;
        /*} else {
            // linux
            $shFile = $dirTemp.'/'.$type.'.sh';
            $shellText = '#!/bin/bash'.PHP_EOL;
            $shellText .= 'source '.$this->pathStableDiffusion.'/vladimirgav/programs/sdvenv/Scripts/activate'.PHP_EOL;
            $shellText .= 'export XDG_CACHE_HOME='.$this->pathStableDiffusion.'/vladimirgav/programs/sdvenv/cache'.PHP_EOL;
        }*/


        // Определяем тип генерации картинки
        $resultFile = $dirTemp.'/result.txt';
        if($type=='txt2img'){
            $shellText .= 'python '.$this->pathStableDiffusion.'/vladimirgav/scripts/vgTxt2img.py '.$file_data_json.' > '.$resultFile.PHP_EOL;
            file_put_contents($shFile, $shellText);
        }
        if($type=='img2img'){
            $shellText .= 'python '.$this->pathStableDiffusion.'/vladimirgav/scripts/vgImg2img.py '.$file_data_json.' > '.$resultFile.PHP_EOL;
            file_put_contents($shFile, $shellText);
        }

        // Выполняем скрипт
        $shellScript = $shFile.' &';
        $txt2imgSh = shell_exec($shellScript);

        // Получаем результат
        if(file_exists($resultFile)){
            $resultDataJson = file_get_contents($resultFile);
            if(!empty($resultDataJson)){
                $resultData = json_decode($resultDataJson, true);
                if(!empty($resultData['img_id']) && !empty($resultData['imgs'])){
                    $resultData['imgs_dir'] = str_replace('\\', "/", $resultData['imgs_dir']);
                    foreach($resultData['imgs'] as $imgKey => $imgdata){
                        $resultData['imgs'][$imgKey]['FilePath'] = str_replace('\\', "/", $imgdata['FilePath']);
                    }
                    file_put_contents($dirData.'/'.$resultData['img_id'].'.json', json_encode($resultData, JSON_PRETTY_PRINT));
                    return ['error' => 0, 'data' => 'Success', 'resultData'=>$resultData];
                }
            }
        }

        return ['error' => 1, 'data' => 'Unknown error'];
    }

}