const {Web3} = require('./../../../../forwindows/nodejs/node-v18.16.0-win-x64/project/node_modules/web3');
//const web3 = new Web3("https://etherscan.io");

const {hdkey} = require('./../../../../forwindows/nodejs/node-v18.16.0-win-x64/project/node_modules/ethereumjs-wallet');
const bip39 = require('./../../../../forwindows/nodejs/node-v18.16.0-win-x64/project/node_modules/bip39');

function showResult(dataResult) {
    console.log(JSON.stringify(dataResult));
    process.exit();
}

if (process.argv.length !== 3) {
    showResult({'error':1, 'data':'argv 3 empty'});
}

var numAccounts = process.argv[2];

async function generateAccountsFromSeed(seedPhrase, numAccounts) {
    try {
        // Проверяем валидность seed фразы
        if (!bip39.validateMnemonic(seedPhrase)) {
            throw new Error('Invalid seed phrase');
        }

        // Создаем HD Wallet из seed фразы
        const hdWallet = hdkey.fromMasterSeed(bip39.mnemonicToSeedSync(seedPhrase));

        const accounts = [];
        for (let i = 0; i < numAccounts; i++) {
            // Получаем производные аккаунты из HD Wallet
            const derivedWallet = hdWallet.derivePath(`m/44'/60'/0'/0/${i}`);
            const address = `0x${derivedWallet.getWallet().getAddress().toString('hex')}`;
            const privateKey = derivedWallet.getWallet().getPrivateKey().toString('hex');
            accounts.push({address, privateKey});
        }

        return accounts;
    } catch (error) {
        console.error('Error generating accounts:', error.message);
        return [];
    }
}

var generateSeedPhrase = () => {
    const mnemonic = bip39.generateMnemonic();
    return mnemonic;
};
var seedPhrase = generateSeedPhrase();

// Используем функцию для получения 5 аккаунтов из seed фразы
generateAccountsFromSeed(seedPhrase, numAccounts)
    .then((accounts) => {
        console.log(JSON.stringify({error:0, data:'success', seed:seedPhrase, accounts:accounts}));
    })
    .catch((err) => {
        console.log(JSON.stringify({error: 1, data: 'error', err: err}))
    });