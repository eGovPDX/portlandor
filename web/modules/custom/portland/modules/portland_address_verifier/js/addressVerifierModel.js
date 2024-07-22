AddressVerifierModel.locationItem = function (data, $element = null, isSingleton = false) {

    // PortlandMaps sends incorrectly formatted address data if there is only 1 suggestion to return.
    // If a singleton, the isSingleton flag will be true, and we'll do some kludgey string manipulation.
    if (isSingleton) {
        var arrAddress = data.address.split(', ');
        data.address = arrAddress[0];
    }
    this.fullAddress = AddressVerifierModel.buildFullAddress(data.address, data.unit, data.attributes.jurisdiction, data.attributes.zip_code).toUpperCase();
    this.displayAddress = data.address.toUpperCase() + ', ' + data.attributes.jurisdiction.toUpperCase();
    this.street = data.address.toUpperCase();
    this.streetNumber = data.attributes.address_number;
    this.streetQuadrant = data.attributes.street_direction;
    this.streetDirectionSuffix = data.attributes.street_direction_suffix ? data.attributes.street_direction_suffix.trim() : "";
    this.streetName = data.attributes.street_name + " " + data.attributes.street_type;
    if (this.streetDirectionSuffix) {
        this.streetName += " " + this.streetDirectionSuffix;
    }
    this.city = data.attributes.jurisdiction.toUpperCase();
    this.state = data.attributes.state.toUpperCase();
    this.zipCode = data.attributes.zip_code;
    this.lat = data.attributes.lat;
    this.lon = data.attributes.lon;
    this.x = data.location.x;
    this.y = data.location.y;
    this.unit = "";
}

const REVERSE_GEOCODE_URL = 'https://www.portlandmaps.com/api/intersects/?geometry=%7B%20%22x%22:%20${x},%20%22y%22:%20${y},%20%22spatialReference%22:%20%7B%20%22wkid%22:%20%223857%22%7D%20%7D&include=all&detail=1&api_key=${apiKey}';


function AddressVerifierModel(jQuery, element, apiKey) {
    this.$ = jQuery;
    this.element = element;
    this.apiKey = apiKey;
    this.intersectsLocation = null;
}

AddressVerifierModel.prototype.fetchAutocompleteItems = function (addrSearch, $element) {
    const apiKey = this.apiKey;
    var apiUrl = `https://www.portlandmaps.com/api/suggest/?intersections=1&elements=1&landmarks=1&alt_coords=1&api_key=${apiKey}&query=${encodeURIComponent(addrSearch)}`;

    return this.$.ajax({
        url: apiUrl,
        method: 'GET'
    }).then(function(response) {
        if (response && response.candidates && Array.isArray(response.candidates)) {
            // KLUDGE: There's an issue with the PortlandMaps suggests API where the data is
            // formatted differently when there is only a single candidate returned, as opposed
            // to multiple candidates. The locationItem object constructor avoids this issue
            // by always assembling the address from its component parts if we send the isSingleton flag.
            
            if (response.candidates.length > 1) {
                return response.candidates.map(function(candidate) {
                    var retItem = new AddressVerifierModel.locationItem(candidate, $element);
                    return retItem;
                });
            } else if (response.candidates.length == 1) {
                return response.candidates.map(function(candidate) {
                    var retItem = new AddressVerifierModel.locationItem(candidate, $element, true);
                    return retItem;
                });
            } else {
                return [];
            }
        } else {
            // Handle the case where the response is not in the expected format
            console.error('Unexpected response format:', response);
            return [];
        }
    });
};

AddressVerifierModel.prototype._getSphericalMercCoords = function (lat, lon) {
  // Radius of the Earth in meters
  const R = 6378137;
  
  // Convert the longitude from degrees to radians
  const x = lon * (Math.PI / 180) * R;
  
  // Convert the latitude from degrees to radians
  const latRad = lat * (Math.PI / 180);
  
  // Calculate the y value using the Mercator projection formula
  const y = R * Math.log(Math.tan((Math.PI / 4) + (latRad / 2)));
  
  return { x, y };
}

AddressVerifierModel.locationItem.prototype.parseStreetData = function(street) {
    // Assuming street is in the format "1234 NW Main St"
    const streetParts = street.split(' ');
    this.streetNumber = streetParts.shift();
    this.streetQuadrant = streetParts.shift();
    this.streetName = streetParts.join(' ');
};

// static functions

AddressVerifierModel.buildFullAddress = function (address, city, state, zip, unit = null) {
    var fullAddress = address;
    fullAddress += unit ? " " + unit : "";
    fullAddress += ", " + city + ", " + state + "  " + zip;
    return fullAddress;
}

// AddressVerifierModel.buildFullAddressX = function (item, $element) {
//     var streetAddress = item.address + (item.unit ? " " + item.unit : "");
//     return [streetAddress, item.attributes.jurisdiction ? item.attributes.jurisdiction + ', ' + item.attributes.state : '']
//         .filter(Boolean)
//         .join(', ')
//         + (item.attributes.zip_code ? ' ' + item.attributes.zip_code : '');
// }

// AddressVerifierModel.updateFullAddress = function (item) {
//     var streetAddress = item.street + (item.unit ? " " + item.unit : "");
//     return [streetAddress, item.city ? item.city + ', ' + item.state : '']
//         .filter(Boolean)
//         .join(', ')
//         + (item.zip ? ' ' + item.attributes.zip_code : '');
// }

AddressVerifierModel.buildMailingLabel = function (item, $element, useHtml = false) {
    var lineBreak = useHtml ? "<br>" : "\r\n";
    var unit = $element.find('#unit_number').val();
    var label = item.street;
    if (item.unit) {
        label += " " + unit.toUpperCase();
    }
    label += lineBreak + item.city + ", " + item.state + " " + item.zipCode;
    return label;
}

AddressVerifierModel.prototype.updateLocationFromIntersects = function(lat, lon, item, callback, view) {
    var xy = this._getSphericalMercCoords(lat, lon);
    url = REVERSE_GEOCODE_URL;
    url = url.replace('${x}', xy.x).replace('${y}', xy.y).replace('${apiKey}', this.apiKey);
    // var self = this;

    this.$.ajax({
        url: url, success: function (response) {
            item.taxlotId = response.detail.taxlot[0].property_id;
            item.city = response.detail.zipcode[0].name;
            item.fullAddress = AddressVerifierModel.buildFullAddress(item.street, item.city, item.state, item.zipCode);
            callback(item, view);
        },
        error: function (e) {
            // if the PortlandMaps API is down, this is where we'll get stuck.
            // any way to fail the location lookup gracefull and still let folks submit?
            // at least display an error message.
            console.error(e);
            //showErrorModal("An error occurred while attemping to obtain location information from PortlandMaps.com.");
        }
    });
}

