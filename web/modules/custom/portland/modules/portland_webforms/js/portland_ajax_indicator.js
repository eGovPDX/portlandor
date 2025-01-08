(($) => {
  Drupal.behaviors.portlandAjaxIndicator = {
    attach() {
      const $overlay = $('#ajax-overlay');
      $(document).ajaxStart(() => {
        $overlay.attr('aria-hidden', 'false');
        $overlay.addClass('shown');
      });

      $(document).ajaxStop(() => {
        $overlay.attr('aria-hidden', 'true');
        $overlay.removeClass('shown');
      });

      $(document).ajaxError(() => {
        alert('An error occurred while processing the request.');
        $overlay.attr('aria-hidden', 'true');
        $overlay.removeClass('shown');
      });
    }
  };
})(jQuery);
