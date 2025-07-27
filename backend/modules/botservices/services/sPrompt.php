<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

namespace modules\botservices\services;

class sPrompt
{
    protected static $instance;

    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * @param $message_text - 'model_id: model \n img_width: 512'
     * @param $keyFirstRow - 'key_text'
     * @param $keysPrompt - ['model_id','img_width','img_height']
     * @return array
     */
    public function getPromptDataByMessage($message_text, $keyFirstRow, $keysPrompt) {
        $message_text = $this->removeCommand($message_text);

        $promptData=[];
        $rowsArr = explode("\n", $message_text);
        foreach($rowsArr as $rowString){
            $rowString = trim($rowString);
            $rowArr = explode(':', $rowString);
            if(!empty($rowArr[0]) && !empty($rowArr[1])){
                $rowArr[0] = mb_strtolower($rowArr[0]);
                if(in_array(trim($rowArr[0]), $keysPrompt)){
                    $rowValue = str_replace(trim($rowArr[0]).":", "", $rowString);
                    $promptData[trim($rowArr[0])] = trim($rowValue);
                }
            }
        }

        // If other parameters are not found, then we will write the entire text into the key prompt.
        if(empty($promptData) && !empty($message_text)){
            $prompt = strip_tags($message_text);
            $prompt = str_replace(["\r", "\n"], ' ', $prompt);
            $promptData[$keyFirstRow] = $prompt;
        }

        /*if(empty($promptData['prompt'])){
            $promptData['prompt'] = strip_tags($tgData['messageTextLower']);
            $promptData['prompt'] = str_replace(["\r", "\n"], ' ', $promptData['prompt']);
            print_r($promptData);
        }*/

        return ['error' => 0, 'data' => 'Success', 'rowsArr'=>$rowsArr, 'promptData'=> $promptData];
    }

    public function getMessageTextLower($message_text){
        $message_text = mb_strtolower($message_text);
        $message_text = $this->removeSpaces($message_text);
        return $message_text;
    }

    public function removeSpaces($message_text){
        $message_text = str_replace('  ', ' ', $message_text);
        $message_text = trim($message_text);
        return $message_text;
    }

    public function removeBotName($message_text, $bot_command){
        if (stripos($message_text, '@') === false) {
            return $message_text;
        }
        $message_text = $this->removeSpaces($message_text);
        $ArrWords = explode(' ', $message_text);

        // We delete the bot name, for example we replace /ai@Name_bot -> /ai
        //$ArrWords[0] = preg_replace('/(.*)(\/'.$bot_command.'@[^ ]*)(.*)/', '/'.$bot_command.' $1$3', mb_strtolower($ArrWords[0]));
        //$ArrWords[0] = preg_replace('/(.*)(\/'.$bot_command.'\@[^ ]*)(.*)/', '/'.$bot_command.' $1$3', mb_strtolower($ArrWords[0]));
        $ArrWords[0] = preg_replace('#(.*)(/' . $bot_command . '\@[^ ]*)(.*)#', '/' . $bot_command . ' $1$3', mb_strtolower($ArrWords[0]));

        $message_text_new = implode(' ', $ArrWords);

        $message_text_new = $this->removeSpaces($message_text_new);

        return $message_text_new;
    }

    public function removeCommand($message_text){
        $message_text = $this->removeSpaces($message_text);
        $ArrWords = explode(' ', $message_text);

        if(stripos($ArrWords[0], '/') !== false){
            if(stripos($ArrWords[0], "\n") !== false){
                $ArrWords[0] = explode("\n", $ArrWords[0])[1];
            } else {
                unset($ArrWords[0]);
            }
        }

        $message_text_new = implode(' ', $ArrWords);
        $message_text_new = $this->removeSpaces($message_text_new);

        return $message_text_new;


        //$message_text = str_replace('/'.$bot_command, '', $message_text);
        //$message_text = $this->removeSpaces($message_text);
        //return $message_text;
    }

    public function isEnglishText($text) {
        return preg_match('/^[\x00-\x7F]+$/', $text); // ASCII only
    }
}