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

          // Reverse geocode click to populate address fields
          try {
            const sphericalMerc = L.Projection.SphericalMercator.project(L.latLng(lat, lng));
            const x = sphericalMerc.x;
            const y = sphericalMerc.y;
            const apiKey = drupalSettings.portlandmaps_api_key;
            const url = `https://www.portlandmaps.com/api/intersects/?geometry=%7B%20%22x%22:%20${x},%20%22y%22:%20${y},%20%22spatialReference%22:%20%7B%20%22wkid%22:%20%223857%22%7D%20%7D&include=all&detail=1&api_key=${apiKey}`;

            console.log('[AddressVerifier] Reverse geocode URL:', url);
            fetch(url)
              .then((res) => res.json())
              .then((data) => {
                console.log('[AddressVerifier] Reverse geocode raw response:', data);
                const describe = (data && typeof data.describe === 'string') ? data.describe : null;

                // Interpret detail structure from Intersects API
                const detail = (data && typeof data.detail === 'object') ? data.detail : {};
                const cityArray = Array.isArray(detail.city) ? detail.city : null;
                const zipArray = Array.isArray(detail.zipcode) ? detail.zipcode : null;
                const cityFromDetail = (cityArray && cityArray.length > 0 && cityArray[0] && typeof cityArray[0].name === 'string') ? cityArray[0].name : null;
                const postalCity = (zipArray && zipArray.length > 0 && zipArray[0] && typeof zipArray[0].name === 'string') ? zipArray[0].name : null;
                const postalZip = (zipArray && zipArray.length > 0 && zipArray[0] && typeof zipArray[0].zip === 'string') ? zipArray[0].zip : null;

                // Determine unincorporated logic and whether to use postal city/zip
                const truthy = (v) => v === true || v === 1 || (typeof v === 'string' && (v.toLowerCase() === 'true' || v === '1'));
                const findUnincorporated = truthy(this.settings.find_unincorporated);
                const isUnincorporated = !(cityArray && cityArray.length > 0);

                const city = (isUnincorporated && findUnincorporated) ? (postalCity || '') : (cityFromDetail || '');
                // Always prefer postal ZIP from detail.zipcode when available, regardless of incorporation status.
                const zip = postalZip || '';
                const state = 'OR';
                console.log('[AddressVerifier] Parsed:', { describe, city, state, zip, isUnincorporated, findUnincorporated });

                // Populate fields inside this element wrapper only
                const $el = this.$element;
                // Helper: find field by id or webform composite name.
                const findField = (id, nameSuffix) => {
                  const byId = $el.find('#' + id);
                  if (byId.length) return byId;
                  // Drupal Webform composites often render names like "name[location_address]".
                  const byName = $el.find(`[name$='[${nameSuffix}]']`);
                  if (byName.length) return byName;
                  // Fallback to global search by id if wrapper scoping misses.
                  const globalById = $(document).find('#' + id);
                  if (globalById.length) return globalById;
                  return $el.find(`[name$='[${nameSuffix}]']`);
                };

                // Prefer `describe` from API if provided.
                const addressValue = (describe && describe.trim().length > 0) ? describe : '';
                if (addressValue) {
                  findField('location_address', 'location_address').val(addressValue.toUpperCase()).trigger('change');
                }
                if (city) {
                  findField('location_city', 'location_city').val(city.toUpperCase()).trigger('change');
                }
                if (state) {
                  const $state = findField('location_state', 'location_state');
                  const st = state.toUpperCase();
                  // Try match by value first (abbr like OR)
                  let matched = false;
                  const byValue = $state.find(`option[value="${st}"]`);
                  if (byValue.length) {
                    $state.val(byValue.val()).trigger('change');
                    matched = true;
                  }
                  if (!matched) {
                    // Try match by text (full name)
                    let $opt = $state.find('option').filter(function () {
                      const txt = (this.text || '').toUpperCase();
                      return txt === st;
                    });
                    if ($opt.length) {
                      $state.val($opt.val()).trigger('change');
                      matched = true;
                    }
                  }
                }
                if (zip) {
                  findField('location_zip', 'location_zip').val(zip).trigger('change');
                }
                // Also set composed full address and coordinates
                const fullAddressBase = (describe && describe.trim().length > 0) ? describe : '';
                if (fullAddressBase) {
                  const fullAddress = `${fullAddressBase}${city ? ', ' + city : ''}${zip ? ' ' + zip : ''}`.toUpperCase();
                  findField('location_full_address', 'location_full_address').val(fullAddress);
                }
                findField('location_lat', 'location_lat').val(lat);
                findField('location_lon', 'location_lon').val(lng);
                // Use the same projected mercator values from above
                findField('location_x', 'location_x').val(x);
                findField('location_y', 'location_y').val(y);
                // Set unincorporated flag for downstream handlers/logic
                findField('location_is_unincorporated', 'location_is_unincorporated').val(isUnincorporated ? '1' : '0');
                // Set jurisdiction: if unincorporated and not using postal city, use "UNINCORPORATED".
                // Otherwise prefer actual city (incorporated) or postal city when find_unincorporated is true.
                const jurisdiction = (isUnincorporated)
                  ? (findUnincorporated ? (postalCity || 'UNINCORPORATED') : 'UNINCORPORATED')
                  : (cityFromDetail || '');
                if (typeof jurisdiction === 'string') {
                  findField('location_jurisdiction', 'location_jurisdiction').val(jurisdiction.toUpperCase()).trigger('change');
                }
              })
              .catch((err) => {
                console.error('[AddressVerifier] Reverse geocode failed', err);
              });
          } catch (err) {
            console.error('[AddressVerifier] Reverse geocode error', err);
          }
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
