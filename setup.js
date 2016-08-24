/**
 * Set poll interval for you
 */
var OZW = require('openzwave-shared'),
    interval = 120000; // 120000 = 2 min = 120 sec

var zwave = new OZW({
    Logging: true,     // disable file logging (OZWLog.txt)
    ConsoleOutput: false // disable console logging
});

zwave.on('polling enabled', function (nodeid) {
    console.log('Polling enabled on ' + nodeid);
})

zwave.on('scan complete', function () {
    console.log('Current Poll: ' + zwave.getPollInterval());
    zwave.setPollInterval(interval);
    console.log('New Poll: ' + zwave.getPollInterval());
    zwave.disconnect();
});

zwave.connect('/dev/ttyACM0');

process.on('SIGINT', function () {
    console.log('disconnecting...');
    zwave.disconnect();
    process.exit();
});



