(function ($) {

  var hinterXHR = new XMLHttpRequest();
  var map;
  var marker;

  Drupal.behaviors.portland_location_picker = {
    attach: function (context, settings) {

      // canned response data for development and testing
      var response; // = { "status": "success", "spatialReference": { "wkid": 102100, "latestWkid": 3857 }, "candidates": [{ "location": { "x": -1.3645401627E7, "y": 5708911.764 }, "attributes": { "sp_x": 7669661.490, "sp_y": 694349.134, "city": "PORTLAND", "jurisdiction": "PORTLAND", "state": "OREGON", "lon": -122.57872839300, "id": 40159, "type": "intersection", "lat": 45.55241828270, "county": "MULTNOMAH" }, "address": "NE 82ND AVE AND NE SANDY BLVD", "extent": { "ymin": 5708911.514, "ymax": 5708912.014, "xmin": -1.3645401877E7, "xmax": -1.3645401377E7 } }] };

      const DEFAULT_LATITUDE = 45.51;
      const DEFAULT_LONGITUDE = -122.65;
      const DEFAULT_ZOOM = 11;
      const DEFAULT_ZOOM_CLICK = 17;
      const DEFAULT_ZOOM_VERIFIED = 18;
      const ZOOM_POSITION = 'topright';
      const AUTOCOMPLETE_MIN_CHARACTERS = 2;

      initialize();
      //setUpVerifyButton();
      //setUpPickLinks();
      //setUpAddressAutocomplete();

      function initialize() {

        // initialize map ///////////////////////////////////

        var zoomcontrols = new L.control.zoom({ position: ZOOM_POSITION });
        map = new L.Map("location_map", {
          center: new L.LatLng(DEFAULT_LATITUDE, DEFAULT_LONGITUDE),
          zoomControl: false,
          zoom: DEFAULT_ZOOM
        });

        var baseLayer = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete/MapServer/tile/{z}/{y}/{x}', {
          attribution: "PortlandMaps ESRI"
        });
        var aerialLayer = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete_Aerial/MapServer/tile/{z}/{y}/{x}', {
          attribution: "PortlandMaps ESRI"
        });
        map.addLayer(baseLayer);
        map.addControl(zoomcontrols);

        map.on('click', setMarkerOnClick);

        // Set up verify button //////////////////////////////////
        $(document).on('click', '#location_verify', function (e) {
          e.preventDefault();
          var address = $('#edit-portland-location-picker-location-address').val();
          if (address.length < 1) {
            alert("Please enter an address or cross streets and try again.");
            return false;
          }
          verifyAddress(address);
        });

        // Set up pick links //////////////////////////////////
        $(document).on('click', 'a.pick', function (e) {
          e.preventDefault();
          // get address data from link
          var address = $(this).data('pick-address');
          // put selected address in address field
          $('#edit-portland-location-picker-location-address').val(address);
          // TODO: fix the kludge below. can't figure out how to programmatically close the dialog,
          // so we're programmatically clicking the close button instead.
          $('button:contains("Close")').click();
          // locate address on map
          var lat = $(this).data('lat');
          var lon = $(this).data('lon');
          setMarkerAndZoom(lat, lon, true, true, DEFAULT_ZOOM_VERIFIED);
        });

        
      }

      function setMarkerOnClick(e) {
        setMarkerAndZoom(e.latlng.lat, e.latlng.lng, true, false, DEFAULT_ZOOM_CLICK);
      }

      function verifyAddress(address) {
        var encodedAddress = encodeURI(address);

        // abort any pending requests
        hinterXHR.abort();

        hinterXHR.onreadystatechange = function () {
          if (this.readyState == 4 && this.status == 200) {

            // We're expecting a json response so we convert it to an object
            var response = JSON.parse(this.responseText);

            // clear any previously loaded options in the datalist
            //addresslist.innerHTML = "";

            // When no candidates are found, clear input fields
            if (response.candidates.length == 0) {
              $('input.postal-code').val('');
            }

            processLocationData(response.candidates);
            // response.candidates.forEach(function (item) {
            //   // Create a new <option> element.
            //   var option = document.createElement('option');
            //   option.value = item.address;
            //   // Set default when the data is incomplete
            //   var city = item.attributes.city ? item.attributes.city : 'Portland';
            //   var state = item.attributes.state ? item.attributes.state : 'Oregon';
            //   var zip_code = item.attributes.zip_code ? item.attributes.zip_code : '';
            //   var latitude = item.attributes.lat ? item.attributes.lat : '';
            //   var longitude = item.attributes.lon ? item.attributes.lon : '';
            //   // option.text = item.address + ', ' + city + ', ' + state;
            //   // if(zip_code) option.text += ', ' + item.attributes.zip_code;
            //   option.setAttribute('data-city', city);
            //   option.setAttribute('data-state', state);
            //   option.setAttribute('data-zip', zip_code);
            //   option.setAttribute('data-latitude', latitude);
            //   option.setAttribute('data-longitude', longitude);

            //   // attach the option to the datalist element
            //   addresslist.appendChild(option);
            // });
          };

        }

        // API documentation: https://www.portlandmaps.com/development/#suggest
        var url = "https://www.portlandmaps.com/api/suggest/?intersections=1&alt_coords=1&api_key=" + drupalSettings.portlandmaps_api_key + "&query=" + encodedAddress;
        hinterXHR.open("GET", url, true);
        hinterXHR.send();



        // var url = '/location/verify/' + encodedAddress;
        // $.get(url).done(function (data) {
        //   var test = data;
        //   processLocationData(address, data);
        // });

      }

      function processLocationData(candidates) {

        // if only one candidate, immediately locate it on the map
        if (candidates.length > 1) {
          // multiple candidates, how to handle? how about a modal dialog?
          var $dialog = $('#suggestions_modal');
          var listMarkup = "<p>Multiple possible matches found. Please select one by clicking it.</p><ul>";
          for (var i = 0; i < candidates.length; i++) {
            var c = candidates[i];
            var fulladdress = c.address + ', ' + c.attributes.city + ', ' + c.attributes.state + ' ' + c.attributes.zip_code;
            listMarkup += '<li><a href="#" class="pick" data-lat="' + c.attributes.lat + '" data-lon="' + c.attributes.lon + '" data-pick-address="' + fulladdress + '">' + fulladdress.toUpperCase() + '</a></li>';
          }
          listMarkup += "</ul>";
          $dialog.html(listMarkup);
          Drupal.dialog($dialog, {
            title: 'A title',
            width: '600px',
            buttons: [{
              text: 'Close',
              click: function () {
                $(this).dialog('close');
              }
            }]
          }).showModal();
          $dialog.removeClass('visually-hidden');

        } else if (candidates.length == 1) {
          var lat = candidates[0]["attributes"]["lat"];
          var lon = candidates[0]["attributes"]["lon"];
          setMarkerAndZoom(lat, lon, true, true, DEFAULT_ZOOM_VERIFIED);
        } else {
          // no matches found
          alert('No matches found. Please try again.');
        }

        // add event handler to marker to capture lat/lon on dragend

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
        $('#edit-portland-location-picker-location-latlon-lat').val(lat);
        $('#edit-portland-location-picker-location-latlon-lon').val(lon);

        // set dragend event handler on marker
        marker.on('dragend', function (e) {
          // capture new lat/lon values in hidden fields
          $('#edit-portland-location-picker-location-latlon-lat').val(marker.getLatLng().lat);
          $('#edit-portland-location-picker-location-latlon-lon').val(marker.getLatLng().lng);

          // perform reverse geolocation and call PM API to get approximate address
          // TODO: will this be suggestions, an approximate address, or can the API target the nearest valid address?

          // populate address field with result from API
        });
      }

      function setUpAddressAutocomplete() {
        //$('.location-picker-address').on("keyup", handleAddressKeyup);
      }

    }
  };
})(jQuery);
