import axios from "axios";

const SUGGEST_URL = "https://www.portlandmaps.com/api/suggest/";

export class PortlandMaps {
  constructor(apiKey) {
    this.apiKey = apiKey;
  }

  suggestAddress(query, options, success, failure) {
    let params = new URLSearchParams();
    params.append("query", query);
    params.append("api_key", this.apiKey);

    for (const key in options) {
      if (options.hasOwnProperty(key)) {
        params.append(key, options[key]);
      }
    }

    return axios
      .post(SUGGEST_URL, params)
      .then(value => {
        if (
          value.data &&
          value.data.status === "success" &&
          value.data.candidates
        ) {
          if (value.data.candidates[0]) {
            success({
              location: value.data.candidates[0].location,
              spatialReference: value.data.spatialReference
            });
          } else {
            failure(
              `Portlandmaps.com could not suggest a location for "${query}"`
            );
          }
        } else if (value.data && value.data.status === "error") {
          failure("Portlandmaps.com responded with an error.");
        } else {
          failure("Did not receive a valid response from portlandmaps.com.");
        }
      })
      .catch(() => {
        failure("Portlandmaps.com responded with an error.");
      });
  }
}
