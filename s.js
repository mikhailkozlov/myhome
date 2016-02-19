#!/usr/bin/env node

var OZW = require('openzwave-shared');
var request = require('request');
require('dotenv').config();

console.log('process.env.DB_HOST ' + process.env.DB_HOST);

var zwave = new OZW({
    Logging: false,     // disable file logging (OZWLog.txt)
    ConsoleOutput: false // disable console logging
});


zwave.on('polling enabled', function(nodeid){
	console.log('Polling enabled on '+nodeid);
})

zwave.on('scan complete', function () {
	console.log('Poll: '+zwave.getPollInterval());
	zwave.setPollInterval(120000);
//	zwave.setPollInterval(3, 120000);
//	console.log('Poll: '+zwave.getPollInterval());
//console.log('intensity: '+ zwave.getPollIntensity());



	console.log('Scan complete. Listening for events. Hit ^C to finish.');
});

zwave.on('node ready', function(nodeid, nodeinfo){
	if(nodeid == 2 || nodeid == 3){
//		zwave.setPollInterval(nodeid, 120000);
//		zwave.enablePoll(nodeid, 50, 30000);
	}
});

zwave.on('driver failed', function(){
	console.log('driver failed');
});

// labeles to monitor
var labels = ['Energy', 'Power'];

zwave.on('value changed', function (nodeid, comclass, value) {

    // we only care about main events on entire controller
    if (labels.indexOf(value.label) < 0 && value.instance != 1) {
        return false;
    }

// send data to home server
    request.post('http://127.0.0.1/api/v1/' + value.label.toLowerCase()).form({
        sensor_id: nodeid,
        node: nodeid,
        instance: value.instance,
        value: value.value
    }).on('error', function (err) {
        console.log(err)
    });
});

zwave.connect('/dev/ttyACM0');

process.on('SIGINT', function () {
    // console.log(nodes);
    console.log('disconnecting...');
    zwave.disconnect();
    process.exit();
});

