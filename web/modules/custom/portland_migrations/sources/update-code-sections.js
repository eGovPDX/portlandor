const csv = require('csv-parser');
const fs = require('fs');
const createCsvWriter = require('csv-writer').createObjectCsvWriter;

var data = []
fs.createReadStream('city_code_sections.csv')
    .pipe(csv())
    .on('data', (row) => {
        var numbers = row['name'].match(/(\d+)/g);
        console.log(numbers[0], numbers[1], numbers[2])
        data.push({
          'id': numbers[0] + '-' + numbers[1] + '-' + numbers[2],
          'chapter_id': numbers[0] + '-' + numbers[1],
          'number': numbers[2],
          'name': row['name'],
          'url': row['url'],
          'text': row['text'],
        })
    })
    .on('end', () => {

        console.log('CSV file successfully processed');

        const csvWriter = createCsvWriter({
            path: 'out.csv',
            // id,chapter_id,number,text,name,url
            header: [
                { id: 'id', title: 'id' },
                { id: 'chapter_id', title: 'chapter_id' },
                { id: 'number', title: 'number' },
                { id: 'name', title: 'name' },
                { id: 'url', title: 'url' },
                { id: 'text', title: 'text' },
            ]
        });
        csvWriter
            .writeRecords(data)
            .then(() => console.log('The CSV file was written successfully'));
    });
