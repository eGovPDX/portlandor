// global_api.js
class GlobalApi {
  constructor(constants) {
    this.constants = constants;
  }

  async reverseGeocode(lat, lng, apiKey) {
    const url = this.constants.URLS.REVERSE_GEOCODE.replace('${x}', lng).replace('${y}', lat).replace('${apiKey}', apiKey);
    const response = await fetch(url);
    return response.json();
  }

  // Other API methods
}