function waitForCleave(callback) {
  if(window.Cleave) {
    callback();
  }
  else {
    setTimeout(function() {
      waitForCleave(callback);
    }, 100)
  }
}

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.cleaveTelephone = {
    attach: function (context, drupalSettings) {
      if (drupalSettings['cleave_telephone']) {
        $(once('cleave-processed', '.cleave-telephone', context)).each(function (index, element) {
          var id = $(element).attr('id');
          var settings = drupalSettings['cleave_telephone'][id];

          waitForCleave(function() {
            var cleave = new Cleave(element, {
              phone: true,
              delimiter: '-',
              phoneRegionCode: settings.phoneRegionCode,
            });
          });
        });
      }
    }
  }

}(jQuery, Drupal, drupalSettings));
