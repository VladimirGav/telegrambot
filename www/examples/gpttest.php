<?php
exit;
use modules\telegram\services\sTelegram;
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Устанавливаем и подключаем Composer
require_once __DIR__.'/../../backend/defines.php';

if(!file_exists(_FILE_api_gpt_)){
    echo 'OpenAI API KEY is empty';
    exit;
}
$api_gpt = trim(file_get_contents(_FILE_api_gpt_));
if(empty($api_gpt)){
    echo 'OpenAI API KEY is empty';
    exit;
}

$messageTextLower = 'List the oceans';
$model_id = 'gpt-3.5-turbo';
$historyMessagesId=0;
$ChatGPTAnswerData = \modules\openai\services\sOpenAI::instance()->getChatGPTAnswer($api_gpt, $messageTextLower, $historyMessagesId, $model_id);
echo '<pre>';
print_r($ChatGPTAnswerData);
echo '</pre>';
