//const Web3 = require('./../../../../forwindows/nodejs/node-v18.16.0-win-x64/project/node_modules/web3');
const { Web3 } = require('./../../../../forwindows/nodejs/node-v18.16.0-win-x64/project/node_modules/web3');
const web3 = new Web3("https://etherscan.io");

var accountData = web3.eth.accounts.create(web3.utils.randomHex(32));
console.log(JSON.stringify(accountData));