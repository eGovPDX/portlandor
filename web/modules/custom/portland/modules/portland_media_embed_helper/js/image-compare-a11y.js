/**
 * @file
 * Screen reader improvements for the Image Slider (image-compare component).
 *
 * - Stops the label being announced twice. Move the text to the input's 
 *   aria-label and hide the span.
 * - Announces the value as how much of each image is shown, e.g.
 *   "70% Existing, 30% Proposed", instead of a bare "70". The labels come from
 *   each image's caption (the label preset chosen in the embed dialog), or
 *   "left image" / "right image" when there are none.
 */
((Drupal, once) => {
  /**
   * Returns the text of the caption shown on one of the slider's images.
   */
  function getImageLabel(element, slot) {
    const caption = element.querySelector(`[slot="${slot}"] .image-label`);
    return caption ? caption.textContent.trim() : '';
  }

  Drupal.behaviors.portlandImageCompareA11y = {
    attach(context) {
      once('portland-image-compare-a11y', 'image-compare', context).forEach(
        (element) => {
          const root = element.shadowRoot;
          const input = root && root.querySelector('input[type="range"]');
          const labelText = root && root.querySelector('.js-label-text');
          if (!input || !labelText) {
            return;
          }

          input.setAttribute(
            'aria-label',
            labelText.textContent.replace(/\s+/g, ' ').trim(),
          );
          labelText.setAttribute('aria-hidden', 'true');

          // The value is the share of the left image (image-1) shown.
          const leftLabel =
            getImageLabel(element, 'image-1') || Drupal.t('left image');
          const rightLabel =
            getImageLabel(element, 'image-2') || Drupal.t('right image');
          const updateValueText = () => {
            const left = Math.round(Number(input.value));
            input.setAttribute(
              'aria-valuetext',
              Drupal.t('@left_percent% @left_label, @right_percent% @right_label', {
                '@left_percent': left,
                '@left_label': leftLabel,
                '@right_percent': 100 - left,
                '@right_label': rightLabel,
              }),
            );
          };
          updateValueText();
          input.addEventListener('input', updateValueText);
          input.addEventListener('change', updateValueText);
        },
      );
    },
  };
})(Drupal, once);
