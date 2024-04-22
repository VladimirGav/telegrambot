<?php

$tgData['messageTextLower'] = \modules\botservices\services\sPrompt::instance()->removeBotName($tgData['message_text'], 'new_wallet');
if (stripos($tgData['messageTextLower'], '/new_wallet') !== false && !empty($BotSettings['enableWallets'])) {
    $tgData['messageTextLower'] = str_replace('/new_wallet', '', $tgData['messageTextLower']);
    $tgData['messageTextLower'] = trim($tgData['messageTextLower']);

    $countWallet = 1;
    if(!empty($tgData['messageTextLower'])){
        if((int)$tgData['messageTextLower']>1){
            $countWallet = (int)$tgData['messageTextLower'];
        }
    }

    $waitMessageData = \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $BotSettings['waitMessage'], '', $tgData['message_id']); // show waitMessage

    $WalletsData = \modules\crypto\services\sCrypto::instance()->createSeedWallet($countWallet);

    $ListWallets = '<b>Mnemonic Seed Phrases:</b> '.$WalletsData['seed'].''.PHP_EOL.PHP_EOL;

    $ListWallets .= '<b>Ethereum Accounts ('.$countWallet.'): </b>'.PHP_EOL.PHP_EOL;

    foreach ($WalletsData['accounts'] as $accountKey => $accountData){
        $ListWallets .= '<b>Account '.($accountKey+1).'</b>'.PHP_EOL;
        $ListWallets .= '<b>Address:</b> '.$accountData['address'].PHP_EOL;
        $ListWallets .= '<b>PrivateKey:</b> '.$accountData['privateKey'].PHP_EOL.PHP_EOL;
    }

    \modules\telegram\services\sTelegram::instance()->removeMessage($tgData['bot_token'], $tgData['message_chat_id'],  $waitMessageData['MessageId']); // remove waitMessage
    \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $ListWallets, '', $tgData['message_id']);
    exit;
}