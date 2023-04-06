# Portland Location Picker widget for webforms

This custom sub-module of the Portland module implements a custom composite element for webforms. It is used for address verification, geolocation, and reverse geolocation. It also has the ability to display GeoJSON layers with features such as assets, incidents, and regions. Assets may be informational only or used for selection. Incidents are live Zendesk tickets. Regions are polygons that record the region id when a location is picked inside them.

## Configuration

IMPORTANT: Only one Location widget can be placed in a form page; it must be the only one on the currently viewed page. Multiple instances can be used if they're placed on different pages within the form (using the multi-page form format).

To support the widest array of applicaitons, by default all sub-elements are enabled and none are required. Other sub-elements can be disabled if not needed. A common default pattern was not coded into the custom element plugin, because overriding that pattern would require using arcane YAML code in the custom properties field. Though this requires more initial configuration, it consists of checking boxes in the UI, which is much simpler than figuring out the YAML custom properties.

In practice, at minimum the location_address and location_lat fields should usually be marked required in the element configuration panel. The address field should always be populated after a map click; if a location with no address is used, then "N/A" is put in the field so that it passes required field validation. If the location_lat field has a value, it can be assumed that the location_lon field has one. Though hidden, the location_lat field has the label "Location," so that's the label that's displayed in the error message if no coordates have been selected.

When fields are marked as required in the UI, instead of the custom code for the widget, they'yre not required if disabled/hidden by conditional logic. If field requirements are hard coded in the module, Drupal still thinks they're required even if they're not enabled/visible. This is one of the reasons no fields are required by default in the module code.

KNOWN ISSUE: If the parent Location element is marked as required (for display purposes only), some of the sub-elements are incorrectly marked as required even when they are not. The exact steps to reproduce this behavior are TBD, but it may occur when some of the visible sub-elements are hidden type fields.

## Conditional logic

Any of the sub-fields may be used in conditional logic. For example, if "Private property" is selected for location_type, a conditional text block can 

## Address verification

Uses the PortlandMaps.com API for address verification and suggestions.

KNOWN ISSUE: As of 11/03/2022, there is a bug in PortlandMaps that causes some single-result suggestions to not include lat/lon coordinates. For example, if you search for "10333," a list of addresses will be returned, and each includes lat/lon that are used to recenter the map when clicked. If you search for "10333 NE Russell," only one suggested address will be returned. This particular address does not include lat/lon. The full verified address is returned, but the map can't center on it. In instances like this where there are no lat/lon coordiantes, but there is a verified address, zeroes are passed into the lat/lon fields so that the form can still be submitted. No other addresses without coordinates are known, and the CGIS team is at a loss as to why this occurs.

## Geolocation

The included Leaflet map locate() function geolocates the user through the browser if location permission has been provided.

## Reverse Geolocation

Uses the ArcGIS reverse geolocation API via PortlandMaps: https://www.portlandmaps.com/arcgis/rest/services/Public/Geocoding_PDX/GeocodeServer/reverseGeocode

Sample GET request: https://www.portlandmaps.com/arcgis/rest/services/Public/Geocoding_PDX/GeocodeServer/reverseGeocode?location=%7B%22x%22%3A-122.6500834%2C+%22y%22%3A45.5083671%2C+%22spatialReference%22%3A%7B%22wkid%22+%3A+4326%7D%7D&distance=100&langCode=&locationType=&featureTypes=&outSR=4326&returnIntersection=false&f=json

Sample JSON response:

```
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
```

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

TODO: Improve output formatting, include region ID field.

## Custom configuration

The sub-elements in the location widget can be manipulated using the Custom Properties field in the element's Advanced tab. The widget has some built-in logic for showing/hiding elements that may not be appropriate for all conditions. For example, to always display the clickable map or Address sub-element, they can be forcibly set to be visible. By default the map is not displayed if the user selects Private Property as the location type. The custom properties are entered in YAML format:

```
location_address__states:  
  visible: true  
location_map__states:  
  visible: true
```

## Adding custom GeoJSON data layers

Using the portland_geojson_views module and included views plugin, GeoJSON layers can be added to the location picker map. There are three main usages: assets, incidents, and regions. In general, we want to use GeoJSON data from a Drupal view, since that allows the query to be cached and requires fewer hits to the data source. This is helpful in cases where rate limits may be an issue.

### Types of GeoJSON layers

An instance of the Location Picker can support up to three layers. The Primary Layer can support assets, incidents, or regions. As the name implies, the Incidents Layer can only show incidents/tickets, and the Regions Layer can only display regions. If only one layer is used, it must be the Primary Layer.

#### Primary Layer

Assets represent physical objects that are owned and maintained by the city. Examples include park amenities and public trash cans. The asset layer may be informational, or individual assets may be used for selection. For example, a user might click the asset icon for a public trash can to report an issue with it. This will store data about the asset and location, such as asset ID, location lat/lon coordinates, and nearest street address if applicable.

### Incidents Layer

The incidents layer displays the loction of open or recently solved Zendesk tickets, usually generated by the current webform. They may be standalone incidents or may be associated with an asset. For example, graffiti reports may be located anywhere, not just on a city asset. Those markers appear on the map at the location where the user clicks. Alternately, trash can issues can only be located on existing city assets, and the incident uses the asset_id to tie it to an asset. When assets are used for selection and an incident already exists for a given asset, the icon is changed to indicate there is an open ticket for it.

### Regions Layer

The regions layer includes any number of polygons that are displayed as shaded areas. When the user clicks to set an issue marker within a region, the region_id is stored in one of the widget's sub-elements and can be used for conditional logic or calculations. For example, if a user is applying for a Temporary Street Use Permit, and their requested location is within a metered parking zone, that will affect the type of permit they need. Conditional logic can be used to hide or show form elements accordingly. Additional javascript may be added to the webform to perform additional actions if needed.

### GeoJSON layer behaviors

The two types of behavior for assets are informational and selection. At present, incidents cannot be used to select a location; they are informational only, but future development may allow users to select existing reports/incidents to subscribe to them and receive updates, rather than creating a new report. Regions are primarily informational, but can also be used for capturing clicks within a region and geofencing.

### Custom properties

These properties are set in the Advanced tab of the Location Picker element. Data is entered in YAML format.

- ***primary_layer_source*** - Sets the URL path to the geoJSON feed for the primary layer

- ***incidents_layer_source*** - Sets the URL path to the geoJSON feed for the incidents layer

- ***regions_layer_source*** - Sets the URL path to the geoJSON feed for the regions layer

- ***primary_layer_type*** - Sets the type of the primary layer, (asset|incident|region)

- ***primary_layer_behavior*** - Sets the behavior of the primary layer (information|selection|selection-only|geofencing)

- ***primary_marker*** - The URL path to a custom icon image for features on the primary layer; the default is a basic gray map marker

- ***selected_marker*** - The URL path to a custom icon image that's used for assets after they've been selected by the user, or when they click the map to specify the location of a new issue; the default is a basic blue map marker

- ***incident_marker*** - The URL path to a custom icon image for standalone incidents or assets that have an associated incident; the default is a red map marker with an exclamation point

- ***disable_popup*** - Disables asset and incident popups if any value is set in the property (not currently implemented) 

- ***verify_button_text*** - Overrides the default "Verify" label in the widget used for verifying or finding an address

- ***primary_feature_name*** - Overrides the name/label used to describe features on the primary layer; default is "asset" (not currently implemented)

- ***feature_layer_visible_zoom*** - The minimum zoom level at which geoJSON features are displayed; the map gets laggy if too many are displayed at once; default value is 16 (maximum is 18 for full zoom)

- ***require_city_limits*** - When TRUE, this property requires locations to be within the Portland city limits. Default is TRUE.

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
