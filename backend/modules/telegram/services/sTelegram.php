<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

namespace modules\telegram\services;

class sTelegram
{

    public $telegramApi = 'https://api.telegram.org/bot';

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


    /**
     * Отправить сообщение
     * @param $bot_token
     * @param $chat_id
     * @param $text
     * @return array
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function sendMessage($bot_token, $chat_id, $text, $reply_markup='', $reply_to_message_id=''){
        $telegram = new \Telegram\Bot\Api($bot_token);
        $dataMessage=[];
        $dataMessage['chat_id']=$chat_id;
        $dataMessage['text']=$text;
        if(!empty($reply_markup)){
            $dataMessage['reply_markup']=$reply_markup;
        }
        if(!empty($reply_to_message_id)){
            $dataMessage['reply_to_message_id']=$reply_to_message_id;
        }
        $dataMessage['parse_mode']='HTML';
        if($dataMessage['parse_mode']=='HTML'){
            $dataMessage['text'] = str_replace("<br>", PHP_EOL, $dataMessage['text']);
            $dataMessage['text'] = str_replace("<br />", PHP_EOL, $dataMessage['text']);
            $dataMessage['text'] = strip_tags($dataMessage['text'], '<b><strong><i><em><u><ins><s><strike><del><span><tg-spoiler><b><a><code><pre>');
        }
        $dataMessage['disable_web_page_preview'] = true;

        $getMessageId=0;
        $error=0;
        $dataText='';

        try {
            $sendMessageData = $telegram->sendMessage($dataMessage);
            $getMessageId = $sendMessageData->getMessageId();
            $dataText = 'Успешно';
        } catch (\Exception $e) {
            $dataText = 'Exception: '.  $e->getMessage();
            $error = 1;
        }


        return ['error' => $error, 'data' => $dataText, 'MessageId'=>$getMessageId];
    }

    public function removeMessage($bot_token, $chat_id, $message_id)
    {
        $telegram = new \Telegram\Bot\Api($bot_token);
        $error=0;
        $dataText = 'Успешно';

        try {
            $deleteMessageData = $telegram->deleteMessage(['chat_id'=>$chat_id, 'message_id'=> $message_id]);
        } catch (\Exception $e) {
            $dataText = 'Exception: '.  $e->getMessage();
            $error = 1;
        }

        return ['error' => $error, 'data' => $dataText];
    }

    /**
     * Устанавливаем связь с ботом и задаем url api
     * @param $bot_token
     * @param $website_url
     * @return false|string
     */
    public function setWebhook($bot_token, $website_url){
        ob_start();
        echo file_get_contents($this->telegramApi.$bot_token.'/setWebhook?url='.$website_url);
        $contents = ob_get_contents();             //  Instead, output above is saved to $contents
        ob_end_clean();
        return $contents;
    }

    public function getWebhookUpdate($bot_token){
        $telegram = new \Telegram\Bot\Api($bot_token);
        $dataMessage = $telegram->getWebhookUpdate();
        return $dataMessage;
    }

    public function checkApi($bot_token){
        $telegram = new \Telegram\Bot\Api($bot_token);
        $response = $telegram->getMe();
        return $response;
    }

    /**
     * Use this method to remove a previously set outgoing webhook.
     * @param $bot_token
     * @return \Telegram\Bot\TelegramResponse
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function removeWebhook($bot_token){
        //exit;
        $telegram = new \Telegram\Bot\Api($bot_token);
        try {
            $response = $telegram->removeWebhook();
        } catch (\Exception $e) {
            return ['error'=>1, 'data' => 'Telegram Bot API Token error'];
        }
        return ['error'=>0, 'data' => $response];
    }


    /**
     * Получаем последнее сообщение избегая повторов
     * @param $bot_token
     * @return array|\Telegram\Bot\Objects\Update
     */
    public function getWebhookLastMessage($bot_token){
        $LatestMessage=[];
        $lastUpdateId = $this->getLastUpdateId($bot_token);
        $dataMessage = $this->getWebhookUpdate($bot_token);

        if(!empty($dataMessage['update_id']) && $dataMessage['update_id'] != $lastUpdateId){
            $this->saveLastUpdateId($bot_token, $dataMessage['update_id']);
            $LatestMessage = $dataMessage;
        }
        return $LatestMessage;
    }

    /**
     * Получаем последнее сообщение избегая повторов
     * @param $bot_token
     * @return array|mixed
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function getUpdatesLastMessage($bot_token){
        $LatestMessage=[];
        $params=[];

        $lastUpdateId = $this->getLastUpdateId($bot_token);
        $params['offset']=$lastUpdateId;
        $telegram = new \Telegram\Bot\Api($bot_token);
        try {
            $UpdatesData = $telegram->getUpdates($params);
        } catch (Exception $e) {
            return ['error'=>1, 'data' => 'Telegram Bot API Token error'];
        }
        $UpdatesDataArr = [];
        if(!empty($UpdatesData) && is_array($UpdatesData)){
            foreach ($UpdatesData as $UpdateData){
                $UpdatesDataArr[]=$UpdateData->toArray();
            }
        }

        $nextid=($lastUpdateId>0)?1:0;
        if(!empty($UpdatesDataArr[$nextid]['update_id'])){
            $this->saveLastUpdateId($bot_token, $UpdatesDataArr[$nextid]['update_id']);
            $LatestMessage = $UpdatesDataArr[$nextid];
        }
        return $LatestMessage;
    }

    public function getLastUpdateId($bot_token){
        $FileLastUpdateId = $this->getFileLastUpdateId($bot_token);
        $lastUpdateId = 0;
        if(file_exists($FileLastUpdateId)){
            $lastUpdateId = file_get_contents($FileLastUpdateId);
        }
        return $lastUpdateId;
    }

    public function saveLastUpdateId($bot_token, $lastUpdateId){
        $FileLastUpdateId = $this->getFileLastUpdateId($bot_token);
        $dirFile = dirname($FileLastUpdateId);

        if (!is_dir($dirFile)) { if (!@mkdir($dirFile, 0755, true)) { return false; } }
        file_put_contents($FileLastUpdateId, $lastUpdateId);
        return true;
    }

    public function getFileLastUpdateId($bot_token){
        $botName = substr($bot_token,-6);
        return __DIR__.'/telegram-ids/last-id-'.$botName.'.txt';
    }

    /**
     * A simple method for testing your bot's auth token.
     * @param $bot_token
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function getMe($bot_token){
        $telegram = new \Telegram\Bot\Api($bot_token);
        $response = $telegram->getMe();
        $botId = $response->getId();
        $firstName = $response->getFirstName();
        $username = $response->getUsername();
    }

    public function forwardMessage($bot_token, $chat_id, $from_chat_id, $message_id)
    {
        $telegram = new \Telegram\Bot\Api($bot_token);
        $response = $telegram->forwardMessage([
            'chat_id' => $chat_id,
            'from_chat_id' => $from_chat_id,
            'message_id' => $message_id
        ]);

        $messageId = $response->getMessageId();
    }

    public function sendPhoto($bot_token, $chat_id, $file, $caption='', $reply_to_message_id='')
    {
        if(!file_exists($file)){
            return ['error' => 1, 'data' => 'File not found'];
        }
        $InputFile = \Telegram\Bot\FileUpload\InputFile::create($file);
        $telegram = new \Telegram\Bot\Api($bot_token);

        $dataMessage=[];
        $dataMessage['chat_id']=$chat_id;
        $dataMessage['photo']=$InputFile;
        if(!empty($caption)){ $dataMessage['caption']=$caption; }
        if(!empty($reply_to_message_id)){ $dataMessage['reply_to_message_id']=$reply_to_message_id; }

        $getMessageId=0;
        $error=0;
        try {
            $sendMessageData = $telegram->sendPhoto($dataMessage);
            $getMessageId = $sendMessageData->getMessageId();
            $dataText = 'Успешно';
        } catch (\Exception $e) {
            $dataText = 'Exception: '.  $e->getMessage();
            $error = 1;
        }

        return ['error' => $error, 'data' => $dataText, 'MessageId'=>$getMessageId];
    }

    public function sendAudio($bot_token, $chat_id, $file, $caption='', $reply_to_message_id='')
    {
        if(!file_exists($file)){
            return ['error' => 1, 'data' => 'File not found'];
        }
        $InputFile = \Telegram\Bot\FileUpload\InputFile::create($file);
        $telegram = new \Telegram\Bot\Api($bot_token);

        $dataMessage=[];
        $dataMessage['chat_id']=$chat_id;
        $dataMessage['audio']=$InputFile;
        if(!empty($caption)){ $dataMessage['caption']=$caption; }
        if(!empty($reply_to_message_id)){ $dataMessage['reply_to_message_id']=$reply_to_message_id; }

        $getMessageId=0;
        $error=0;
        try {
            $sendMessageData = $telegram->sendAudio($dataMessage);
            $getMessageId = $sendMessageData->getMessageId();
            $dataText = 'Успешно';
        } catch (\Exception $e) {
            $dataText = 'Exception: '.  $e->getMessage();
            $error = 1;
        }

        return ['error' => $error, 'data' => $dataText, 'MessageId'=>$getMessageId];
    }

    public function sendDocument($bot_token, $chat_id, $file, $caption='', $reply_to_message_id='')
    {
        if(!file_exists($file)){
            return ['error' => 1, 'data' => 'File not found'];
        }
        $InputFile = \Telegram\Bot\FileUpload\InputFile::create($file);
        $telegram = new \Telegram\Bot\Api($bot_token);

        $dataMessage=[];
        $dataMessage['chat_id']=$chat_id;
        $dataMessage['document']=$InputFile;
        if(!empty($caption)){ $dataMessage['caption']=$caption; }
        if(!empty($reply_to_message_id)){ $dataMessage['reply_to_message_id']=$reply_to_message_id; }

        $getMessageId=0;
        $error=0;
        try {
            $sendMessageData = $telegram->sendDocument($dataMessage);
            $getMessageId = $sendMessageData->getMessageId();
            $dataText = 'Успешно';
        } catch (\Exception $e) {
            $dataText = 'Exception: '.  $e->getMessage();
            $error = 1;
        }

        return ['error' => $error, 'data' => $dataText, 'MessageId'=>$getMessageId];
    }

    public function sendVideo($bot_token, $chat_id, $file, $caption='', $reply_to_message_id='')
    {
        if(!file_exists($file)){
            return ['error' => 1, 'data' => 'File not found'];
        }
        $InputFile = \Telegram\Bot\FileUpload\InputFile::create($file);
        $telegram = new \Telegram\Bot\Api($bot_token);

        $dataMessage=[];
        $dataMessage['chat_id']=$chat_id;
        $dataMessage['video']=$InputFile;
        if(!empty($caption)){ $dataMessage['caption']=$caption; }
        if(!empty($reply_to_message_id)){ $dataMessage['reply_to_message_id']=$reply_to_message_id; }

        $getMessageId=0;
        $error=0;
        try {
            $sendMessageData = $telegram->sendVideo($dataMessage);
            $getMessageId = $sendMessageData->getMessageId();
            $dataText = 'Успешно';
        } catch (\Exception $e) {
            $dataText = 'Exception: '.  $e->getMessage();
            $error = 1;
        }

        return ['error' => $error, 'data' => $dataText, 'MessageId'=>$getMessageId];
    }

    public function sendVoice($bot_token, $chat_id, $file, $caption='', $reply_to_message_id='')
    {
        if(!file_exists($file)){
            return ['error' => 1, 'data' => 'File not found'];
        }
        $InputFile = \Telegram\Bot\FileUpload\InputFile::create($file);
        $telegram = new \Telegram\Bot\Api($bot_token);

        $dataMessage=[];
        $dataMessage['chat_id']=$chat_id;
        $dataMessage['voice']=$InputFile;
        if(!empty($caption)){ $dataMessage['caption']=$caption; }
        if(!empty($reply_to_message_id)){ $dataMessage['reply_to_message_id']=$reply_to_message_id; }

        $getMessageId=0;
        $error=0;
        try {
            $sendMessageData = $telegram->sendVoice($dataMessage);
            $getMessageId = $sendMessageData->getMessageId();
            $dataText = 'Успешно';
        } catch (\Exception $e) {
            $dataText = 'Exception: '.  $e->getMessage();
            $error = 1;
        }

        return ['error' => $error, 'data' => $dataText, 'MessageId'=>$getMessageId];
    }

    public function sendSticker($bot_token, $chat_id, $file, $reply_to_message_id='')
    {
        if(!file_exists($file)){
            return ['error' => 1, 'data' => 'File not found'];
        }
        $InputFile = \Telegram\Bot\FileUpload\InputFile::create($file);
        $telegram = new \Telegram\Bot\Api($bot_token);

        $dataMessage=[];
        $dataMessage['chat_id']=$chat_id;
        $dataMessage['sticker']=$InputFile;
        if(!empty($reply_to_message_id)){ $dataMessage['reply_to_message_id']=$reply_to_message_id; }

        $getMessageId=0;
        $error=0;
        try {
            $sendMessageData = $telegram->sendSticker($dataMessage);
            $getMessageId = $sendMessageData->getMessageId();
            $dataText = 'Успешно';
        } catch (\Exception $e) {
            $dataText = 'Exception: '.  $e->getMessage();
            $error = 1;
        }

        return ['error' => $error, 'data' => $dataText, 'MessageId'=>$getMessageId];
    }

    public function sendLocation($bot_token)
    {
        $telegram = new \Telegram\Bot\Api($bot_token);
        // Sends San Francisco, CA Location.
        $response = $telegram->sendLocation([
            'chat_id' => 'CHAT_ID',
            'latitude' => 37.7576793,
            'longitude' => -122.5076402,
        ]);

        $messageId = $response->getMessageId();
    }

    public function sendChatAction($bot_token)
    {
        $telegram = new \Telegram\Bot\Api($bot_token);
        $telegram->sendChatAction([
            'chat_id' => 'CHAT_ID',
            'action' => 'upload_photo'
        ]);
    }

    public function getUserProfilePhotos($bot_token)
    {
        $telegram = new \Telegram\Bot\Api($bot_token);
        $response = $telegram->getUserProfilePhotos(['user_id' => 'USER_ID']);

        $photos_count = $response->getTotalCount();
        $photos = $response->getPhotos();
    }

    public function getUpdates($bot_token)
    {
        $telegram = new \Telegram\Bot\Api($bot_token);
        $response = $telegram->getUpdates();
    }

    public function getFile($bot_token)
    {
        $telegram = new \Telegram\Bot\Api($bot_token);
        $response = $telegram->getFile(['file_id' => 'ABC12345XYZ6789...']);
    }


}