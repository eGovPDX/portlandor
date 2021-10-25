# Portland Location Picker widget for webforms

This custom sub-module of the Portland module implements a custom composite element for webforms. It is used for address verification, geolocation, and reverse geolocation.

## Configuration

IMPORTANT: When placed in a webform, the widget MUST have the machine name "report_location" in order for the built-in
conditional logic to work. If sub-fields aren't hiding/showing as expect, this is the first thing to check.

## Address verification

Uses the PortlandMaps.com API for address verification and suggestions.

## Geolocation

The included Leaflet map locate() function geolocates the user through the browser if location permission has been provided.

## Reverse Geolocation

Uses the ArcGIS reverse geolocation API via PortlandMaps: https://www.portlandmaps.com/arcgis/rest/services/Public/Geocoding_PDX/GeocodeServer/reverseGeocode

Sample GET request: https://www.portlandmaps.com/arcgis/rest/services/Public/Geocoding_PDX/GeocodeServer/reverseGeocode?location=%7B%22x%22%3A-122.6500834%2C+%22y%22%3A45.5083671%2C+%22spatialReference%22%3A%7B%22wkid%22+%3A+4326%7D%7D&distance=100&langCode=&locationType=&featureTypes=&outSR=4326&returnIntersection=false&f=json

Sample JSON response:

{
  "address": {
    "Street": "1969 SE LADD AVE",
    "City": "Portland",
    "State": "OREGON",
    "ZIP": "97214",
    "Loc_name": "address_pdx"
  },
  "location": {
    "x": -122.65025498720404,
    "y": 45.508273260891066,
    "spatialReference": {
      "wkid": 4326,
      "latestWkid": 4326
    }
  }
}

## Results data

Results from Drupal webforms can be displayed in confirmation messages and delivered to external systems using tokens.

The class Drupal\portland_location_picker\Plugin\WebformElement\PortlandLocationPicker includes functions to format result data for default output. By default, the formatted data is rendered in the format shown below. If the user has provided a Place Name, it's prepended to the address string.

  10333 NE OREGON ST, Portland, OREGON 97220 (45.52834, -122.55596)
  ABC Company, 1234 NE 102ND AVE, PORTLAND, OREGON 97220 (45.52834, -122.55596)

This default data can be accessed using the token [webform_submission:values:report_location], where report_location is the machine name of the field in the webform. Individual values from sub-fields can be accessed by drilling down further into the composite field. For example, the place name returned by reverse geolocation can be accessed using the token [webform_submission:values:report_location:place_name].

The following sub-fields are available:

* location_address
* place_name
* location_lat
* location_lon

If the address data needs to be parsed further, the widget can be refactored to include hidden sub-fields for more granular data points, such as city, zipcode, jurisdiction, etc.