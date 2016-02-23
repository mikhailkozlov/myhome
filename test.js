var request = require('request'),
    logServer = 'http://home';

require('dotenv').config();


// send data to home server
request.post({
    url: logServer + '/api/v1/energy', form: {
        sensor_id: 1,
        node: 2,
        instance: 1,
        value: 10
    }, headers: {
        'API-TOKEN': process.env.API_TOKEN
    }
}, function (err, httpResponse, body) {
    if (httpResponse.statusCode != 200) {
        console.log('Error pushing data: ' + httpResponse.statusMessage); // MK Logger
    }
});

