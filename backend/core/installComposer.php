<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

class installComposer
{
    protected static $instance;

    /**
     * @return installComposer
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }


    /**
     * @param $dirComposer - Set the path to the folder with the composer.json file
     * @param array $requiresArr - ['vladimirgav/githubinphpcomposer']
     * @param int $secondsLimit - 15
     */
    public function installComposerStart($dirComposer, $requiresArr=[], $secondsLimit=15){
        $installComposer = installComposer::instance()->installComposerGo($dirComposer, $requiresArr, $secondsLimit);
        if(!empty($installComposer['error'])){
            echo '<pre>';
            print_r($installComposer);
            echo '</pre>';
            exit;
        }
    }


    /**
     * @param $dirComposer - Set the path to the folder with the composer.json file
     * @param array $requiresArr - ['vladimirgav/githubinphpcomposer']
     * @param int $secondsLimit - 15
     * @return array
     */
    public function installComposerGo($dirComposer, $requiresArr=[], $secondsLimit=15){
        $dirComposer = str_replace('\\', "/", $dirComposer);
        $fileAutoload = $dirComposer.'/vendor/autoload.php';
        $fileJson = $dirComposer.'/composer.json';

        if(!file_exists($fileAutoload)){

            if (!file_exists($dirComposer)) {
                if (!mkdir($dirComposer, 0755, true)) {
                    return ['error' => 1, 'data' => 'Need to create a folder '.$dirComposer.'.'];
                }
            }

            if (!file_exists($fileJson)) {
                return ['error' => 1, 'data' => 'File '.$fileJson.'/composer.json not found.'];
            }

            $PHP_BINDIR_p = $this->getPhpBINDIR();

            $text = '#!/bin/bash'.PHP_EOL;
            //$text .= 'BASEDIR=$(dirname $0) # path to current directory'.PHP_EOL;
            $text .= 'cd '.$dirComposer.' # go to composer folder'.PHP_EOL;
            $text .= $PHP_BINDIR_p.' -r "copy(\'https://getcomposer.org/installer\', \'composer-setup.php\');"'.PHP_EOL;
            $text .= 'export COMPOSER_HOME='.$dirComposer.'/cachecomposer;'.PHP_EOL;
            $text .= $PHP_BINDIR_p.' composer-setup.php'.PHP_EOL;
            $text .= $PHP_BINDIR_p.' -r "unlink(\'composer-setup.php\');"'.PHP_EOL;
            $text .= $PHP_BINDIR_p.' composer.phar install # Install composer'.PHP_EOL;
            $text .= 'rm -r '.$dirComposer.'/cachecomposer'.PHP_EOL;
            foreach ($requiresArr as $require){
                $text .= $PHP_BINDIR_p.' composer.phar require '.$require.' # require '.$require.''.PHP_EOL;
            }

            $composerInstallFileTemp = $dirComposer.'/composer_install_temp.sh';

            if(!file_put_contents($composerInstallFileTemp, $text)){
                return ['error' => 1, 'data' => 'File '.$composerInstallFileTemp.' not created.'];
            }

            $getFilePermsFile = substr(decoct(fileperms($composerInstallFileTemp)), -4);
            if($getFilePermsFile!='0744'){
                if(!chmod($composerInstallFileTemp, 0744)){
                    return ['error' => 1, 'data' => 'File '.$composerInstallFileTemp.'. Permissions required 744.'];
                }
            }

            $commandSH = $composerInstallFileTemp.' > '.$dirComposer.'/composerLog 2>&1 &';
            if(!shell_exec($commandSH)){
                //return ['error' => 1, 'data' => 'To install composer, you need to execute in the console: '.$composerInstallFileTemp];
            }

            for ($i = 1; $i <= 15; $i++) {
                if(file_exists($fileAutoload)){ continue; }
                sleep(1);
            }
            if(!file_exists($fileAutoload)){
                return ['error' => 1, 'data' => 'To install composer, you need to execute in the console: '.$composerInstallFileTemp];
            }
            unlink($composerInstallFileTemp);
            unlink($dirComposer.'/composerLog');
        }

        return ['error' => 0, 'data' => 'Success'];
    }

    /**
     * @param $dirComposer - Set the path to the folder with the composer.json file
     * @param array $requiresArr - ['vladimirgav/githubinphpcomposer']
     * @return array
     */
    public function updateComposer($dirComposer, $requiresArr=[]){
        // php composer.phar update
        $dirComposer = str_replace('\\', "/", $dirComposer);
        $fileAutoload = $dirComposer.'/vendor/autoload.php';
        if(!file_exists($fileAutoload)){
            return ['error' => 1, 'data' => 'File '.$fileAutoload. ' not found.'];
        }

        $PHP_BINDIR_p = $this->getPhpBINDIR();

        $text = '#!/bin/bash'.PHP_EOL;
        $text .= 'cd '.$dirComposer.' # go to composer folder'.PHP_EOL;
        $text .= 'export COMPOSER_HOME='.$dirComposer.'/cachecomposer;'.PHP_EOL;
        $text .= $PHP_BINDIR_p.' composer.phar update # update composer'.PHP_EOL;
        $text .= 'rm -r '.$dirComposer.'/cachecomposer'.PHP_EOL;
        foreach ($requiresArr as $require){
            $text .= $PHP_BINDIR_p.' composer.phar require '.$require.' # require '.$require.''.PHP_EOL;
        }

        $composerUpdateFileTemp = $dirComposer.'/composer_update_temp.sh';

        if(!file_put_contents($composerUpdateFileTemp, $text)){
            return ['error' => 1, 'data' => 'File '.$composerUpdateFileTemp.' not created.'];
        }

        $getFilePermsFile = substr(decoct(fileperms($composerUpdateFileTemp)), -4);
        if($getFilePermsFile!='0744'){
            if(!chmod($composerUpdateFileTemp, 0744)){
                return ['error' => 1, 'data' => 'File '.$composerUpdateFileTemp.'. Permissions required 744.'];
            }
        }

        $commandSH = $composerUpdateFileTemp.' > '.$dirComposer.'/composerLog 2>&1 &';
        if(!shell_exec($commandSH)){
            //return ['error' => 1, 'data' => 'To update composer, you need to execute in the console: '.$composerUpdateFileTemp];
        }

        unlink($composerUpdateFileTemp);
        unlink($dirComposer.'/composerLog');

        return ['error' => 0, 'data' => 'The shell_exec command successfully start'];
    }

    /**
     * @return string|string[]
     */
    public function getPhpBINDIR(){
        $PHP_BINDIR_p = 'php';
        if(!empty(getenv('PHPBIN'))){
            $PHP_BINDIR_p = getenv('PHPBIN'); // windows
        } else {
            if(!empty(PHP_BINARY)){
                $PHP_BINDIR_p = PHP_BINDIR.'/php'; // linux
            }
        }
        if(defined('_PHP_Path_dir_')){
            $PHP_BINDIR_p = _PHP_Path_dir_.'/php';
        }
        $PHP_BINDIR_p = str_replace('\\', "/", $PHP_BINDIR_p);
        return $PHP_BINDIR_p;
    }
}

// TODO Set the path to the folder with the composer.json file
//installComposer::instance()->installComposerStart(__DIR__.'/composer');