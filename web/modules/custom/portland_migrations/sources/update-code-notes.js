const csv = require('csv-parser');
const fs = require('fs');
const createCsvWriter = require('csv-writer').createObjectCsvWriter;

var titles_raw = {}, chapters_raw = {}
fs.createReadStream('citycode_export.csv')
    .pipe(csv())
    .on('data', (row) => {
      if(row['CONTENT_NAME'].startsWith('-')) {
        if(row['CATEGORY_NAME'].startsWith('Title')) {
          titles_raw[row['CATEGORY_NAME']] = row
        }
        else if(row['CATEGORY_NAME'].startsWith('Chapter')) {
          chapters_raw[row['CATEGORY_NAME']] = row
        }
      }
    })
    .on('end', () => {
    
      var titles=[]
      fs.createReadStream('city_code_chapters.csv')
      .pipe(csv())
      .on('data', (row) => {
        // id,title,name,number,documents,url,path_part

        // Find the notes and add a new column
        titles.push({
          'id': row['id'],
          'title': row['title'],
          'name': row['name'],
          'number': row['number'],
          'documents': row['documents'],
          'url': row['url'],
          'path_part': row['path_part'],
          'note': ( chapters_raw[row['name']] ? chapters_raw[row['name']]['TEXT'] : ''),
        })
      })
      .on('end', () => {
        const csvWriter = createCsvWriter({
            path: 'out_chapters.csv',
            // id,chapter_id,number,text,name,url
            header: [
              { id: 'id', title: 'id' },
              { id: 'title', title: 'title' },
              { id: 'name', title: 'name' },
              { id: 'number', title: 'number' },
              { id: 'documents', title: 'documents' },
              { id: 'url', title: 'url' },
              { id: 'path_part', title: 'path_part' },
              { id: 'note', title: 'note' },
            ]
        });
        csvWriter
            .writeRecords(titles)
            .then(() => console.log('The CSV file was written successfully'));
      })

    });
