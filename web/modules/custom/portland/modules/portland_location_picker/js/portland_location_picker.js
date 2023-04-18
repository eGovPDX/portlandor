(function ($, Drupal, drupalSettings) {

  // Here's how to reverse geolocate a park. Note the x/y values in the geometry parameter:
  // https://www.portlandmaps.com/arcgis/rest/services/Public/Parks_Misc/MapServer/2/query?geometry=%7B%22x%22%3A-122.55203425884248%2C%22y%22%3A45.53377174783918%2C%22spatialReference%22%3A%7B%22wkid%22%3A4326%7D%7D&geometryType=esriGeometryPoint&spacialRel=esriSpatialRelIntersects&returnGeometry=false&returnTrueCurves=false&returnIdsOnly=false&returnCountOnly=false&returnDistinctValues=false&f=pjson"
  // returns an object that includes the park name. 
  
  /**
   * Attach the machine-readable name form element behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches machine-name behaviors.
   */
   Drupal.behaviors.portland_location_picker = {
    attach: function (context, settings) {

      $(context).once('location_picker').each(function () {

        // CONSTANTS //////////
        const DEFAULT_LATITUDE = 45.54;
        const DEFAULT_LONGITUDE = -122.65;
        const DEFAULT_ZOOM = 11;
        const DEFAULT_ZOOM_CLICK = 18;
        const DEFAULT_ZOOM_VERIFIED = 18;
        const FEATURE_LAYER_VISIBLE_ZOOM = 16;
        const DEFAULT_ICON_SIZE = [27, 41];
        const DEFAULT_ICON_SHADOW_SIZE = [0, 0];
        const DEFAULT_ICON_ANCHOR = [13, 41];
        const DEFAULT_ICON_SHADOW_ANCHOR = [0, 0];
        const DEFAULT_ICON_POPUP_ANCHOR = [0, -41];

        const ZOOM_POSITION = 'topright';
        const NOT_A_PARK = "You selected park or natural area as the property type, but no park data was found for the selected location. If you believe this is a valid location, please zoom in to find the park on the map, click to select a location, and continue to submit your report.";
        const OPEN_ISSUE_MESSAGE = "If this issue is what you came here to report, there's no need to report it again.";
        const SOLVED_ISSUE_MESSAGE = "This issue was recently solved. If that's not the case, or the issue has reoccured, please submit a new report.";
        const ASSET_ONLY_SELECTION_MESSAGE = "We have zoomed in on the address you provided, but this map only allows you to select existing asset markers. Click one to select it. There may not be any selectable assets in the current view.";
        const VERIFIED_NO_COORDS = "The address you entered is verified, but an error occurred, and it can't be shown on the map. Please zoom in and find the desired location, then click it to set a marker.";
        const CITY_LIMITS_MESSAGE = "The location you selected is not within the Portland city limits. Please try again."
        const DEFAULT_FEATURE_ICON_URL = "/modules/custom/portland/modules/portland_location_picker/images/map_marker_default.png";
        const DEFAULT_INCIDENT_ICON_URL = "/modules/custom/portland/modules/portland_location_picker/images/map_marker_incident.png";
        const DEFAULT_SOLVED_ICON_URL = "/modules/custom/portland/modules/portland_location_picker/images/map_marker_incident_solved.png";
        const CITY_LIMITS_BOUNDARY_URL = "https://www.portlandmaps.com/arcgis/rest/services/Public/COP_OpenData_Boundary/MapServer/10/query?where=CITYNAME%20like%20%27Portland%27&outFields=*&outSR=4326&f=geojson";
        // for all area municipalities, use this url: https://www.portlandmaps.com/arcgis/rest/services/Public/COP_OpenData_Boundary/MapServer/10/query?outFields=*&where=1%3D1&f=geojson
        // TODO: if the multi-municipality geojson is used, we'll need to extend the city limits functionaltiy. right now it assumes the only feature is Portland.
        const PARKS_REVGEOCODE_URL = "https://www.portlandmaps.com/arcgis/rest/services/Public/Parks_Misc/MapServer/2/query?geometry=%7B%22x%22%3A${lng}%2C%22y%22%3A${lat}%2C%22spatialReference%22%3A%7B%22wkid%22%3A4326%7D%7D&geometryType=esriGeometryPoint&spacialRel=esriSpatialRelIntersects&returnGeometry=false&returnTrueCurves=false&returnIdsOnly=false&returnCountOnly=false&returnDistinctValues=false&f=pjson";
        const REVGEOCODE_URL = "https://www.portlandmaps.com/arcgis/rest/services/Public/Geocoding_PDX/GeocodeServer/reverseGeocode?location=%7B%22x%22%3A${lng}%2C+%22y%22%3A${lat}%2C+%22spatialReference%22%3A%7B%22wkid%22+%3A+4326%7D%7D&distance=100&langCode=&locationType=&featureTypes=&outSR=4326&returnIntersection=false&f=json";
        const PRIMARY_LAYER_TYPE = {
          Asset:          "asset",
          Incident:       "incident",
          Region:         "region"
        }
        const PRIMARY_LAYER_BEHAVIOR = {
          Selection:      "selection",
          Information:    "information",
          SelectionOnly:  "selection-only",
          GeoFence:       "geofence"
        }
        const TICKET_STATUS = {
          New:            "new",
          Open:           "open",
          Referred:       "referred",
          Solved:         "solved",
          Closed:         "closed"
        }

        const GEOLOCATION_CACHE_MILLISECONDS = 0;
  
        // GLOBALS //////////
        var map;
        var primaryLayer;
        var incidentsLayer;
        var regionsLayer;
        var cityBoundaryLayer;
        var primaryFeatures;
        var incidentsFeatures;
        var regionsFeatures;
        var cityBoundaryFeatures;
        var locationMarker;
        var locationErrorShown;
        var locateControl;
        var locMarker;
        var locCircle;
        var locateControlContaier;
        var clickedMarker;
        var assetCount;
        var suggestionsModal;
        var locationType;
        var statusModal;
        var baseLayer;
        var aerialLayer;
        var isPark;
        var useParks;
        var shouldRecenterPark = true;
        var currentView = "base";
        var baseLayer = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete/MapServer/tile/{z}/{y}/{x}', { attribution: "PortlandMaps ESRI" });
        var aerialLayer = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete_Aerial/MapServer/tile/{z}/{y}/{x}', { attribution: "PortlandMaps ESRI" });
        var LocateControl = generateLocateControl();
        var AerialControl = generateAerialControl();

        // CUSTOM PROPERTIES SET IN WEBFORM CONFIG //////////
        var elementId = drupalSettings.webform.portland_location_picker.element_id;
        var primaryLayerSource = drupalSettings.webform.portland_location_picker.primary_layer_source;
        var incidentsLayerSource = drupalSettings.webform.portland_location_picker.incidents_layer_source;
        var regionsLayerSource = drupalSettings.webform.portland_location_picker.regions_layer_source;
        var primaryLayerBehavior = drupalSettings.webform.portland_location_picker.primary_layer_behavior;
        var primaryLayerType = drupalSettings.webform.portland_location_picker.primary_layer_type;
        var primaryMarkerUrl = drupalSettings.webform.portland_location_picker.primary_marker ? drupalSettings.webform.portland_location_picker.primary_marker : '/modules/custom/portland/modules/portland_location_picker/images/map_marker_default.png';
        var selectedMarker = drupalSettings.webform.portland_location_picker.selected_marker ? drupalSettings.webform.portland_location_picker.selected_marker : '/modules/custom/portland/modules/portland_location_picker/images/map_marker_default_selected.png';
        var incidentMarker = drupalSettings.webform.portland_location_picker.incident_marker ? drupalSettings.webform.portland_location_picker.incident_marker : '/modules/custom/portland/modules/portland_location_picker/images/map_marker_incident.png';
        var disablePopup = drupalSettings.webform.portland_location_picker.disable_popup ? true : false;
        var verifyButtonText = drupalSettings.webform.portland_location_picker.verify_button_text ? drupalSettings.webform.portland_location_picker.verify_button_text : 'Verify';
        var primaryFeatureName = drupalSettings.webform.portland_location_picker.primary_feature_name ? drupalSettings.webform.portland_location_picker.primary_feature_name : 'asset';
        var featureLayerVisibleZoom = drupalSettings.webform.portland_location_picker.feature_layer_visible_zoom ? drupalSettings.webform.portland_location_picker.feature_layer_visible_zoom : FEATURE_LAYER_VISIBLE_ZOOM;
        var requireCityLimits = drupalSettings.webform.portland_location_picker.require_city_limits === false ? false : true;
        var displayCityLimits = drupalSettings.webform.portland_location_picker.display_city_limits === false ? false : true;

        // properties for the city limits polygon
        if (displayCityLimits) {
          var cityLimitsProperties = {
            color: 'red',
            fillOpacity: 0,
            weight: 1,
            dashArray: "2 4",
            interactive: false
          }
        }

        var defaultSelectedMarkerIcon = L.icon({
          iconUrl:      selectedMarker,
          iconSize:     DEFAULT_ICON_SIZE, // size of the icon
          shadowSize:   [0, 0], // size of the shadow
          iconAnchor:   [13, 41], // point of the icon which will correspond to marker's location
          shadowAnchor: [0, 0],  // the same for the shadow
          popupAnchor:  [0, -41]
        });

        initialize()
        
        // SETUP FUNCTIONS ///////////////////////////////

        var testPolygonLayer;
  
        function initialize() {
  
          // verify only one map widget in webform; complain if more than one
          if ($('.portland-location-picker--wrapper').length > 1) {
            console.log("WARNING: More than one location widget detected. Only one location widget per webform is currently supported. Adding multiples will result in unpredictable behavior.");
          }

          // widget can be configured to use the park selector features or not.
          // if not, we want to disable PortlandMaps parks lookups. this flag
          // will be used to control that.
          useParks = $('select.location-park').length > 0 || $('input#edit-report-location-location-type-park').length > 0;

          // // if select-asset behavior, set label for selected asset text. the term "asset" can be overridden in the widget config.
          // // Example selected asset text: "Selected asset: Trash Can 57110"
          // if (primaryLayerBehavior == "selection") {
          //   $('#selected_asset_label').text('Selected ' + primaryFeatureName + ': ');
          // }

          // disable form submit when pressing enter on address field and click Verify button instead
          $('#location_address').on('keydown', function (e) {
            if (e.keyCode == 13) { $('#location_verify').click(); e.preventDefault(); return false; }
          });
  
          // INITIALIZE MAP //////////
          map = new L.Map("location_map_container", {
            center: new L.LatLng(DEFAULT_LATITUDE, DEFAULT_LONGITUDE),
            zoomControl: false,
            zoom: DEFAULT_ZOOM,
            gestureHandling: true
          });
          map.addLayer(baseLayer);
          map.addControl(new L.control.zoom({ position: ZOOM_POSITION }));
          map.addControl(new AerialControl());
          map.addControl(new LocateControl());
          map.on('locationerror', handleLocationError);
          map.on('locationfound', handleLocateMeFound);

          // only allow map clicks if primary layer behavior is not "selection." if it is, only asset markers can be clicked to select a locaiton.
          if (primaryLayerBehavior != PRIMARY_LAYER_BEHAVIOR.SelectionOnly) { map.on('click', handleMapClick); }

          // set up zoomend handler; we only want to show the primary features/assets layer when zoomed in
          // so that the map isn't too crowded with markers.
          map.on('zoomend', handleZoomEndShowGeoJsonLayer);

          // force a crosshair cursor
          $('.leaflet-container').css('cursor', 'crosshair');

          // if there are coordinates in the hidden lat/lng fields, set the map marker.
          // this is likely a submit postback that had validation errors, so we need to re set it.
          // NOTE: The following code would be problematic if we allow multiple copies of the widget or alternate naming conventions.
          var lat = $('input[name=' + elementId + '\\[location_lat\\]]').val();
          var lng = $('input[name=' + elementId + '\\[location_lon\\]]').val();
          if (lat && lng && lat !== "0" && lng !== "0") {
            setLocationMarker(lat, lng);
            doZoomAndCenter(lat, lng);
          }
  
          // Set up address verify button
          $('.location-picker-address').after(`<input class="btn location-verify button js-form-submit form-submit" type="button" id="location_verify" name="op" value="${verifyButtonText}">`);
          $('.location-picker-address').after('<span class="verified-checkmark address invisible" title="Location is verified!">✓</span>');
          $(document).on('click', '#location_verify', function (e) {
            e.preventDefault();
            // Portland Maps API for location suggestions doesn't work property when an ampersand is used to identify intersections
            var address = $('.location-picker-address').val().replace("&", "and");
            if (address.length < 1) { showStatusModal("Please enter an address or cross streets and try again."); return false; }
            verifyAddressPortlandMaps(address);
          });
          // turn off verified checkmark if value changes
          $('.location-picker-address').on('keyup', function () {
            setUnverified();
          });
  
          // Set up pick links //////////////////////////////////
          $(document).on('click', 'a.pick', function (e) {
            e.preventDefault();
            // get address data from link
            var address = $(this).data('pick-address');
            // put selected address in address field
            $('.location-picker-address').val(address);
            suggestionsModal.dialog('close');
            // locate address on map
            var lat = $(this).data('lat');
            var lng = $(this).data('lng');
            var latlng = new L.LatLng(lat, lng);
            if (latlng.lat && latlng.lng) {
              doMapClick(latlng);
              setVerified();
            } else {
              showStatusModal(VERIFIED_NO_COORDS);
              setLatLngHiddenFields(latlng.lat, latlng.lng);
              setVerified();
            }
          });
  
          // set up status modal ///////////////////////////////
          // this will display error messages, or a status indicator when performing slow ajax operations such as self geolocation
          statusModal = $('#status_modal');
  
          // set up location type radios //////////////////////
          // some of the sub elements need to be shown/hidden depending on the location type
          $('fieldset.location-type input[type="radio"]').on("click", function() {
            handleLocationTypeClick($(this));
          });
  
          // set up parks select list /////////////////////////
          if (useParks) {
            $('#location_park').after('<span class="verified-checkmark park invisible" title="Location is verified!">✓</span>');
            $('#location_park').select2({
              escapeMarkup: function (markup) { return markup; },
              language: { noResults: function() { return 'No results found. Please try again, or select one of the other property type options above.'; } }
            });
            $('#location_park').on("change", function() {
                var park = $(this).val();
                if (park) { locateParkFromSelector(park); }
            });
          }

          if (displayCityLimits) {
            initializeCityLimitsLayer();
          }

          // INITIALIZE GEOJSON LAYERS //////////
          processGeoJsonData();
        }

        function initializeCityLimitsLayer() {
          // CITY_LIMITS_BOUNDARY_URL
          $.ajax({
            url: CITY_LIMITS_BOUNDARY_URL, success: function(cityBoundaryResponse) {
              cityBoundaryFeatures = cityBoundaryResponse.features;
              console.log(cityBoundaryFeatures.length + " city boundary regions found.");
              cityBoundaryLayer = L.geoJson(cityBoundaryFeatures, cityLimitsProperties).addTo(map);
              cityBoundaryLayer.municipality = cityBoundaryFeatures[0].properties.CITYNAME;
            }
          });
        }

        /**
         * Retrieves external GeoJSON data and performs any processing, such as matching incidents to assets.
         */
        function processGeoJsonData() {

          // if there are any layer in use, the Primary Layer must be used.
          if (primaryLayerSource) {
            primaryLayer = L.geoJson(); // can we create this on the fly?

            $.ajax({
              url: primaryLayerSource, success: function(primaryResponse) {
                primaryFeatures = primaryResponse.features;
                console.log(primaryFeatures.length + " features found on primary layer.");

                // pull in incidents layer, if applicable.
                // since the other layers are dependent on the primaryLayer, they need to be retrieved
                // in the success function of the primary layer ajax call. otherwise the we'd need to use
                // synchronous ajax calls, which is not ideal.
                if (incidentsLayerSource) {

                  $.ajax({
                    url: incidentsLayerSource, success: function(incidentsResponse) {
                      incidentsFeatures = incidentsResponse.features;
                      console.log(incidentsFeatures.length + " incidents found.");

                      // perform double loop between primary and incidents layers
                      // to link incidents to assets if applicable. we need to do this
                      // before adding the features to the map. loops are named so we can
                      // selectively break them.
                      primaryLoop:
                      for (var i = 0; i < primaryFeatures.length; i++) {

                        incidentsLoop:
                        // loop backwards becasue we're going to remove incidents that are attached to assets
                        // and move the incident 
                        for (var j = incidentsFeatures.length - 1; j >= 0; j--) {

                          // is the incident associated with the asset?
                          if (primaryFeatures[i].properties.id == incidentsFeatures[j].properties.asset_id) {
                            // add incident details to asset details
                            primaryFeatures[i].properties.incidentDetail = incidentsFeatures[j].properties.detail;
                            primaryFeatures[i].properties.hasOpenIncident = incidentsFeatures[j].properties.status == "open" || incidentsFeatures[j].properties.status == "new";
                            primaryFeatures[i].properties.hasSolvedIncident = incidentsFeatures[j].properties.status == "solved";
                            incidentsFeatures.splice(j, 1);
                          }
                        }
                      }
                      initPrimaryLayer(primaryFeatures, primaryLayer);

                      // if primary layer mode is selection (as opposed to selection-only), there may be incidents
                      // not associated with assets. these will be in the incidentsFeatures array. any with associated
                      // assets have been spliced out of the array and the incident data copied to the asset element.
                      initIncidentsLayer(incidentsFeatures, incidentsLayer);
                    }
                  });
                } else {
                  initPrimaryLayer(primaryFeatures, primaryLayer);
                }

                if (regionsLayerSource) {
                  $.ajax({
                    url: regionsLayerSource, success: function(regionsResponse) {
                      regionsFeatures = regionsResponse.features;
                      console.log(regionsFeatures.length + " regions found.");

                      initRegionsLayer(regionsFeatures, regionsLayer);
                    }
                  });
                }
              }
            });
          }
        }

        function initPrimaryLayer(features, layer) {
          var zoom = map.getZoom();
          var markerUrl = primaryMarkerUrl ? primaryMarkerUrl : DEFAULT_FEATURE_ICON_URL;
          var standardMarker = L.icon({
            iconUrl:      markerUrl,
            iconSize:     DEFAULT_ICON_SIZE,
            shadowSize:   DEFAULT_ICON_SHADOW_SIZE,
            iconAnchor:   DEFAULT_ICON_ANCHOR,
            shadowAnchor: DEFAULT_ICON_SHADOW_ANCHOR,
            popupAnchor:  DEFAULT_ICON_POPUP_ANCHOR,
            className:    "feature"
          });
          var incidentMarkerUrl = incidentMarkerUrl ? incidentMarkerUrl : DEFAULT_INCIDENT_ICON_URL;
          var incidentMarker = L.icon({
            iconUrl:      incidentMarkerUrl,
            iconSize:     DEFAULT_ICON_SIZE,
            shadowSize:   DEFAULT_ICON_SHADOW_SIZE,
            iconAnchor:   DEFAULT_ICON_ANCHOR,
            shadowAnchor: DEFAULT_ICON_SHADOW_ANCHOR,
            popupAnchor:  DEFAULT_ICON_POPUP_ANCHOR,
            className:    "incident"
          });
          var solvedMarkerUrl = solvedMarkerUrl ? solvedMarkerUrl : DEFAULT_SOLVED_ICON_URL;
          var solvedMarker = L.icon({
            iconUrl:      solvedMarkerUrl,
            iconSize:     DEFAULT_ICON_SIZE,
            shadowSize:   DEFAULT_ICON_SHADOW_SIZE,
            iconAnchor:   DEFAULT_ICON_ANCHOR,
            shadowAnchor: DEFAULT_ICON_SHADOW_ANCHOR,
            popupAnchor:  DEFAULT_ICON_POPUP_ANCHOR,
            className:    "incident solved"
          });

          // should the features be clickable? in most cases yes, and the marker click
          // should be handled. it will usually either open a popup or cause the marker
          // to be selected as the report location. that logic is handled in the
          // handleMarkerClick event handler function.
          var isInteractive = true;

          primaryLayer = L.geoJson(features, {
            coordsToLatLng: function (coords) {
              return new L.LatLng(coords[1], coords[0]);
            },
            pointToLayer: function(feature, latlng) {
              if (feature.properties.hasOpenIncident || 
                  (primaryLayerType == PRIMARY_LAYER_TYPE.Incident && (feature.properties.status == TICKET_STATUS.New || feature.properties.status == TICKET_STATUS.Open))) {
                marker = incidentMarker ;
              } else if (feature.properties.hasSolvedIncident || (primaryLayerType == PRIMARY_LAYER_TYPE.Incident && feature.properties.status == TICKET_STATUS.Solved)) {
                marker = solvedMarker;
              } else {
                marker = standardMarker;
              }
              return L.marker(latlng, {
                icon:           marker,
                draggable:      false,
                riseOnHover:    true,
                iconSize:       DEFAULT_ICON_SIZE
              });
            },
            onEachFeature: function(feature, layer) {

              // if this is a region, disable autopan. otherwise we want it on.
              var autoPanValue = primaryLayerType == PRIMARY_LAYER_TYPE.Region ? false : true;
              layer.bindPopup(generatePopupContent(feature), { maxWidth: 250, offset: L.point(0,0), autoPan: autoPanValue });

              // if region, use mouseover to show popup
              if (primaryLayerType == PRIMARY_LAYER_TYPE.Region) {
                layer.on("mouseover", function(e) { layer.openPopup(e.latlng); });
                layer.on("mousemove", function(e) { layer.openPopup(e.latlng); });
                layer.on("mouseout",  function(e) { layer.closePopup(); });
                layer.on("click", handleMapClick);
              } else {
                layer.on("click", handleMarkerClick);
              }
            },
            interactive: isInteractive
          });
          if (zoom >= featureLayerVisibleZoom) {
            primaryLayer.addTo(map);
          }
        }

        function initIncidentsLayer(features, layer) {
          // if there are any items in the features array, they should be displayed as incidents
          // on the map. these won't be associated with an asset but standalone incidents.
          var incidentMarkerUrl = incidentMarkerUrl ? incidentMarkerUrl : DEFAULT_INCIDENT_ICON_URL;
          var incidentMarker = L.icon({
            iconUrl:      DEFAULT_INCIDENT_ICON_URL,
            iconSize:     DEFAULT_ICON_SIZE,
            shadowSize:   DEFAULT_ICON_SHADOW_SIZE,
            iconAnchor:   DEFAULT_ICON_ANCHOR,
            shadowAnchor: DEFAULT_ICON_SHADOW_ANCHOR,
            popupAnchor:  DEFAULT_ICON_POPUP_ANCHOR,
            className:    "incident"
          });
          var solvedMarkerUrl = solvedMarkerUrl ? solvedMarkerUrl : DEFAULT_SOLVED_ICON_URL;
          var solvedMarker = L.icon({
            iconUrl:      solvedMarkerUrl,
            iconSize:     DEFAULT_ICON_SIZE,
            shadowSize:   DEFAULT_ICON_SHADOW_SIZE,
            iconAnchor:   DEFAULT_ICON_ANCHOR,
            shadowAnchor: DEFAULT_ICON_SHADOW_ANCHOR,
            popupAnchor:  DEFAULT_ICON_POPUP_ANCHOR,
            className:    "incident solved"
          });

          incidentsLayer = L.geoJson(features, {
            coordsToLatLng: function (coords) {
              return new L.LatLng(coords[1], coords[0]);
            },
            pointToLayer: function(feature, latlng) {
              if (feature.properties.status != TICKET_STATUS.Solved) {
                marker = incidentMarker;
              } else {
                marker = solvedMarker;
              }
              return L.marker(latlng, {
                icon:           marker,
                draggable:      false,
                riseOnHover:    true,
                iconSize:       DEFAULT_ICON_SIZE
              });
            },
            onEachFeature: function(feature, layer) {
              layer.bindPopup(generatePopupContent(feature), { maxWidth: 250, offset: L.point(0,0) });
              // if region, use mouseover to show popup
              layer.on("click", handleMarkerClick);
            },
            interactive: true
          });
        }

        function initRegionsLayer(features, layer) {
          layer = L.geoJson(features, {
            color: 'blue',
            interactive: false
          }).addTo(map);
        }

        function generateLocateControl() {
          return L.Control.extend({
            options: {
              position: "bottomright"
            },
            onAdd: function (map) {
              locateControlContaier = L.DomUtil.create('div', 'leaflet-bar locate-control leaflet-control leaflet-control-custom');
              locateControlContaier.style.backgroundImage = "url(/modules/custom/portland/modules/portland_location_picker/images/map_locate.png)";
              locateControlContaier.title = 'Locate me';
              locateControlContaier.onclick = handleLocateButtonClick;
              return locateControlContaier;
            }
          });
        }

        function generateAerialControl() {
          return L.Control.extend({
            options: {
              position: "bottomright"
            },
            onAdd: function (map) {
              aerialControlContainer = L.DomUtil.create('div', 'leaflet-bar locate-control leaflet-control leaflet-control-custom');
              aerialControlContainer.style.backgroundImage = "url(/modules/custom/portland/modules/portland_location_picker/images/map_aerial.png)";
              aerialControlContainer.title = 'Aerial view';
              aerialControlContainer.onclick = handleAerialButtonClick;
              return aerialControlContainer;
            }
          });
        }

        function generatePopupContent(feature) {
          var name = feature.properties.name ? feature.properties.name : "Feature";
          var detail = feature.properties.detail ? feature.properties.detail : "";
          var incidentDetail = feature.properties.incidentDetail ? feature.properties.incidentDetail : "";

          if (feature.properties.incidentDetail) {
            var test = 1;
          }

          var message = "";
          if (feature.properties.status) {
            message = feature.properties.status == "open" || feature.properties.status == "new" ? OPEN_ISSUE_MESSAGE : SOLVED_ISSUE_MESSAGE;
            message = "<medium><em>" + message + "</em></medium>";
          }
          return `<p><b>${name}</b></p>${detail}${incidentDetail}${message}`;
        }
  
        // EVENT HANDLERS ///////////////////////////////

        // this handler is called for primary layer features and incident layer features,
        // so the markers might represent assets or incidents, but only assets should be selectable.
        function handleMarkerClick(marker) {

          L.DomEvent.preventDefault(marker);
          // if there was a previously set marker, reset it...
          resetLocationMarker();
          resetClickedMarker();
          clearLocationFields();

          if (isAssetSelectable(marker)) {
            selectAsset(marker);
          } else {
            // user clicked something not selectable; reset data collection fields
            clearLocationFields();
          }
        }

        function handleLocationTypeClick(radios) {
          // this might get called when the parks list is disabled, do a null check first
          var select = $('#location_park');
          if (select.length < 1) return;

          // reset park list; it may have been changed
          $('#location_park')[0].selectedIndex = 0;
  
          // if type is not private, the map is exposed but needs to be redrawn
          locationType = radios.val();
          if (locationType != 'private') {
            redrawMap();
          }
  
        }
  
        function handleLocateButtonClick(e) {
          cancelEventBubble(e);
          locationErrorShown = false;
          selfLocateBrowser();
        }
  
        function handleAerialButtonClick(e) {
          cancelEventBubble(e);
          locationErrorShown = false;
          toggleAerialView();
        }

        function handleMapClick(e) {
          doMapClick(e.latlng);
        }
  
        function handleLocateMeFound(e) {
          if (locCircle) {
            map.removeLayer(locCircle);
          }
          var radius = e.accuracy;
          locCircle = L.circle(e.latlng, radius, { weight: 2, fillOpacity: 0.1 }).addTo(map);
          reverseGeolocate(e.latlng);
          locateControlContaier.style.backgroundImage = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_locate_on.png")';
  
          closeStatusModal();
        }
  
        function handleLocationError(e) {
          var message = e.message;
          statusModal.dialog('close');
          showStatusModal(message);
          locateControlContaier.style.backgroundImage = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_locate.png")';
        }

        function handleZoomEndShowGeoJsonLayer() {
          // close all popups when zooming
          map.closePopup();
          
          var zoomlevel = map.getZoom();
          if (zoomlevel < featureLayerVisibleZoom){
            if (primaryLayer && map.hasLayer(primaryLayer)) {
                map.removeLayer(primaryLayer);
            }
            if (incidentsLayer && map.hasLayer(incidentsLayer)) {
              map.removeLayer(incidentsLayer);
            }
            if (testPolygonLayer && map.hasLayer(testPolygonLayer)) {
              map.removeLayer(testPolygonLayer);
            }
          }
          if (zoomlevel >= featureLayerVisibleZoom){
            if (primaryLayer && !map.hasLayer(primaryLayer)){
              map.addLayer(primaryLayer);
            }
            if (incidentsLayer && !map.hasLayer(incidentsLayer)) {
              map.addLayer(incidentsLayer);
            }
            if (testPolygonLayer && !map.hasLayer(testPolygonLayer)) {
              map.addLayer(testPolygonLayer);
            }
          }
          // TODO: if we only want to add markers in the visible area of the map after zooming in to a certain level,
          // use getBounds to get the polygon that represents the map viewport, then check markers to see if they're contained.
          // var bounds = map.getBounds();
          // console.log(bounds);
        }

        // HELPER FUNCTIONS ///////////////////////////////
        function checkRegion(latlng) {
          // determine whether click is within a region on the primaryLayer layer, and store the region_id.
          // this is done if the primary layer type is Region, or if the regions layer is populated.
          if (primaryLayerType == PRIMARY_LAYER_TYPE.Region || regionsLayerSource) {
            var testLayer;
            if (regionsLayer) {
              testLayer = regionsLayer;
            } else {
              testLayer = primaryLayer;
            }
            var inLayer = leafletPip.pointInLayer(latlng, testLayer, false);
            if (inLayer.length > 0) {
              // NOTE: The following code would be problematic if we allow multiple copies of the widget or alternate naming conventions.
              $('input[name=' + elementId + '\\[location_region_id\\]]').val(inLayer[0].feature.properties.region_id);
            } else {
              // clear region_id field
              $('input[name=' + elementId + '\\[location_region_id\\]]').val("");
            }
          }
        }

        function handleCityLimits(latlng) {
          // we want to call this late in the event handling process, so that previously collected coordinates
          // or address values are cleared first.
          if (requireCityLimits) {
            // check if click is within city limits. if not, use js alert to show error message and return null.
            var inLayer = leafletPip.pointInLayer(latlng, cityBoundaryLayer, false);
            if (inLayer.length <= 0) {
              showStatusModal(CITY_LIMITS_MESSAGE);
              return false;
            } else {
              // TODO: If/when we start supporting clicks in other municipalities, this will need to be more dynamic.
              // right now it only supports Portland city limits.
              if (cityBoundaryLayer.municipality == "Portland") {
                $('#location_is_portland').val("Yes");
                $('#location_municipality_name').val(cityBoundaryLayer.municipality);
              }
            }
          }
          return true;
        }

        function doMapClick(latlng) {
          // normally when the map is clicked, we want to zoom to the clicked location
          // and perform a reverse lookup. there are some cases where we may want to 
          // perform additional actions. for example, if location type = park, we also
          // need to do a reverse parks lookup and adjust the park selector accordingly.

          // if primary layer behavior is selection-only, we don't allow map clicks, but we
          // still want to zoom in on that location.
          // if (primaryLayerBehavior == PRIMARY_LAYER_BEHAVIOR.SelectionOnly) return false;

          resetClickedMarker();
          resetLocationMarker();
          clearLocationFields();
  
          // clear place name and park selector fields; they will get reset if appropriate after the click.
          $('.place-name').val("");
          $('#location_park').val("");
  
          if (locCircle) {
            map.removeLayer(locCircle);
            locateControlContaier.style.backgroundImage = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_locate.png")';
          }
  
          reverseGeolocate(latlng);
          checkRegion(latlng);
        }
  
        function isAssetSelectable(marker) {
          // defines the criteria under which an asset can be selected
          if (primaryLayerBehavior != PRIMARY_LAYER_BEHAVIOR.Selection && primaryLayerBehavior != PRIMARY_LAYER_BEHAVIOR.SelectionOnly) {
            return false;
          }
          if (marker.target.feature.properties.hasOpenIncident && marker.target.feature.properties.status != TICKET_STATUS.Solved) {
            return false;
          }
          return true;
        }

        function selectAsset(marker) {
          // NOTE: at this time, only assets are selectable. if we got to this point,
          // we can assume the marker is an asset. no need to handle marker clicks for
          // non-asset incidents since the popup behavior still occurs by default.

          // IMPORTANT: store original marker icon, so we can swap back
          // function resetClickedMarker restores the original icon
          marker.originalIcon = marker.target.options.icon;

          // update asset marker to use use selected marker icon
          newIcon = L.icon({ iconUrl: selectedMarker });
          marker.target.setIcon(newIcon);
          L.DomUtil.addClass(marker.target._icon, 'selected');

          // store a reference to the new marker so we can revert it later if needed
          clickedMarker = marker;

          // set location form fields with asset data
          captureSelectedAssetMarkerData(marker);

          // need to call reverseGeolocate in order to capture nearest address,
          // this is the one instance where we don't want to zoom and center when clicking 
          // an existing marker (2nd argument = false).
          reverseGeolocate(marker.latlng, false);
        }
  
        function resetLocationMarker() {
          if (locationMarker) {
            map.removeLayer(locationMarker);
            locationMarker = null;
          }
        }
  
        function resetClickedMarker() {
          if (clickedMarker && clickedMarker.target && clickedMarker.target._icon) {
            // reset clicked marker's icon to original
            clickedMarker.target.setIcon(clickedMarker.originalIcon);
            L.DomUtil.removeClass(clickedMarker.target._icon, 'selected');
            //map.closePopup();
          }
        }

        function clearLocationFields() {
          // whenever the map is clicked, we want to clear all the location fields that
          // get populated by the location, such as lat, lon, address, region id, etc.
          // every map click essentially resets the previous click. this function clears
          // the relevant location fields.
          $('#location_address').val('');
          $('#location_lat').val('');
          $('#location_lon').val('');
          $('#location_x').val('');
          $('#location_y').val('');
          $('#place_name').val('');
          $('#location_asset_id').val('');
          $('#location_region_id').val('');
          $('#location_municipality_name').val('');
          $('#location_is_portland').val('');
        }

        // this is the helper function that fires when an asset marker is clicked.
        function setLocationType(type) {
          // NOTE: The following code would be problematic if we allow multiple copies of the widget or alternate naming conventions.
          $("input[name='" + elementId + "[location_type]'][value='" + type + "']").click();
        }
  
        function redrawMap() {
          // if map is initialized while hidden, this function needs to be called when the map is
          // exposed, so it can redraw the tiles.
          //map.invalidateSize();
          setTimeout(function () { map.invalidateSize(); }, 200);
        }
  
        function verifyAddressPortlandMaps(address) {
          var encodedAddress = encodeURI(address);
          // API documentation: https://www.portlandmaps.com/development/#suggest
          var url = "https://www.portlandmaps.com/api/suggest/?intersections=1&alt_coords=1&api_key=" + drupalSettings.portlandmaps_api_key + "&query=" + encodedAddress;
          $.ajax({
            url: url, success: function (response) {
              if (response.length < 1 || (response.candidates && response.candidates.length < 1)) {
                showStatusModal("No matching locations found. Please try a different address and try again.");
                setUnverified();
                return false;
              } else if (response.error) {
                showErrorModal(response.error.message);
                setUnverified();
                return false;
              }
              processLocationData(response.candidates);
            }
          });
  
        }
  
        function processLocationData(candidates) {
  
          if (candidates.length > 1) {
            // multiple candidates, how to handle? how about a modal dialog?
            suggestionsModal = $('#suggestions_modal');
            var listMarkup = "<p>Please select an address by clicking it.</p><ul>";
            for (var i = 0; i < candidates.length; i++) {
              var c = candidates[i];
              var fulladdress = buildFullAddress(c);
              listMarkup += '<li><a href="#" class="pick" data-lat="' + c.attributes.lat + '" data-lng="' + c.attributes.lon + '" data-pick-address="' + fulladdress + '">' + fulladdress.toUpperCase() + '</a></li>';
            }
            listMarkup += "</ul>";
            suggestionsModal.html(listMarkup);
            Drupal.dialog(suggestionsModal, {
              title: 'Multiple possible matches found',
              width: '600px',
              buttons: [{
                text: 'Close',
                click: function () {
                  $(this).dialog('close');
                }
              }]
            }).showModal();
            suggestionsModal.removeClass('visually-hidden');
          } else if (candidates.length == 1) {
            // if only one candidate, immediately locate it on the map
            var lat = candidates[0]["attributes"]["lat"];
            var lng = candidates[0]["attributes"]["lon"];
            // put full address in field
            var fulladdress = buildFullAddress(candidates[0]);
            $('.location-picker-address').val(fulladdress);

            // there is currently a bug in the json provided by PortlandMaps when a singular address is
            // returned by the address verification query. the lat and lon are null in that case. as a
            // temporary workaround, only set the location marker if the values are present, and populate
            // the required lat/lon fields with zeroes so that the form can still be submitted. at least
            // it will capture the address, and the report will still be usable.
            if (lat && lng) {
              doZoomAndCenter(lat, lng);
              checkRegion(new L.LatLng(lat, lng));
              if (primaryLayerBehavior != PRIMARY_LAYER_BEHAVIOR.SelectionOnly) {
                setLocationMarker(lat, lng);
              } else {
                showStatusModal(ASSET_ONLY_SELECTION_MESSAGE);
              }
            } else {
              setLatLngHiddenFields(0, 0);
              showStatusModal(VERIFIED_NO_COORDS);
            }

            setVerified();
          } else {
            // no matches found
            showStatusModal("No matches found. Please try again.");
          }
        }

        function doZoomAndCenter(lat, lng, zoomLevel = DEFAULT_ZOOM_CLICK) {
          if (lat != "0" && lng != "0") {
            map.setView([lat, lng], zoomLevel);
          }
          
        }

        function captureSelectedAssetMarkerData(marker) {
          // copy asset title to place name field
          if (marker.target.feature.properties.name) {
            $('#place_name').val(marker.target.feature.properties.name);
          }

          // copy asset detail to location details field.
          // asset detail will be html formatted. strip tags first?
          if (marker.target.feature.properties.detail) {
            var detail = marker.target.feature.properties.detail.replace("<br>", "\n");
            detail = $(detail).text();
            $('#location_details').val(detail);
          }

          // fields that are input type="hidden" need to be selected by name attribute; they won't have ids
          setLatLngHiddenFields(marker.latlng.lat, marker.latlng.lng);
          // NOTE: The following code would be problematic if we allow multiple copies of the widget or alternate naming conventions.
          $('input[name=' + elementId + '\\[location_asset_id\\]]').val(marker.target.feature.properties.id);
        }

        function setLatLngHiddenFields(lat, lng) {
          if (!lat) lat = "0";
          if (!lng) lng = "0";
          $('input[name=' + elementId + '\\[location_lat\\]]').val(lat);
          $('input[name=' + elementId + '\\[location_lon\\]]').val(lng);
          console.log('Set coordinates: ' + $('input[name=' + elementId + '\\[location_lat\\]]').val() + ', ' + $('input[name=' + elementId + '\\[location_lon\\]]').val());

          var sphericalMerc = L.Projection.SphericalMercator.project(L.latLng(lat,lng));
          $('input[name=' + elementId + '\\[location_x\\]]').val(sphericalMerc.x);
          $('input[name=' + elementId + '\\[location_y\\]]').val(sphericalMerc.y);
          console.log('Set spherical mercator coordinates: ' + $('input[name=' + elementId + '\\[location_x\\]]').val() + ', ' + $('input[name=' + elementId + '\\[location_y\\]]').val());
        }

        // set location marker on map. this is only used with map clicks, not marker clicks.
        // asset marker clicks are handled by the selectAsset(marker) function.
        // gets called when:
        // -  map initialization, if postback and hidden lat/lng are set
        // -  when an address suggestion is clicked
        // -  processLocationData(candidates) if only one address selection is returned
        // -  reverseGeolocate returns a park (reverseGeolocatePark)
        // -  reverseGeolocate doesn't return a park (reverseGeolocateNotPark)
        function setLocationMarker(lat, lng) {

          // remove previous location marker
          if (locationMarker) {
            map.removeLayer(locationMarker);
            locationMarker = null;
          }

          setLatLngHiddenFields(lat, lng);

          // there is a small bug in PortlandMaps that sometimes causes lat/lng to not be provided.
          // we use zeroes instead, but don't want to set a marker or zoom in to 0,0 (also known as Null Island).
          if (lat == "0" && lng == "0") {
            return false;
          }

          locationMarker = L.marker([lat, lng], { icon: defaultSelectedMarkerIcon, draggable: true, riseOnHover: true, iconSize: DEFAULT_ICON_SIZE  }).addTo(map);

          // if address marker is moved, we want to capture the new coordinates
          locationMarker.off();
          locationMarker.on('dragend', function (e) {
            var latlng = locationMarker.getLatLng();
            setLatLngHiddenFields(latlng.lat,latlng.lng);
            reverseGeolocate(latlng);
          });
        }

        // takes selected coordinates and performs reverse geolocation using the PortlandMaps API. 
        // gets called by:
        // - function handleMapClick(e)
        // - function handleLocateMeFound(e)
        // - handleMarkerClick > selectAsset(marker)
        // - locationMarker.on('dragend')
        function reverseGeolocate(latlng, zoomAndCenter = true) {
          // this function performs two reverse geocoding lookups. one checks whether the click is inside a park.
          // if it's not, the second lookup is done using the ArcGIS API to find the address/place of the clicked coorodinates.
          var lat = latlng.lat;
          var lng = latlng.lng;
          setUnverified();
          shouldRecenterPark = false;

          // clear fields
          $('#location_address').val();

  
          // performs parks reverse geocoding using portlandmaps.com API.
          // the non-parks reverse geocoding is called within the success function,
          // chaining the two calls together.
          if (useParks) {
            // the reverseGeolocateParks function also calls reverseGeolocateNotPark
            reverseGeolocateParks(lat, lng, zoomAndCenter);
          } else {
            reverseGeolocateNotPark(lat, lng, zoomAndCenter);
          }
        }
  
        function reverseGeolocateParks(lat, lng, zoomAndCenter = true) {
          var reverseParksGeocodeUrl = PARKS_REVGEOCODE_URL.replace('${lat}', lat).replace('${lng}', lng);
          $.ajax({
            url: reverseParksGeocodeUrl, success: function (result) {
              var jsonResult = JSON.parse(result);
              
              if (jsonResult.features && jsonResult.features.length > 0) {
                // it's a park. process the data from portlandmaps and exit function.
  
                setLocationType("park");
                if (zoomAndCenter) {
                  doZoomAndCenter(lat, lng);    
                  if (primaryLayerBehavior != PRIMARY_LAYER_BEHAVIOR.SelectionOnly) {
                    setLocationMarker(lat, lng);
                  } else {
                    showStatusModal(ASSET_ONLY_SELECTION_MESSAGE);
                  }
                }
  
                // attempt to set park selector. if not exact match, set selector
                // value to "0" (Other/Not found)
                var parkName = jsonResult.features[0].attributes.NAME;
                $('#location_park option').filter(function () {
                  return $(this).text() == parkName;
                }).prop('selected', true);
  
                var parkId = $('#location_park').val();
                if (!parkId) {
                  // the park selector could not be set, most likely because there was not an exact name match
                  // between Portland.gov and PortlandMaps.com. ideally, the names will be synchronized and
                  // and this condition will never occur.
                  $('#location_park').val("0"); // this value sets park selector to Other/Not found and keeps the map visible
                }
                $('#location_park').trigger('change');
  
                // set place name field and mark as verified
                $('.place-name').val(parkName);
                setVerified("park");
  
                // There shouldn't be an address for a park, so use N/A
                $('.location-picker-address').val("N/A");

              } else {
                // it's not a park and not managed by Parks!
  
                // if location type is set to parks, but we got to this point, we want to
                // switch the type to "other" so that it goes to 311 for triage.
                if (locationType == "park") {
                  zoomAndCenter = true;
                  setLocationType("other");
                }
  
                $('#location_park').val('0'); // set park selector to 
                $('#location_park').trigger('change');

                reverseGeolocateNotPark(lat, lng, zoomAndCenter);
  
              }
            }
          });
        }
  
        function reverseGeolocateNotPark(lat, lng, zoomAndCenter = true) {
          // now do PortlandMaps ArcGIS reverse geocoding call to get the non-park address for the location
          // https://www.portlandmaps.com/arcgis/rest/services/Public/Geocoding_PDX/GeocodeServer/reverseGeocode
          var reverseGeocodeUrl = REVGEOCODE_URL.replace('${lat}', lat).replace('${lng}', lng);
          $.ajax({
            url: reverseGeocodeUrl, success: function (response) {
              if (response.length < 1 || !response.address || !response.location) {
                // portlandmaps doesn't have data for this location.
                // set location type to "other" so 311 can triage but still set marker.
                // address field may be required by the form, so something needs to go there.
                if (!handleCityLimits(L.latLng(lat, lng))) return false;
                if (zoomAndCenter) {
                  doZoomAndCenter(lat, lng);    
                  if (primaryLayerBehavior != PRIMARY_LAYER_BEHAVIOR.SelectionOnly) {
                    setLocationMarker(lat, lng);
                  } else {
                    showStatusModal(ASSET_ONLY_SELECTION_MESSAGE);
                  }
                }
                if (locationType == "park") {
                  setLocationType("other");
                }
                if (response.error) {
                  
                  $('#location_address').val("N/A");
                  setUnverified();
                } else if (response && response.features && response.features[0].attributes && response.features[0].attributes.NAME) {
                  var locName = response.features[0].attributes.NAME;
                  $('#location_address').val(locName);
                  setUnverified();
                };
                return false;
                // showStatusModal("There was a problem retrieving data for the selected location.");
              }

              if (handleCityLimits(L.latLng(lat,lng))) {
                processReverseLocationData(response, lat, lng, zoomAndCenter);
              }
            }
          });
        }

        function processReverseLocationData(data, lat, lng, zoomAndCenter = true) {
          if (zoomAndCenter) {
            doZoomAndCenter(lat, lng);    
            if (primaryLayerBehavior != PRIMARY_LAYER_BEHAVIOR.SelectionOnly) {
              setLocationMarker(lat, lng);
            } else {
              showStatusModal(ASSET_ONLY_SELECTION_MESSAGE);
            }
          }
          var street = data.address.Street;
          var city = data.address.City;
          var state = data.address.State;
          var postal = data.address.ZIP;
          var addressLabel = street.length > 0 ? street + ', ' + city + ', ' + state + ' ' + postal : city;
          $('.location-picker-address').val(addressLabel);
          setVerified();
        }
  
        function locateParkFromSelector(id) {
          // this function is triggered by the parks selector onchange. when user selects a park, look up the
          // lat/lng and show it on the map. HOWEVER, the selector might get updated if the user clicks into a
          // park on the map. in that case, we skip the onchange park geolocation.
          // this function uses a view in Drupal to return parks data using the node id as a parameter.
          
          if (!shouldRecenterPark) {
            shouldRecenterPark = true;
            return false;
          }
  
          // if user selected the "Other/Not found" option (value = "0"), don't do the lookup,
          // but do unhide the map and redraw it.
          if (id === "0") {
            // change location type to "I'm not sure"
            setLocationType("other");
            redrawMap(); // workaround for map redraw issue when initialized while hidden
            return false;
          }
          var url = '/api/parks/locationpicker/' + id; // this is a drupal view that returns json about the park
          // this lookup uses the Park Finder view, which is a search view.
          // if there is a problem with the search index, in particular in
          // a local environment, it will not return results but should still
          // work in a multidev or Live.
          $.ajax({
            url: url, success: function (result) {
              if (result.length < 1) {
                showStatusModal(NOT_A_PARK);
                setUnverified();
                return;
              }
  
              // the coordinates returned by this call are for park entrances. some may have more than
              // one entrance, such as large parks like Washington Park. we use the first one in the array.
              // the geolocaiton data needs to be escaped; best way is in a textarea element (kludgey but works).
              var txt = document.createElement("textarea");
              txt.innerHTML = result[0].location;
              var json = JSON.parse(txt.value);
              var lng = json.coordinates[0];
              var lat = json.coordinates[1];
              setLocationMarker(lat, lng);
              doZoomAndCenter(lat, lng);    
              redrawMap();
              setVerified("park");
            }
          });
        }
  
        function selfLocateBrowser() {
          var t = setTimeout(function () {
            // display status indicator
            showStatusModal("Triangulating on your current location. Please wait...");
            map.locate({ watch: false, setView: true, maximumAge: GEOLOCATION_CACHE_MILLISECONDS, enableHighAccuracy: true });
          }, 500);
        }
  
        function toggleAerialView() {
          if (currentView != "aerial") {
            map.removeLayer(baseLayer);
            map.addLayer(aerialLayer);
            currentView = "aerial";
            // show icon active
            aerialControlContainer.style.background = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_base.png")';
          } else {
            map.removeLayer(aerialLayer);
            map.addLayer(baseLayer);
            currentView = "base";
            aerialControlContainer.style.background = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_aerial.png")';
          }
        }
  
        function buildFullAddress(c){
          return [c.address, c.attributes.city, c.attributes.state]
                  .filter(Boolean)
                  .join(', ')
                  + ' ' + (c.attributes.zip_code || '');
        }
  
        function showStatusModal(message) {
          statusModal.html('<p class="status-message">' + message + '</p>');
          Drupal.dialog(statusModal, {
            width: '600px',
            buttons: [{
              text: 'Close',
              click: function () {
                $(this).dialog('close');
              }
            }]
          }).showModal();
          statusModal.removeClass('visually-hidden');
        }
  
        function closeStatusModal() {
          statusModal.dialog('close');
        }
  
        function showErrorModal(message) {
          message = message + '<br><br>Please try again in a few moments. If the error persists, please <a href="/feedback">contact us</a>.';
          showStatusModal(message);
        }
  
        function setVerified(type = "address") {
          $('.verified-checkmark.' + type).removeClass('invisible');
          $('.location-verify.' + type).prop('disabled', true);
        }
  
        function setUnverified(type = "address") {
          $('.verified-checkmark').addClass('invisible');
          $('.location-verify').prop('disabled', false);
        }
  
        function cancelEventBubble(e) {
          var evt = e ? e : window.event;
          if (evt.stopPropagation) evt.stopPropagation();
          if (evt.cancelBubble != null) evt.cancelBubble = true;
        }
  
        // this function monitors our map div and fires redrawMap function if visibility changes.
        function onVisible(element, callback) {
          new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
              if(entry.intersectionRatio > 0) {
                callback(element);
                observer.disconnect();
              }
            });
          }).observe(element);
        }
        onVisible(document.querySelector("#location_map_container"), () => redrawMap());
  




      });

    }
  };
})(jQuery, Drupal, drupalSettings);
