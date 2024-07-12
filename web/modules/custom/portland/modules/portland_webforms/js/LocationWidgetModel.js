class LocationWidgetModel {
    constructor(jQuery, element, apiKey) {
      this.$ = jQuery;
      this.element = element;
      this.apiKey = apiKey;
      console.log("LocationWidgetModel is plugged in");
    }
  
    fetchAddressSuggestions(keyword, $element) {
      // call API to return suggestions
    }
  
    static locationItem(data, $element = null, isSingleton = false) {
      // PortlandMaps sends incorrectly formatted address data if there is only 1 suggestion to return.
      // If a singleton, the isSingleton flag will be true, and we'll do some kludgey string manipulation.
      if (isSingleton) {
        var arrAddress = data.address.split(', ');
        data.address = arrAddress[0];
      }
      return {
        fullAddress: AddressVerifierModel.buildFullAddress(data, $element).toUpperCase(),
        displayAddress: data.address.toUpperCase() + ', ' + data.attributes.jurisdiction.toUpperCase(),
        street: data.address.toUpperCase(),
        streetNumber: data.attributes.address_number,
        streetQuadrant: data.attributes.street_direction,
        streetDirectionSuffix: data.attributes.street_direction_suffix ? data.attributes.street_direction_suffix.trim() : "",
        streetName: data.attributes.street_name + " " + data.attributes.street_type + (data.attributes.street_direction_suffix ? " " + data.attributes.street_direction_suffix : ""),
        city: data.attributes.jurisdiction.toUpperCase(),
        state: data.attributes.state.toUpperCase(),
        zipCode: data.attributes.zip_code,
        lat: data.attributes.lat,
        lon: data.attributes.lon,
        x: data.location.x,
        y: data.location.y,
        unit: ""
      };
    }
  }
  