# Portland Webforms

## Location Widget ##

### Configuration Parameters ###

#### primary_boundary ####
Type: object

primary_boundary:
- enabled [true|false*]
- visible [true*|false]
- enforce [true*|false]
- url [string] - *the url/path to the geojson file that defines the city boundary; defaults to the city limits may be overridden if the service has a different jurisdiction footprint*
- border_style [string] - NOT IMPLEMENTED

#### layers ####
Type: array, unlimited

layer:
- name \[string\]
- description \[string\]
- url \[string\]
- type \[**asset**|incident|boundary\]
  - *definitions*
    - *asset - a layer of assets that can be dispalyed for informational purposes or used for location selection*
    - *incident - a layer of incidents that can be dispalyed for informational purposes or used for location selection*
    - *boundary - a polygon or polygons used for determining whether a feature is within a defined region; can be used for geofencing*
  - visible_border \[**true**|false\]
  - border_style \[string\]
  - capture_id \[**true**|false\]
  - id_parameter \[string\] - *the parameter name of the object property to be captured if capture_id is true*
  - enforce_boundary \[**true**|false\] - *with boundaries, disallows clicks outside the boundary*
  - boundary_message \[string\] - *a custom message that can be displayed when disallowed clicks occur outside the boundary*
- behavior \[**informational**|single_select|multi_select\]
  - *definitions*
    - *informational - asset, incident, or boundary are for informational purposes only, cannot be selected* TODO: how to define what gets displayed?
    - *single_select - a single asset or incident may be selected*
    - *multi_select - multiple assets or incidents may be selected* NOT IMPLEMENTED
- asset_marker \[**default url**|custom url\] - *default marker url/path for assets*
- asset_marker_selected \[**default url**|custom url\] - *selected state marker url/path for assets*
- incident_marker \[**default url**|custom url\] - *Open state marker url/path for incidents, or default marker if no Open/Solved states*
- incident_marker_solved \[**default url**|custom url\] - *Solved state marker url/path for incidents*
- incident_marker_selected \[**default url**|custom url\] - *selected state marker url/path for incidents; used for both Open and Solved states*

```
layers:
  - name: "Current Graffiti Reports"
    description: "All open and recently solved graffiti reports"
    url: "/api/tickets/graffiti"
    type: "incident"
    behavior: "informational"
  - name: "Graffiti Resolution Zones"
    description: "Defines regions around the city where contractors are assigned"
    url: ""
    type: "boundary"
    behavior: "informational"
    enforce_boundary: "false"
    
```


Suggested format from Chet:

## Custom Webform Element Parameters

This section describes the custom parameters used in the Drupal 10 webform element advanced tab custom properties field.

### Parameters

#### `layers`
An array of layer objects, each defining a specific data layer to be included in the map. Each layer object contains the following properties:

##### Properties:

- **`name`**: *(string)*  
  The name of the layer.
  - **Usage**: This is displayed as the title for the layer.
  - **Example**: `"Current Graffiti Reports"`

- **`description`**: *(string)*  
  A brief description of the layer.
  - **Usage**: Provides additional context about the layer.
  - **Example**: `"All open and recently solved graffiti reports"`

- **`url`**: *(string)*  
  The API endpoint or URL where the layer data can be fetched from.
  - **Usage**: Used to retrieve data for the layer.
  - **Example**: `"/api/tickets/graffiti"`

- **`type`**: *(string)*  
  The type of data the layer represents. Possible values are `"incident"` for individual reports or `"boundary"` for defined regions.
  - **Usage**: Indicates how the layer data should be interpreted.
  - **Example**: `"incident"`

- **`behavior`**: *(string)*  
  Describes the layer's behavior, typically `"informational"` for data that provides information without any enforcement.
  - **Usage**: Defines how the layer should behave within the application.
  - **Example**: `"informational"`

- **`enforce_boundary`**: *(string, optional)*  
  Indicates whether the boundary should be enforced. The default value is `"false"`.
  - **Usage**: If set to `"true"`, actions outside this boundary might be restricted.
  - **Default**: `"false"`
  - **Example**: `"false"`

### Example Configuration

Below is an example YAML configuration:

```yaml
layers:
  - name: "Current Graffiti Reports"
    description: "All open and recently solved graffiti reports"
    url: "/api/tickets/graffiti"
    type: "incident"
    behavior: "informational"
  - name: "Graffiti Resolution Zones"
    description: "Defines regions around the city where contractors are assigned"
    url: ""
    type: "boundary"
    behavior: "informational"
    enforce_boundary: "false"
