/**

 @TODO:
 - Log to DB or json files
 - Add Pusher or sockets notification
 - Write parser for VW driving data
 - create JS page on mikhailkozlov.com that will show current state
 - make it run as service
 - write up so people do not have to strugle
 **/

// load the modern build
var _ = require('lodash');
var OpenZWave = require('openzwave');
var winston = require('winston');
var moment = require('moment');
var Firebase = require("firebase");
var request = require('request');

winston.add(winston.transports.File, {filename: 'storage/logs/energy.log'});
winston.remove(winston.transports.Console);

var zwave = new OpenZWave('/dev/cu.usbmodem1431');

zwave.connect();

zwave.on('driver failed', function () {
    console.log('failed to start driver');
    zwave.disconnect();
    process.exit();
});

// node def
var nodes = {
    2: {
        label: "Car Changer",
        slug: "evcs",
        total: 0, // we probably need to read this from DB
        status: [
            {
                label: "Idle",
                range: [0, 20]
            },
            {
                label: "Climate On",
                range: [20, 200]
            },
            {
                label: "Charging",
                range: [201, 8000]
            }
        ]
    },
    3: {
        label: "AC Fan",
        slug: "acfan",
        total: 0,
        status: [
            {
                label: "Off",
                range: [0, 20]
            },
            {
                label: "On",
                range: [20, 2000]
            }
        ]

    }
};

// labeles to monitor
var labels = ['Energy', 'Power'];

// main connection
var myFirebaseRef = new Firebase("https://vivid-heat-6441.firebaseio.com/");

// login
myFirebaseRef.authWithCustomToken('TOKEN', function (error, authData) {
    if (error) {
        console.log("Login Failed!", error);
    }
});

// read data from Fire
_.forEach(nodes, function (details, id) {
    // name them
    zwave.setName(id, details.label);

    // read last total
    myFirebaseRef.child(details.slug + '/total').once("value", function (snap) {

        nodes[id].total = snap.val();
    });
});

// listen to events from z-wire
zwave.on('value changed', function (nodeid, comclass, value) {

    // we only care about main events on entire controller
    if (labels.indexOf(value.label) < 0 && value.instance != 1) {
        return;
    }

    // log data locally
    winston.log('info', nodes[nodeid].label + ' - ' + value.label, {node: nodeid, value: value});

    // send data to home server
    request.post('http://home:8000/api/v1/' + value.label.toLowerCase()).form({
        sensor_id: nodeid,
        node: nodeid,
        instance: value.instance,
        value: value.value
    }).on('error', function (err) {
        console.log(err)
    });

    // time
    var now = moment();

    // we have Power update
    if (value.label == 'Power') {
        var status = {
            status: '',
            value: value.value,
            time: moment().format()
        };

        _.forEach(nodes[nodeid].status, function (i, s) {
            if (_.inRange(parseInt(value.value, 10), i.range[0], i.range[1])) {
                status.status = i.label;
            }
        });

        // update record
        myFirebaseRef.child(nodes[nodeid].slug + '/status').set(status);
    }
});

zwave.on('scan complete', function () {
    console.log('Scan complete. Listening for events. Hit ^C to finish.');
});

process.on('SIGINT', function () {
    // console.log(nodes);
    console.log('disconnecting...');
    zwave.disconnect();
    process.exit();
});

setTimeout(function () {
    zwave.disconnect();
    process.exit();
}, 1700000);
