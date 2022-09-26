const https = require('https')
process.env["NODE_TLS_REJECT_UNAUTHORIZED"] = 0;

// https://portlandor.lndo.site/cron/VCoo1xmqcsDM8yoGnI4Sg3oGQIBSyu-D4n1soZORIeZXGwPu5j-ZZmoTRzKmQJa-YNIW-pPd9A
const options = {
  hostname: 'pe-330-employees.pantheonsite.io', //'portlandor.lndo.site',
  port: 443,
  path: '/cron/Ge_RWquuSZHUUhmMhFORfEV50bY7bImdMDe8LqI_b1eYMd7TQF0imCCMuxNorAh0DDo1HGdMig',
  // path: '/cron/VCoo1xmqcsDM8yoGnI4Sg3oGQIBSyu-D4n1soZORIeZXGwPu5j-ZZmoTRzKmQJa-YNIW-pPd9A',
  method: 'GET'
}

var count = 10000;
callback = function(response) {
  var str = '';

  //another chunk of data has been received, so append it to `str`
  response.on('data', function (chunk) {
    str += chunk;
  });

  //the whole response has been received, so we just print it out here
  response.on('end', function () {
    console.log(str);
    if( count > 0) {
      https.request(options, callback).end();
      count--;
    }
  });
}

https.request(options, callback).end();

