<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

namespace modules\crypto\services;

class sCrypto
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

    public $pathNodejs = __DIR__.'/../../../../forwindows/nodejs/node-v18.16.0-win-x64/node.exe';
    public $pathNodejsProject = __DIR__.'/../../../../forwindows/nodejs/node-v18.16.0-win-x64/project/node_modules';

    public function createWallet(){
        sleep(1);
        $dirTemp = __DIR__.'/temp';
        if (!file_exists($dirTemp)) {
            mkdir($dirTemp, 0777, true);
        }

        // Определяемся, что будем запускать bat или sh
        //if(!empty(getenv('PHPBIN'))){
            // windows
            $shFile = $dirTemp.'/createWallet.bat';
            $shellText = '@echo off'.PHP_EOL;
            $shellText .= $this->pathNodejs.' '.__DIR__.'/../nodejs/crypto.js'.PHP_EOL;
        /*} else {
            // linux
            $shFile = $dirTemp.'/createWallet.sh';
            $shellText = '#!/bin/bash'.PHP_EOL;
            $shellText .= 'node '.__DIR__.'/../nodejs/crypto.js'.PHP_EOL;
        }*/

        file_put_contents($shFile, $shellText);

        $shellScript = $shFile.' &';
        $jsonData = shell_exec($shellScript);
        return json_decode($jsonData, true);
    }

    public function createSeedWallet($countWallet=1){
        sleep(1);
        $dirTemp = __DIR__.'/temp';
        if (!file_exists($dirTemp)) {
            mkdir($dirTemp, 0777, true);
        }

        // Определяемся, что будем запускать bat или sh
        //if(!empty(getenv('PHPBIN'))){
        // windows
        $shFile = $dirTemp.'/seedWallet.bat';
        $shellText = '@echo off'.PHP_EOL;
        $shellText .= $this->pathNodejs.' '.__DIR__.'/../nodejs/seedWallet.js '.$countWallet.PHP_EOL;
        /*} else {
            // linux
            $shFile = $dirTemp.'/seedWallet.sh';
            $shellText = '#!/bin/bash'.PHP_EOL;
            $shellText .= 'node '.__DIR__.'/../nodejs/seedWallet.js '.$countWallet.PHP_EOL;
        }*/

        file_put_contents($shFile, $shellText);

        $shellScript = $shFile.' &';
        $jsonData = shell_exec($shellScript);
        return json_decode($jsonData, true);
    }

    public function generateSeedPhrase(){
        //sleep(1);
        $dirTemp = __DIR__.'/temp';
        if (!file_exists($dirTemp)) {
            mkdir($dirTemp, 0777, true);
        }

        // Определяемся, что будем запускать bat или sh
        //if(!empty(getenv('PHPBIN'))){
        // windows
        $shFile = $dirTemp.'/generateSeedPhrase.bat';
        $shellText = '@echo off'.PHP_EOL;
        $shellText .= $this->pathNodejs.' '.__DIR__.'/../nodejs/generateSeedPhrase.js'.PHP_EOL;
        /*} else {
            // linux
            $shFile = $dirTemp.'/generateSeedPhrase.sh';
            $shellText = '#!/bin/bash'.PHP_EOL;
            $shellText .= 'node '.__DIR__.'/../nodejs/generateSeedPhrase.js'.PHP_EOL;
        }*/

        file_put_contents($shFile, $shellText);

        $shellScript = $shFile.' &';
        $jsonData = shell_exec($shellScript);
        return json_decode($jsonData, true);
    }

}