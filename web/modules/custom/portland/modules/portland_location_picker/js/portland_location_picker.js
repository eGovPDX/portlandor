(function ($, Drupal, drupalSettings) {

  var initialized = false;

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

      $('main', context).once('location_picker').each(function () {

        // CONSTANTS //////////
        const DEFAULT_LATITUDE = 45.51;
        const DEFAULT_LONGITUDE = -122.65;
        const DEFAULT_ZOOM = 11;
        const DEFAULT_ZOOM_CLICK = 18;
        const DEFAULT_ZOOM_VERIFIED = 18;
        const DEFAULT_ICON_SIZE = [27, 41];
        const FEATURE_LAYER_VISIBLE_ZOOM = 16;
        const ZOOM_POSITION = 'topright';
        const NOT_A_PARK = "You selected park or natural area as the property type, but no park data was found for the selected location. If you believe this is a valid location, please zoom in to find the park on the map, click to select a location, and continue to submit your report.";
        const OPEN_ISSUE_MESSAGE = "If this issue is what you came here to report, there's no need to report it again.";
        const SOLVED_ISSUE_MESSAGE = "This issue was recently solved. If that's not the case, or the issue has reoccured, please submit a new report.";
        const DEFAULT_FEATURE_ICON_URL = "/modules/custom/portland/modules/portland_location_picker/images/map_marker_incident.png";
  
        // GLOBALS //////////
        var map;
        var geoJsonLayer;
        var primaryLayerMarkerGroup;
        var incidentsLayerMarkerGroup;
        var primaryFeatures;
        var incidentsFeatures;
        var addressMarker;
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
        var shouldRecenterPark = true;
        var currentView = "base";
        var baseLayer = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete/MapServer/tile/{z}/{y}/{x}', { attribution: "PortlandMaps ESRI" });
        var aerialLayer = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete_Aerial/MapServer/tile/{z}/{y}/{x}', { attribution: "PortlandMaps ESRI" });
        var LocateControl = generateLocateControl();
        var AerialControl = generateAerialControl();

        // CUSTOM PROPERTIES SET IN WEBFORM CONFIG //////////
        var primaryLayerSource = drupalSettings.webform.portland_location_picker.primary_layer_source;
        var incidentsLayerSource = drupalSettings.webform.portland_location_picker.incidents_layer_source;
        var primaryLayerBehavior = drupalSettings.webform.portland_location_picker.primary_layer_behavior;
        var primaryLayerType = drupalSettings.webform.portland_location_picker.primary_layer_type;
        var primaryMarker = drupalSettings.webform.portland_location_picker.primary_marker ? drupalSettings.webform.portland_location_picker.primary_marker : '/modules/custom/portland/modules/portland_location_picker/images/map_marker_default.png';
        var selectedMarker = drupalSettings.webform.portland_location_picker.selected_marker ? drupalSettings.webform.portland_location_picker.selected_marker : '/modules/custom/portland/modules/portland_location_picker/images/map_marker_default_selected.png';
        var incidentMarker = drupalSettings.webform.portland_location_picker.incident_marker ? drupalSettings.webform.portland_location_picker.incident_marker : '/modules/custom/portland/modules/portland_location_picker/images/map_marker_incident.png';
        var disablePopup = drupalSettings.webform.portland_location_picker.disable_popup;
        var verifyButtonText = drupalSettings.webform.portland_location_picker.verify_button_text ? drupalSettings.webform.portland_location_picker.verify_button_text : 'Verify';
        var primaryFeatureName = drupalSettings.webform.portland_location_picker.primary_feature_name ? drupalSettings.webform.portland_location_picker.primary_feature_name : 'asset';
        var featureLayerVisibleZoom = drupalSettings.webform.portland_location_picker.feature_layer_visible_zoom ? drupalSettings.webform.portland_location_picker.feature_layer_visible_zoom : FEATURE_LAYER_VISIBLE_ZOOM;

        // if ajax is used in the webform (for computed twig, for example), this script
        // and the initialize function may get called multiple times for some reason.
        // adding this flag prevents re-initialization of the map.
        if (!initialized) { initialize(); initialized = true; }
        
        // SETUP FUNCTIONS ///////////////////////////////
  
        function initialize() {
  
          // verify only one map widget in webform; complain if more than one
          if ($('.portland-location-picker--wrapper').length > 1) {
            console.log("WARNING: More than one location widget detected. Only one location widget per webform is currently supported. Adding multiples will result in unpredictable behavior.");
          }

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
          map.on('locationfound', handleLocationFound);

          // only allow map clicks if primary layer behavior is not "selection." if it is, only asset markers can be clicked to select a locaiton.
          if (primaryLayerBehavior != "selection") { map.on('click', handleMapClick); }

          // set up zoomend handler; we only want to show the primary features/assets layer when zoomed in
          // so that the map isn't too crowded with markers.
          map.on('zoomend', handleZoomEndShowGeoJsonLayer);

          // force a crosshair cursor
          $('.leaflet-container').css('cursor', 'crosshair');

          // if there are coordinates in the hidden lat/lon fields, set the map marker.
          // this is likely a submit postback that had validation errors, so we need to re set it.
          var lat = $('#location_lat').val();
          var lon = $('#location_lon').val();
          if (lat && lon) {
            setMarkerAndZoom(lat, lon, true, true, DEFAULT_ZOOM_CLICK);
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
            var lon = $(this).data('lon');
            setMarkerAndZoom(lat, lon, true, true, DEFAULT_ZOOM_VERIFIED);
            setVerified();
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
          $('#location_park').after('<span class="verified-checkmark park invisible" title="Location is verified!">✓</span>');
          $('#location_park').select2({
            escapeMarkup: function (markup) { return markup; },
            language: { noResults: function() { return 'No results found. Please try again, or select one of the other property type options above.'; } }
          });
          $('#location_park').on("change", function() {
              var park = $(this).val();
              if (park) { locateParkFromSelector(park); }
          });

          // INITIALIZE FEATURE LAYER //////////
          if (primaryLayerSource) {

            var markerIcon = primaryMarker ? primaryMarker : DEFAULT_FEATURE_ICON_URL;
            primaryLayerMarkerGroup = L.geoJson();
    
            // get primary layer data
            $.ajax({
              url: primaryLayerSource, success: function (primaryResponse) {

                primaryFeatures = primaryResponse.features;
                assetCount = primaryFeatures.length;
                console.log(assetCount + " assets found");

                if (incidentsLayerSource) {
                  // there's an incident (tickets) layer. we'll need to retrieve it, and then do a double
                  // loop to see if any of the incident asset_id values match the asset id values. if so,
                  // we use the incident marker if provided.
                  $.ajax({
                    url: incidentsLayerSource, success: function (incidentsResponse) {

                      incidentsFeatures = incidentsResponse.features;
                      incidentsLayerMarkerGroup = L.geoJson();

                      primaryLoop:
                      for (var i = 0; i < primaryFeatures.length; i++) {

                        var assetid;
                        var ticketassetid;
                        var feature = primaryFeatures[i];
                        var classname = "";

                        secondaryLoop:
                        for (var j = 0; j < incidentsFeatures.length; j++) {
                          markerIcon = primaryMarker;
                          var incident = incidentsFeatures[j];
                          assetid = feature.properties.id;
                          ticketassetid = incident.properties.asset_id;

                          // determine the marker icon to use. 
                          if (assetid == ticketassetid) {
                            classname = "incident";
                            feature.properties.status = incident.properties.status ? incident.properties.status : "";
                            if (feature.properties.detail) {
                              feature.properties.detail += incident.properties.detail;
                            } else {
                              feature.properties.detail = incident.properties.detail;
                            }
                            feature.properties.date_reported = incident.properties.date_reported;
                            if (feature.properties.status == "open" || feature.properties.status == "new") {
                              // only set hasIncident if the status is open; this allows solved but recurred incident to be reproted
                              feature.properties.hasIncident = true;
                            } else {
                              classname += " solved"; 
                              feature.properties.date_resolved = incident.properties.date_resolved;
                            }
                            if (incidentMarker) {
                              markerIcon = incidentMarker;
                            }
                            break secondaryLoop;
                          }
                        }

                        var newMarker = L.icon({
                          iconUrl:      markerIcon,
                          iconSize:     DEFAULT_ICON_SIZE, // size of the icon
                          shadowSize:   [0, 0], // size of the shadow
                          iconAnchor:   [13, 41], // point of the icon which will correspond to marker's location
                          shadowAnchor: [0, 0],  // the same for the shadow
                          popupAnchor:  [0, -41],
                          className:    classname
                        });

                        addMarkerToMap(feature, newMarker);

                      }
                    }
                  });
                } else {

                  if (primaryLayerType == "incident") {
                    markerIcon = incidentMarker;
                  }

                  var newMarker = L.icon({
                    iconUrl:      markerIcon,
                    iconSize:     DEFAULT_ICON_SIZE, // size of the icon
                    shadowSize:   [0, 0], // size of the shadow
                    iconAnchor:   [13, 41], // point of the icon which will correspond to marker's location
                    shadowAnchor: [0, 0],  // the same for the shadow
                    popupAnchor:  [0, -41]
                  });
        
                  for (var j = 0; j < primaryResponse.features.length; j++) {
                    addMarkerToMap(primaryResponse.features[j], newMarker);
                  }
                }
              }
            });
            handleZoomEndShowGeoJsonLayer();
          }
        }

        function addMarkerToMap(primaryFeature, addMarker, incidentFeature = null) {
          var addToLayer = primaryFeature.properties.hasIncident ? incidentsLayerMarkerGroup : primaryLayerMarkerGroup;
          
          var newFeature = L.geoJSON(primaryFeature, {
            coordsToLatLng: function (coords) {
              return new L.LatLng(coords[0], coords[1]);
            },
            pointToLayer: function (feature, latlng) {
              return L.marker(latlng, { icon: addMarker, iconSize: DEFAULT_ICON_SIZE, bubblingMouseEvents: false });
            },
            onEachFeature: function(feature, layer) {
              //if (!disablePopup && feature.properties.status) {
                popupOptions = { maxWidth: 250 };
                // var name = feature.properties.name ? feature.properties.name : "Asset " + feature.properties.id;
                // var incidentStatus = feature.properties.status ? "<p>Status: " + feature.properties.status + "</p>" : "";
                // var dateReported = feature.properties.date_reported ? "<p>Date Reported: " + feature.properties.date_reported + "</p>" : "";
                // //var message = feature.properties.status == "Open" ? OPEN_ISSUE_MESSAGE : SOLVED_ISSUE_MESSAGE;
                // var description = feature.properties.custom_graffiti_description ? "<p>Description: " + feature.properties.custom_graffiti_description + "</p>" : "";
                layer.bindPopup(generatePopupContent(feature), popupOptions);
              //}
            }
          });

          newFeature.on('click', handleMarkerClick);

          // add click handler to marker if behavior is "selection"; marker click is used to set location.
          // on click capture location lat/lon (hidden field), asset ID (hidden field), and asset title (address field)

          // how do we dislay the selected location in the widget? Maybe asset title in the address field?
          
          newFeature.addTo(addToLayer);
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
          var name = feature.properties.name ? feature.properties.name : primaryLayerType == "incident" ? "Incident" : "Asset";
          var detail = feature.properties.detail ? feature.properties.detail : "";

          var message = "";
          if (feature.properties.status) {
            message = feature.properties.status == "open" || feature.properties.status == "new" ? OPEN_ISSUE_MESSAGE : SOLVED_ISSUE_MESSAGE;
            message = "<p><em>" + message + "</em></p>";
          }
          return `<p><b>${name}</b></p>${detail}${message}`;
        }
  
        // EVENT HANDLERS ///////////////////////////////

        function handleMarkerClick(marker) {

          L.DomEvent.preventDefault(marker);

          if (addressMarker) {
            map.removeLayer(addressMarker);
            addressMarker = null;
          }
          resetClickedMarker();

          if (primaryLayerBehavior == "selection" && !marker.layer.feature.properties.hasIncident) {

            // store original marker icon, so we can swap back
            marker.originalIcon = marker.layer.options.icon;

            // use selected marker icon
            newIcon = L.icon({ iconUrl: selectedMarker });
            marker.layer.setIcon(newIcon);
            L.DomUtil.addClass(marker.layer._icon, 'selected');

            clickedMarker = marker;

            // set location form fields with asset data
            selectAsset(marker);

            reverseGeolocate(marker.latlng);

          } else {
            $('#place_name').val('');
            $('#location_lat').val('');
            $('#location_lon').val('');
          }
        }

        function resetClickedMarker() {
          if (clickedMarker) {
            // reset clicked marker's icon to original
            clickedMarker.layer.setIcon(clickedMarker.originalIcon);
            L.DomUtil.removeClass(clickedMarker.layer._icon, 'selected');
            //map.closePopup();
          }
        }

        function selectAsset(marker) {
            // copy asset title to holder
            $('#place_name').val(marker.layer.feature.properties.name);

            // copy asset coordiantes to lat/lon fields
            $('#location_lat').val(marker.latlng.lat);
            $('#location_lon').val(marker.latlng.lng);

            // copy asset id to hidden field
            $('#location_asset_id').val(marker.layer.feature.properties.id);
        }
  
        function handleLocationTypeClick(radios) {
          // reset park list; it may have been changed
          $('#location_park')[0].selectedIndex = 0;
  
          // if type is not private, the map is exposed but needs to be redrawn
          locationType = radios.val();
          if (locationType != 'private') {
            redrawMap();
          }
  
          // if type is street or other, show location name field
          var placeNameContainer = $('#place_name').parent();
          if (locationType == "street" || locationType == "other") {
            placeNameContainer.removeClass('visually-hidden');
          } else {
            placeNameContainer.addClass('visually-hidden');
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
          // normally when the map is clicked, we want to zoom to the clicked location
          // and perform a reverse lookup. there are some cases where we may want to 
          // perform additional actions. for example, if location type = park, we also
          // need to do a reverse parks lookup and adjust the park selector accordingly.
  
          // clear place name and park selector fields; they will get reset if appropriate after the click.
          $('.place-name').val("");
          $('#location_park').val("");
  
          if (locCircle) {
            map.removeLayer(locCircle);
            locateControlContaier.style.backgroundImage = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_locate.png")';
          }
  
          // don't zoom in as far for parks; we don't need to
          // var zoom = locationType == "park" ? DEFAULT_ZOOM_CLICK - 1 : DEFAULT_ZOOM_CLICK;
          // setMarkerAndZoom(e.latlng.lat, e.latlng.lng, true, false, zoom);
          reverseGeolocate(e.latlng);
        }
  
        function handleLocationFound(e) {
          if (locCircle) {
            map.removeLayer(locCircle);
          }
          var radius = e.accuracy;
          locCircle = L.circle(e.latlng, radius, { weight: 2, fillOpacity: 0.1 }).addTo(map);
          setMarkerAndZoom(e.latlng.lat, e.latlng.lng, true, true, DEFAULT_ZOOM_VERIFIED);
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
            if (primaryLayerMarkerGroup && map.hasLayer(primaryLayerMarkerGroup)) {
                map.removeLayer(primaryLayerMarkerGroup);
            }
            if (incidentsLayerMarkerGroup && map.hasLayer(incidentsLayerMarkerGroup)) {
              map.removeLayer(incidentsLayerMarkerGroup);
            }
          }
          if (zoomlevel >= featureLayerVisibleZoom){
            if (primaryLayerMarkerGroup && !map.hasLayer(primaryLayerMarkerGroup)){
              map.addLayer(primaryLayerMarkerGroup);
            }
            if (incidentsLayerMarkerGroup && !map.hasLayer(incidentsLayerMarkerGroup)) {
              map.addLayer(incidentsLayerMarkerGroup);
            }
          }
          // TODO: if we only want to add markers in the visible area of the map after zooming in to a certain level,
          // use getBounds to get the polygon that represents the map viewport, then check markers to see if they're contained.
          // var bounds = map.getBounds();
          // console.log(bounds);
        }

        function handleStillThereClick(id) {
          alert('Still there! ' + id);
        }
  
        // HELPER FUNCTIONS ///////////////////////////////
  
        function setLocationType(type) {
          $("input[name='report_location[location_type]'][value='" + type + "']").click();
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
              listMarkup += '<li><a href="#" class="pick" data-lat="' + c.attributes.lat + '" data-lon="' + c.attributes.lon + '" data-pick-address="' + fulladdress + '">' + fulladdress.toUpperCase() + '</a></li>';
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
            var lon = candidates[0]["attributes"]["lon"];
            // put full address in field
            var fulladdress = buildFullAddress(candidates[0]);
            $('.location-picker-address').val(fulladdress);
            setMarkerAndZoom(lat, lon, true, true, DEFAULT_ZOOM_VERIFIED);
            setVerified();
          } else {
            // no matches found
            showStatusModal("No matches found. Please try again.");
          }
        }
  
        function setMarkerAndZoom(lat, lon, zoom, center, zoomlevel) {
          //resetClickedMarker(); // if a marker was used for selection
  
          if (addressMarker) {
            // remove previous address marker
            map.removeLayer(addressMarker);
            addressMarker = null;
          }

          // set new layer
          var latlon = [lat, lon];
          if (primaryLayerBehavior != "selection") {
            addressMarker = L.marker(latlon, { draggable: true, riseOnHover: true, iconSize: DEFAULT_ICON_SIZE  }).addTo(map);
            // if address marker is moved, we want to capture the new coordinates
            addressMarker.on('dragend', function (e) {
              // capture new lat/lon values in hidden fields
              var latlng = addressMarker.getLatLng();
              $('.location-lat').val(latlng.lat);
              $('.location-lon').val(latlng.lng);
              reverseGeolocate(latlng);
            });
          }
          if (center) {
            map.setView(latlon);
          }
          if (zoom) {
            map.setView(latlon, zoomlevel);
          }
  
          // anytime a marker is set or moved, put the latlon in the hidden fields
          $('.location-lat').val(lat);
          $('.location-lon').val(lon);
  

        }
  
        function reverseGeolocate(latlng) {
          // this function performs two reverse geocoding lookups. one checks whether the click is inside a park.
          // if it's not, the second lookup is done using the ArcGIS API to find the address/place of the clicked coorodinates.
          var lat = latlng.lat;
          var lng = latlng.lng;
          setUnverified();
          shouldRecenterPark = false;
  
          // performs parks reverse geocoding using portlandmaps.com API.
          // the non-parks reverse geocoding is called within the success function,
          // chaining the two calls together.
          var reverseParksGeocodeUrl = `https://www.portlandmaps.com/arcgis/rest/services/Public/Parks_Misc/MapServer/2/query?geometry=%7B%22x%22%3A${lng}%2C%22y%22%3A${lat}%2C%22spatialReference%22%3A%7B%22wkid%22%3A4326%7D%7D&geometryType=esriGeometryPoint&spacialRel=esriSpatialRelIntersects&returnGeometry=false&returnTrueCurves=false&returnIdsOnly=false&returnCountOnly=false&returnDistinctValues=false&f=pjson`;
          $.ajax({
            url: reverseParksGeocodeUrl, success: function (result) {
              var jsonResult = JSON.parse(result);
              var lat = latlng.lat;
              var lng = latlng.lng;
              
              if (jsonResult.features && jsonResult.features.length > 0) {
                // it's a park. process the data from portlandmaps and exit function.
  
                setLocationType("park");
                setMarkerAndZoom(lat, lng, true, false, DEFAULT_ZOOM_CLICK);
  
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
                
                return true;
  
              } else {
                // it's not a park and not managed by Parks!
  
                // if location type is set to parks, but we got to this point, we want to
                // switch the type to "other" so that it goes to 311 for triage.
                if (locationType == "park") {
                  shouldRecenterPark = true;
                  setLocationType("other");
                }
  
                $('#location_park').val('0'); // set park selector to 
                $('#location_park').trigger('change');
  
                // now do PortlandMaps ArcGIS reverse geocoding call to get the non-park address for the location
                // https://www.portlandmaps.com/arcgis/rest/services/Public/Geocoding_PDX/GeocodeServer/reverseGeocode
                var reverseGeocodeUrl = `https://www.portlandmaps.com/arcgis/rest/services/Public/Geocoding_PDX/GeocodeServer/reverseGeocode?location=%7B%22x%22%3A${lng}%2C+%22y%22%3A${lat}%2C+%22spatialReference%22%3A%7B%22wkid%22+%3A+4326%7D%7D&distance=100&langCode=&locationType=&featureTypes=&outSR=4326&returnIntersection=false&f=json`;
                // arcgis_reversegeocode_url = `https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/reverseGeocode?f=json&locationType=street&location=${lng},${lat}`;
                $.ajax({
                  url: reverseGeocodeUrl, success: function (response) {
                    if (response.length < 1 || !response.address || !response.location) {
                      // portlandmaps doesn't have data for this location.
                      // set location type to "other" so 311 can triage but still set marker.
                      // and clear address field; address is not required for "other."
                      setMarkerAndZoom(lat, lng, true, false, DEFAULT_ZOOM_CLICK);
                      if (locationType == "park") {
                        setLocationType("other");
                      }
                      var locName = "N/A";
                      if (jsonResult && jsonResult.features.length > 0 && jsonResult.features[0].attributes && jsonResult.features[0].attributes.NAME) {
                        var locName = jsonResult.features[0].attributes.NAME;
                      }
                      $('#location_address').val(locName);
                      setUnverified();
                      return false;
                      // showStatusModal("There was a problem retrieving data for the selected location.");
                    }
                    processReverseLocationData(response, lat, lng);
                  }
                });
              }
            }
          });
        }
  
        function processReverseLocationData(data, lat, lng) {
          // don't set marker and zoom if primary layer behavior is "selection"
          if (primaryLayerBehavior != "selection") {
            setMarkerAndZoom(lat, lng, true, false, DEFAULT_ZOOM_CLICK);
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
          // lat/lon and show it on the map. HOWEVER, the selector might get updated if the user clicks into a
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
              }
  
              // the coordinates returned by this call are for park entrances. some may have more than
              // one entrance, such as large parks like Washington Park. we use the first one in the array.
              // the geolocaiton data needs to be escaped; best way is in a textarea element (kludgey but works).
              var txt = document.createElement("textarea");
              txt.innerHTML = result[0].location;
              var json = JSON.parse(txt.value);
              var lon = json.coordinates[0];
              var lat = json.coordinates[1];
              setMarkerAndZoom(lat, lon, true, true, DEFAULT_ZOOM_VERIFIED - 1);
              redrawMap();
              setVerified("park");
            }
          });
        }
  
        function selfLocateBrowser() {
          var t = setTimeout(function () {
            // display status indicator
            showStatusModal("Triangulating on your current location. Please wait...");
            map.locate({ watch: false, setView: true, maximumAge: 20000, enableHighAccuracy: true });
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
          if (c.attributes.type == "intersection") {
            return c.address;
          }
          var address = c.address;
          var city = c.attributes.city;
          var state = c.attributes.state;
          var zip = c.attributes.zip_code;
          return address + ', ' + city + ', ' + state + ' ' + zip;
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
