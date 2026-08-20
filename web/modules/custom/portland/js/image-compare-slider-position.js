(function (Drupal, once) {
  // The cloudfour/image-compare web component renders its draggable range
  // input inside a shadow root (see web/libraries/cloudfour--image-compare/
  // dist/index.js), stretched to the full height of the image via
  // `label { align-items: stretch }`. That's why the handle always shows up
  // vertically centered on the image. The component doesn't expose a
  // CSS custom property, shadow part, or attribute for the handle's
  // vertical position, so regular theme CSS can't reach it from outside an
  // open shadow root. Inject an override style directly into each
  // element's shadow root instead, anchoring the handle to the bottom.
  const styled = new WeakSet();

  function applyOverride(element) {
    if (!element || element.tagName !== 'IMAGE-COMPARE' || !element.shadowRoot || styled.has(element)) {
      return;
    }
    styled.add(element);
    const style = document.createElement('style');
    style.textContent = `
      label {
        align-items: flex-end;
      }
      input {
        height: var(--thumb-size);
        margin-bottom: 5px;
      }
    `;
    element.shadowRoot.appendChild(style);
  }

  Drupal.behaviors.portlandImageCompareSliderPosition = {
    attach(context) {
      once('portland_image_compare_slider_position', 'image-compare', context).forEach(applyOverride);
    },
  };

  // CKEditor5's entity_embed live preview loads via a raw fetch() and
  // `element.innerHTML = preview` (see entity_embed/js/ckeditor5_plugins/
  // drupalentity/src/editing.js:_loadPreview()), which never calls
  // Drupal.attachBehaviors(). That leaves the behavior above unable to reach
  // the preview rendered inside CKEditor after inserting or editing a
  // slider. Watch the document for image-compare elements appearing
  // anywhere so the CKEditor preview gets the same override.
  if (document.body) {
    new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType !== Node.ELEMENT_NODE) {
            return;
          }
          if (node.matches?.('image-compare')) {
            applyOverride(node);
          }
          node.querySelectorAll?.('image-compare').forEach(applyOverride);
        });
      });
    }).observe(document.body, { childList: true, subtree: true });
  }
})(Drupal, once);
