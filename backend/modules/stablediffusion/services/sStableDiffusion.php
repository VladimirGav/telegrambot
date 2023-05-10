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

    public $pathStableDiffusion = 'D:/sd/stablediffusion';

    public function getTxt2Img($model_id, $prompt, $n_prompt){
        $sdData=[];
        $sdData['type']='txt2img';
        $sdData['prompt']=$prompt;
        $sdData['n_prompt']=$n_prompt;
        $sdData['model_id']=$model_id;

        $SdImg = $this->getSdImg($sdData);
        return $SdImg;
    }

    public function getImg2Img($model_id, $prompt, $n_prompt, $img_original){
        $sdData=[];
        $sdData['type']='img2img';
        $sdData['img_original']=$img_original;
        $sdData['prompt']=$prompt;
        $sdData['n_prompt']=$n_prompt;
        $sdData['model_id']=$model_id;

        $SdImg = $this->getSdImg($sdData);
        return $SdImg;
    }

    public function getSdImg($sdData){
        // Получаем данные
        $type = (!empty($sdData['type']))?mb_strtolower($sdData['type']):'txt2img'; // txt2img, img2img
        $prompt = (!empty($sdData['prompt']))?$sdData['prompt']:'';
        $n_prompt = (!empty($sdData['n_prompt']))?$sdData['n_prompt']:'';
        $model_id = (!empty($sdData['model_id']))?$sdData['model_id']:'stabilityai/stable-diffusion-2-1-base';
        $imgs_count = (!empty($sdData['imgs_count']))?$sdData['model_id']:1;
        $size = (!empty($sdData['size']))?$sdData['size']:'';

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

        // Генерируем входные данные для SD
        $img_id=time();
        $inputDataArr = [];
        $inputDataArr['img_id'] = $img_id;
        $inputDataArr['imgs_count'] = $imgs_count;
        $inputDataArr['model_id'] = $model_id;
        $inputDataArr['prompt'] = $prompt;
        $inputDataArr['n_prompt'] = $n_prompt;
        $inputDataArr['imgs_dir'] = $dirImgs;
        if(!empty($sdData['img_original'])){
            $inputDataArr['img_original'] = $sdData['img_original'];
        }

        $file_data_json = $dirTemp.'/'.$type.'.json';
        file_put_contents($file_data_json, json_encode($inputDataArr));

        // Определяемся, что будем запускать bat или sh
        //if(!empty(getenv('PHPBIN'))){
            // windows
            $shFile = $dirTemp.'/'.$type.'.bat';
            $shellText = '@echo off'.PHP_EOL;
            $shellText .= 'call '.$this->pathStableDiffusion.'/sdvenv/Scripts/activate.bat'.PHP_EOL;
            $shellText .= 'set XDG_CACHE_HOME='.$this->pathStableDiffusion.'/sdvenv/cache'.PHP_EOL;
        /*} else {
            // linux
            $shFile = $dirTemp.'/'.$type.'.sh';
            $shellText = '#!/bin/bash'.PHP_EOL;
            $shellText .= 'source '.$this->pathStableDiffusion.'/sdvenv/Scripts/activate'.PHP_EOL;
            $shellText .= 'export XDG_CACHE_HOME='.$this->pathStableDiffusion.'/sdvenv/cache'.PHP_EOL;
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
                    return ['error' => 0, 'data' => 'Success', 'resultData'=>$resultData];
                }
            }
        }

        return ['error' => 1, 'data' => 'Unknown error'];
    }

}