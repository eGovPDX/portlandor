/**
 * To make a form auto submit, all you have to do is 3 things:
 *
 * $form['#attached']['library'][] = 'ctools_views/autosubmit';
 *
 * On gadgets you want to auto-submit when changed, add the views-auto-submit
 * class. With FAPI, add:
 * @code
 *  '#attributes' => array('class' => array('views-auto-submit')),
 * @endcode
 *
 * If you want to have auto-submit for every form element,
 * add the views-auto-submit-full-form to the form. With FAPI, add:
 * @code
 *   '#attributes' => array('class' => array('views-auto-submit-full-form')),
 * @endcode
 *
 * Finally, you have to identify which button you want clicked for autosubmit.
 * The behavior of this button will be honored if it's ajaxy or not:
 * @code
 *  '#attributes' => array('class' => array('views-use-ajax', 'views-auto-submit-click')),
 * @endcode
 *
 * Currently only 'select', 'radio', 'checkbox' and 'textfield' types are supported. We probably
 * could use additional support for HTML5 input types.
 */

(function ($, drupalSettings) {

Drupal.behaviors.ViewsAutoSubmit = {
  attach: function(context) {
    // 'this' references the form element
    function triggerSubmit (e) {
      var $this = $(this);
      if (!$this.hasClass('views-ajaxing')) {
        $this.find('.views-auto-submit-click').click();
      }
    }

    // the change event bubbles so we only need to bind it to the outer form
    $('form.views-auto-submit-full-form', context)
      .add('.views-auto-submit', context)
      .filter('form, select, input:not(:text, :submit)')
      .once('views-auto-submit')
      .change(function (e) {
        if ($(e.target).is(':not(:text, :submit)')) {
          triggerSubmit.call(e.target.form);
        }
      })
      // In case of using selectmenu.js
      .on('selectmenuchange', function (e,ui) {
        // don't trigger on text change for full-form
        if ($(e.target).is(':not(:text, :submit)')) {
          triggerSubmit.call(e.target.form);
        }
      });

    // e.keyCode: key
    var discardKeyCode = [
      16, // shift
      17, // ctrl
      18, // alt
      20, // caps lock
      33, // page up
      34, // page down
      35, // end
      36, // home
      37, // left arrow
      38, // up arrow
      39, // right arrow
      40, // down arrow
       9, // tab
      13, // enter
      27  // esc
    ];
    // Don't wait for change event on textfields
    $('.views-auto-submit-full-form input:text, input:text.views-auto-submit', context)
        .once('views-auto-submit').each(function () {
      // each textinput element has his own timeout
      var timeoutID = 0;
      $(this)
          .bind('keydown keyup', function (e) {
            if ($.inArray(e.keyCode, discardKeyCode) === -1) {
              timeoutID && clearTimeout(timeoutID);
            }
          })
          .keyup(function(e) {
            if ($.inArray(e.keyCode, discardKeyCode) === -1) {
              timeoutID = setTimeout($.proxy(triggerSubmit, this.form), 500);
            }
          });
    });
  }
}
}(jQuery, drupalSettings));
