var http = require('http')

var options = {
  host: 'ipecho.net',
  port: 80,
  path: '/plain'
};

http.get(options, function(res) {
    console.log('STATUS: ' + res.statusCode);
    console.log('HEADERS: ' + JSON.stringify(res.headers));
    res.setEncoding('utf8');
    res.on('data', function (chunk) {
        console.log('BODY: ' + chunk);
    });
}).on('error', function(e) {
  console.log("Got error: " + e.message);
});
