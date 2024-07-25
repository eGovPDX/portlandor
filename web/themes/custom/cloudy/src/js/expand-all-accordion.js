/**
 * @file
 * Add a link to expand or collapse all accordion sections.
 */
(function ($, Drupal) {
  // Keep track of which accordion has already been processed
  var accordionIdsProcessed = [];
  const observer = new MutationObserver(function (records, observer) {
    var accordionNodes = document.querySelectorAll("div.aria-accordion");
    accordionNodes.forEach(function(accordionNode, index) {
      if(accordionIdsProcessed.includes(accordionNode.id)) return;

      var accordion = $(accordionNode);
      var panel_id_array = accordion.find('div.aria-accordion__panel').map(function () { return jQuery(this).attr("id") });
      var aria_controls_string = Array.prototype.join.call(panel_id_array, ' ');

      accordion.prepend('<p class="toggle-accordian-text text-end d-block mb-1"><a href="javascript:void(0)" class="toggle-accordion" aria-expanded="false" aria-controls="' + aria_controls_string + '"></a></p>');

      accordion.delegate(
        'a.toggle-accordion',
        'click',
        function () {
          var toggleControl = $(this);
          toggleControl.toggleClass('active');
          toggleControl.hasClass('active') ? (
            accordion.find('.aria-accordion__heading > button').attr('aria-expanded', 'true'),
            accordion.find('div.aria-accordion__panel').attr("hidden", false),
            toggleControl.attr("aria-expanded", "true")
          ) : (
            accordion.find('.aria-accordion__heading > button').attr('aria-expanded', 'false'),
            accordion.find('div.aria-accordion__panel').attr("hidden", true),
            toggleControl.attr("aria-expanded", "false")
          );
        }
      );

      accordionIdsProcessed.push(accordionNode.id);
    });
  });

  observer.observe(
    document.body,
    {
      subtree: true,
      childList: true,
    }
  );
})(jQuery, Drupal);
