// location_event_handlers.js
class LocationEventHandlers {

  static async handleMapClick(e, caller) {

    caller.showLoader();
    caller.resetMapClick();
    const latlng = e.latlng;
    // TODO: RESET MAP CLICK:
    // - show ajax loader
    // - reset clicked marker
    // - clear location fields

    // Call the reverseGeocode function and process the result
    try {
      const result = await caller.api.reverseGeocode(latlng, caller.settings.apiKey);

      caller.selectedLocation = caller.processGeocodeResult(result, latlng);

      if (!caller.selectedLocation) {
        // display error message
        throw new Error("Location could not be determined.");
      }

      // perform layer actions
      for (var i = 0; i < caller.layers.length; i++) {
        switch (caller.layers[i].config.type) {
          case "boundary":
            // what happens when a boundary layer is clicked? call isLocationValid()
            // - check whether click is within boundary:
            //   - if not within boundary and boundary_enforce==true, then return false.
            //   - if within boundary, return region ID from parameter defined in feature_property_path
            var valid = caller.isLocationValid(caller.layers[i], caller.selectedLocation);
            //var layerResult = caller.isWithinBoundary(caller.layers[i], caller.selectedLocation);
            break;

          case "asset":
            // what happens when an asset layer is clicked? we handle the asset being
            // clicked in a handler on the marker. whether we still allow the marker to
            // be set elsewhere on the map is determined by the behavior of the asset 
            // layer, if single_select or multi_select.

            // if any asset layers are 


            caller.LAYER_BEHAVIOR = {
              INFORMATIONAL: 'informational',
              SINGLE_SELECT: 'single_select',
              MULTI_SELECT: 'multi_select',
              OPTIONAL_SELECT: 'optional_select'
            };
            // we need to know whether an asset marker is clicked, rather than the layer.
            // marker click will get handled elsewhere, do we just ignore the layer click then?
            break;

          case "incident":
            // similar to asset layer, we really just want to handle when the incident marker
            // is clicked. otherwise, we allow location to be set. 
            break;

          default:
            break;

        }
      }


    } catch (error) {
      caller.debug(error.message + " You may have selected a location outside our service area.");
      // TODO: display error message to user in modal dialog
    } finally {
      caller.hideLoader();
    }
  }

}
