Drupal.behaviors.dynamicGlossaryTooltip = {
  attach(context, drupalSettings) {
    once('dynamicGlossaryTooltip', '[data-entity-substitution="glossary_term"]', context).forEach(link => {
      const uuid = link.getAttribute('data-entity-uuid');
      if (!uuid) return;

      const path = `/jsonapi/node/glossary_term/${encodeURIComponent(uuid)}`;

      fetch(path)
        .then(res => {
          const contentType = res.headers.get('content-type') || '';
          if (!res.ok || !contentType.includes('application/json')) {
            throw new Error(`Invalid JSON response for UUID "${uuid}"`);
          }
          return res.json();
        })
        .then(data => {
          if (!data || !Array.isArray(data) || data.length === 0) {
            throw new Error(`No data found for UUID "${uuid}".`);
          }

          // Access the first element of the array
          const termData = data[0];
          const termLabel = termData.title || 'Glossary Term';
          const pronunciation = termData.pronunciation || '';
          const description = termData.short_definition || 'No description available.'; // Updated line
          const url = termData.url || '#';

          const tooltipId = `glossary-tooltip-${uuid}`;

          const wrapper = document.createElement('span');
          wrapper.classList.add('glossary-term-wrapper');
          wrapper.setAttribute('tabindex', '0');
          wrapper.innerHTML = `
            <span class="glossary-term-label" aria-describedby="${tooltipId}">${termLabel}</span>
            <span class="glossary-popper" id="${tooltipId}" role="tooltip">
              <div class="glossary-content">
                <strong class="term-title">${termLabel}</strong>
                <p class="term-pronunciation">${pronunciation}</p>
                <p class="term-definition">${description}</p>
                <a class="learn-more button button--primary" href="${url}" rel="noopener noreferrer" aria-label="Learn more about glossary term ${termLabel}">Learn more</a>
              </div>
              <div class="popper-arrow" data-popper-arrow></div>
            </span>
          `;

          link.replaceWith(wrapper);

          const reference = wrapper.querySelector('.glossary-term-label');
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

          // Ensure the tooltip position stays accurate on scroll/resize
          ['scroll', 'resize'].forEach(event =>
            window.addEventListener(event, () => popperInstance.update(), { passive: true })
          );

          function show() {
            clearTimeout(hideTimeout);
            tooltip.classList.add('visible');
            tooltip.setAttribute('aria-hidden', 'false'); // Make tooltip visible to screen readers
            tooltip.focus(); // Move focus to the tooltip
            popperInstance.update();
          }

          function hide() {
            hideTimeout = setTimeout(() => {
              tooltip.classList.remove('visible');
              tooltip.setAttribute('aria-hidden', 'true'); // Hide tooltip from screen readers
              reference.focus(); // Return focus to the glossary term
            }, 300); // Increased delay
          }

          // Ensure the tooltip is focusable
          tooltip.setAttribute('tabindex', '-1');
          tooltip.setAttribute('aria-hidden', 'true'); // Initially hidden from screen readers

          wrapper.addEventListener('mouseenter', show);
          wrapper.addEventListener('focus', show);
          wrapper.addEventListener('mouseleave', hide);
          wrapper.addEventListener('blur', hide);
          tooltip.addEventListener('mouseenter', show);
          tooltip.addEventListener('mouseleave', hide);

          // Add keyboard dismissal for the tooltip
          document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
              hide();
            }
          });
        })
        .catch(err => {
          console.warn(`Glossary tooltip failed to load for UUID "${uuid}":`, err);

          const notFoundSpan = document.createElement('span');
          notFoundSpan.classList.add('glossary-term-missing');
          notFoundSpan.textContent = link.textContent.trim();
          link.replaceWith(notFoundSpan);
        });
    });
  }
};
