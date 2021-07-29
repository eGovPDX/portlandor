(function ($) {

  var request = new XMLHttpRequest();
  var map;
  var marker;

  Drupal.behaviors.portland_location_picker = {
    attach: function (context, settings) {

      // canned response data for development and testing
      var response; // = { "status": "success", "spatialReference": { "wkid": 102100, "latestWkid": 3857 }, "candidates": [{ "location": { "x": -1.3645401627E7, "y": 5708911.764 }, "attributes": { "sp_x": 7669661.490, "sp_y": 694349.134, "city": "PORTLAND", "jurisdiction": "PORTLAND", "state": "OREGON", "lon": -122.57872839300, "id": 40159, "type": "intersection", "lat": 45.55241828270, "county": "MULTNOMAH" }, "address": "NE 82ND AVE AND NE SANDY BLVD", "extent": { "ymin": 5708911.514, "ymax": 5708912.014, "xmin": -1.3645401877E7, "xmax": -1.3645401377E7 } }] };

      const DEFAULT_LATITUDE = 45.51;
      const DEFAULT_LONGITUDE = -122.65;
      const DEFAULT_ZOOM = 11;
      const DEFAULT_ZOOM_CLICK = 18;
      const DEFAULT_ZOOM_VERIFIED = 18;
      const ZOOM_POSITION = 'topright';
      const AUTOCOMPLETE_MIN_CHARACTERS = 2;

      initialize();

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
        // var aerialLayer = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete_Aerial/MapServer/tile/{z}/{y}/{x}', {
        //   attribution: "PortlandMaps ESRI"
        // });
        map.addLayer(baseLayer);
        map.addControl(zoomcontrols);
        map.on('click', handleMapClick);
        // force a crosshair cursor
        $('.leaflet-container').css('cursor', 'crosshair');

        // Set up verify button //////////////////////////////////
        $('.location-picker-address').after('<input class="btn location-verify button js-form-submit form-submit" type="submit" id="location_verify" name="op" value="Locate">');
        $(document).on('click', '#location_verify', function (e) {
          e.preventDefault();
          var address = $('.location-picker-address').val();
          // Portland Maps API for location suggestions doesn't work property when an ampersand is used
          // to identify an intersection. It must be the word "and."
          address = address.replace("&", "and");
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
          $('.location-picker-address').val(address);
          // TODO: fix the kludge below. can't figure out how to programmatically close the dialog,
          // so we're programmatically clicking the close button instead.
          $('button:contains("Close")').click();
          // locate address on map
          var lat = $(this).data('lat');
          var lon = $(this).data('lon');
          setMarkerAndZoom(lat, lon, true, true, DEFAULT_ZOOM_VERIFIED);
        });
      }

      function handleMapClick(e) {
        setMarkerAndZoom(e.latlng.lat, e.latlng.lng, true, false, DEFAULT_ZOOM_CLICK);
        reverseLookup(e.latlng.lat, e.latlng.lng);
      }

      function verifyAddress(address) {
        var encodedAddress = encodeURI(address);
        // abort any pending requests
        request.abort();
        // set up event handler to process response
        request.onreadystatechange = function () {
          if (this.readyState == 4 && this.status == 200) {
            var response = JSON.parse(this.responseText);
            if (response.candidates.length == 0) {
              alert('No matching locations found. Please try a different address and try again.');
              return false;
            }
            processLocationData(response.candidates);
          };
        }
        // API documentation: https://www.portlandmaps.com/development/#suggest
        var url = "https://www.portlandmaps.com/api/suggest/?intersections=1&alt_coords=1&api_key=" + drupalSettings.portlandmaps_api_key + "&query=" + encodedAddress;
        request.open("GET", url, true);
        request.send();
      }

      function reverseLookup(lat, lng) {
        // abort any pending requests
        request.abort();
        // set up event handler to process response
        request.onreadystatechange = function () {
          if (this.readyState == 4 && this.status == 200) {
            var response = JSON.parse(this.responseText);
            // if (response.candidates.length == 0) {
            //   alert('No matching locations found. Please try a different address and try again.');
            //   return false;
            // }
            processReverseLocationData(response);
          };
        }
        // API documentation: https://developers.arcgis.com/rest/geocode/api-reference/geocoding-reverse-geocode.htm
        var url = 'https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/reverseGeocode?f=json&locationType=street&location=' + lng + ',' + lat;
        // featureTypes paramter values:
        // StreetInt, DistanceMarker, StreetAddress, StreetName, POI, Subaddress, PointAddress, Postal, Locality

        request.open("GET", url, true);
        request.send();
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

      function processReverseLocationData(data) {
        var address = data.address.Address;
        var city = data.address.City;
        var business = data.address.PlaceName;
        var addressLabel = address.length > 0 ? address + ', ' + city : city;
        $('.location-picker-address').val(addressLabel);
        $('#place_name').val(business);
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
        $('.geofield-lat').val(lat);
        $('.geofield-lon').val(lon);

        // set dragend event handler on marker
        marker.on('dragend', function (e) {
          // capture new lat/lon values in hidden fields
          var latlng = marker.getLatLng();
          $('.geofield-lat').val(latlng.lat);
          $('.geofield-lon').val(latlng.lng);
          reverseLookup(latlng.lat, latlng.lng);
        });
      }

    }
  };
})(jQuery);
