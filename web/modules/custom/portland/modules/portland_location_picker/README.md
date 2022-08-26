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

* location_type
* location_address
* place_name
* location_details
* location_asset_id
* location_region_id
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

Using the portland_geojson_views module and included views plugin, geoJSON layers can be added to the location picker map. The main useages are to display exising incidents (Zendesk tickets) and/or city assets such as trash cans, park amenities, etc. The preferred method of consuming a geoJSON feed for use as a map layer is to create a geoJSON view that calls external data sources (creating it as a vew allows the data to be cached), and then configuring the Location widget to consume that feed by setting associated custom properties.

### GeoJSON layer types and behaviors

The two main types of layers are assets and incidents. Assets are city assets such as trash cans or park amenities. Incidents are issues that have been reported by community members and usually represent Zendesk tickets.

The two main types of behavior for layers are informational and selection. Informational features simply display data about an asset or incident on the map. Selection features are used by the form user to select a precise location; when clicked, a selection feature's location lat/lon coordinates are captured.

The Location widget supports up to two layers: primary and incidents. The primary layer can display assets or incidents. The incidents layer is used only to display incidents when an assets layer is in use. 

### Custom properties (in the element's Advanced tab)

Data is entered in YAML format.

- ***primary_layer_source*** - Sets the URL path to the geoJSON feed for the primary layer

- ***incidents_layer_source*** - Sets the URL path to the geoJSON feed for the incidents layer

- ***primary_layer_behavior*** - Sets the behavior of the primary layer (informational|selection|geofencing)

- ***primary_layer_type*** - Sets the type of the primary layer, (asset|informational|region)

- ***primary_marker*** - The URL path to a custom icon image for features on the primary layer; the default is a basic gray map marker

- ***selected_marker*** - The URL path to a custom icon image that's used for assets after they've been selected by the user, or when they click the map to specify the location of a new issue; the default is a basic blue map marker

- ***incident_marker*** - The URL path to a custom icon image for standalone incidents or assets that have an associated incident; the default is a red map marker with an exclamation point

- ***disable_popup*** - Disables asset and incident popups if any value is set in the property (not currently implemented) 

- ***verify_button_text*** - Overrides the default "Verify" label in the widget used for verifying or finding an address

- ***primary_feature_name*** - Overrides the name/label used to describe features on the primary layer; default is "asset" (not currently implemented)

- ***feature_layer_visible_zoom*** - The minimum zoom level at which geoJSON features are displayed; the map gets laggy if too many are displayed at once; default value is 16 (maximum is 18 for full zoom)

### Example of custom properties

This is the advanced properties configuration used in the Report a Problem with a Public Trash Can reporting form. The first six properties are used to manipulate how the widget and its subelements are rendered. The remaining properties are from the list above and are used to configure the geoJSON layers and how the user interacts with them.

```
selected_asset_name__title: 'Selected asset'
location_address__states:
  visible: true
location_map__description_display: after
location_map__states:
  visible: true
place_name__readonly: true
place_name__description_display: invisible
primary_layer_source: /api/features/trashcans
incidents_layer_source: /api/tickets/trashcans
primary_layer_behavior: selection
primary_layer_type: asset
primary_feature_name0: 'trash can'
primary_marker: /modules/custom/portland/modules/portland_location_picker/images/map_marker_trashcan.png
selected_marker: /modules/custom/portland/modules/portland_location_picker/images/map_marker_trashcan_selected.png
verify_button_text: Locate
feature_layer_visible_zoom: 17
```

## Creating a geoJSON feed using Views

In order to integrate correctly with the Location widget, the geoJSON feed must not only include standard feature and location data, but it must also include a name and detail property that includes additional data and markup about the feature, either asset or incident.

### Asset

- Standard properties
  - type (geometry type)
  - coordinates (lat/lon)
- Custom properties
  - id
    - *The system ID of the asset, not necessarily a number that is useful to the end user. It's used primarily to link tickets to assets.*
  - name
    - *A custom text field that's used as display name/header in the feature popup.*
  - description
    - *(Optional) A custom text field comprised of various Zendesk fields exposed by the views plug-in. It includes markup so that it appears exactly as the user should see it in the popup.*
  - region_id
    - *(Optional) The field/value that's used to identify the region in which the user's selected location exists. Typically included in a Zendesk ticket or accessed with custom webform javascript and used in conditional logic.*

### Incident

Uses the same format as Asset, but also includes an asset_id custom property if the incident is associated with an asset in the primary layer.
