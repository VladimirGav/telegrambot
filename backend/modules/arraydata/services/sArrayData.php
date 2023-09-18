<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

namespace modules\arraydata\services;

class sArrayData
{
    protected static $instance;

    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public $dirTemp = __DIR__.'/temp/arraydata';

    public function getData($id, $folder = '') {
        $dirTemp = $this->dirTemp;
        if(!empty($folder)){
            $dirTemp = $dirTemp.'/'.$folder;
        }
        $file_data_json = $dirTemp.'/'.$id.'.json';

        if(!file_exists($file_data_json)){
            return [];
        }

        return json_decode(file_get_contents($file_data_json), true);
    }

    public function saveData($id, $data, $folder = '') {
        $dirTemp = $this->dirTemp;
        if(!empty($folder)){
            $dirTemp = $dirTemp.'/'.$folder;
        }
        if (!file_exists($dirTemp)) {
            mkdir($dirTemp, 0777, true);
        }

        $file_data_json = $dirTemp.'/'.$id.'.json';
        file_put_contents($file_data_json, json_encode($data, JSON_PRETTY_PRINT));

        return ['error' => 0, 'data' => 'Success'];
    }
}