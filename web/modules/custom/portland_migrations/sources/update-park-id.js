const csv = require('csv-parser');
const fs = require('fs');
const createCsvWriter = require('csv-writer').createObjectCsvWriter;

var id_table = []
var data = []
fs.createReadStream('Park_PropertyID_LUT_20191121.csv')
    .pipe(csv())
    .on('data', (row) => {
        // console.log(row);
        id_table[row['POL_Prop_ID']] = row['PROPERTYID']
    })
    .on('end', () => {

        fs.createReadStream('parks.csv')
            .pipe(csv())
            .on('data', (row) => {
                // console.log(row);
                row['NewPropertyID'] = id_table[row['PropertyID']]
                data.push(row)
            })
            .on('end', () => {
                console.log('CSV file successfully processed');

                const csvWriter = createCsvWriter({
                    path: 'out.csv',
                    // PropertyID,Property,Address,YearAcquired,OwnedAcres,PolParkFinder,Zip,SubArea,SpecialInfo,ProgramInfo,HistoricalInfo,Phone,AltProperty,PayToPark,Facebook,ActivityCatalog,ConstantContact,latitude,longitude
                    header: [
                        { id: 'PropertyID', title: 'PropertyID' },
                        { id: 'NewPropertyID', title: 'NewPropertyID' },
                        { id: 'Property', title: 'Property' },
                        { id: 'Address', title: 'Address' },
                        { id: 'YearAcquired', title: 'YearAcquired' },
                        { id: 'OwnedAcres', title: 'OwnedAcres' },
                        { id: 'PolParkFinder', title: 'PolParkFinder' },
                        { id: 'Zip', title: 'Zip' },
                        { id: 'SubArea', title: 'SubArea' },
                        { id: 'SpecialInfo', title: 'SpecialInfo' },
                        { id: 'ProgramInfo', title: 'ProgramInfo' },
                        { id: 'HistoricalInfo', title: 'HistoricalInfo' },
                        { id: 'Phone', title: 'Phone' },
                        { id: 'AltProperty', title: 'AltProperty' },
                        { id: 'PayToPark', title: 'PayToPark' },
                        { id: 'Facebook', title: 'Facebook' },
                        { id: 'ActivityCatalog', title: 'ActivityCatalog' },
                        { id: 'ConstantContact', title: 'ConstantContact' },
                        { id: 'latitude', title: 'latitude' },
                        { id: 'longitude', title: 'longitude' }
                    ]
                });
                csvWriter
                    .writeRecords(data)
                    .then(() => console.log('The CSV file was written successfully'));
            });
    });
