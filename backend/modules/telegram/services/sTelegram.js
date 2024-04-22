const TelegramBot = require('./../../../../forwindows/nodejs/node-v18.16.0-win-x64/project/node_modules/node-telegram-bot-api');


class sTelegram {

    // class constructor
    constructor(data = {}) {
        // Dapp data
        this.data = data;
        this.bot_token = '';
    }

    async sendMessage($bot_token, $chat_id, $text) {
        var bot = new TelegramBot($bot_token, {polling: false});
        try {
            var sendData = await bot.sendMessage($chat_id, $text, { parse_mode: 'HTML' });
            return {'error':0, 'data':'success', 'MessageId':sendData.message_id, 'sendData':sendData};
        } catch (err) {
            return {'error':1, 'data':'error: '+err};
        }
    }
}

module.exports = { sTelegram }; // nodejs