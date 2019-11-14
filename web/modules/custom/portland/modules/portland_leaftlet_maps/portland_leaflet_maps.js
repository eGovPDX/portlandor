(function ($) {
  Drupal.Leaflet.prototype._create_layer_orig = Drupal.Leaflet.prototype.create_layer;
  Drupal.Leaflet.prototype.create_layer = function (layer, key) {
    // Load ESRI feature layers
    if (layer.esri_layer_type === 'featureLayer') {
      var mapLayer = new L.esri.featureLayer({
        url: layer.urlTemplate,
      });
      mapLayer._leaflet_id = key;
      return mapLayer;
    }

    if (layer.type === 'google' && layer.options.detectRetina && L.Browser.retina) {
      layer.urlTemplate += '&style=high_dpi&w=512';
      layer.options.tileSize = 512;
      var mapLayer = new L.TileLayer(layer.urlTemplate, layer.options);
      mapLayer._leaflet_id = key;
      return mapLayer;
    }
    // Default to the original code;
    return Drupal.Leaflet.prototype._create_layer_orig(layer, key);
  };


  Drupal.Leaflet.prototype._create_feature_orig = Drupal.Leaflet.prototype.create_feature;
  Drupal.Leaflet.prototype.create_feature = function(feature) {

    // feature.entity_id is the map ID: "1371"
    var mapid = "leaflet-map-media-map-"+ feature.entity_id +"-field-geo-file";
    var map = null;

    // All map using the same map file has similar ID. Find the first one that doesn't have feature loaded
    for(var property in Drupal.Leaflet) {
      if (Drupal.Leaflet.hasOwnProperty(property) && 
        property.indexOf("leaflet-map-media-map-"+ feature.entity_id) === 0) {
        map = Drupal.Leaflet[property].lMap;
        if(map.featureAdded) continue;
      }
    }

    if( !map ) return;

    // Handle the custom geo file
    if( feature.feature_source == 'file') {
      var featureLayer = L.geoJSON(null);
      featureLayer.options.onEachFeature = function(feature, layer) {
        for (var layer_id in layer._layers) {
          for (var i in layer._layers[layer_id]._latlngs) {
            Drupal.Leaflet.bounds.push(layer._layers[layer_id]._latlngs[i]);
          }
        }

        // Drupal.Leaflet.bounds.push(layer.getBounds());

        if (feature.properties.style) {
          layer.setStyle(feature.properties.style);
        }
        if (feature.properties.leaflet_id) {
          layer._leaflet_id = feature.properties.leaflet_id;
        }
        if (feature.properties.popup) {
          layer.bindPopup(feature.properties.popup);
        }
      };

      var xhr = new XMLHttpRequest();
      xhr.open("GET", feature.file_url, true);
      if( feature.file_type == 'shapefile') {
        xhr.responseType = "arraybuffer";
        xhr.onload = function (oEvent) {
          if (xhr.status !== 200) {
            console.error('Failed to load geo file: ' + feature.file_url);
            return
          }
          var byteArray = new Uint8Array(xhr.response);
          // Convert shapefile binary into geojson text
          shp(byteArray).then(function (geojson) {
            featureLayer.addData(geojson);
            featureLayer.setStyle(map.options.path);
            map.fitBounds(featureLayer.getBounds());
            map.featureAdded = true;
          })
        };
      }
      // Default file type is GeoJSON
      else {
        // Load Geo file from server and add it to the map
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.responseType = 'json';
        xhr.onload = function() {
            if (xhr.status !== 200) {
              console.error('Failed to load geo file: ' + feature.file_url);
              return
            }
            // In IE, xhr.response is still a string
            if (typeof xhr.response === 'string' || xhr.response instanceof String) {
              featureLayer.addData(JSON.parse(xhr.response));
            }
            else {
              featureLayer.addData(xhr.response);
            }
            featureLayer.setStyle(map.options.path);
            map.fitBounds(featureLayer.getBounds());
            map.featureAdded = true;
        };
      }
      xhr.send();
      return featureLayer;
    }
    else {
      // Default to the original code
      return this._create_feature_orig(feature);
    }
  }

})(jQuery);

// Once the Leaflet Map is loaded with its features.
jQuery(document).on('leaflet.map', function (e, settings, lMap, mapid) {
  if( drupalSettings.portlandmaps_layer && drupalSettings.portlandmaps_id ) {
    var features = L.esri.featureLayer({
      url: drupalSettings.portlandmaps_layer,
      where: drupalSettings.portlandmaps_id, // 'PropertyID=' + drupalSettings.portlandmaps_id,
      style: function (feature, layer) {
        return JSON.parse(settings.settings.path);
      },
    }).addTo(lMap);

    features.once('load', function (evt) {
      // create a new empty Leaflet bounds object
      var bounds = L.latLngBounds([]);
      // loop through the features returned by the server
      features.eachFeature(function (layer) {
        // get the bounds of an individual feature
        var layerBounds = layer.getBounds();
        // extend the bounds of the collection to fit the bounds of the new feature
        bounds.extend(layerBounds);
      });

      // once we've looped through all the features, zoom the map to the extent of the collection
      lMap.fitBounds(bounds);
    });
  }

});

L.TileLayerQuad = L.TileLayer.extend({

  getTileUrl: function (tilePoint) {
    this._adjustTilePoint(tilePoint);

    return L.Util.template(this._url, L.extend({
      s: this._getSubdomain(tilePoint),
      q: this._xyzToQuad(tilePoint.x, tilePoint.y, this._getZoomForUrl())
    }, this.options));
  },

  /** Convert xyz tile coordinates to a single quadtree key string.
   *
   * The length of the quadkey equals the zoom level. Note: zoom > 0.
   *
   * Adapted from http://msdn.microsoft.com/en-us/library/bb259689.aspx
   */
  _xyzToQuad: function (x, y, zoom) {
    var quadKey = '', digit, mask;
    for (var z = zoom; z > 0; z--) {
      digit = 0;
      mask = 1 << (z - 1);
      if ((x & mask) !== 0) {
        digit = 1;
      }
      if ((y & mask) !== 0) {
        digit += 2;
      }
      // At this point digit equals 0, 1, 2 or 3. Append digit to quad key and
      // advance to the next zoom level to calculate the next digit.
      quadKey += digit;
    }
    return quadKey;
  }
});

