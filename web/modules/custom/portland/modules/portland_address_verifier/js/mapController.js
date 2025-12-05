(function ($, Drupal, drupalSettings) {
  class PortlandAddressMapController {
    constructor($element, settings) {
      this.$element = $element;
      this.elementId = $element.attr('id').replace(/--[^-]+--wrapper$/, '--wrapper');
      this.settings = settings || {};
      this.model = new PortlandAddressMapModel($, $element, settings);
      this.view = new PortlandAddressMapView($, $element, this.model);
      this._marker = null;
    }

    init() {
      const mapId = this.elementId + '--map';
      const container = this.view.ensureContainer(mapId);
      if (typeof L !== 'undefined' && container) {
        const map = L.map(mapId).setView([45.54, -122.65], 11);
        const configuredMaxZoom = (typeof this.settings.max_zoom !== 'undefined') ? Number(this.settings.max_zoom) : 18;
        const maxZoom = Math.min(isNaN(configuredMaxZoom) ? 18 : configuredMaxZoom, 21);
        const baseLayer = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete/MapServer/tile/{z}/{y}/{x}', {
          attribution: 'PortlandMaps ESRI',
          maxZoom
        });
        const aerialLayer = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete_Aerial/MapServer/tile/{z}/{y}/{x}', {
          attribution: 'PortlandMaps ESRI',
          maxZoom
        });
        baseLayer.addTo(map);

        // Simple aerial toggle control (no new properties)
        const AerialControl = L.Control.extend({
          options: { position: 'bottomright' },
          onAdd: function () {
            const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
            container.title = 'Toggle aerial view';
            container.style.backgroundImage = 'url(/modules/custom/portland/modules/portland_address_verifier/images/map_aerial.png)';
            container.style.backgroundSize = '26px 26px';
            container.style.width = '26px';
            container.style.height = '26px';
            container.onclick = function (e) {
              L.DomEvent.stopPropagation(e);
              if (map.hasLayer(aerialLayer)) {
                map.removeLayer(aerialLayer);
                map.addLayer(baseLayer);
                container.style.backgroundImage = 'url(/modules/custom/portland/modules/portland_address_verifier/images/map_aerial.png)';
              } else {
                map.removeLayer(baseLayer);
                map.addLayer(aerialLayer);
                container.style.backgroundImage = 'url(/modules/custom/portland/modules/portland_address_verifier/images/map_base.png)';
              }
            };
            return container;
          }
        });
        map.addControl(new AerialControl());

        // Drop a pin on map click and log lat/lon
        map.on('click', (e) => {
          const { lat, lng } = e.latlng;
          if (this._marker) {
            map.removeLayer(this._marker);
            this._marker = null;
          }
          this._marker = L.marker([lat, lng]).addTo(map);
          console.log('[AddressVerifier] Clicked location:', { lat, lon: lng });
          map.setView([lat, lng], maxZoom);
        });

        // Store map instance for external updates
        this._map = map;
      }
    }
  }

  Drupal.behaviors.addressVerifierMap = {
    attach: function (context) {
      $(once('address-verifier-map', '.portland-address-verifier--wrapper', context)).each(function () {
        const $element = $(this);
        const elementId = $element.attr('id').replace(/--[^-]+--wrapper$/, '--wrapper');
        const settings = (drupalSettings.webform && drupalSettings.webform.portland_address_verifier && drupalSettings.webform.portland_address_verifier[elementId]) || {};

        const useMap = settings.use_map === true
          || settings.use_map === 1
          || (typeof settings.use_map === 'string' && (settings.use_map.toLowerCase() === 'true' || settings.use_map === '1'));

        if (!useMap) { return; }

        const controller = new PortlandAddressMapController($element, settings);
        controller.init();

        // Listen for address verification lat/lon and update marker
        $element[0].addEventListener('address-verifier:latlon', (evt) => {
          const { lat, lon } = evt.detail || {};
          if (typeof lat === 'number' && typeof lon === 'number') {
            const configuredMaxZoom = (typeof controller.settings.max_zoom !== 'undefined') ? Number(controller.settings.max_zoom) : 18;
            const maxZoom = Math.min(isNaN(configuredMaxZoom) ? 18 : configuredMaxZoom, 21);
            // Remove old marker (if any) and add new one
            if (controller._marker) {
              controller._map.removeLayer(controller._marker);
              controller._marker = null;
            }
            // Use existing map instance; do not recreate
            controller._map.setView([lat, lon], maxZoom);
            controller._marker = L.marker([lat, lon]).addTo(controller._map);
            console.log('[AddressVerifier] Verified address location:', { lat, lon });
          }
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
