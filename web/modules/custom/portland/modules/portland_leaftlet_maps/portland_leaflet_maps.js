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
    // If this is NOT a geo file, call original function to handle it
    if( feature.feature_source != 'file')
      return this._create_feature_orig(feature);

    // feature.entity_id is the map ID: "1371"
    var mapid = "leaflet-map-media-map-"+ feature.entity_id +"-field-geo-file";
    var map = null;

    for(var property in Drupal.Leaflet) {
      if (Drupal.Leaflet.hasOwnProperty(property)) {
        // All map using the same map file has similar ID. Find the first one that doesn't have feature loaded
        if(property.indexOf("leaflet-map-media-map-"+ feature.entity_id) === 0) {
          map = Drupal.Leaflet[property].lMap;
          if(map.featureAdded) continue;
        }
      }
    }

    if( !map ) return;

    // Handle the custom geo file
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

})(jQuery);

// Show the redo search button when the map is moved or panned
function mapZoomedOrMovedByUser(e) {
  jQuery('.leaflet-control-search').removeClass('d-none');
}

// Once the Leaflet Map is loaded with its features.
var mapLoaded = false;
jQuery(document).on('leaflet.map', function (e, settings, lMap, mapid) {
  // Add the satellite view button
  if( jQuery('div.leaflet-control-satellite').length === 0 ) {
    jQuery('div.leaflet-top.leaflet-right')
      .append('<div class="leaflet-control-satellite leaflet-bar leaflet-control"><a class="leaflet-control-satellite-button leaflet-bar-part" href="#" title="Toggle View"><i class="fas fa-globe"></i></a></div>');
  
    jQuery('.leaflet-control-satellite-button').on('click', function(e) {
      var mapIcon = jQuery(this).find('i.fas');
      if(mapIcon) {
        if(jQuery(mapIcon).hasClass('fa-globe')) {
          lMap.addLayer(satelliteView);
          jQuery(mapIcon).removeClass('fa-globe');
          jQuery(mapIcon).addClass('fa-map-marked-alt');
        }
        else if(jQuery(mapIcon).hasClass('fa-map-marked-alt')) {
          lMap.removeLayer(satelliteView);
          jQuery(mapIcon).removeClass('fa-map-marked-alt');
          jQuery(mapIcon).addClass('fa-globe');
        }
        e.preventDefault();
      }
    })  
  }

  // Prevent double loading
  // if(mapLoaded) return; mapLoaded = true;

  drupalSettings.map = lMap;

  var satelliteView = L.esri.tiledMapLayer({
    url: 'https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete_Aerial/MapServer'
  });

  // Add a button to redo search in map
  if( window.location.pathname === '/parks/finder' && jQuery('div.leaflet-control-search').length === 0 ) {
    jQuery('div.leaflet-top.leaflet-left')
      .after('<div class="leaflet-control-search leaflet-bar leaflet-control d-none"><a class="leaflet-control-search-button leaflet-bar-part" href="#" title="Search in map" style="width:100%;font-size:1.1em;">Redo search in map</a></div>');
    jQuery('.leaflet-control-search-button').on('click', function(e) {
      var bounds = drupalSettings.map.getBounds();
      var bottomLeftTopRight = encodeURIComponent(bounds.getSouth().toFixed(5) + ',' + bounds.getWest().toFixed(5) + ',' + bounds.getNorth().toFixed(5) + ',' + bounds.getEast().toFixed(5));
      // If there is no query string, simply append ours
      if( window.location.href.indexOf('?') === -1) {
        document.location = window.location.href + '?bbox=' + bottomLeftTopRight;
      }
      else {
        // When there is already a bbox value in original query string, replace it
        // Find the "bbox="
        var bboxIndex = window.location.href.indexOf('bbox=');
        // If there is no "bbox" value, append ours to the end
        if(bboxIndex === -1) {
          document.location = window.location.href + '&bbox=' + bottomLeftTopRight;
        }
        // Otherwise, replace existing one
        else {
          // If there is no & after bbox
          var ampIndex = window.location.href.indexOf('&', bboxIndex);
          if( ampIndex === -1) {
            document.location = window.location.href.substring(0, bboxIndex) + 'bbox=' + bottomLeftTopRight;
          }
          else {
            document.location = window.location.href.substring(0, bboxIndex) + 'bbox=' + bottomLeftTopRight + window.location.href.substring(ampIndex);
          }
        }
      }
      jQuery('.leaflet-control-search').addClass('d-none');
      e.preventDefault();
    })
  }

  // A workaround to detect user triggered zoom or move.
  // There is a pending PR for Leaflet
  // https://github.com/Leaflet/Leaflet/pull/6929/files/0aff3cd1712f54abfd7370c90b6afa0f49024f51
  lMap.on('dragend', mapZoomedOrMovedByUser).on('dblclick', mapZoomedOrMovedByUser);
  jQuery('div.leaflet-control-zoom > a').on('click', mapZoomedOrMovedByUser);

  // Highlight the park in map when it's scrolled just under the map
  // jQuery(window).on('scroll', function() { 
  //   var allCards = jQuery('div.node--type-park-facility.card');
  //   var allMarkers = jQuery('div.park-marker');
  //   var allMarkerTitles = jQuery('.park-marker-title');
  //   for(var i=0; i<allCards.length; i++) {
  //     if( 
  //         ( i == allCards.length - 1  && (jQuery(window).scrollTop() + 400 + 80 > jQuery(allCards[i]).offset().top) )
  //         ||
  //         ( 
  //           (jQuery(window).scrollTop() + 400 + 80 > jQuery(allCards[i]).offset().top) && 
  //           (jQuery(window).scrollTop() + 400 + 80 <= jQuery(allCards[i+1]).offset().top) 
  //         ) 
  //       ) {
  //       allMarkers.each(function() {
  //         jQuery(this).removeClass( "park-marker-lg" ).addClass( "park-marker-sm" );
  //       });
  //       jQuery('div[data-id="' + jQuery(allCards[i]).attr('data-card-id') +'"]').removeClass( "park-marker-sm" ).addClass( "park-marker-lg" );
  //       allMarkerTitles.each(function() {
  //         jQuery(this).addClass( "d-none" );
  //       });
  //       jQuery('div[data-id="' + jQuery(allCards[i]).attr('data-card-id') +'"] > .park-marker-title').removeClass( "d-none" );
  //       break;
  //     }
  //   }
  // });


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

