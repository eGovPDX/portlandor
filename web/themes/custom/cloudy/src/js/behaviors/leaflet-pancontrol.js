Drupal.behaviors.leafletPanControl = {
  attach: function(context) {
    jQuery(context).on('leafletMapInit', function (e, settings, map) {
      if (map !== undefined && !map._panControlAdded) {
        window.L.control.pan({ position: 'bottomleft' }).addTo(map);
        console.log('Adding pan control to map: ', settings.id);
        map._panControlAdded = true;
      }
    });
  }
};
