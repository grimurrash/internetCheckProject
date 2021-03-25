const request = require('request');
const fs = require('fs')

let config;

fs.readFile('settings.json',function(err, data) {
    if (err) console.log('Ошибка загрузки настроек')

    config = JSON.parse(data)
})


setInterval(() => {
    request({
        method: 'GET',
        url: config.url,
        qs: {
            objectId: config.id
        }
    }, function (err, res, body) {
        if (!err && res.statusCode === 200) console.log('Время подключения обновлено')
        else console.log('Ошибка при подлючении к серверу')
    })
}, 5000)