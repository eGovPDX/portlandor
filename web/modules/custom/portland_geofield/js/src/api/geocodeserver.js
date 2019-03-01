import axios from "axios";

const REVERSE_GEOCODE_URL =
  "https://www.portlandmaps.com/arcgis/rest/services/Public/Address_Geocoding_PDX/GeocodeServer/reverseGeocode";
const GEOCODE_ADDRESS_URL = "";

export class GeocodeServer {
  constructor() {}

  getAddress(x, y) {
    const url = `${REVERSE_GEOCODE_URL}?location=${x},${y}&f=json`;

    return axios.get(encodeURI(url));
  }

  geocodeAddress(address) {
    const query = {
      records: [
        {
          attributes: {
            OBJECTID: 1,
            Street: address.street,
            City: address.city,
            State: address.state,
            Zip: address.zip
          }
        }
      ]
    };

    const url = `${GEOCODE_ADDRESS_URL}?addresses=${JSON.stringify(
      query
    )}&f=json`;

    return axios.get(encodeURI(url));
  }
}
