#!/usr/bin/env node

require('dotenv').config(); // load config
var OZW = require('openzwave-shared'), // load z-wave
    request = require('request'); // load request

var zwave = new OZW({
    Logging: false,     // disable file logging (OZWLog.txt)
    ConsoleOutput: false // disable console logging
});

zwave.on('scan complete', function () {
    console.log('Scan complete. Listening for events. Hit ^C to finish.');
});

zwave.on('driver failed', function () {
    console.log('driver failed');
    console.log('trying to disconnect...');
    zwave.disconnect();
});

// labeles to monitor
var labels = ['Energy', 'Power'];

zwave.on('value changed', function (nodeid, comclass, value) {

    // we only care about main events on entire controller
    if (labels.indexOf(value.label) < 0 && value.instance != 1) {
        return false;
    }

    // send data to home server
    request.post({
        url: process.env.API_SERVER + '/api/v1/energy', form: {
            sensor_id: nodeid,
            node: nodeid,
            instance: value.instance,
            value: value.value
        }, headers: {
            'API-TOKEN': process.env.API_TOKEN
        }
    }, function (err, httpResponse, body) {
        console.log(err); // MK Logger
        // in most cases we do not care, but we want to see error
        //if (httpResponse.statusCode != 200) {
        //    console.log('Error pushing data: ' + httpResponse.statusMessage); // MK Logger
        //}
    });
});

zwave.connect('/dev/ttyACM0');

process.on('SIGINT', function () {
    // console.log(nodes);
    console.log('disconnecting...');
    zwave.disconnect();
    process.exit();
});

