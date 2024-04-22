<?php

$tgData['messageTextLower'] = \modules\botservices\services\sPrompt::instance()->removeBotName($tgData['message_text'], 'new_seed');
if (stripos($tgData['messageTextLower'], '/new_seed') !== false && !empty($BotSettings['enableWallets'])) {
    $tgData['messageTextLower'] = str_replace('/new_seed', '', $tgData['messageTextLower']);
    $tgData['messageTextLower'] = trim($tgData['messageTextLower']);

    $countSeed = 1;
    if(!empty($tgData['messageTextLower'])){
        if((int)$tgData['messageTextLower']>1){
            $countSeed = (int)$tgData['messageTextLower'];
        }
    }

    $waitMessageData = \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $BotSettings['waitMessage'], '', $tgData['message_id']); // show waitMessage

    $ListSeed = '<b>Mnemonic Seed Phrases ('.$countSeed.'): </b>'.PHP_EOL.PHP_EOL;
    for ($i = 1; $i <= $countSeed; $i++) {
        $SeedData = \modules\crypto\services\sCrypto::instance()->generateSeedPhrase();
        $ListSeed .= '<b>'.$i.':</b> '.$SeedData['seed'].PHP_EOL;
    }

    \modules\telegram\services\sTelegram::instance()->removeMessage($tgData['bot_token'], $tgData['message_chat_id'],  $waitMessageData['MessageId']); // remove waitMessage
    \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $ListSeed, '', $tgData['message_id']);
    exit;
}