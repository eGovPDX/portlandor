(function ($) {

  var response = { "status": "success", "spatialReference": { "wkid": 102100, "latestWkid": 3857 }, "candidates": [{ "location": { "x": -1.3642874419E7, "y": 5707881.163 }, "attributes": { "jurisdiction": "PORTLAND", "zip_code": 97220, "state": "OREGON", "lon": -122.55603, "sp_x": 7675414.962, "sp_y": 691837.194, "status": "Active", "city": "Portland", "zip4": 2753, "id": 862775, "type": "address", "lat": 45.54593, "county": "MULTNOMAH" }, "address": "10333 NE FARGO ST", "extent": { "ymin": 5707880.913, "ymax": 5707881.413, "xmin": -1.3642874669E7, "xmax": -1.3642874169E7 } }, { "location": { "x": -1.3642911751E7, "y": 5704930.168 }, "attributes": { "jurisdiction": "PORTLAND", "zip_code": 97220, "state": "OREGON", "lon": -122.55636, "sp_x": 7675156.747, "sp_y": 685076.490, "status": "Active", "city": "Portland", "zip4": 4016, "id": 862776, "type": "address", "lat": 45.52737, "county": "MULTNOMAH" }, "address": "10333 NE HOYT ST", "extent": { "ymin": 5704929.918, "ymax": 5704930.418, "xmin": -1.3642912001E7, "xmax": -1.3642911501E7 } }, { "location": { "x": -1.3642866728E7, "y": 5705085.216 }, "attributes": { "jurisdiction": "PORTLAND", "zip_code": 97220, "state": "OREGON", "lon": -122.55596, "sp_x": 7675264.396, "sp_y": 685398.818, "status": "Active", "city": "Portland", "zip4": 4021, "id": 636684, "type": "address", "lat": 45.52834, "county": "MULTNOMAH" }, "address": "10333 NE OREGON ST", "extent": { "ymin": 5705084.966, "ymax": 5705085.466, "xmin": -1.3642866978E7, "xmax": -1.3642866478E7 } }, { "location": { "x": -1.3642898744E7, "y": 5707163.257 }, "attributes": { "sp_x": 7675317.089, "sp_y": 690192.319, "status": "Active", "city": "Portland", "jurisdiction": "PORTLAND", "zip_code": 97220, "state": "OREGON", "lon": -122.55624, "id": 1235952, "type": "address", "lat": 45.54142, "county": "MULTNOMAH" }, "address": "10333 NE RUSSELL CT", "extent": { "ymin": 5707163.007, "ymax": 5707163.507, "xmin": -1.3642898994E7, "xmax": -1.3642898494E7 } }, { "location": { "x": -1.3642891339E7, "y": 5706895.928 }, "attributes": { "jurisdiction": "PORTLAND", "zip_code": 97220, "state": "OREGON", "lon": -122.55618, "sp_x": 7675319.106, "sp_y": 689544.571, "status": "Active", "city": "Portland", "zip4": 3738, "id": 614613, "type": "address", "lat": 45.53974, "county": "MULTNOMAH" }, "address": "10333 NE SACRAMENTO ST", "extent": { "ymin": 5706895.678, "ymax": 5706896.178, "xmin": -1.3642891589E7, "xmax": -1.3642891089E7 } }, { "location": { "x": -1.3642906371E7, "y": 5706750.479 }, "attributes": { "jurisdiction": "PORTLAND", "zip_code": 97220, "state": "OREGON", "lon": -122.55631, "sp_x": 7675274.958, "sp_y": 689215.507, "status": "Active", "city": "Portland", "zip4": 3750, "id": 614766, "type": "address", "lat": 45.53882, "county": "MULTNOMAH" }, "address": "10333 NE THOMPSON ST", "extent": { "ymin": 5706750.229, "ymax": 5706750.729, "xmin": -1.3642906621E7, "xmax": -1.3642906121E7 } }] };

// 45.5247094241752, -122.63455154569482

  Drupal.behaviors.portland_location_picker = {
    attach: function (context, settings) {

      var map;

      setUpVerifyButton();
      initializeMap();

      function setUpVerifyButton() {
        // insert module custom js here...
        console.log('Portland Location Picker javascript is plugged in!');

        // set up verify button
        $(document).on('click', '#location_verify', function (e) {
          e.preventDefault();

          // get address field value
          var address = $('#edit-portland-location-picker-location-address').val();
          if (address.length < 1) {
            alert("Please enter an address or cross streets and try again.");
            return false;
          }

          var encodedAddress = encodeURI(address);
          var url = '/location/verify/' + encodedAddress;

          // submit address verification request and parse return data
          $.get(url).done(function (data) {
            var test = data;
            processLocationData(address, data);
          });
        });
      }

      function processLocationData(address, data) {
        // console.log("Response data from PortlandMaps API for location " + address);
        // console.log(response);

        // if only one candidate, immediately locate it on the map
        if (response.candidates.length > 1) {
          // multiple candidates, how to handle? how about a modal dialog?
          var $dialog = $('#suggestions_modal');
          $dialog.append("<ul>");
          for (var i = 0; i < response.candidates.length; i++) {
            var c = response.candidates[i];
            var fulladdress = c.address + ', ' + c.attributes.city + ', ' + c.attributes.state + ' ' + c.attributes.zip_code;
            $dialog.append('<li><a href="#">' + fulladdress + '</a></li>');
          }
          $dialog.append("</ul>");
          Drupal.dialog($dialog, {
            title: 'A title',
            buttons: [{
              text: 'Close',
              click: function () {
                $(this).dialog('close');
              }
            }]
          }).showModal();
          $dialog.removeClass('visually-hidden');

        } else if (response.candidates.length == 1) {
          var lat = response.candidates[0]["attributes"]["lat"];
          var lon = response.candidates[0]["attributes"]["lon"];
          var latlon = [lat, lon];
          var marker = L.marker(latlon, { draggable: true, riseOnHover: true }).addTo(map);
          map.setView(latlon, 18);
        } else {
          // no matches found
          alert('No matches found. Please try again.');
        }

        $test = 134;
        // if multiple candidates

        // if no candidates

      }







      function initializeMap() {
        const DEFAULT_LATITUDE = 45.51;
        const DEFAULT_LONGITUDE = -122.65;
        const DEFAULT_ZOOM = 11;
        const ZOOM_POSITION = 'topright';

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
      }





    }
  };
})(jQuery);
