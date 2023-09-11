

Feature Layer Configuration

Custom properties example:

feature_layers:
  - name: 'Trash Cans'
    geojson_url: '/api/features/trashcans'
    type: 'asset'
    behavior: 'selection'
    icon_url: '/modules/custom/portland/modules/portland_location_picker/map_marker_trashcan.png'
    icon_url_selected: '/modules/custom/portland/modules/portland_location_picker/map_marker_trashcan_selected.png'
    visible_zoom: 16
  - name: 'Graffiti Reports'
    geojson_url: '/api/tickets/graffiti'
    type: 'incident'
    behavior: 'information'
    icon_url: '/modules/custom/portland/modules/portland_location_picker/map_marker_incident.png'
