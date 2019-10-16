/**
 * @file
 * The JavaScript file for the wysiwyg integration.
 */

(function ($) {

  /**
   * A CKEditor plugin for vido embeds.
   */
  CKEDITOR.plugins.add('media_embed', {

    /**
     * Set the plugin modes.
     */
    modes: {
      wysiwyg: 1
    },

    /**
     * Define the plugin requirements.
     */
    requires: 'widget',

    /**
     * Allow undo actions.
     */
    canUndo: true,

    /**
     * Init the plugin.
     */
    init: function (editor) {
      this.registerWidget(editor);
      this.addCommand(editor);
      this.addIcon(editor);
    },

    /**
     * Add the command to the editor.
     */
    addCommand: function (editor) {
      var self = this;
      var modalSaveWrapper = function (values) {
        editor.fire('saveSnapshot');
        self.modalSave(editor, values);
        editor.fire('saveSnapshot');
      };
      editor.addCommand('media_embed', {
        exec: function (editor, data) {
          // If the selected element while we click the button is an instance
          // of the media_embed widget, extract it's values so they can be
          // sent to the server to prime the configuration form.
          var existingValues = {};
          if (editor.widgets.focused && editor.widgets.focused.name == 'media_embed') {
            existingValues = editor.widgets.focused.data.json;
          }
          Drupal.ckeditor.openDialog(editor, Drupal.url('media-embed-wysiwyg/dialog/' + editor.config.drupal.format), existingValues, modalSaveWrapper, {
            title: Drupal.t('Media Embed'),
            dialogClass: 'media-embed-dialog'
          });
        }
      });
    },

    /**
     * A callback that is triggered when the modal is saved.
     */
    modalSave: function (editor, values) {
      // Insert a media widget that understands how to manage a JSON encoded
      // object, provided the media_embed property is set.
      var widget = editor.document.createElement('p');
      widget.setHtml(JSON.stringify(values));
      editor.insertHtml(widget.getOuterHtml());
    },

    /**
     * Register the widget.
     */
    registerWidget: function (editor) {
      var self = this;
      editor.widgets.add('media_embed', {
        downcast: self.downcast,
        upcast: self.upcast,
        mask: true
      });
    },

    /**
     * Check if the element is an instance of the media widget.
     */
    upcast: function (element, data) {
      // Upcast check must be sensitive to both HTML encoded and plain text.
      if (!element.getHtml().match(/^({(?=.*preview_thumbnail\b)(?=.*settings\b)(?=.*media_url\b)(?=.*settings_summary)(.*)})$/)) {
        return;
      }
      data.json = JSON.parse(element.getHtml());
      element.setHtml(Drupal.theme('mediaEmbedWidget', data.json));
      return element;
    },

    /**
     * Turns a transformed widget into the downcasted representation.
     */
    downcast: function (element) {
      element.setHtml(JSON.stringify(this.data.json));
    },

    /**
     * Add the icon to the toolbar.
     */
    addIcon: function (editor) {
      if (!editor.ui.addButton) {
        return;
      }
      editor.ui.addButton('media_embed', {
        label: Drupal.t('Media Embed'),
        command: 'media_embed',
        icon: this.path + '/icon.png'
      });
    }
  });

  /**
   * The widget template viewable in the WYSIWYG after creating a media.
   */
  Drupal.theme.mediaEmbedWidget = function (settings) {
    return [
      '<span class="media-embed-widget">',
        '<img class="media-embed-widget__image" src="' + Drupal.checkPlain(settings.preview_thumbnail) + '">',
        '<span class="media-embed-widget__summary">',
          Drupal.checkPlain(settings.settings_summary),
        '</span>',
      '</span>'
    ].join('');
  };

})(jQuery);
