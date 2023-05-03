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

    public $pathStableDiffusion = 'D:/sd/stable-diffusion';

    public function getImg($prompt, $model_id='stabilityai/stable-diffusion-2-1-base', $size='256x256'){

        $dirTemp = __DIR__.'/temp';
        if (!file_exists($dirTemp)) {
            mkdir($dirTemp, 0777, true);
        }
        $dirImgs = __DIR__.'/temp/imgs';
        if (!file_exists($dirImgs)) {
            mkdir($dirImgs, 0777, true);
        }

        $file_txt2img_json = $dirTemp.'/txt2img.json';
        $resultFile = $dirTemp.'/result.txt';

        //if(!empty(getenv('PHPBIN'))){
            // windows
            $shFile = $dirTemp.'/create_txt2img.bat';
            $shellText = '@echo off'.PHP_EOL;
            $shellText .= 'call '.$this->pathStableDiffusion.'/sdvenv/Scripts/activate.bat'.PHP_EOL;
            $shellText .= 'set XDG_CACHE_HOME='.$this->pathStableDiffusion.'/sdvenv/cache'.PHP_EOL;
        /*} else {
            // linux
            $shFile = $dirTemp.'/create_txt2img.sh';
            $shellText = '#!/bin/bash'.PHP_EOL;
            $shellText .= 'source '.$this->pathStableDiffusion.'/sdvenv/Scripts/activate'.PHP_EOL;
            $shellText .= 'export XDG_CACHE_HOME='.$this->pathStableDiffusion.'/sdvenv/cache'.PHP_EOL;
        }*/

        $shellText .= 'python '.$this->pathStableDiffusion.'/vladimirgav/scripts/vgTxt2img.py '.$file_txt2img_json.' > '.$resultFile.PHP_EOL;
        file_put_contents($shFile, $shellText);

        $inputDataArr = [];
        $inputDataArr['img_id'] = 1;
        $inputDataArr['imgs_count'] = 1;
        $inputDataArr['model_id'] = $model_id;
        $inputDataArr['prompt'] = $prompt;
        $inputDataArr['imgs_dir'] = $dirImgs;

        file_put_contents($file_txt2img_json, json_encode($inputDataArr));

        $shellScript = $shFile.' &';
        $txt2imgSh = shell_exec($shellScript);

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