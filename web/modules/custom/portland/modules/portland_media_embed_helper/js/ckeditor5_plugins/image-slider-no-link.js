/**
 * @file
 * CKEditor 5 plugin that prevents linking an embedded Image Slider.
 *
 * While an Image Slider embed is selected, this plugin force-disables the
 * "link" command, which disables the toolbar button (hidden by
 * ckeditor5-image-slider-no-link.css) and makes Cmd/Ctrl+K a no-op, since the
 * link UI checks the command before opening.
 *
 * @see portland_media_embed_helper.ckeditor5.yml
 */
((CKEditor5) => {
  const EMBED_BUTTON = 'insert_image_slider';
  const DISABLE_ID = 'portlandImageSliderNoLink';

  function isImageSlider(item) {
    return (
      !!item &&
      item.is('element', 'drupalEntity') &&
      item.getAttribute('drupalEntityEmbedButton') === EMBED_BUTTON
    );
  }

  class ImageSliderNoLink {
    constructor(editor) {
      this.editor = editor;
    }

    static get pluginName() {
      return 'ImageSliderNoLink';
    }

    afterInit() {
      const { editor } = this;
      const { model } = editor;
      const linkCommand = editor.commands.get('link');
      if (!linkCommand) {
        return;
      }

      // Disable the link command while an Image Slider is selected.
      const { selection } = model.document;
      const update = () => {
        if (isImageSlider(selection.getSelectedElement())) {
          linkCommand.forceDisabled(DISABLE_ID);
        } else {
          linkCommand.clearForceDisabled(DISABLE_ID);
        }
      };
      selection.on('change:range', update);
      model.document.on('change:data', update);
    }
  }

  CKEditor5.portlandImageSlider = { ImageSliderNoLink };
})((window.CKEditor5 = window.CKEditor5 || {}));
