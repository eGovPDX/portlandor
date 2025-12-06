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
                const addr = data && data.address ? data.address : null;
                if (addr) {
                  const street = addr.Street || '';
                  const city = addr.City || '';
                  const state = addr.State || '';
                  const zip = addr.ZIP || '';
                  console.log('[AddressVerifier] Reverse geocode address:', addr);
                  console.log('[AddressVerifier] Parsed:', { street, city, state, zip });
                  // TEMP: stop here to verify data before populating fields
                  return;

                  // Populate fields inside this element wrapper only
                  const $el = this.$element;
                  $el.find('#location_address').val(street.toUpperCase()).trigger('change');
                  $el.find('#location_city').val(city.toUpperCase()).trigger('change');
                  if (state) {
                    const $state = $el.find('#location_state');
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
                    if (!matched) {
                      // Map abbr to full name and try text match
                      const STATE_NAMES = {
                        'AL':'ALABAMA','AK':'ALASKA','AZ':'ARIZONA','AR':'ARKANSAS','CA':'CALIFORNIA','CO':'COLORADO','CT':'CONNECTICUT','DE':'DELAWARE','FL':'FLORIDA','GA':'GEORGIA','HI':'HAWAII','ID':'IDAHO','IL':'ILLINOIS','IN':'INDIANA','IA':'IOWA','KS':'KANSAS','KY':'KENTUCKY','LA':'LOUISIANA','ME':'MAINE','MD':'MARYLAND','MA':'MASSACHUSETTS','MI':'MICHIGAN','MN':'MINNESOTA','MS':'MISSISSIPPI','MO':'MISSOURI','MT':'MONTANA','NE':'NEBRASKA','NV':'NEVADA','NH':'NEW HAMPSHIRE','NJ':'NEW JERSEY','NM':'NEW MEXICO','NY':'NEW YORK','NC':'NORTH CAROLINA','ND':'NORTH DAKOTA','OH':'OHIO','OK':'OKLAHOMA','OR':'OREGON','PA':'PENNSYLVANIA','RI':'RHODE ISLAND','SC':'SOUTH CAROLINA','SD':'SOUTH DAKOTA','TN':'TENNESSEE','TX':'TEXAS','UT':'UTAH','VT':'VERMONT','VA':'VIRGINIA','WA':'WASHINGTON','WV':'WEST VIRGINIA','WI':'WISCONSIN','WY':'WYOMING','DC':'DISTRICT OF COLUMBIA'
                      };
                      const full = STATE_NAMES[st] || st;
                      const $optFull = $state.find('option').filter(function () {
                        const txt = (this.text || '').toUpperCase();
                        return txt === full;
                      });
                      if ($optFull.length) {
                        $state.val($optFull.val()).trigger('change');
                      }
                    }
                  }
                  if (zip) {
                    $el.find('#location_zip').val(zip).trigger('change');
                  }
                  // Also set composed full address and coordinates
                  const fullAddress = `${street}, ${city} ${zip}`.toUpperCase();
                  $el.find('#location_full_address').val(fullAddress);
                  $el.find('#location_lat').val(lat);
                  $el.find('#location_lon').val(lng);
                  const sphericalMerc2 = L.Projection.SphericalMercator.project(L.latLng(lat, lng));
                  $el.find('#location_x').val(sphericalMerc2.x);
                  $el.find('#location_y').val(sphericalMerc2.y);
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
