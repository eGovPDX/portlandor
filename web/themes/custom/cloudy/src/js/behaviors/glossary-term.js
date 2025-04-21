Drupal.behaviors.dynamicGlossaryTooltip = {
  attach(context, drupalSettings) {
    once('dynamicGlossaryTooltip', 'a[data-entity-type="taxonomy_term"]', context).forEach(link => {
      const uuid = link.getAttribute('data-entity-uuid');
      const termUrl = link.getAttribute('href');
      const originalText = link.textContent;

      if (!uuid) return;

      const isLoggedIn = drupalSettings.user && drupalSettings.user.uid && drupalSettings.user.uid !== 0;

      var path = `/jsonapi/taxonomy_term/glossary/${uuid}`;

      fetch(path)
        .then(res => {
          const contentType = res.headers.get('content-type') || '';
          if (!res.ok || !contentType.includes('application/vnd.api+json')) {
            throw new Error(`Invalid JSON response for term ${uuid}`);
          }
          return res.json();
        })
        .then(data => {
          const termLabel = data.data.attributes.name || 'Glossary Term';
          const pronunciation = data.data.attributes.field_english_pronunciation || '';
          const description = data.data.attributes.field_short_definition?.value || 'No description available.';

          let imageHtml = '';
          const imageData = data.included?.find(
            item =>
              item.type === 'file--file' &&
              item.id === data.data.relationships.field_image?.data?.id
          );

          if (imageData) {
            const imageUrl = imageData.attributes.uri.url;
            imageHtml = `<img src="${imageUrl}" alt="" class="glossary-image" />`;
          }

          const tooltipId = `glossary-tooltip-${uuid}`;

          const wrapper = document.createElement('span');
          wrapper.classList.add('glossary-term-wrapper');
          wrapper.setAttribute('tabindex', '0');
          wrapper.innerHTML = `
            <span class="glossary-term" aria-describedby="${tooltipId}">${originalText}</span>
            <span class="glossary-popper" id="${tooltipId}" role="tooltip">
              <div class="glossary-content">
                ${imageHtml}
                <strong class="term-title">${termLabel}</strong>
                <p class="term-pronunciation">${pronunciation}</p>
                <p class="term-definition">${description}</p>
                <a class="learn-more" href="${termUrl}" target="_blank" rel="noopener noreferrer">Learn more</a>
              </div>
              <div class="popper-arrow" data-popper-arrow></div>
            </span>
          `;

          link.replaceWith(wrapper);

          const reference = wrapper.querySelector('.glossary-term');
          const tooltip = wrapper.querySelector('.glossary-popper');
          const arrow = wrapper.querySelector('[data-popper-arrow]');
          let hideTimeout;

          const createPopper = window.Popper?.createPopper;
          if (!createPopper) return;

          const popperInstance = createPopper(reference, tooltip, {
            placement: 'top',
            strategy: 'fixed',
            modifiers: [
              { name: 'offset', options: { offset: [0, 2] } },
              { name: 'arrow', options: { element: arrow } },
              { name: 'preventOverflow', options: { padding: 8 } },
            ],
          });

          function show() {
            clearTimeout(hideTimeout);
            tooltip.classList.add('visible');
            popperInstance.update();
          }

          function hide() {
            hideTimeout = setTimeout(() => {
              tooltip.classList.remove('visible');
            }, 150);
          }

          wrapper.addEventListener('mouseenter', show);
          wrapper.addEventListener('focus', show);
          wrapper.addEventListener('mouseleave', hide);
          wrapper.addEventListener('blur', hide);
          tooltip.addEventListener('mouseenter', show);
          tooltip.addEventListener('mouseleave', hide);
        })
        .catch(err => {
          console.warn('Glossary tooltip failed to load:', err);

          if (!isLoggedIn) {
            const span = document.createElement('span');
            span.textContent = originalText;
            link.replaceWith(span);
            return;
          }

          const tooltipId = `glossary-tooltip-missing-${uuid}`;

          const wrapper = document.createElement('span');
          wrapper.classList.add('glossary-term-wrapper');
          wrapper.setAttribute('tabindex', '0');
          wrapper.innerHTML = `
            <span class="glossary-term glossary-missing" aria-describedby="${tooltipId}">
              <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
              ${originalText}
            </span>
            <span class="glossary-popper" id="${tooltipId}" role="tooltip">
              <div class="glossary-content">
                <p class="term-definition">
                  Taxonomy term not found. If you are the editor of this page, please remove the broken link.
                </p>
              </div>
              <div class="popper-arrow" data-popper-arrow></div>
            </span>
          `;

          link.replaceWith(wrapper);

          const reference = wrapper.querySelector('.glossary-term');
          const tooltip = wrapper.querySelector('.glossary-popper');
          const arrow = wrapper.querySelector('[data-popper-arrow]');
          let hideTimeout;

          const createPopper = window.Popper?.createPopper;
          if (!createPopper) return;

          const popperInstance = createPopper(reference, tooltip, {
            placement: 'top',
            strategy: 'fixed',
            modifiers: [
              { name: 'offset', options: { offset: [0, 2] } },
              { name: 'arrow', options: { element: arrow } },
              { name: 'preventOverflow', options: { padding: 8 } },
            ],
          });

          function show() {
            clearTimeout(hideTimeout);
            tooltip.classList.add('visible');
            popperInstance.update();
          }

          function hide() {
            hideTimeout = setTimeout(() => {
              tooltip.classList.remove('visible');
            }, 150);
          }

          wrapper.addEventListener('mouseenter', show);
          wrapper.addEventListener('focus', show);
          wrapper.addEventListener('mouseleave', hide);
          wrapper.addEventListener('blur', hide);
          tooltip.addEventListener('mouseenter', show);
          tooltip.addEventListener('mouseleave', hide);
        });
    });
  }
};
