(function ($, Drupal, drupalSettings, L) {

  var initialized = false;
  var map;

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
    attach: function (context) {

      var test = $('main');

      $(once('location_picker', 'fieldset.portland-location-picker--wrapper', context)).each(function () {

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
        const NOT_A_PARK = "You selected park or natural area as the property type, but no park data was found for the selected location. If you believe this is a valid location, please zoom in to find the park on the map, tap or click to select a location, and continue to submit your report.";
        const OPEN_ISSUE_MESSAGE = "If this issue is what you came here to report, there's no need to report it again.";
        const SOLVED_ISSUE_MESSAGE = "This issue was recently solved. If that's not the case, or the issue has reoccured, please submit a new report.";
        const VERIFIED_NO_COORDS = "The address you entered is valid, but an error occurred, and it can't be shown on the map. Please zoom in and find the desired location, then click it to set a marker.";
        const VERIFY_ADDRESS_TEXT = "Enter an address, then click the button to verify the location. Or click the map to select a location and determine the verified address.";
        const DEFAULT_FEATURE_ICON_URL = "/modules/custom/portland/modules/portland_location_picker/images/map_marker_default.png";
        const DEFAULT_INCIDENT_ICON_URL = "/modules/custom/portland/modules/portland_location_picker/images/map_marker_incident.png";
        const DEFAULT_SOLVED_ICON_URL = "/modules/custom/portland/modules/portland_location_picker/images/map_marker_incident_solved.png";
        const ERROR_MODAL_DEFAULT_TEXT = 'Please try again in a few moments. If the error persists, let us know using the <a href="/feedback?subject=The%20page%20looks%20broken">website feedback form</a>. You can also call us at <a href="tel:311">311</a>&nbsp;or&nbsp;<a href="tel:+15038234000">503-823-4000</a>.';
        const NO_MATCHING_ADDRESS_TEXT = "Sorry, we were unable to locate that address. Please try a different address nearby, or zoom in and find it on the map.";
        const NO_MATCHING_ADDRESS_TEXT_VERIFY_MODE = "Sorry, we were unable to locate that address. Please verify the address was entered correctly.";
        const REVERSE_GEOCODE_URL = 'https://www.portlandmaps.com/api/intersects/?geometry=%7B%20%22x%22:%20${x},%20%22y%22:%20${y},%20%22spatialReference%22:%20%7B%20%22wkid%22:%20%223857%22%7D%20%7D&include=all&detail=1&api_key=${apiKey}';
        const API_BOUNDARY_URL = "https://www.portlandmaps.com/arcgis/rest/services/Public/Boundaries/MapServer/0/query";
        const API_PARKS_BOUNDARY_URL = "https://www.portlandmaps.com/arcgis/rest/services/Public/Parks_Misc/MapServer/2/query?where=1%3D1&f=geojson";
        const PRIMARY_LAYER_TYPE = {
          Asset: "asset",
          Incident: "incident",
          Region: "region",
          RegionHidden: "region-hidden"
        }
        const PRIMARY_LAYER_BEHAVIOR = {
          Selection: "selection",
          Information: "information",
          SelectionOnly: "selection-only",
          GeoFence: "geofence"
        }
        const TICKET_STATUS = {
          New: "new",
          Open: "open",
          Referred: "referred",
          Solved: "solved",
          Closed: "closed"
        }
        // the hidden location_type field should only have one of these values; these match the old
        // radio buttons that have been replaced by the location type service.
        const LOCATION_TYPE = {
          Street: "street",
          Private: "private",
          Park: "park",
          Waterway: "waterway",
          Other: "other"
        }

        const GEOLOCATION_CACHE_MILLISECONDS = 0;

        // GLOBALS //////////
        var primaryLayer;
        var incidentsLayer;
        var regionsLayer;
        var cityBoundaryLayer;
        var boundaryLayer;
        var municipalitiesLayer;
        var primaryFeatures;
        var incidentsFeatures;
        var regionsFeatures;
        var locationMarker;
        var locCircle;
        var locateControlContaier;
        var clickedMarker;
        var suggestionsModal;
        var statusModal;
        var baseLayer;
        var aerialLayer;
        var currentView = "base";
        var baseLayer = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete/MapServer/tile/{z}/{y}/{x}', { attribution: "PortlandMaps ESRI" });
        var aerialLayer = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete_Aerial/MapServer/tile/{z}/{y}/{x}', { attribution: "PortlandMaps ESRI" });
        var LocateControl = generateLocateControl();
        var AerialControl = generateAerialControl();
        var verifyHidden = false;

        // CUSTOM PROPERTIES SET IN WEBFORM CONFIG //////////
        var addressVerify = drupalSettings.webform ? drupalSettings.webform.portland_location_picker.address_verify : "";
        var elementId = drupalSettings.webform ? drupalSettings.webform.portland_location_picker.element_id : "";
        var primaryLayerSource = drupalSettings.webform ? drupalSettings.webform.portland_location_picker.primary_layer_source : "";
        var incidentsLayerSource = drupalSettings.webform ? drupalSettings.webform.portland_location_picker.incidents_layer_source : "";
        var regionsLayerSource = drupalSettings.webform ? drupalSettings.webform.portland_location_picker.regions_layer_source : "";
        var primaryLayerBehavior = drupalSettings.webform ? drupalSettings.webform.portland_location_picker.primary_layer_behavior : "";
        var primaryLayerType = drupalSettings.webform ? drupalSettings.webform.portland_location_picker.primary_layer_type : "";
        var primaryMarkerUrl = drupalSettings.webform && drupalSettings.webform.portland_location_picker.primary_marker ? drupalSettings.webform.portland_location_picker.primary_marker : '/modules/custom/portland/modules/portland_location_picker/images/map_marker_default.png';
        var selectedMarker = drupalSettings.webform && drupalSettings.webform.portland_location_picker.selected_marker ? drupalSettings.webform.portland_location_picker.selected_marker : '/modules/custom/portland/modules/portland_location_picker/images/map_marker_default_selected.png';
        var incidentMarker = drupalSettings.webform && drupalSettings.webform.portland_location_picker.incident_marker ? drupalSettings.webform.portland_location_picker.incident_marker : '/modules/custom/portland/modules/portland_location_picker/images/map_marker_incident.png';
        var verifyButtonText = drupalSettings.webform ? drupalSettings.webform.portland_location_picker.verify_button_text : "";
        var featureLayerVisibleZoom = drupalSettings.webform && drupalSettings.webform.portland_location_picker.feature_layer_visible_zoom ? drupalSettings.webform.portland_location_picker.feature_layer_visible_zoom : FEATURE_LAYER_VISIBLE_ZOOM;
        var requireCityLimits = drupalSettings.webform && drupalSettings.webform.portland_location_picker.require_city_limits === true ? true : false;
        var requireCityLimitsPlusParks = drupalSettings.webform && drupalSettings.webform.portland_location_picker.require_city_limits_plus_parks === true ? true : false;
        var disablePlaceNameAutofill = drupalSettings.webform && drupalSettings.webform.portland_location_picker.disable_place_name_autofill === true ? true : false;
        var regionIdPropertyName = drupalSettings.webform && drupalSettings.webform.portland_location_picker.region_id_property_name ? drupalSettings.webform.portland_location_picker.region_id_property_name : "region_id";

        var boundaryUrl = drupalSettings.webform ? drupalSettings.webform.portland_location_picker.boundary_url : "";
        var displayBoundary = drupalSettings.webform && drupalSettings.webform.portland_location_picker.display_boundary === false ? false : true;
        var requireBoundary = drupalSettings.webform && drupalSettings.webform.portland_location_picker.require_boundary === true ? true : false;
        var outOfBoundsMessage = drupalSettings.webform ? drupalSettings.webform.portland_location_picker.out_of_bounds_message : "";

        var apiKey = drupalSettings.portlandmaps_api_key;

        var locationType;
        var locationTextBlock;

        var cityLimitsProperties = {
          color: 'red',
          fillOpacity: 0,
          weight: 1,
          dashArray: "2 4",
          interactive: false
        }

        var defaultSelectedMarkerIcon = L.icon({
          iconUrl: selectedMarker,
          iconSize: DEFAULT_ICON_SIZE, // size of the icon
          shadowSize: [0, 0], // size of the shadow
          iconAnchor: [13, 41], // point of the icon which will correspond to marker's location
          shadowAnchor: [0, 0],  // the same for the shadow
          popupAnchor: [0, -41]
        });

        // geofencing backwards compatibility
        if (requireCityLimits) {
          requireBoundary = true;
          boundaryUrl = "https://www.portlandmaps.com/arcgis/rest/services/Public/Boundaries/MapServer/0/query?where=1%3D1&objectIds=35&outFields=*&returnGeometry=true&f=geojson";
        }

        if (requireCityLimitsPlusParks) {
          requireBoundary = true;
          boundaryUrl = "https://www.portlandmaps.com/arcgis/rest/services/Public/CGIS_Layers/MapServer/33/query?where=1%3D1&outFields=*&returnGeometry=true&f=geojson";
        }

        // if ajax is used in the webform (for computed twig, for example), this script
        // and the initialize function may get called multiple times for some reason.
        // adding this flag prevents re-initialization of the map.
        initialized = initialize();

        // SETUP FUNCTIONS ///////////////////////////////

        function initialize() {

          // disable form submit when pressing enter on address field and click Verify button instead
          $('#location_search').on('keydown', function (e) {
            if (e.keyCode == 13) {
              e.preventDefault();
              if (!verifyHidden) {
                $('#location_verify').click();
              }
              return false;
            }
          });

          // INITIALIZE MAP //////////
          if (map) {
            map.off();
            map.remove();
            map = undefined;
          }
          var test = $('#location_map_container');
          if (test.length < 1) return false;
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

          // instantiate location text block but don't add it to the map until a location is selected.
          locationTextBlock = L.control({
            position: 'bottomleft'
          });

          locationTextBlock.onAdd = function (map) {
            var customElement = L.DomUtil.create('div', 'custom-control');
            // Prevent map interaction when clicking inside the control
            L.DomEvent.disableClickPropagation(customElement);
            customElement.innerHTML = `
              <div id="location-text-container">
                <div id="location-icon"><img src="/modules/custom/portland/modules/portland_location_picker/images/map_marker_default_selected.png"></div>
                <div id="location-text"><strong><span id="location-text-value"></span></strong><br>
                  lat: <span id="location-text-lat"></span>,&nbsp;lon: <span id="location-text-lng"></span></div>
              </div>`;
            customElement
            return customElement;
          };

          // only allow map clicks if primary layer behavior is not "selection." if it is, only asset markers can be clicked to select a locaiton.
          if (primaryLayerBehavior != PRIMARY_LAYER_BEHAVIOR.SelectionOnly) { map.on('click', handleMapClick); }

          // set up zoomend handler; we only want to show the primary features/assets layer when zoomed in
          // so that the map isn't too crowded with markers.
          map.on('zoomend', handleZoomEndShowGeoJsonLayer);

          // force a crosshair cursor
          $('.leaflet-container').css('cursor', 'crosshair');

          // Set up address verify button, autocomplete, and help text
          $('#location_search').after(`<input class="button button--primary location-verify js-form-submit form-submit" type="button" id="location_verify" name="op" value="${verifyButtonText}">`);
          $('#location_search').on('keyup', function (e) {
            if (e.keyCode != 13) {
              showVerifyButton();
            }
          });
          if (addressVerify) {
            $('#location_search').after('<span class="verified-checkmark address invisible" title="Location is verified!">✓</span>');
            // turn off verified checkmark if value changes
            $('#location_search').on('keyup', function () {
              setUnverified();
            });
            $('#location_address--description').text(VERIFY_ADDRESS_TEXT);
          }
          $(document).on('click', '#location_verify', function (e) {
            e.preventDefault();
            // Portland Maps API for location suggestions doesn't work property when an ampersand is used to identify intersections
            var address = $('#location_search').val().replace("&", "and");
            if (address.length < 1) { showStatusModal("Please enter an address or cross streets and try again."); return false; }
            verifyAddressPortlandMaps(address);
          });
          initializeSearchAutocomplete();

          // Set up pick links //////////////////////////////////
          $(document).on('click', 'a.pick', function (e) {
            e.preventDefault();
            // get address data from link
            var address = $(this).data('pick-address');
            // put selected address in search field
            $('#location_search').val(address);
            showVerifiedLocation(address);
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

          if (displayBoundary && boundaryUrl != "") {
            // defaults to true && [basic Portland boundary]
            initailizeBounaryLayer();
          }

          // INITIALIZE GEOJSON LAYERS //////////
          processGeoJsonData();

          // if there are coordinates in the hidden lat/lng fields, set the map marker.
          // this may be a submit postback that had validation errors, so we need to re set it.
          // WARNING: if boundaryLayer isn't loaded yet, this will fail in checkWithinBounds().
          restoreLocationFromPostback();
        }

        function restoreLocationFromPostback() {
          var verifiedAddress = addressVerify ? $('input[name=' + elementId + '\\[location_address\\]]').val() : undefined;
          var lat = $('input[name=' + elementId + '\\[location_lat\\]]').val();
          var lng = $('input[name=' + elementId + '\\[location_lon\\]]').val();
          if (lat && lng && lat !== "0" && lng !== "0") {
            showLoader();

            if (requireBoundary) {
              if (boundaryLayer) {
                setLocationMarker(lat, lng);
                doZoomAndCenter(lat, lng);
                doMapClick(new L.LatLng(lat, lng), verifiedAddress);

              } else {
                setTimeout(function () {
                  if (boundaryLayer) {
                    setLocationMarker(lat, lng);
                    doZoomAndCenter(lat, lng);
                    doMapClick(new L.LatLng(lat, lng), verifiedAddress);

                  } else {
                    restoreLocationFromPostback();
                  }
                }, 1000);
              }

            } else {
              setLocationMarker(lat, lng);
              doZoomAndCenter(lat, lng);
              doMapClick(new L.LatLng(lat, lng), verifiedAddress);
            }

            // WARNING: Need to wait until boundaryLayer and regions layers are loaded, if applicable

          }
        }

        function initializeSearchAutocomplete() {
          // set up search field with autocomplete ////////////////////////////
          $('#location_search').autocomplete({
            source: function (request, response) {
              const searchTerm = encodeURIComponent(request.term);
              var apiUrl = `https://www.portlandmaps.com/api/suggest/?intersections=1&landmarks=1&alt_coords=1&api_key=${apiKey}&query=${searchTerm}`;

              $.ajax({
                url: apiUrl,
                success: function (results) {
                  // Pass the results to the response callback
                  response(results.candidates);
                },
                error: function (e) {
                  // Handle any error cases
                  console.error(e);
                }
              });
            },
            minLength: 3,
            select: function (event, ui) {
              var address = ui.item.address;
              // if in address verify mode, add all details to address
              if (addressVerify) {
                address = buildFullAddress(ui.item);
              }

              $(this).val(address);
              var lat = ui.item.attributes.lat;
              var lon = ui.item.attributes.lon;
              var latlon = new L.LatLng(lat, lon);
              reverseGeolocate(latlon, true, address);
              $(this).autocomplete('close');
              $('.verified-checkmark.address').removeClass('invisible');
              return false; // returning true causes the field to be cleared
            },
            response: function (event, ui) {
              const items = ui.content;
              ui.content = items;
            },
            create: function () {
              $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
                var address = item.address;
                var fullAddress = item.address;
                fullAddress += item.attributes.city ? ", " + item.attributes.city : "";
                fullAddress += item.attributes.state ? ", " + item.attributes.state : "";
                fullAddress += item.attributes.zip_code ? "  " + item.attributes.zip_code : "";

                var lat = item.attributes.lat;
                var lon = item.attributes.lon;
                var locationType = item.attributes.location_type;

                // Create the <li> element with data attributes
                const li = $('<li>')
                  .attr('data-lat', lat)
                  .attr('data-lng', lon)
                  .attr('data-address', fullAddress)
                  .attr('data-location-type', locationType)
                  .append(address)
                  .appendTo(ul);

                return li;
              };
            }
          });
        }

        function initailizeBounaryLayer() {
          // new function. uses default Portland basic boundary from PortlandMaps with border visible by default.
          // visiblity and boundary URL (geoJSON) can be configure in widget custom properties.
          // boundary_url = "https://www.portlandmaps.com/arcgis/rest/services/Public/Boundaries/MapServer/0/query?where=1%3D1&objectIds=35&outFields=*&returnGeometry=true&f=geojson"
          // display_boundary = true

          if (boundaryUrl) {
            $.ajax({
              url: boundaryUrl,
              success: function (cityBoundaryResponse) {
                var cityBoundaryFeatures = cityBoundaryResponse.features;
                boundaryLayer = L.geoJson(cityBoundaryFeatures, cityLimitsProperties).addTo(map);
                if (boundaryLayer.municipality) {
                  boundaryLayer.municipality = cityBoundaryFeatures[0].properties.CITYNAME;
                }
                console.log("Boundary layer loaded.");
              },
              error: function (e) {
                // if the PortlandMaps API is down, this is where we'll get stuck.
                // any way to fail the location lookup gracefull and still let folks submit?
                // at least display an error message.
                console.error(e);
                showErrorModal("An error occurred while attemping to load the boundary layer.");
              }
            });
          }
        }

        function processGeoJsonData() {

          // if there are any layer in use, the Primary Layer must be used first.
          if (primaryLayerSource) {
            primaryLayer = L.geoJson(); // can we create this on the fly?

            showLoader();

            $.ajax({
              url: primaryLayerSource, success: function (primaryResponse) {
                primaryFeatures = primaryResponse.features;
                console.log(primaryFeatures.length + " features found on primary layer.");

                // pull in incidents layer, if applicable.
                // since the other layers are dependent on the primaryLayer, they need to be retrieved
                // in the success function of the primary layer ajax call. otherwise the we'd need to use
                // synchronous ajax calls, which is not ideal.
                if (incidentsLayerSource) {

                  $.ajax({
                    url: incidentsLayerSource, success: function (incidentsResponse) {
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
                          if (primaryFeatures[i].properties.id && primaryFeatures[i].properties.id == incidentsFeatures[j].properties.asset_id) {
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

                hideLoader();

                if (regionsLayerSource) {
                  showLoader();
                  $.ajax({
                    url: regionsLayerSource,
                    async: false,
                    success: function (regionsResponse) {
                      regionsFeatures = regionsResponse.features;
                      console.log(regionsFeatures.length + " regions found.");

                      initRegionsLayer(regionsFeatures, regionsLayer);
                      hideLoader();
                    },
                    error: function (e) {
                      // if the PortlandMaps API is down, this is where we'll get stuck.
                      // any way to fail the location lookup gracefull and still let folks submit?
                      // at least display an error message.
                      console.error(e);
                      showErrorModal("An error occurred while attemping to load the regions layer.");
                      hideLoader();
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
            iconUrl: markerUrl,
            iconSize: DEFAULT_ICON_SIZE,
            shadowSize: DEFAULT_ICON_SHADOW_SIZE,
            iconAnchor: DEFAULT_ICON_ANCHOR,
            shadowAnchor: DEFAULT_ICON_SHADOW_ANCHOR,
            popupAnchor: DEFAULT_ICON_POPUP_ANCHOR,
            className: "feature"
          });
          var incidentMarkerUrl = incidentMarkerUrl ? incidentMarkerUrl : DEFAULT_INCIDENT_ICON_URL;
          var incidentMarker = L.icon({
            iconUrl: incidentMarkerUrl,
            iconSize: DEFAULT_ICON_SIZE,
            shadowSize: DEFAULT_ICON_SHADOW_SIZE,
            iconAnchor: DEFAULT_ICON_ANCHOR,
            shadowAnchor: DEFAULT_ICON_SHADOW_ANCHOR,
            popupAnchor: DEFAULT_ICON_POPUP_ANCHOR,
            className: "incident"
          });
          var solvedMarkerUrl = solvedMarkerUrl ? solvedMarkerUrl : DEFAULT_SOLVED_ICON_URL;
          var solvedMarker = L.icon({
            iconUrl: solvedMarkerUrl,
            iconSize: DEFAULT_ICON_SIZE,
            shadowSize: DEFAULT_ICON_SHADOW_SIZE,
            iconAnchor: DEFAULT_ICON_ANCHOR,
            shadowAnchor: DEFAULT_ICON_SHADOW_ANCHOR,
            popupAnchor: DEFAULT_ICON_POPUP_ANCHOR,
            className: "incident solved"
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
            pointToLayer: function (feature, latlng) {
              if (feature.properties.hasOpenIncident ||
                (primaryLayerType == PRIMARY_LAYER_TYPE.Incident && (feature.properties.status == TICKET_STATUS.New || feature.properties.status == TICKET_STATUS.Open))) {
                marker = incidentMarker;
              } else if (feature.properties.hasSolvedIncident || (primaryLayerType == PRIMARY_LAYER_TYPE.Incident && feature.properties.status == TICKET_STATUS.Solved)) {
                marker = solvedMarker;
              } else {
                marker = standardMarker;
              }
              return L.marker(latlng, {
                icon: marker,
                draggable: false,
                riseOnHover: true,
                iconSize: DEFAULT_ICON_SIZE
              });
            },
            onEachFeature: function (feature, layer) {
              if (primaryLayerType == PRIMARY_LAYER_TYPE.RegionHidden) {
                layer.setStyle({ opacity: 0, fillOpacity: 0 });
              } else {
                // if this is a region, disable autopan. otherwise we want it on.
                var autoPanValue = primaryLayerType == PRIMARY_LAYER_TYPE.Region ? false : true;
                layer.bindPopup(generatePopupContent(feature), { maxWidth: 250, offset: L.point(0, 0), autoPan: autoPanValue });

                // if region, use mouseover to show popup
                if (primaryLayerType == PRIMARY_LAYER_TYPE.Region) {
                  layer.on("mouseover", function (e) { layer.openPopup(e.latlng); });
                  layer.on("mousemove", function (e) { layer.openPopup(e.latlng); });
                  layer.on("mouseout", function (e) { layer.closePopup(); });
                } else {
                }
              }

              if (primaryLayerType == PRIMARY_LAYER_TYPE.Region || primaryLayerType == PRIMARY_LAYER_TYPE.RegionHidden) {
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
            iconUrl: DEFAULT_INCIDENT_ICON_URL,
            iconSize: DEFAULT_ICON_SIZE,
            shadowSize: DEFAULT_ICON_SHADOW_SIZE,
            iconAnchor: DEFAULT_ICON_ANCHOR,
            shadowAnchor: DEFAULT_ICON_SHADOW_ANCHOR,
            popupAnchor: DEFAULT_ICON_POPUP_ANCHOR,
            className: "incident"
          });
          var solvedMarkerUrl = solvedMarkerUrl ? solvedMarkerUrl : DEFAULT_SOLVED_ICON_URL;
          var solvedMarker = L.icon({
            iconUrl: solvedMarkerUrl,
            iconSize: DEFAULT_ICON_SIZE,
            shadowSize: DEFAULT_ICON_SHADOW_SIZE,
            iconAnchor: DEFAULT_ICON_ANCHOR,
            shadowAnchor: DEFAULT_ICON_SHADOW_ANCHOR,
            popupAnchor: DEFAULT_ICON_POPUP_ANCHOR,
            className: "incident solved"
          });

          incidentsLayer = L.geoJson(features, {
            coordsToLatLng: function (coords) {
              return new L.LatLng(coords[1], coords[0]);
            },
            pointToLayer: function (feature, latlng) {
              if (feature.properties.status != TICKET_STATUS.Solved) {
                marker = incidentMarker;
              } else {
                marker = solvedMarker;
              }
              return L.marker(latlng, {
                icon: marker,
                draggable: false,
                riseOnHover: true,
                iconSize: DEFAULT_ICON_SIZE
              });
            },
            onEachFeature: function (feature, layer) {
              layer.bindPopup(generatePopupContent(feature), { maxWidth: 250, offset: L.point(0, 0) });
              // if region, use mouseover to show popup
              layer.on("click", handleMarkerClick);
            },
            interactive: true
          });
        }

        function initRegionsLayer(features, layer) {
          layer = L.geoJson(features, {
            color: 'red',
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
              locateControlContaier.title = 'Locate Me';
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
          if (zoomlevel < featureLayerVisibleZoom) {
            if (primaryLayer && map.hasLayer(primaryLayer)) {
              map.removeLayer(primaryLayer);
            }
            if (incidentsLayer && map.hasLayer(incidentsLayer)) {
              map.removeLayer(incidentsLayer);
            }
          }
          if (zoomlevel >= featureLayerVisibleZoom) {
            if (primaryLayer && !map.hasLayer(primaryLayer)) {
              map.addLayer(primaryLayer);
            }
            if (incidentsLayer && !map.hasLayer(incidentsLayer)) {
              map.addLayer(incidentsLayer);
            }
          }
          // var bounds = map.getBounds();
        }

        // HELPER FUNCTIONS ///////////////////////////////

        function checkWithinBounds(latlng) {
          // this handles checking whether map clicks are within the specified boundary.
          // returns TRUE or FALSE.

          // if boundaryUrl && requireBoundary:
          if (requireBoundary && boundaryUrl) {
            // use PiP library to determine whether map click was in bounds.
            var result = leafletPip.pointInLayer(latlng, boundaryLayer, false);

            if (result.length < 1) {
              // not within bounds, show message and return false.
              showStatusModal(outOfBoundsMessage);
              return false;
            } else {
              return true;
            }
          } else {
            // if none of the "require" boundary vars are true, return true to indicate the
            // click wasn't out of bounds.
            return true;
          }
        }

        function doMapClick(latlng, verifiedAddress) {
          // show loading indicator
          showLoader();

          // normally when the map is clicked, we want to zoom to the clicked location
          // and perform reverse geocoding.

          // if primary layer behavior is selection-only, or geofencing is enabled,
          // don't allow location selection, but still zoom in on that location and
          // display location description.

          resetClickedMarker();
          resetLocationMarker();
          clearLocationFields();

          if (locCircle) {
            map.removeLayer(locCircle);
            locateControlContaier.style.backgroundImage = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_locate.png")';
          }

          reverseGeolocate(latlng, true, verifiedAddress);
        }

        function showLoader() {
          $('.loader-container').css("display","flex");
        }

        function hideLoader() {
          $('.loader-container').css("display","none");
        }

        function checkRegion(latlng) {
          // determine whether click is within a region on the primaryLayer layer, and store the region_id.
          // this is done if the primary layer type is Region, or if the regions layer is populated.
          if (primaryLayerType == PRIMARY_LAYER_TYPE.Region || primaryLayerType == PRIMARY_LAYER_TYPE.RegionHidden || regionsLayerSource) {
            var testLayer;
            if (regionsLayer) {
              testLayer = regionsLayer;
            } else {
              testLayer = primaryLayer;
            }

            showLoader();
            if (testLayer.options.interactive) {
              checkClickInRegion(latlng, testLayer);
            } else {
              setTimeout(function () {
                if (testLayer.options.interactive) {
                  checkClickInRegion(latlng, testLayer);
                } else {
                  checkRegion(latlng);
                }
              }, 1000);
            }
          }
        }

        function checkClickInRegion(latlng, testLayer) {
          var inLayer = leafletPip.pointInLayer(latlng, testLayer, false);
          if (inLayer.length > 0) {
            // NOTE: The following code would be problematic if we allow multiple copies of the widget or alternate naming conventions.
            $('input[name=' + elementId + '\\[location_region_id\\]]').val(inLayer[0].feature.properties[regionIdPropertyName]).trigger('change');
          } else {
            // clear region_id field
            $('input[name=' + elementId + '\\[location_region_id\\]]').val("").trigger('change');
          }
          hideLoader();
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
          reverseGeolocate(marker.latlng, true);
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
          }
        }

        function clearLocationFields() {
          // whenever the map is clicked, we want to clear all the location fields that
          // get populated by the location, such as lat, lon, address, region id, etc.
          // every map click essentially resets the previous click. this function clears
          // the relevant location fields.
          $('input[name=' + elementId + '\\[location_lat\\]]').val('');
          $('input[name=' + elementId + '\\[location_lon\\]]').val('');
          $('input[name=' + elementId + '\\[location_x\\]]').val('');
          $('input[name=' + elementId + '\\[location_y\\]]').val('');
          $('input[name=' + elementId + '\\[location_asset_id\\]]').val('');
          $('input[name=' + elementId + '\\[location_region_id\\]]').val('').trigger('change');
          $('input[name=' + elementId + '\\[location_municipality_name\\]]').val('');
          $('input[name=' + elementId + '\\[location_attributes\\]]').val('');

          // only clear place name if autofill is disabled
          if (!disablePlaceNameAutofill) {
            $('input[name=' + elementId + '\\[place_name\\]]').val('');
          }
        }

        function setLocationDetails(results) {
          var location_type = "";
          $('input[name=' + elementId + '\\[location_types\\]]').val("");
          $('input[name=' + elementId + '\\[location_attributes\\]]').val("");
          var internal_details = "";
          var type_count = 0;

          if (results.taxlot) {
            $('input[name=' + elementId + '\\[location_type_taxlot\\]]').val("true");
            location_type += "taxlot,";
            var taxlot_id = results.detail.taxlot[0].property_id;
            internal_details += "Tax lot: " + taxlot_id + ', ';
            type_count += 1;
          }
          if (results.park) {
            $('input[name=' + elementId + '\\[location_type_park\\]]').val("true");
            location_type += "park,";
            var park_id = results.detail.park[0].id;
            internal_details += "Park id: " + park_id + ", ";
            type_count += 1;
          }
          if (results.waterbody) {
            $('input[name=' + elementId + '\\[location_type_waterbody\\]]').val("true");
            location_type += "waterbody,";
            var waterbody_id = results.detail.waterbody[0].id;
            var waterbody_type = results.detail.waterbody[0].type;
            internal_details += "Waterbody id: " + waterbody_id + ', Waterbody type: ' + waterbody_type + ", ";
            type_count += 1;
          }
          if (results.trail) {
            $('input[name=' + elementId + '\\[location_type_trail\\]]').val("true");
            location_type += "trail,";
            var trail_id = results.detail.trail[0].name;
            var trail_name = results.detail.trail[0].name;
            if (trail_id) {
              internal_details += "Trail id: " + trail_id + ", ";
            }
            if (trail_name) {
              internal_details += "Trail name: " + trail_name + ", ";
            }
            type_count += 1;
          }
          if (results.stream) {
            $('input[name=' + elementId + '\\[location_type_stream\\]]').val("true");
            location_type += "stream,";
            type_count += 1;
          }
          if (results.street) {
            $('input[name=' + elementId + '\\[location_type_street\\]]').val("true");
            location_type += "street,";
            type_count += 1;
          }
          if (results.row) {
            $('input[name=' + elementId + '\\[location_type_row\\]]').val("true");
            location_type += "row,";
            type_count += 1;
          }

          location_type = removeTrailingComma(location_type);
          internal_details = removeTrailingComma(internal_details);

          $('input[name=' + elementId + '\\[location_types\\]]').val(location_type);
          $('input[name=' + elementId + '\\[location_attributes\\]]').val(internal_details);

          if (results.detail.city) {
            var city_name = results.detail.city[0].name;
            $('input[name=' + elementId + '\\[location_municipality_name\\]]').val(city_name);
            $('input[name=' + elementId + '\\[location_municipality_name\\]]').trigger('change');
          }

          if (results.detail.zipcode) {
            var zipcode = results.detail.zipcode[0].zip;
            $('input[name=' + elementId + '\\[location_zipcode\\]]').val(zipcode);
          }

          $('input[name=' + elementId + '\\[location_type_taxlot\\]]').trigger('change');
          $('input[name=' + elementId + '\\[location_type_park\\]]').trigger('change');
          $('input[name=' + elementId + '\\[location_type_waterbody\\]]').trigger('change');
          $('input[name=' + elementId + '\\[location_type_trail\\]]').trigger('change');
          $('input[name=' + elementId + '\\[location_type_stream\\]]').trigger('change');
          $('input[name=' + elementId + '\\[location_type_street\\]]').trigger('change');
          $('input[name=' + elementId + '\\[location_type_row\\]]').trigger('change');

        }

        function removeTrailingComma(myStr) {
          if (!myStr) return "";
          myStr = myStr.trim();
          if (myStr.endsWith(',')) {
            var retval = myStr.substring(0, myStr.length - 1);
            return retval;
          }
          if (myStr.endsWith(', ')) {
            var retval = myStr.substring(0, myStr.length - 2);
            return retval;
          }
        }

        function redrawMap() {
          // if map is initialized while hidden, this function needs to be called when the map is
          // exposed, so it can redraw the tiles.
          setTimeout(function () { map.invalidateSize(); }, 200);
        }

        function verifyAddressPortlandMaps(address) {
          var encodedAddress = encodeURIComponent(address);
          // API documentation: https://www.portlandmaps.com/development/#suggest
          var url = "https://www.portlandmaps.com/api/suggest/?intersections=1&alt_coords=1&api_key=" + drupalSettings.portlandmaps_api_key + "&query=" + encodedAddress;
          $.ajax({
            url: url, success: function (response) {
              if (response.length < 1 || (response.candidates && response.candidates.length < 1)) {
                showStatusModal(addressVerify ? NO_MATCHING_ADDRESS_TEXT_VERIFY_MODE : NO_MATCHING_ADDRESS_TEXT);
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
              listMarkup += '<li><a href="#" class="pick" data-lat="' + c.attributes.lat + '" data-lng="' + c.attributes.lon + '" data-pick-address="' + c.address + '">' + fulladdress.toUpperCase() + '</a></li>';
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
            showVerifiedLocation(fulladdress, lat, lng);

            // put park name in place_name field
            if (candidates[0].attributes.location_type == "PARK" && !disablePlaceNameAutofill) {
              $('#place_name').val(fulladdress);
            }

            // in some rare cases, the lat and lon are null in json provided by PortlandMaps. as a
            // workaround, only set the location marker if the values are present, and populate
            // the required lat/lon fields with zeroes so that the form can still be submitted. at least
            // it will capture the address, and the report will still be usable.
            if (lat && lng) {
              doMapClick(new L.LatLng(lat, lng), fulladdress);
              setVerified();
            } else {
              setLatLngHiddenFields(0, 0);
              showStatusModal(VERIFIED_NO_COORDS);
            }

            setVerified();

          } else {
            // no matches found
            showStatusModal(addressVerify ? NO_MATCHING_ADDRESS_TEXT_VERIFY_MODE : NO_MATCHING_ADDRESS_TEXT);
          }
        }

        function doZoomAndCenter(lat, lng, zoomLevel = DEFAULT_ZOOM_CLICK) {
          if (lat && lng && lat != "0" && lng != "0") {
            map.setView([lat, lng], zoomLevel);
            return true;
          } else {
            console.log("Location could not be set (lat:" + lat + ", lon:" + lng + ")");
            showStatusModal(VERIFIED_NO_COORDS);
          }
          return false;
        }

        function hideVerifyButton() {
          $('#location_verify').addClass('disabled');
          verifyHidden = true;
          showPrecisionText();
        }

        function showVerifyButton() {
          $('#location_verify').removeClass('disabled');
          verifyHidden = false;
        }

        function showPrecisionText() {
          if (!addressVerify) {
            $('#precision_text').removeClass('visually-hidden');
          }
        }

        function hidePrecisionText() {
          $('#precision_text').addClass('visually-hidden');
        }

        function captureSelectedAssetMarkerData(marker) {
          // copy asset title to place name field
          if (marker.target.feature.properties.name && !disablePlaceNameAutofill) {
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

          var sphericalMerc = L.Projection.SphericalMercator.project(L.latLng(lat, lng));
          $('input[name=' + elementId + '\\[location_x\\]]').val(sphericalMerc.x);
          $('input[name=' + elementId + '\\[location_y\\]]').val(sphericalMerc.y);
        }

        function setLocationMarker(lat, lng) {

          // only do this if widget config allows
          if (primaryLayerBehavior == PRIMARY_LAYER_BEHAVIOR.SelectionOnly) {
            // Don't set marker if behavior is selection only. But do we want to show a help popup?
            return false;
          }

          hideVerifyButton();

          // remove previous location marker
          if (locationMarker) {
            map.removeLayer(locationMarker);
            locationMarker = null;
          }

          setLatLngHiddenFields(lat, lng);
          hideVerifyButton();

          // there is a small bug in PortlandMaps that sometimes causes lat/lng to not be provided.
          // we use zeroes instead, but don't want to set a marker or zoom in to 0,0 (also known as Null Island).
          if (lat == "0" && lng == "0") {
            return false;
          }

          locationMarker = L.marker([lat, lng], { icon: defaultSelectedMarkerIcon, draggable: true, riseOnHover: true, iconSize: DEFAULT_ICON_SIZE }).addTo(map);

          // if address marker is moved, we want to capture the new coordinates
          locationMarker.off();
          locationMarker.on('dragend', function (e) {
            var latlng = locationMarker.getLatLng();
            setLatLngHiddenFields(latlng.lat, latlng.lng);
            reverseGeolocate(latlng);
          });

          hideLoader();
        }

        function reverseGeolocate(latlng, zoomAndCenter = true, verifiedAddress) {
          setUnverified();
          shouldRecenterPark = false;
          var url = '';

          // if (!addressVerify) {
          url = REVERSE_GEOCODE_URL;
          // use new intersects API call to reverse geolocate and get location details and description
          var sphericalMerc = L.Projection.SphericalMercator.project(latlng);
          var x = sphericalMerc.x;
          var y = sphericalMerc.y;
          url = url.replace('${x}', x).replace('${y}', y).replace('${apiKey}', apiKey);

          $.ajax({
            url: url, success: function (response) {
              // if (response.length < 1 || !response.address || !response.location) {
              //   // location data not available, how to handle?
              //   console.log('Location not found');
              // }
              processReverseLocationData(response, latlng.lat, latlng.lng, zoomAndCenter, verifiedAddress);
            },
            error: function (e) {
              // if the PortlandMaps API is down, this is where we'll get stuck.
              // any way to fail the location lookup gracefull and still let folks submit?
              // at least display an error message.
              console.error(e);
              showErrorModal("An error occurred while attemping to obtain location information from PortlandMaps.com.");
            }

          });
        }

        function parseDescribeData(data, isWithinBounds) {
          var description = "";

          // if park, get park name
          if (data.park && data.detail.park[0].name != null) {
            description = data.detail.park[0].name.toUpperCase();
            if (!disablePlaceNameAutofill) {
              $('#place_name').val(data.detail.park[0].name);
            }
          } else if (data.waterbody && data.detail.waterbody[0].name != null) {
            description = data.detail.waterbody[0].name.toUpperCase();
          } else {
            // if within boudns, use data description. otherwise just city and zip
            // be displayed.
            if (isWithinBounds) {
              description = data.describe ? data.describe.toUpperCase() : "";
            }
          }

          if (data.detail.city && data.detail.city[0].name != null) {
            if (description) {
              description += ", " + data.detail.city[0].name.toUpperCase();
            } else {
              description = data.detail.city[0].name.toUpperCase();
            }
          }

          if (!description) {
            return "N/A";
          }

          if (description.startsWith("0-0")) {
            description = description.slice(3);
          }

          if (data.detail.zipcode && data.detail.zipcode[0].zip != null) {
            description += "  " + data.detail.zipcode[0].zip;
          }

          return description;
        }

        function parseVerifyData(data, isWithinBounds) {
          var description = data.address.Street + ", " + data.address.City + " " + data.address.ZIP;
          return description.toUpperCase();
        }

        function processReverseLocationData(data, lat, lng, zoomAndCenter = true, verifiedAddress) {
          // KLUGE: Address data coming from PortlandMaps has a trailing space; trim it.
          if (data.describe) {
            var describe = data.describe.trim();
            data.describe = describe;
          }

          var isWithinBounds = checkWithinBounds(new L.LatLng(lat, lng));
          var isVerifiedAddress = true;
          if (zoomAndCenter) {
            // if click is outside boundary, we want to zoom in but not full zoom (default zoom click - 2).
            // if already zoomed in more, dont change zoom.
            var currentZoom = map.getZoom();
            var zoomLevel = isWithinBounds ? DEFAULT_ZOOM_CLICK : currentZoom < DEFAULT_ZOOM_CLICK - 2 ? DEFAULT_ZOOM_CLICK - 2 : currentZoom;
            doZoomAndCenter(lat, lng, zoomLevel);
            var latlng = new L.LatLng(lat, lng);
            if (addressVerify && !data.taxlot) {
              showStatusModal("The location you selected does not have an address. Please try again.");
              isVerifiedAddress = false;
            }

            if (primaryLayerBehavior != PRIMARY_LAYER_BEHAVIOR.SelectionOnly && isWithinBounds && isVerifiedAddress) {
              setLocationMarker(lat, lng, isWithinBounds);
              checkRegion(latlng)
            }
          }

          // if in address verify mode, use the verified address from suggest API
          // rather than the "described" address that is less accurate
          var description = addressVerify ? verifiedAddress : parseDescribeData(data, isWithinBounds);

          showVerifiedLocation(description, lat, lng, isWithinBounds, isVerifiedAddress);

          // if park, set location name
          if (data.park) {
            $('#location_name').val(data.detail.park[0].name);
          }

          setLocationDetails(data);
        }

        function showVerifiedLocation(description, lat, lng, isWithinBounds, isVerifiedAddress) {
          $('#verified_location_text').text(description);

          if (!locationTextBlock.map) locationTextBlock.addTo(map);

          if (!lat || !lng) {
            return false;
          }

          lat = lat.toFixed(6);
          lng = lng.toFixed(6);

          $('#location-text-value').text(description);
          $('#location-text-lat').text(lat);
          $('#location-text-lng').text(lng);

          // if verify mode, also put location description in address field
          if (addressVerify) {
            $('#location_search').val(description);
          };

          if (isVerifiedAddress) setVerified();
          $('#location_address').val(description);
        }

        function hideVerifiedLocation() {
          $('#verified_location_text').text("");
          $('#verified_location').addClass('visually-hidden');
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

        function buildFullAddress(c) {
          return [c.address, c.attributes.city ? c.attributes.city + ' ' + c.attributes.state : '']
            .filter(Boolean)
            .join(', ')
            + (c.attributes.zip_code ? ' ' + c.attributes.zip_code : '');
        }

        function showStatusModal(message) {
          hideLoader();
          statusModal.html('<p class="status-message mb-0">' + message + '</p>');
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
          const dialogContent = document.getElementById('status_modal');
          const focusableElements = dialogContent.querySelectorAll('a, button, input, textarea');

          focusableElements.forEach((element) => {
            element.setAttribute('tabindex', '-1');
          });
        }

        function closeStatusModal() {
          statusModal.dialog('close');
        }

        function showErrorModal(message) {
          hideLoader();
          message = message + "<br><br>" + ERROR_MODAL_DEFAULT_TEXT;
          showStatusModal(message);
        }

        function setVerified(type = "address") {
          if (addressVerify) {
            $('.verified-checkmark.' + type).removeClass('invisible');
            $('.location-verify.' + type).prop('disabled', true);
          }
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
              if (entry.intersectionRatio > 0) {
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
})(jQuery, Drupal, drupalSettings, L);
