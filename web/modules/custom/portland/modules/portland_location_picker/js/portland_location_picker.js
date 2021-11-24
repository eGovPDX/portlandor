(function ($) {

  var initialized = false;

  // Here's how to reverse geolocate a park. Note the x/y values in the geometry parameter:
  // https://www.portlandmaps.com/arcgis/rest/services/Public/Parks_Misc/MapServer/2/query?geometry=%7B%22x%22%3A-122.55203425884248%2C%22y%22%3A45.53377174783918%2C%22spatialReference%22%3A%7B%22wkid%22%3A4326%7D%7D&geometryType=esriGeometryPoint&spacialRel=esriSpatialRelIntersects&returnGeometry=false&returnTrueCurves=false&returnIdsOnly=false&returnCountOnly=false&returnDistinctValues=false&f=pjson"
  // returns an object that includes the park name. 
  
  Drupal.behaviors.portland_location_picker = {
    attach: function (context, settings) {

      const DEFAULT_LATITUDE = 45.51;
      const DEFAULT_LONGITUDE = -122.65;
      const DEFAULT_ZOOM = 11;
      const DEFAULT_ZOOM_CLICK = 18;
      const DEFAULT_ZOOM_VERIFIED = 18;
      const ZOOM_POSITION = 'topright';
      const NOT_A_PARK = "You selected park or natural area as the property type, but no park data was found for the selected location. If you believe this is a valid location or are unsure, plese continue to submit your report.";

      var request = new XMLHttpRequest();
      var map;
      var marker;
      var locationErrorShown;
      var locateControl;
      var locMarker;
      var locCircle;
      var locateControlContaier;

      var response; // = { "status": "success", "spatialReference": { "wkid": 102100, "latestWkid": 3857 }, "candidates": [{ "location": { "x": -1.3645401627E7, "y": 5708911.764 }, "attributes": { "sp_x": 7669661.490, "sp_y": 694349.134, "city": "PORTLAND", "jurisdiction": "PORTLAND", "state": "OREGON", "lon": -122.57872839300, "id": 40159, "type": "intersection", "lat": 45.55241828270, "county": "MULTNOMAH" }, "address": "NE 82ND AVE AND NE SANDY BLVD", "extent": { "ymin": 5708911.514, "ymax": 5708912.014, "xmin": -1.3645401877E7, "xmax": -1.3645401377E7 } }] };
      var suggestionsModal;
      var locationType;
      var statusModal;
      var baseLayer;
      var aerialLayer;
      var isPark;
      var shouldRecenterPark = true;
      var currentView = "base";
      var baseLayer = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete/MapServer/tile/{z}/{y}/{x}', {
        attribution: "PortlandMaps ESRI"
      });
      var aerialLayer = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete_Aerial/MapServer/tile/{z}/{y}/{x}', {
        attribution: "PortlandMaps ESRI"
      });

      var LocateControl = L.Control.extend({
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

      var AerialControl = L.Control.extend({
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

      
      // if ajax is used in the webform (for computed twig, for example), this script
      // and the initialize function may get called multiple times for some reason.
      // adding this flag prevents that.
      if (!initialized) {
        initialize();
        initialized = true;
      }
      

      // SETUP FUNCTIONS ///////////////////////////////

      function initialize() {

        // initialize map ///////////////////////////////////
        var zoomcontrols = new L.control.zoom({ position: ZOOM_POSITION });
        map = new L.Map("location_map_container", {
          center: new L.LatLng(DEFAULT_LATITUDE, DEFAULT_LONGITUDE),
          zoomControl: false,
          zoom: DEFAULT_ZOOM
        });
        map.addLayer(baseLayer);
        map.addControl(zoomcontrols);
        map.on('click', handleMapClick);
        map.on('locationerror', handleLocationError);
        map.on('locationfound', handleLocationFound);
        // force a crosshair cursor
        $('.leaflet-container').css('cursor', 'crosshair');
        aerialControl = new AerialControl();
        map.addControl(aerialControl);
        locateControl = new LocateControl();
        map.addControl(locateControl);
        // if there are coordinates in the hidden lat/lon fields, set the map marker.
        // this is likely a submit postback that had validation errors, so we need to re set it.
        var lat = $('#location_lat').val();
        var lon = $('#location_lon').val();
        if (lat && lon) {
          setMarkerAndZoom(lat, lon, true, true, DEFAULT_ZOOM_CLICK);
        }

        // Set up verify button //////////////////////////////////
        $('.location-picker-address').after('<input class="btn location-verify button js-form-submit form-submit" type="button" id="location_verify" name="op" value="Verify">');
        $('.location-picker-address').after('<span class="verified-checkmark address invisible" title="Location is verified!">✓</span>');
        $(document).on('click', '#location_verify', function (e) {
          e.preventDefault();
          var address = $('.location-picker-address').val();
          // Portland Maps API for location suggestions doesn't work property when an ampersand is used
          // to identify an intersection. It must be the word "and."
          address = address.replace("&", "and");
          if (address.length < 1) {
            showStatusModal("Please enter an address or cross streets and try again.");
            return false;
          }
          verifyAddressPortlandMaps(address);
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

        // set up address field /////////////////////////////
        // turn off verified checkmark if value changes
        $('.location-picker-address').on('keyup', function () {
          setUnverified();
        });

        // set up location type radios //////////////////////
        $('fieldset.location-type input[type="radio"]').on("click", function() {
          handleLocationTypeClick($(this));
        });

        // set up parks select list /////////////////////////
        $('#location_park').after('<span class="verified-checkmark park invisible" title="Location is verified!">✓</span>');
        $('#location_park').select2({
          escapeMarkup: function (markup) { return markup; },
          language: {
            noResults: function() {
              return 'No results found. Please try again, or select one of the other property type options above.';
            }
          }
        });
        $('#location_park').on("change", function() {
          //if (locationType == 'park') {
            var park = $(this).val();
            if (park) {
              locateParkFromSelector(park);
            }
          //}
        });
      }



      // EVENT HANDLERS ///////////////////////////////

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

      // HELPER FUNCTIONS ///////////////////////////////

      function setLocationType(type) {
        $("input[name='report_location[location_type]'][value='" + type + "']").click();
      }

      function redrawMap() {
        // if map is initialized while hidden, this function needs to be called when the map is
        // exposed, so it can redraw the tiles.
        //map.invalidateSize();
        setTimeout(function () { map.invalidateSize(); }, 500);
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
        // remove previous marker
        if (marker) {
          map.removeLayer(marker);
          marker = null;
        }

        // set new layer
        var latlon = [lat, lon];
        marker = L.marker(latlon, { draggable: true, riseOnHover: true }).addTo(map);
        if (center) {
          map.setView(latlon);
        }
        if (zoom) {
          map.setView(latlon, zoomlevel);
        }

        // anytime a marker is set or moved, put the latlon in the hidden fields
        $('.location-lat').val(lat);
        $('.location-lon').val(lon);

        // set dragend event handler on marker
        marker.on('dragend', function (e) {
          // capture new lat/lon values in hidden fields
          var latlng = marker.getLatLng();
          $('.location-lat').val(latlng.lat);
          $('.location-lon').val(latlng.lng);
          reverseGeolocate(latlng);
        });
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

              // clear address field. in some instances it might be visible, such as in
              // the streamlined implementation of the widget.
              $('.location-picker-address').val("");

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
                    $('#location_address').val("");
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
        setMarkerAndZoom(lat, lng, true, false, DEFAULT_ZOOM_CLICK);
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
        var url = '/api/parks/' + id; // this is a drupal view that returns json about the park
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

    }
  };
})(jQuery);
