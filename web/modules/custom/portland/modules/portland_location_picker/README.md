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

## Custom configuration

The sub-elements in the location widget can be manipulated using the Custom Properties field in the element's Advanced tab. The widget has some built-in logic for showing/hiding elements that may not be appropriate for all conditions. For example, to always display the clickable map or Address sub-element, they can be forcibly set to be visible. The custom properties are entered in YAML format:

`location_address__states:
  visible: true
location_map__states:
  visible: true`

## Adding custom GeoJSON data layers

This partially completed prototype functionality allows geoJSON features to be displayed in the location widget's map. It's preferable to use a Views geoJSON feed so that the data is cached. 

`geojson_layer: /api/tickets/graffiti
geojson_layer_behavior: informational
geojson_layer_type: incident`

In the above example, the **geojson_layer** value is the URL of the geoJSON view that displays open graffiti report tickets. The geojson_layer_behavior and geojson_layer_type values are meant to control how the elements are displayed on the map. However, the widget is currently hard-coded to display graffiti reports. When additional layer types are added, conditional logic will need to be added to appropriately display the new data.

TODO: Finish this functionality. Currently only the geojson_layer value is used. The display of the data is hard coded.

- **geojson_layer** - The URL of the geoJSON data feed

- **geojson_layer_behavior** - Determines how the user interacts with the layer
  - *informational*
  - *selection* - Allows user to select a feature as the location being reported

- **geojson_layer_type** - The type of data being displayed
  - *incident*
  - *feature*

- **geojson_feature_icon** - The URL of the icon to be used for each instance of the feature
