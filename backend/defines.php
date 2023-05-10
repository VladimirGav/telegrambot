<?php
$backend = __DIR__;

// Если запускаем через консоль windows
if(!empty($_SERVER['argv'][1]) && $_SERVER['argv'][1]=='console' && !empty($_SERVER['argv'][2])){
    define('_PHP_Path_dir_', $_SERVER['argv'][2]);
}

// Установим composer
$dirComposer = $backend.'/composer';
require_once $backend .'/core/installComposer.php';
\installComposer::instance()->installComposerStart($dirComposer);

/** Пример обработки сообщений телеграм бота */
require_once $dirComposer .'/vendor/autoload.php';

/*
 * Домашняя ссылка
 */
$HomeUri = '';
if(isset($_SERVER['HTTP_HOST'])){
    $protokol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
    $HomeUri = $protokol.$_SERVER['HTTP_HOST'];
}

define('_HOME_URL_', $HomeUri);

$dirSettings = __DIR__.'/settings';
if (!is_dir($dirSettings)) {
    if (!@mkdir($dirSettings, 0777, true)) {
        exit('Не удалось создать папку для настроек '.$dirSettings);
    }
}
define('_FILE_bot_settings_', $dirSettings.'/bot_settings.json');
define('_FILE_bot_token_', $dirSettings.'/bot_token.txt');
define('_FILE_api_gpt_', $dirSettings.'/api_gpt.txt');



