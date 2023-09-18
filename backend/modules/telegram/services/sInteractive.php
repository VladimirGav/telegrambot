<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

namespace modules\telegram\services;

class sInteractive
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

    public $keysDelimiter = '_';
    public $arrKeysValues = [];
    public $previousElement = [];

    public function getInteractive($CommandName, $InteractiveArrData, $InteractiveKeysStr){
        $this->arrKeysValues = [];
        $this->previousElement = [];

        if(empty($CommandName)){
            return ['error'=> 1, 'data' => 'CommandName is empty'];
        }

        if(empty($InteractiveArrData)){
            return ['error'=> 1, 'data' => 'InteractiveArrData is empty'];
        }

        $outDataArr = [
            'isFinish' => 0,
            'editMarkup' => 0,
            'keysArr' => [],
            'arrKeysValues' => [],
            'reply_markup' => '',
            'select_text'=>''
        ];

        $inputDataArr = [
            'CommandName' => $CommandName,
            'InteractiveArrData' => $InteractiveArrData,
            'InteractiveKeysStr' => $InteractiveKeysStr,
        ];

        $InteractiveKeysStr = str_replace($CommandName, "", $InteractiveKeysStr);
        if(mb_substr($InteractiveKeysStr,0,1) == $this->keysDelimiter){
            $InteractiveKeysStr = mb_substr($InteractiveKeysStr,1);
        }

        $keysArr = explode('_', $InteractiveKeysStr);
        $outDataArr['keysArr'] = $keysArr;

        $ElementSelect=[];
        if($InteractiveKeysStr != ''){
            if($InteractiveArrData['TypeSelect']=='tree'){
                $ElementSelectByKeys = $this->getElementSelectByKeysTree($InteractiveArrData['ElementSelect'], $keysArr);
            } else {
                $ElementSelectByKeys = $this->getElementSelectByKeysSimple($InteractiveArrData['ElementsSelect'], $keysArr);
            }

            if(!empty($ElementSelectByKeys['error'])){
                return $ElementSelectByKeys;
            }
            if(!empty($ElementSelectByKeys['ElementSelect'])){
                $ElementSelect = $ElementSelectByKeys['ElementSelect'];
            }

            $outDataArr['editMarkup'] = 1;

            //array_shift($this->arrKeysValues);
            $outDataArr['arrKeysValues'] = $this->arrKeysValues;
        } else {
            $outDataArr['editMarkup'] = 0;
            if($InteractiveArrData['TypeSelect']=='tree'){
                $ElementSelect = $InteractiveArrData['ElementSelect'];
            }
            if($InteractiveArrData['TypeSelect']=='simple' && !empty($InteractiveArrData['ElementsSelect'][0])){
                $ElementSelect = $InteractiveArrData['ElementsSelect'][0];
            }
        }

        if(empty($ElementSelect['select_data'])){
            $outDataArr['isFinish'] = 1;
        } else {
            $ReplyMarkup = $this->getReplyMarkup($CommandName.$this->keysDelimiter.$InteractiveKeysStr, $ElementSelect);
            $outDataArr['reply_markup'] = $ReplyMarkup['reply_markup'];
            $outDataArr['select_text'] = $ElementSelect['select_text'];
        }

        return ['error'=> 0, 'data' => 'Success ', 'inputDataArr'=> $inputDataArr, 'outDataArr'=>$outDataArr];
    }

    public function getReplyMarkup($InteractiveKeysStr, $ElementSelect){
        $inline_keyboard=[];

        $iRow = 0;
        $columns = 1;
        if(!empty($ElementSelect['columns'])){
            $columns = $ElementSelect['columns'];
        }
        $iColumns = 0;
        foreach ($ElementSelect['select_data'] as $InteractiveKey => $InteractiveData){
            $callback_data = $InteractiveKeysStr.'_'.$InteractiveKey;
            $callback_data = str_replace('__', "_", $callback_data);
            $inline_keyboard[$iRow][] = ["text"=>$InteractiveData['select_name'], "callback_data"=>$callback_data];

            $iColumns++;
            if($iColumns>=$columns){
                $iColumns=0;
                $iRow++;
            }
        }
        $keyboard=["inline_keyboard"=>$inline_keyboard];
        $reply_markup = json_encode($keyboard);

        return ['error'=> 0, 'data' => 'Success ', 'reply_markup'=> $reply_markup];
    }

    public function getElementSelectByKeysTree($ElementSelect, $keysArr=[]){

        if(empty($ElementSelect)){
            return ['error'=> 1, 'data' => 'ElementSelect is empty'];
        }

        if(!empty($this->previousElement)){
            $this->arrKeysValues[$this->previousElement['select_key']] = $ElementSelect['select_value'];
        }
        $this->previousElement = $ElementSelect;

        if(empty($keysArr)){
            return ['error'=> 0, 'data' => 'Success ', 'ElementSelect'=>$ElementSelect];
        }

        foreach ($keysArr as $keyEl => $SelectKey){
            if(!empty($ElementSelect['select_data'][$SelectKey])){
                array_shift($keysArr);
                return $this->getElementSelectByKeys($ElementSelect['select_data'][$SelectKey], $keysArr);
            } else {
                return ['error'=> 1, 'data' => 'Key not found'];
            }
        }

        return ['error'=> 1, 'data' => 'Unknown error'];
    }

    public function getElementSelectByKeysSimple($ElementsSelect, $keysArr=[]){

        /*echo '<pre>';
        print_r($ElementsSelect);
        echo '</pre>';
        echo '<pre>';
        print_r($keysArr);
        echo '</pre>';*/

        foreach ($ElementsSelect as $ElementKey => $ElementSelect){

            if(isset($keysArr[$ElementKey])){
                if(isset($ElementSelect['select_data'][$keysArr[$ElementKey]]['select_value'])){
                    $this->arrKeysValues[$ElementSelect['select_key']] = $ElementSelect['select_data'][$keysArr[$ElementKey]]['select_value'];
                } else {
                    return ['error'=> 1, 'data' => 'Key not found'];
                }
            } else {
                return ['error'=> 0, 'data' => 'Success ', 'ElementSelect'=>$ElementSelect];
            }

        }

        return ['error'=> 0, 'data' => 'Success', 'isFinish' => 1];
    }

    public function getSelectData(){

    }

    /**
     * @param $TypeSelect - tree or simple
     * @return array
     */
    public function getExampleInteractiveArrData($TypeSelect){
        $InteractiveArrData=[];

        // Structure in the form of a tree, where the number of child branches can be different and unique
        if($TypeSelect=='tree'){
            $InteractiveArrData['TypeSelect'] = 'tree';

            // Level 1

            $InteractiveArrData['ElementSelect']['columns'] = '2';
            $InteractiveArrData['ElementSelect']['select_value'] = 'Value_Element_0';
            $InteractiveArrData['ElementSelect']['select_name'] = 'Element 1';
            $InteractiveArrData['ElementSelect']['select_text'] = 'Text select Element_1';
            $InteractiveArrData['ElementSelect']['select_key'] = 'Key_Element_1';

            // Level 2

            $InteractiveArrData['ElementSelect']['select_data'][0]['select_value'] = 'Value_Element_1_1';
            $InteractiveArrData['ElementSelect']['select_data'][0]['select_name'] = 'Element 1 1';
            $InteractiveArrData['ElementSelect']['select_data'][0]['select_text'] = 'Text select Element_1_1';
            $InteractiveArrData['ElementSelect']['select_data'][0]['select_key'] = 'Key_Element_2';

            $InteractiveArrData['ElementSelect']['select_data'][1]['select_value'] = 'Value_Element_1_2';
            $InteractiveArrData['ElementSelect']['select_data'][1]['select_name'] = 'Element 1 2';

            $InteractiveArrData['ElementSelect']['select_data'][2]['select_value'] = 'Value_Element_1_3';
            $InteractiveArrData['ElementSelect']['select_data'][2]['select_name'] = 'Element 1 3';

            // Level 3

            $InteractiveArrData['ElementSelect']['select_data'][0]['select_data'][0]['select_value'] = 'Value_Element_2_1';
            $InteractiveArrData['ElementSelect']['select_data'][0]['select_data'][0]['select_name'] = 'Element 2 1';
            $InteractiveArrData['ElementSelect']['select_data'][0]['select_data'][0]['select_text'] = 'Text select Element_2';
            $InteractiveArrData['ElementSelect']['select_data'][0]['select_data'][0]['select_key'] = 'Key_Element_3';

            // Level 4

            $InteractiveArrData['ElementSelect']['select_data'][0]['select_data'][0]['select_data'] = [];
        }

        // Structure where the questions are the same and consistent
        if($TypeSelect=='simple'){
            $InteractiveArrData['TypeSelect'] = 'simple';

            // Choice 1

            $InteractiveArrData['ElementsSelect'][] = [
                'columns' => '2',
                'select_value' => 'Value_Element_0',
                'select_name' => 'Element 1',
                'select_text' => 'Text select Element_1',
                'select_key' => 'Key_Element_1',
                'select_data' => [
                    ['select_value' => 'Value_Element_1_1', 'select_name' => 'Element 1 1'],
                    ['select_value' => 'Value_Element_1_2', 'select_name' => 'Element 1 2'],
                ]
            ];

            // Choice 2

            $InteractiveArrData['ElementsSelect'][] = [
                'select_value' => 'Value_Element_0',
                'select_name' => 'Element 2',
                'select_text' => 'Text select Element_2',
                'select_key' => 'Key_Element_2',
                'select_data' => [
                    ['select_value' => 'Value_Element_2_1', 'select_name' => 'Element 2 1'],
                    ['select_value' => 'Value_Element_2_2', 'select_name' => 'Element 2 2'],
                ]
            ];


        }

        return $InteractiveArrData;
    }


    public function exampleTG(){
        $messageTextLower = '/sd_0_0';

        $InteractiveArrData = \modules\telegram\services\sInteractive::instance()->getExampleInteractiveArrData('simple');
        $InteractiveKeysStr = explode(' ', $messageTextLower)[0];
        $InteractiveResData = \modules\telegram\services\sInteractive::instance()->getInteractive('/sd', $InteractiveArrData, $InteractiveKeysStr);

        if(!empty($InteractiveResData['error'])){
            print_r($InteractiveResData);
            exit;
        }
        if(empty($InteractiveResData['outDataArr']['isFinish'])){
            if(empty($InteractiveResData['outDataArr']['editMarkup'])){
                //sTelegram::instance()->sendMessage($bot_token, $message_chat_id, $InteractiveResData['outDataArr']['select_text'], $InteractiveResData['outDataArr']['reply_markup'], $message_id);
            } else {
                //sTelegram::instance()->editMessageText($bot_token, $dataCallback['callback_query']['message']['chat']['id'], $dataCallback['callback_query']['message']['message_id'], $InteractiveResData['outDataArr']['select_text'], $InteractiveResData['outDataArr']['reply_markup']);
                //sTelegram::instance()->editMessageReplyMarkup($bot_token, $dataCallback['callback_query']['message']['chat']['id'], $dataCallback['callback_query']['message']['message_id'], '', $InteractiveResData['outDataArr']['reply_markup']);
            }
            exit;
        }
        if(!empty($dataCallback['callback_query']['message']['message_id'])){
            //sTelegram::instance()->removeMessage($bot_token, $dataCallback['callback_query']['message']['chat']['id'],  $dataCallback['callback_query']['message']['message_id']); // remove
        }

        echo '<pre>';
        print_r($InteractiveResData);
        echo '</pre>';
    }
}