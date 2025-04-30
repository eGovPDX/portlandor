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

          const termData = data[0];
          const termLabel = termData.title || 'Glossary Term';

          // Check if Popper.js is enabled and viewport is not mobile
          const createPopper = window.Popper?.createPopper;
          const isMobileViewport = window.innerWidth <= 768;

          if (createPopper && !isMobileViewport) {
            // Remove the title attribute if Popper.js is enabled and viewport is not mobile
            link.removeAttribute('title');
          } else {
            // Keep the title attribute for mobile viewports or if Popper.js is not available
            if (link.title && link.title.trim().toLowerCase() === link.textContent.trim().toLowerCase()) {
              link.title = 'Learn more about this term';
            }
          }

          const pronunciation = termData.pronunciation || '';
          const description = termData.short_definition || 'No description available.';
          const url = termData.url || '#';

          const tooltipId = `glossary-tooltip-${uuid}`;

          const pronunciationElement = pronunciation
            ? `<p class="term-pronunciation">${pronunciation}</p>`
            : '';

          const wrapper = document.createElement('span');
          wrapper.classList.add('glossary-term-wrapper');
          wrapper.setAttribute('tabindex', '0');
          wrapper.innerHTML = `
            <span class="glossary-popper" id="${tooltipId}" role="tooltip">
              <div class="glossary-content">
                <strong class="term-title">${termLabel}</strong>
                ${pronunciationElement}
                <p class="term-definition">${description}</p>
                <a class="learn-more button button--primary" href="${url}" rel="noopener noreferrer" aria-label="Learn more about glossary term ${termLabel}">Learn more</a>
              </div>
            </span>
          `;

          // Create the icon element
          const icon = document.createElement('i');
          icon.classList.add('fa-regular', 'fa-circle-info'); // Updated icon classes

          // Add a non-breaking space before the icon
          link.appendChild(document.createTextNode('\u00A0'));

          // Append the icon to the link
          link.appendChild(icon);

          // Insert the wrapper and append the link
          link.parentNode.insertBefore(wrapper, link);
          wrapper.appendChild(link);

          link.classList.add('glossary-term-link');

          const reference = link;
          const tooltip = wrapper.querySelector('.glossary-popper');
          const arrow = tooltip.querySelector('[data-popper-arrow]');
          let hideTimeout;

          // Check if Popper.js is available and if the viewport width is greater than 768px
          if (!createPopper || window.innerWidth <= 768) return;

          // Force reflow before Popper init
          tooltip.offsetHeight;

          requestAnimationFrame(() => {
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
              tooltip.setAttribute('aria-hidden', 'false');
              tooltip.style.transform = 'scale(1)'; // GPU promotion
              tooltip.offsetHeight; // Force repaint
              popperInstance.update();
            }

            function hide() {
              hideTimeout = setTimeout(() => {
                tooltip.classList.remove('visible');
                tooltip.setAttribute('aria-hidden', 'true');
                reference.focus();
              }, 300);
            }

            tooltip.setAttribute('tabindex', '-1');
            tooltip.setAttribute('aria-hidden', 'true');

            wrapper.addEventListener('mouseenter', show);
            wrapper.addEventListener('focus', show);
            wrapper.addEventListener('mouseleave', hide);
            wrapper.addEventListener('blur', hide);
            tooltip.addEventListener('mouseenter', show);
            tooltip.addEventListener('mouseleave', hide);

            document.addEventListener('keydown', (event) => {
              if (event.key === 'Escape') {
                hide();
              }
            });
          });
        })
        .catch(err => {
          console.warn(`Glossary tooltip failed to load for UUID "${uuid}":`, err);

          // Create a <span> to replace the <a> tag
          const notFoundSpan = document.createElement('span');
          notFoundSpan.textContent = link.textContent.trim();

          // Add the glossary-term-missing class and title attribute if the user is logged in
          if (drupalSettings.user && drupalSettings.user.uid && drupalSettings.user.uid !== 0) {
            notFoundSpan.classList.add('glossary-term-missing');
          }

          // Add the title attribute to indicate the term is missing
          notFoundSpan.setAttribute('title', 'Glossary term missing. Please contact a group administrator.');

          // Replace the <a> tag with the <span>
          link.replaceWith(notFoundSpan);
        });
    });
  }
};
