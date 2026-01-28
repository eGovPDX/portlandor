Drupal.behaviors.leafletPanControl = {
  attach: function(context, settings) {
    if (!settings.leaflet) {
      return;
    }

    jQuery.each(settings.leaflet, function(mapid, map_settings) {
      const map = map_settings.lMap;
      if (map !== undefined && !map._panControlAdded) {
        window.L.control.pan({ position: 'bottomleft' }).addTo(map);
        map._panControlAdded = true;
      }
    });
  }
};
