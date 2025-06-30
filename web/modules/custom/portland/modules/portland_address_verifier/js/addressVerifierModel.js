AddressVerifierModel.locationItem = function (data, $element = null, isSingleton = false) {

  // PortlandMaps sends incorrectly formatted address data if there is only 1 suggestion to return.
  // If a singleton, the isSingleton flag will be true, and we'll do some kludgey string manipulation.
  if (isSingleton) {
    var arrAddress = data.address.split(', ');
    data.address = arrAddress[0];
  }
  this.fullAddress = AddressVerifierModel.buildFullAddress(data.address, data.attributes.city, data.attributes.state, data.attributes.zip_code, data.unit).toUpperCase();
  this.displayAddress = data.address.toUpperCase();// + ', ' + data.attributes.jurisdiction.toUpperCase();
  this.street = data.address.toUpperCase();
  this.streetNumber = data.attributes.address_number;
  this.streetQuadrant = data.attributes.street_direction;
  this.streetDirectionSuffix = data.attributes.street_direction_suffix ? data.attributes.street_direction_suffix.trim() : "";
  this.streetName = data.attributes.street_name;
  this.streetType = data.attributes.street_type;
  if (this.streetDirectionSuffix) {
    this.streetType += " " + this.streetDirectionSuffix;
  }
  this.city = data.attributes.city;
  this.jurisdiction = data.attributes.jurisdiction;
  this.state = data.attributes.state ? data.attributes.state.toUpperCase() : "";
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
  }).then(function (response) {
    if (response && response.candidates && Array.isArray(response.candidates)) {
      // KLUDGE: There's an issue with the PortlandMaps suggests API where the data is
      // formatted differently when there is only a single candidate returned, as opposed
      // to multiple candidates. The locationItem object constructor avoids this issue
      // by always assembling the address from its component parts if we send the isSingleton flag.

      if (response.candidates.length > 1) {
        return response.candidates.map(function (candidate) {
          var retItem = new AddressVerifierModel.locationItem(candidate, $element);
          return retItem;
        });
      } else if (response.candidates.length == 1) {
        return response.candidates.map(function (candidate) {
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

AddressVerifierModel.prototype.fetchPropertyDetails = function (addrSearch, $element) {
  const apiKey = this.apiKey;
  var apiUrl = `https://www.portlandmaps.com/api/suggest/?intersections=1&elements=1&landmarks=1&alt_coords=1&api_key=${apiKey}&query=${encodeURIComponent(addrSearch)}`;

  return this.$.ajax({
    url: apiUrl,
    method: 'GET'
  }).then(function (response) {
    if (response && response.candidates && Array.isArray(response.candidates)) {
      if (response.candidates.length == 1) {
        return response.candidates.map(function (candidate) {
          var retItem = new AddressVerifierModel.locationItem(candidate, $element, true);
          return retItem;
        });
      } else if (response.candidates.length > 1) {
        return response.candidates.map(function (candidate) {
          var retItem = new AddressVerifierModel.locationItem(candidate, $element, true);
          return retItem;
        });
      } else {
        return [];
      }
    } else {
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

AddressVerifierModel.locationItem.prototype.parseStreetData = function (street) {
  // Assuming street is in the format "1234 NW Main St"
  const streetParts = street.split(' ');
  this.streetNumber = streetParts.shift();
  this.streetQuadrant = streetParts.shift();
  this.streetName = streetParts.join(' ');
};

// static functions

AddressVerifierModel.buildFullAddress = function (address, city, state, zip, unit = null) {
  var fullAddress = address;
  fullAddress += unit ? ", " + unit : "";
  fullAddress += ", " + city + ", " + state + " " + zip;
  return fullAddress;
}

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

AddressVerifierModel.prototype.updateLocationFromIntersects = function (lat, lon, item, callback, view) {
  var xy = this._getSphericalMercCoords(lat, lon);
  let url = REVERSE_GEOCODE_URL;
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

AddressVerifierModel.prototype.callSecondaryQuery = function (url, x, y, callback, view, capturePath, captureField, $) {
  url = url + "&geometry=" + x + "," + y;
  this.$.ajax({
    url: url, success: function (response) {
      callback(response, view, capturePath, captureField, $);
    },
    error: function (e) {
      // if the PortlandMaps API is down, this is where we'll get stuck.
      // any way to fail the location lookup gracefull and still let folks submit?
      // at least display an error message.
      console.error(e);
    }
  });
}

AddressVerifierModel.getPropertyByPath = function (obj, path, parse = "stringify", omit_nulls = false) {
  const parts = path.split('.');

  function extract(o, keys) {
    if (keys.length === 0) return o;

    const [first, ...rest] = keys;
    const match = first.match(/^(.+)\[(\d*)\]$/);

    if (match) {
      const [, key, index] = match;
      const arr = o?.[key];

      if (!Array.isArray(arr)) return undefined;

      if (index === '') {
        return arr.map(item => extract(item, rest));
      } else {
        return extract(arr[Number(index)], rest);
      }
    }

    return extract(o?.[first], rest);
  }

  function pruneNulls(value) {
    if (value === null || value === undefined) return undefined;

    if (Array.isArray(value)) {
      const cleaned = value.map(pruneNulls).filter(v => v !== undefined);
      return cleaned.length > 0 ? cleaned : undefined;
    }

    if (typeof value === 'object') {
      const cleaned = Object.entries(value).reduce((acc, [key, val]) => {
        const pruned = pruneNulls(val);
        if (pruned !== undefined) acc[key] = pruned;
        return acc;
      }, {});
      return Object.keys(cleaned).length > 0 ? cleaned : undefined;
    }

    return value;
  }

  let retVal = extract(obj, parts);

  if (omit_nulls) {
    retVal = pruneNulls(retVal);
  }

  const isEmptyArray = Array.isArray(retVal) && retVal.length === 0;
  const isEmptyObject = typeof retVal === 'object' && retVal !== null && Object.keys(retVal).length === 0;

  if (isEmptyArray || isEmptyObject) {
    return "";
  }

  let result;

  if (parse === "flatten" && typeof retVal === 'object' && retVal !== null) {
    result = AddressVerifierModel.flattenObjectToDelimitedString(retVal);
  } else if (parse === "stringify") {
    result = JSON.stringify(retVal);
  } else {
    result = retVal;
  }

  return result;
};

AddressVerifierModel.flattenObjectToDelimitedString = function (obj) {
  const entries = Object.entries(obj);
  const parts = [];

  for (let [key, value] of entries) {
    let stringValue;

    if (value === null) {
      stringValue = 'null';
    } else if (value === undefined) {
      stringValue = 'undefined';
    } else if (typeof value !== 'string' && typeof value !== 'number') {
      stringValue = JSON.stringify(value);
    } else {
      stringValue = String(value);
    }

    // Escape if necessary
    if (/[=|]/.test(stringValue)) {
      stringValue = `"${stringValue.replace(/"/g, '\\"')}"`;
    }

    const part = `${key}=${stringValue}`;
    parts.push(part);
  }

  const result = parts.join('|');
  return result;
};