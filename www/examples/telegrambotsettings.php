<?php
/** Пример настройки связи с телеграм ботом */

// Подключим автозагрузчик composer, defines
use modules\telegram\services\sTelegram;

require_once __DIR__ .'/../system/defines.php';
require_once __DIR__ .'/../system/vendor/autoload.php';

?>
<p><b>Создание токена/бота</b></p>
<p>Заходим в Telegram и добавляем @BotFather</p>
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

    <button type="submit">Задать url для бота</button>
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
    $fp = fopen(__DIR__."/bot_token.txt", "w");
    fwrite($fp, $text);
    fclose($fp);


}


?>

