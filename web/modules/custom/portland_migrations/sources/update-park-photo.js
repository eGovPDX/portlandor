const csv = require('csv-parser');
const fs = require('fs');
const createCsvWriter = require('csv-writer').createObjectCsvWriter;

var park_rows = []
var data = []
fs.createReadStream('out.csv')
    .pipe(csv())
    .on('data', (row) => {
        // park_rows.push(row)
        if(row['PropertyID'] === '999999')
        console.log(row)
    })
    .on('end', () => {

        process.exit()

        fs.createReadStream('park_photos_new.csv')
            .pipe(csv())
            .on('data', (row) => {

                let filename = row['filename']
                    .split('.').slice(0, -1).join('.')
                    .replace(/\-/g, ' ');

                let searchParkResult = false
                for(var i=0; i<park_rows.length; i++){
                    let park = park_rows[i]
                    if(filename.startsWith(park['Property'].toLowerCase())) {
                        searchParkResult = true;
                        row['PropertyID'] = park['PropertyID']
                        row['filename_converted'] = filename
                        row['park_name'] = park['Property'].toLowerCase()
                        break;
                    }
                }

                if(!searchParkResult) {
                    row['PropertyID'] = 999999
                }
                data.push(row)
                // process.exit()
            })
            .on('end', () => {
                console.log('CSV file successfully processed');

                const csvWriter = createCsvWriter({
                    path: 'out.csv',
                    // PropertyID,Property,Address,YearAcquired,OwnedAcres,PolParkFinder,Zip,SubArea,SpecialInfo,ProgramInfo,HistoricalInfo,Phone,AltProperty,PayToPark,Facebook,ActivityCatalog,ConstantContact,latitude,longitude
                    header: [
                        { id: 'PropertyID', title: 'PropertyID' },
                        { id: 'filename', title: 'filename' }
                    ]
                });
                csvWriter
                    .writeRecords(data)
                    .then(() => console.log('The CSV file was written successfully'));
            });
    });
