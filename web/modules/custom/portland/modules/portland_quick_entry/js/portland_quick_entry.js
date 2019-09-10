(function ($, Drupal) {
  Drupal.behaviors.autoFocus = {
    attach: function () {
      if ($.urlParam('autofocus')) {
        $("main.main-content .form-wrapper:first .form-text").focus();
      }
    }
  };
  $.urlParam = function (name) {
    var results = new RegExp('[\?&]' + name + '=([^&#]*)')
      .exec(window.location.search);

    return (results !== null) ? results[1] || 0 : false;
  }
}(jQuery, Drupal));