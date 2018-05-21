/**
 * @file
 * dialog.ajax.js
 */
(function ($, Drupal) {

  var dialogAjaxCurrentButton;
  var dialogAjaxOriginalButton;

  $(document)
    .ajaxSend(function () {
      if (dialogAjaxCurrentButton && dialogAjaxOriginalButton) {
        dialogAjaxCurrentButton.html(dialogAjaxOriginalButton.html());
        dialogAjaxCurrentButton.prop('disabled', dialogAjaxOriginalButton.prop('disabled'));
      }
    })
    .ajaxComplete(function () {
      if (dialogAjaxCurrentButton && dialogAjaxOriginalButton) {
        dialogAjaxCurrentButton.html(dialogAjaxOriginalButton.html());
        dialogAjaxCurrentButton.prop('disabled', dialogAjaxOriginalButton.prop('disabled'));
      }
      dialogAjaxCurrentButton = null;
      dialogAjaxOriginalButton = null;
    })
  ;

  /**
   * {@inheritdoc}
   */
  Drupal.behaviors.dialog.prepareDialogButtons = function prepareDialogButtons($dialog) {
    var buttons = [];
    var $buttons = $dialog.find('.form-actions').find('button, input[type=submit], .form-actions a.button');
    $buttons.each(function () {
      var $originalButton = $(this).css({
        display: 'block',
        width: 0,
        height: 0,
        padding: 0,
        border: 0,
        overflow: 'hidden'
      });
      var classes = $originalButton.attr('class').replace('use-ajax-submit', '');
      buttons.push({
        text: $originalButton.html() || $originalButton.attr('value'),
        class: classes,
        click: function click(e) {
          dialogAjaxCurrentButton = $(e.target);
          dialogAjaxOriginalButton = $originalButton;
          if ($originalButton.is('a')) {
            $originalButton[0].click();
          }
          else {
            $originalButton.trigger('mousedown').trigger('mouseup').trigger('click');
            e.preventDefault();
          }
        }
      });
    });
    return buttons;
  };

})(window.jQuery, window.Drupal, window.Drupal.bootstrap);
