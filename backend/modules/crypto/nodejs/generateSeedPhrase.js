const bip39 = require('./../../../../forwindows/nodejs/node-v18.16.0-win-x64/project/node_modules/bip39');

const generateSeedPhrase = () => {
    const mnemonic = bip39.generateMnemonic();
    return mnemonic;
};

const seedPhrase = generateSeedPhrase();
console.log(JSON.stringify({error:0, data:'success', seed:seedPhrase}));