# Portland Webforms

## Location Widget

### Configuration Parameters

- **`default_zoom`**: *(integer)*
  The default zoom level for the basemap when it's first rendered
  - **Default**: 11

- **`location_type`**: *(point|line|polygon)*
  The type of geometry being captured. If line or polygon, the appropriate drawing tool icons are displayed. Otherwise, clicks on the map result in a location marker being set. NOTE: CURRENTLY ONLY SUPPORTS POINTS. TODO: Support lines and polygons.
  - **Default**: point

- **`location_marker`**: *(string)*
  If location_type is "point," the URL path to the point marker.
  - **Default**: /modules/custom/modules/portland_webforms/images/map_marker.png

- **`location_line_style`**: *(TBD)*
  If location_type is "line," then this is the style specification for the line. FOR FUTURE DEVELOPMENT.
  - **Default**: ""

- **`location_polygon_style`**: *(TBD)*
  If location_type is "polygon," then this is the style specification for the polygon. FOR FUTURE DEVELOPMENT.
  - **Default**: ""

#### `primary_boundary`
Defines properties of the map's default primary boundary.

##### Properties:

- **`enabled`**: *(true/false)*
  - **Usage**: Determines whether the default layer is enabled.
  - **Default**: true

- **`visible`**: *(true/false)*
  - **Usage**: Determines whether the default layer is visible.
  - **Default**: true

- **`enforce`**: *(true/false)*
  - **Usage**: Determines whether the default layer is enforced as a geofencing boundary.
  - **Default**: true

- **`url`**: *(string)*
  - **Usage**: The url/path to the geojson file that defines the city boundary; defaults to the city limits may be overridden if the service has a different jurisdiction footprint.
  - **Example**: `"https://www.portlandmaps.com/arcgis/rest/services/Public/Boundaries/MapServer/0/query"`

- **`boundary_message`**: *(string)*
  - **Usage**: The message that's displayed if the user clicks outside the boundary if `enforce` is true.
  - **Default**: You've selected a location outside the Portland city limits. Please try again.

- **`border_style`**: *(string)*
  - **Usage**: *NOT IMPLEMENTED* 

#### `layers`
An array of layer definitions, each defining a specific data layer to be included in the map. Each layer object contains the following properties:

- **`name`**: *(string)*  
  The name of the layer.
  - **Example**: Current Graffiti Reports

- **`description`**: *(string)*  
  A brief description of the layer.
  - **Example**: All open and recently solved graffiti reports

- **`url`**: *(string)*  
  The API endpoint or URL where the layer data can be retrieved.
  - **Example**: /api/tickets/graffiti

- **`type`**: *(asset|incident|boundary)*  
  The type of data the layer represents. Possible values are `"asset"` for assets such as park amenities, `"incident"` for individual reports, or `"boundary"` for defined regions.
  - **Default**: asset

- **`behavior`**: *(informational|single_select|multi_select)*  
  Specifies the model to use when a feature in a layer is selected/clicked.
  - informational - asset, incident, or boundary are for informational purposes only, cannot be selected. **TODO: how to define what gets displayed on click or hover?**
  - single_select - a single asset or incident may be selected.
  - multi_select - multiple assets or incidents may be selected. NOT IMPLEMENTED
  - **Default**: informational

- **`capture_property_path`**: *(string)*  
  The path to the property of the asset/incident/region that is to be captured from the JSON returned by the API call. Uses standard javascript notation for arrays and objects.
  - **Example**: features[0].details.asset_id

- **`capture_property_destination_field`**: *(string)*  
  The ID of the webform field that will be used to store the value represented by capture_property_path. The default is location_region_id, which is a built-in sub-element, but any webform field with a unique ID can be used.
  - **Default**: location_region_id

- **`feature_zoom`**: *(integer)*  
  The zoom level at which to display the features from the layer. This is useful when there are a larege number of assets, incidents or regions, such that the display would be slow to load and render. If specified and the current zoom is equal to or greater than this value, the extent geometry is passed to the API query in order to filter the results before display.

- **`boundary_visible`**: *(true/false)*  
  If boundary, specifies whether the border is visible.
  - **Default**: false

- **`boundary_style`**: *(true/false)*  
  - If boundary, specifies the boundary style.

- **`boundary_enforced`**: *(true/false)*  
  If boundary, specifies whether to allow location selections outside the boundary.
  - **Default**: true

- **`boundary_message`**: *(true/false)*  
  The error message displayed when a click is outside the boundary and enforce_boundary is true.
  - **Default**: You've selected a location outside our service area. Please try again.

- **`feature_marker`**: *(string)*  
  The URL path to the marker icon used by assets/incidents in the feature layer.
  - **Default**: /modules/custom/modules/portland_webforms/images/map_marker_default.png

- **`feature_marker_selected`**: *(string)*  
  The URL path to the icon used to signify that an asset/incident marker has been selected.
  - **Default**: /modules/custom/modules/portland_webforms/images/map_marker_default_selected.png

- **`feature_marker_solved`**: *(string)*  
  The URL path to the icon used to signify that an incident is solved. Used when displaying historical incidents or those that have been recently solved.
  - **Default**: /modules/custom/modules/portland_webforms/images/map_marker_incident_solved.png

- **`filter_by_extent`**: *(true/false)*  
  When true, the current map extent coordinates are passed to the API for filtering purposes.
  - **Default**: true

#### `queries` ####`
An array of query definitions, each defining a specific data query to be performed when the map is clicked and coordinates are provided.

- **`name`**: *(string)*  
  The name of the query.
  - **Example**: Current Graffiti Reports

- **`description`**: *(string)*  
  A brief description of the query.
  - **Example**: All open and recently solved graffiti reports

- **`url`**: *(string)*  
  The API endpoint or URL of the query.
  - **Example**: /api/tickets/graffiti

### Widget Sub-Elements

#### Data Collection Sub-Elements

These are form elements used to capture input from the user.

- **`location_address`**:  
  Used to capture the first line of the location address, and doubles as the main address search and autocomplete field.

- **`location_unit`**:  
  Used to capture the unit number of a location, if applicable.

- **`location_city`**:  
  Used to display and/or capture the city of a location, returned by the primary reverse geocoding API call. Because address lookup and verification is not 100% accurate, users need the ability to change values if needed. When this field is filled by an API call, the user will typically have the ability to edit it.

- **`location_state`**:  
  Used to display and/or capture the state of a location. Because address lookup and verification is not 100% accurate, users need the ability to change values if needed. When this field is filled by an API call, the user will typically have the ability to edit it.

- **`location_zip`**:  
  Used to display and/or capture the zipcode of a location. Because address lookup and verification is not 100% accurate, users need the ability to change values if needed. When this field is filled by an API call, the user will typically have the ability to edit it.

- **`location_name`**:  
  Used to display and/or capture the name of a location, such as a park or business. Some APIs may return a name; some use cases might have the user enter a name. Can be hidden or modified in config.

- **`location_details`**:  
  Used to solicit more information about the location from the user. Can be hidden or modified in config.

#### Markup Sub-Elements

These are markup elements generally used to display information to the user but can also be used to collect input, such as the Leaflet map that collects location data.

- **`location_map`**:  
  The DOM container used to display the Leaflet map.

- **`suggestions_modal`**:  
  The modal dialog used to dispay address suggestions.

- **`status_modal`**:  
  The modal dialog used to display status messages and errors.

- **`precision_text`**:  
  A div that contains help text about accurately finding a location.

#### Data Storage Sub-Elements

These are hidden fields used to store data from API queries and make it available to downstream processes, such as sending in webform handlers to email recipients and APIs.

- **`location_lat`**:  
  The latitude of the location.
  - **Example**: 45.54020270827299

- **`location_lon`**:  
  The longitude of the location.
  - **Example:**: -122.72964477539062

- **`location_x`**:  
  The x coordinate of the location using a spherical mercator projection; used by APIs provided by PortlandMaps.
  - **Example**: -13662201.561635833

- **`location_y`**:  
  The y coordinate of the location using a spherical mercator projection; used by APIs provided by PortlandMaps.
  - **Example**: 5706970.069713437

- **`location_types`**:  
  A comma-delimited list of location types returned by the primary reverse geocoding API call, such as taxlot, park, waterbody, trail, stream, street, row. A location may have multiple overlapping types. For example, a trail in a park would be of type trail, park, and taxlot (parks are considered taxlots and have taxlot IDs). NOTE: Do we need individual flag fields for each type?
  - **Example**: taxlot,park,trail

- **`location_attributes`**:  
  A comma-delimited list of location attributes returned by the primary reverse geocoding API call, such as Taxlot ID, Park ID, Waterbody ID, Waterbody Type, Trail ID, Trail Name. Label and value are colon-delimited.
  - **Example**: TaxlotID:R123456,ParkID:249,TrailID:449977,TrailName:I-205 Bike Path

- **`location_region_id`**:  
  A comma-delimited list of region IDs in which the location falls. There may be multiple layers/regions on the map that have their own ID, so labels and values are colon-delimited like location attributes. The webform field into which the region ID is stored can be specified in the layer configuration property capture_property_destination_field.
  - **Example**: NaturalInventory:Forest Park,ServiceZones:Washington Park Service Zone,NatureZones:West

- **`location_asset_id`**:  
  When assets are used for selection, such as trash cans or park amenities, this is the field that stores the ID of the selected asset.
