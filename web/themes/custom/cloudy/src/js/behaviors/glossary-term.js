Drupal.behaviors.dynamicGlossaryTooltip = {
  attach(context, drupalSettings) {
    once('dynamicGlossaryTooltip', 'span.glossary-term', context).forEach(span => {
      const term = span.textContent.trim();
      if (!term) return;

      const path = `/jsonapi/glossary/lookup/${encodeURIComponent(term)}`;

      fetch(path)
        .then(res => {
          const contentType = res.headers.get('content-type') || '';
          if (!res.ok || !contentType.includes('application/json')) {
            throw new Error(`Invalid JSON response for term "${term}"`);
          }
          return res.json();
        })
        .then(data => {
          if (!Array.isArray(data) || data.length === 0) {
            throw new Error(`No data found for term "${term}"`);
          }

          const termData = data[0];
          const termLabel = termData.title || 'Glossary Term';
          const pronunciation = termData.pronunciation || '';
          const description = termData.short_definition || 'No description available.';
          const url = termData.url || '#';

          const tooltipId = `glossary-tooltip-${term.replace(/\s+/g, '-').toLowerCase()}`;

          const wrapper = document.createElement('span');
          wrapper.classList.add('glossary-term-wrapper');
          wrapper.setAttribute('tabindex', '0');
          wrapper.innerHTML = `
            <span class="glossary-term-label" aria-describedby="${tooltipId}">${term}</span>
            <span class="glossary-popper" id="${tooltipId}" role="tooltip">
              <div class="glossary-content">
                <strong class="term-title">${termLabel}</strong>
                <p class="term-pronunciation">${pronunciation}</p>
                <p class="term-definition">${description}</p>
                <a class="learn-more" href="${url}" target="_blank" rel="noopener noreferrer">Learn more</a>
              </div>
              <div class="popper-arrow" data-popper-arrow></div>
            </span>
          `;

          span.replaceWith(wrapper);

          const reference = wrapper.querySelector('.glossary-term-label');
          const tooltip = wrapper.querySelector('.glossary-popper');
          const arrow = wrapper.querySelector('[data-popper-arrow]');
          let hideTimeout;

          const createPopper = window.Popper?.createPopper;
          if (!createPopper) return;

          const popperInstance = createPopper(reference, tooltip, {
            placement: 'top',
            strategy: 'absolute', // <-- FIXED HERE
            modifiers: [
              { name: 'offset', options: { offset: [0, 2] } },
              { name: 'arrow', options: { element: arrow } },
              { name: 'preventOverflow', options: { padding: 8 } },
            ],
          });

          // Ensure the tooltip position stays accurate on scroll/resize
          ['scroll', 'resize'].forEach(event =>
            window.addEventListener(event, () => popperInstance.update(), { passive: true })
          );

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
          console.warn(`Glossary tooltip failed to load for term "${term}":`, err);

          const isLoggedIn = drupalSettings.user && drupalSettings.user.uid && drupalSettings.user.uid !== 0;
          if (!isLoggedIn) {
            span.replaceWith(document.createTextNode(term));
            return;
          }

          const tooltipId = `glossary-tooltip-missing-${term.replace(/\s+/g, '-').toLowerCase()}`;
          const wrapper = document.createElement('span');
          wrapper.classList.add('glossary-term-wrapper');
          wrapper.setAttribute('tabindex', '0');
          wrapper.innerHTML = `
            <span class="glossary-term glossary-missing" aria-describedby="${tooltipId}">
              ${term}
            </span>
            <span class="glossary-popper" id="${tooltipId}" role="tooltip">
              <div class="glossary-content">
                <p class="term-definition">
                  Glossary term not found. Please check the term or contact the site administrator.
                </p>
              </div>
              <div class="popper-arrow" data-popper-arrow></div>
            </span>
          `;

          span.replaceWith(wrapper);

          const reference = wrapper.querySelector('.glossary-term');
          const tooltip = wrapper.querySelector('.glossary-popper');
          const arrow = wrapper.querySelector('[data-popper-arrow]');
          let hideTimeout;

          const createPopper = window.Popper?.createPopper;
          if (!createPopper) return;

          const popperInstance = createPopper(reference, tooltip, {
            placement: 'top',
            strategy: 'absolute',
            modifiers: [
              { name: 'offset', options: { offset: [0, 2] } },
              { name: 'arrow', options: { element: arrow } },
              { name: 'preventOverflow', options: { padding: 8 } },
            ],
          });

          ['scroll', 'resize'].forEach(event =>
            window.addEventListener(event, () => popperInstance.update(), { passive: true })
          );

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
