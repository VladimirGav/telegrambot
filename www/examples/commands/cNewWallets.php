<?php
$tgData['messageTextLower'] = \modules\botservices\services\sPrompt::instance()->removeBotName($tgData['message_text'], 'new_wallets');
if (stripos($tgData['messageTextLower'], '/new_wallets') !== false && !empty($BotSettings['enableWallets'])) {
    $tgData['messageTextLower'] = str_replace('/new_wallets', '', $tgData['messageTextLower']);
    $tgData['messageTextLower'] = trim($tgData['messageTextLower']);

    $countWallets = 1;
    if(!empty($tgData['messageTextLower'])){
        if((int)$tgData['messageTextLower']>1){
            $countWallets = (int)$tgData['messageTextLower'];
        }
    }

    $waitMessageData = \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $BotSettings['waitMessage'], '', $tgData['message_id']); // show waitMessage

    $ListWallets = '<b>Ethereum wallets ('.$countWallets.'): </b>'.PHP_EOL.PHP_EOL;
    for ($i = 1; $i <= $countWallets; $i++) {
        $WalletData = \modules\crypto\services\sCrypto::instance()->createWallet();
        $ListWallets .= '<b>Wallet '.$i.'</b>'.PHP_EOL;
        $ListWallets .= '<b>Address:</b> '.$WalletData['address'].PHP_EOL;
        $ListWallets .= '<b>PrivateKey:</b> '.$WalletData['privateKey'].PHP_EOL.PHP_EOL;
    }

    \modules\telegram\services\sTelegram::instance()->removeMessage($tgData['bot_token'], $tgData['message_chat_id'],  $waitMessageData['MessageId']); // remove waitMessage
    \modules\telegram\services\sTelegram::instance()->sendMessage($tgData['bot_token'], $tgData['message_chat_id'], $ListWallets, '', $tgData['message_id']);
    exit;
}