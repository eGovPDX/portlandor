// global_utilities.js
class GlobalUtilities {

    static debug(message) {
        console.log(message);
    }

    static locationFactory(type, result) {
        var location = {};
        if (type == "intersects") {
            location.address = result.detail.taxlot ? result.detail.taxlot[0].address.trim() : "";
            location.city = result.detail.taxlot ? result.detail.taxlot[0].city.toUpperCase() : "";
            location.county = result.detail.county ? result.detail.county[0].name.toUpperCase() : "";
            location.state = result.detail.taxlot ? result.detail.taxlot[0].state : "";
            location.zip = result.detail.taxlot ? result.detail.taxlot[0].zip_code : "";
            location.taxlotId = result.detail.taxlot ? result.detail.taxlot[0].taxlot_id : "";
            location.park = result.park;
            location.row = result.row;
            location.stream = result.stream;
            location.street = result.street;
            location.taxlot = result.taxlot;
            location.trail = result.trail;
            location.waterbody = result.waterbody;
            location.lat = result.latlng.lat;
            location.lng = result.latlng.lng;
            location.x = result.xy.x;
            location.y = result.xy.y;
            location.fullAddress = location.address + ", " + location.city + ", " + location.state + " " + location.zip;
            location.displayAddress = location.address + ", " + location.city + " " + location.zip;
        }
        return location;
    }

    static getPropertyByPath(jsonObject, path) {
        const keys = path.split('.');

        return keys.reduce((obj, key) => {
            if (!obj) return undefined;

            // Check if the key includes an array index, like 'features[0]'
            const arrayIndexMatch = key.match(/(.+)\[(\d+)\]$/);

            if (arrayIndexMatch) {
                const arrayKey = arrayIndexMatch[1];
                const index = arrayIndexMatch[2];
                return obj[arrayKey] && obj[arrayKey][index] !== undefined ? obj[arrayKey][index] : undefined;
            } else {
                return obj[key] !== undefined ? obj[key] : undefined;
            }
        }, jsonObject);
    }




}
