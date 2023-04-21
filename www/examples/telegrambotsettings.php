<?php
/**
 * VladimirGav
 * GitHub Website: https://vladimirgav.github.io/
 * GitHub: https://github.com/VladimirGav
 * Copyright (c)
 */

// Устанавливаем и подключаем Composer
require_once __DIR__.'/../../backend/defines.php';

/** Пример настройки связи с телеграм ботом */

use modules\telegram\services\sTelegram;

?>
<div class="telegramSetContent">
<p><b>Создание токена/бота</b></p>
<p>Заходим в Telegram и добавляем <a target="_blank" href="https://t.me/BotFather">@BotFather</a></p>
<p>Пишем ему:</p>
    <code>
    "/start" - Старт<br>
    "/newbot" - Новый бот<br>
    "Любое название бота" - Имя бота<br>
    "Name_bot" - Логин бота, должен заканчиваться на bot<br>
        </code>
    <p>Если все в порядке приходит токен бота примерно такой "7345887:AAElClcpnLz8fGX2vEEaa"</p>
<form method="post">
    <p>Введите токен</p>
    <input name="bot_token" type="text" placeholder="7345887:AAElClcpnLz8fGX2vEEaa" value="">

    <p>Введите url api бота, требуется с https</p>
    <input name="website_url" type="text" value="<?= _HOME_URL_ ?>/examples/telegrambotapi.php">

    <p>Введите <a target="_blank" href="https://platform.openai.com/account/api-keys">API KEY ChatGPT</a> (по желанию). Будут работать команды:</p>
    <code>
        /ai - любой вопрос. Отвечает ChatGPT<br>
        /img - описание картинки. - нейросеть ChatGPT рисует картинку<br>
    </code>
    <input name="api_gpt" type="text" value="">

    <!--<p>Stable Diffusion. <a target="_blank" href="https://beta.dreamstudio.ai/account">API KEY DreamStudio's</a> (по желанию). Будут работать команды:</p>
    <code>
        /sd - описание картинки. - нейросеть Stable Diffusion рисует картинку<br>
    </code>
    <input name="api_sd" type="text" value="">-->

    <button type="submit">Сохранить и задать url для бота</button>
</form>


<?php
// Зададим url для бота и сохраним токен
$dataPost = $_POST;
if(!empty($dataPost['bot_token']) && !empty($dataPost['website_url'])){

    // Задаем вебхук
    $setWebhook = sTelegram::instance()->setWebhook($dataPost['bot_token'], $dataPost['website_url']);
    echo '<p style="color: blue;">Задаем вебхук: '.$setWebhook.'</p>';

    // Сохраним токен в файл
    $text = $dataPost['bot_token'];
    file_put_contents(_FILE_bot_token_, $text);


}

if(!empty($dataPost['api_gpt'])){
    $text = $dataPost['api_gpt'];
    file_put_contents(_FILE_api_gpt_, $text);
}


?>
</div>

<style>
    .telegramSetContent {
        max-width: 1000px;
        /* margin: 0 auto; */
        background: #f3f3f3;
        padding: 15px;
        border-radius: 15px;
    }
    .telegramSetContent * {
        font-size: 14px;
    }
    .telegramSetContent input {
        height: 40px;
        width: 100%;
        margin-bottom: 5px;
        margin-top: 5px;
    }
    .telegramSetContent button {
        background: #5290ff;
        color: #fff;
        border: none;
        padding: 8px 10px;
        text-transform: uppercase;
        margin-top: 10px;
    }
</style>

