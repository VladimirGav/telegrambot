<?php

namespace modules\telegram\services;

use Binance\API;
use modules\trader\models\mTraderCurrency;
use modules\trader\models\mTraderCurVal;
use modules\trader\models\mTraderCurValTemp;
use modules\trader\models\mTraderOrder;

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

    /**
     * Use this method to remove a previously set outgoing webhook.
     * @param $bot_token
     * @return \Telegram\Bot\TelegramResponse
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function removeWebhook($bot_token){
        $telegram = new \Telegram\Bot\Api($bot_token);
        $response = $telegram->removeWebhook();
        return $response;
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

    public function sendPhoto($bot_token)
    {
        $telegram = new \Telegram\Bot\Api($bot_token);
        $response = $telegram->sendPhoto([
            'chat_id' => 'CHAT_ID',
            'photo' => 'path/to/photo.jpg',
            'caption' => 'Some caption'
        ]);

        $messageId = $response->getMessageId();
    }

    public function sendAudio($bot_token)
    {
        $telegram = new \Telegram\Bot\Api($bot_token);
        $response = $telegram->sendAudio([
            'chat_id' => 'CHAT_ID',
            'audio' => 'path/to/audio.mp3',
        ]);

        $messageId = $response->getMessageId();
    }

    public function sendDocument($bot_token)
    {
        $telegram = new \Telegram\Bot\Api($bot_token);
        $response = $telegram->sendDocument([
            'chat_id' => 'CHAT_ID',
            'document' => 'path/to/document.pdf',
            'caption' => 'This is a document',
        ]);

        $messageId = $response->getMessageId();
    }

    public function sendSticker($bot_token)
    {
        $telegram = new \Telegram\Bot\Api($bot_token);
        $response = $telegram->sendSticker([
            'chat_id' => 'CHAT_ID',
            'sticker' => 'path/to/sticker.webp',
        ]);

        $messageId = $response->getMessageId();
    }

    public function sendVideo($bot_token)
    {
        $telegram = new \Telegram\Bot\Api($bot_token);
        $response = $telegram->sendVideo([
            'chat_id' => 'CHAT_ID',
            'video' => 'path/to/video.mp4',
        ]);

        $messageId = $response->getMessageId();
    }

    public function sendVoice($bot_token)
    {
        $telegram = new \Telegram\Bot\Api($bot_token);
        $response = $telegram->sendVoice([
            'chat_id' => 'CHAT_ID',
            'voice' => 'path/to/voice.ogg',
        ]);

        $messageId = $response->getMessageId();
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