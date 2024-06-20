AddressVerifierModel.locationItem = function (data, isSingleton = false) {

    // PortlandMaps sends incorrectly formatted address data if there is only 1 suggestion to return.
    // If a singleton, the isSingleton flag will be true, and we'll do some kludgey string manipulation.
    if (isSingleton) {
        var arrAddress = data.address.split(', ');
        data.address = arrAddress[0];
    }
    this.fullAddress = AddressVerifierModel.buildFullAddress(data).toUpperCase();
    this.displayAddress = data.address.toUpperCase() + ', ' + data.attributes.city.toUpperCase();
    this.street = data.address.toUpperCase();
    this.streetNumber = "";
    this.streetQuadrant = "";
    this.streetName = "";
    this.city = data.attributes.city.toUpperCase();
    this.state = data.attributes.state.toUpperCase();
    this.zipCode = data.attributes.zip_code;
    this.lat = data.attributes.lat;
    this.lon = data.attributes.lon;
    this.x = data.location.x;
    this.y = data.location.y;
    this.unit = "";

    this.parseStreetData(data.address);
}

function AddressVerifierModel(jQuery, element, apiKey) {
    this.$ = jQuery;
    this.element = element;
    this.apiKey = apiKey;
}

AddressVerifierModel.prototype.fetchAutocompleteItems = function (addrSearch) {
    const apiKey = this.apiKey;
    const apiUrl = `https://www.portlandmaps.com/api/suggest/?intersections=1&landmarks=1&alt_coords=1&api_key=${apiKey}&query=${encodeURIComponent(addrSearch)}`;

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
                    var retItem = new AddressVerifierModel.locationItem(candidate);
                    return retItem;
                });
            } else if (response.candidates.length == 1) {
                return response.candidates.map(function(candidate) {
                    var retItem = new AddressVerifierModel.locationItem(candidate, true);
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

AddressVerifierModel.locationItem.prototype.parseStreetData = function(street) {
    // Assuming street is in the format "1234 NW Main St"
    const streetParts = street.split(' ');
    this.streetNumber = streetParts.shift();
    this.streetQuadrant = streetParts.shift();
    this.streetName = streetParts.join(' ');
};

// static functions

AddressVerifierModel.buildFullAddress = function (item) {
    return [item.address, item.attributes.city ? item.attributes.city + ', ' + item.attributes.state : '']
        .filter(Boolean)
        .join(', ')
        + (item.attributes.zip_code ? ' ' + item.attributes.zip_code : '');
}

AddressVerifierModel.buildMailingLabel = function (item) {
    var label = item.street;
    if (item.unit) {
        label += ", UNIT " + item.unit;
    }
    label += "\r\n" + item.city + ", " + item.state + " " + item.zipCode;
    return label;
}

