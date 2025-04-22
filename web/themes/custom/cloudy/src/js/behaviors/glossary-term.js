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
                <a class="learn-more button button--primary" href="${url}" rel="noopener noreferrer" aria-label="Learn more about glossary term ${termLabel}">Learn more</a>
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
          console.warn(`Glossary tooltip failed to load for term "${term}":`, err);

          const isLoggedIn = drupalSettings.user && drupalSettings.user.uid && drupalSettings.user.uid !== 0;
          if (!isLoggedIn) {
            const notFoundSpan = document.createElement('span');
            notFoundSpan.classList.add('glossary-term-missing');
            notFoundSpan.textContent = term;
            span.replaceWith(notFoundSpan);
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
                  <strong>Glossary term "${term}" not found.</strong> Please verify that the term exists or contact an administrator for assistance.
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
        });
    });
  }
};
