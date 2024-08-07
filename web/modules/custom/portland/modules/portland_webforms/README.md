# Portland Webforms

## Location Widget ##

### Configuration Parameters ###

#### city_boundary ####
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