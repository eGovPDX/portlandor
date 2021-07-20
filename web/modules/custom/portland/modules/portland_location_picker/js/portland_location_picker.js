(function ($) {
  Drupal.behaviors.portland_location_picker = {
    attach: function (context, settings) {

      // insert module custom js here...
      console.log('Portland Location Picker javascript is plugged in!');

      const DEFAULT_LATITUDE = 45.51;
      const DEFAULT_LONGITUDE = -122.65;
      const DEFAULT_ZOOM = 11;
      const ZOOM_POSITION = 'topright';

      var zoomcontrols = new L.control.zoom({ position: ZOOM_POSITION });
      var map = new L.Map("location_map", {
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



      // $('.media-embed-field-launch-modal', context).once().click(function (e) {
      //   // Allow the thumbnail that launches the modal to link to other places
      //   // such as media URL, so if the modal is sidestepped things degrade
      //   // gracefully.
      //   e.preventDefault();
      //   $.colorbox($.extend(settings.colorbox, { 'html': $(this).data('media-embed-field-modal') }));
      // });

    }
  };
})(jQuery);
