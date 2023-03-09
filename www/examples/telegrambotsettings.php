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
<p><b>Создание токена/бота</b></p>
<p>Заходим в Telegram и добавляем <a target="_blank" href="https://t.me/BotFather">@BotFather</a></p>
<p>Пишем ему:<br>
    "/start" - Старт<br>
    "/newbot" - Новый бот<br>
    "Любое название" - Имя бота<br>
    "NameYoubot" - Логин бота, должен заканчиваться на bot<br>
    Если все в порядке приходит токен бота примерно такой "7345887:AAElClcpnLz8fGX2vEEaa"<br>

</p>
<form method="post">
    <p>Введите токен</p>
    <input name="bot_token" type="text" placeholder="7345887:AAElClcpnLz8fGX2vEEaa" value="">

    <p>Введите url api бота</p>
    <input name="website_url" type="text" value="<?= _HOME_URL_ ?>/examples/telegrambotapi.php">

    <p>Введите <a target="_blank" href="https://platform.openai.com/account/api-keys">api ChatGPT</a> (по желанию, будет работать команда: /ai ваш вопрос к ChatGPT)</p>
    <input name="api_gpt" type="text" value="">

    <button type="submit">Сохранить и задать url для бота</button>
</form>


<style>
    input {
        height: 40px;
        width: 100%;
        margin-bottom: 20px;
    }
</style>


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

