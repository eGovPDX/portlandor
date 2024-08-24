// global_api.js
class GlobalApi {
  constructor(constants) {
    this.constants = constants;
  }

  async reverseGeocode(latlng, apiKey) {
    var xy = L.Projection.SphericalMercator.project(latlng);
    var url = this.constants.URLS.REVERSE_GEOCODE.replace('{{x}}', xy.x).replace('{{y}}', xy.y).replace('{{apiKey}}', apiKey);
    var response = await fetch(url);
    response = response.json();
    return response;
  }

  // Other API methods
}