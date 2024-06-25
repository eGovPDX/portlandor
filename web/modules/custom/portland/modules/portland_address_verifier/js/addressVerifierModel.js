AddressVerifierModel.locationItem = function (data, $element = null, isSingleton = false) {

    // PortlandMaps sends incorrectly formatted address data if there is only 1 suggestion to return.
    // If a singleton, the isSingleton flag will be true, and we'll do some kludgey string manipulation.
    if (isSingleton) {
        var arrAddress = data.address.split(', ');
        data.address = arrAddress[0];
    }
    this.fullAddress = AddressVerifierModel.buildFullAddress(data, $element).toUpperCase();
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

function AddressVerifierModel(jQuery, element, apiKey) {
    this.$ = jQuery;
    this.element = element;
    this.apiKey = apiKey;
}

AddressVerifierModel.prototype.fetchAutocompleteItems = function (addrSearch, $element) {
    const apiKey = this.apiKey;
    const apiUrl = `https://www.portlandmaps.com/api/suggest/?intersections=1&elements=1&landmarks=1&alt_coords=1&api_key=${apiKey}&query=${encodeURIComponent(addrSearch)}`;

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

AddressVerifierModel.locationItem.prototype.parseStreetData = function(street) {
    // Assuming street is in the format "1234 NW Main St"
    const streetParts = street.split(' ');
    this.streetNumber = streetParts.shift();
    this.streetQuadrant = streetParts.shift();
    this.streetName = streetParts.join(' ');
};

// static functions

AddressVerifierModel.buildFullAddress = function (item, $element) {
    var streetAddress = item.address + (item.unit ? " " + item.unit : "");
    return [streetAddress, item.attributes.jurisdiction ? item.attributes.jurisdiction + ', ' + item.attributes.state : '']
        .filter(Boolean)
        .join(', ')
        + (item.attributes.zip_code ? ' ' + item.attributes.zip_code : '');
}

AddressVerifierModel.updateFullAddress = function (item, $element) {
    var streetAddress = item.street + (item.unit ? " " + item.unit : "");
    return [streetAddress, item.city ? item.city + ', ' + item.state : '']
        .filter(Boolean)
        .join(', ')
        + (item.zip ? ' ' + item.attributes.zip_code : '');
}

AddressVerifierModel.buildMailingLabel = function (item, $element, useHtml = false) {
    var lineBreak = useHtml ? "<br>" : "\r\n";
    var unit = $element.find('#unit_number').val();
    var label = item.street;
    if (item.unit) {
        label += " " + unit;
    }
    label += lineBreak + item.city + ", " + item.state + " " + item.zipCode;
    return label;
}

