AddressVerifierModel.locationItem = function (data) {
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
            // to multiple candidates. The logic below takes this into account.
            
            if (response.candidates.length > 1) {
                return response.candidates.map(function(candidate) {
                    var retItem = new AddressVerifierModel.locationItem(candidate);
                    return retItem;
                });
            } else if (response.candidates.length == 1) {
                return response.candidates.map(function(candidate) {
                    var retItem = new AddressVerifierModel.locationItem(candidate);
                    // fix fullAddress
                    retItem.fullAddress = AddressVerifierModel.buildFullAddress(candidate);
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

AddressVerifierModel.prototype.verifyAddress = function (address) {
    // Call the third-party API to verify the address
    // Update verifiedAddress with the verified address data
    alert('Doing verification now');
};

AddressVerifierModel.prototype.getVerifiedAddress = function () {
    return "123 Fake St, Anytown, USA 10101";
};

// static functions

AddressVerifierModel.buildFullAddress = function (c) {
    return [c.address, c.attributes.city ? c.attributes.city + ', ' + c.attributes.state : '']
        .filter(Boolean)
        .join(', ')
        + (c.attributes.zip_code ? ' ' + c.attributes.zip_code : '');
}

AddressVerifierModel.locationItem.prototype.parseStreetData = function(street) {
    // Assuming street is in the format "1234 NW Main St"
    const streetParts = street.split(' ');
    this.streetNumber = streetParts.shift();
    this.streetQuadrant = streetParts.shift();
    this.streetName = streetParts.join(' ');
};
