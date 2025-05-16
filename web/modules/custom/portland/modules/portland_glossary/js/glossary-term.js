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
          const pronunciation = termData.pronunciation || '';
          const description = termData.short_definition || 'No description available.';
          const url = termData.url || '#';
          const hasLongDefinition = !!termData.has_long_definition;

          const seeAlsoLinks = Array.isArray(termData.see_also) && termData.see_also.length
            ? `<div class="term-see-also"><strong>See also:</strong> ` +
                termData.see_also.map(item =>
                  `<a href="${item.url}" rel="noopener noreferrer">${item.title}</a>`
                ).join('') +
              ` </div>`
            : '';

          const tooltipId = `glossary-tooltip-${uuid}`;

          const pronunciationElement = pronunciation
            ? `<p class="term-pronunciation">${pronunciation}</p>`
            : '';

          const learnMoreButton = hasLongDefinition
            ? `<a class="learn-more button button--primary" href="${url}" rel="noopener noreferrer" aria-label="Learn more about glossary term ${termLabel}">Learn more</a>`
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
                ${seeAlsoLinks}
                ${learnMoreButton}
              </div>
            </span>
          `;

          link.parentNode.insertBefore(wrapper, link);
          wrapper.appendChild(link);

          // Detect mobile devices (phones & tablets, regardless of viewport size)
          function isMobileDevice() {
            // Checks for touch capability and common mobile user agents
            return (
              window.matchMedia('(pointer: coarse)').matches ||
              'ontouchstart' in window ||
              /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
            );
          }
          const isMobile = isMobileDevice();

          let reference;
          if (isMobile || !hasLongDefinition) {
            // Replace link with span for mobile OR if there is no long definition
            reference = document.createElement('span');
            reference.className = link.className;
            reference.textContent = link.textContent;
            reference.setAttribute('tabindex', '0');
            // Copy data attributes if needed
            Array.from(link.attributes).forEach(attr => {
              if (attr.name.startsWith('data-')) {
                reference.setAttribute(attr.name, attr.value);
              }
            });
            link.replaceWith(reference);
          } else {
            reference = link;
          }

          const tooltip = wrapper.querySelector('.glossary-popper');
          const arrow = tooltip.querySelector('[data-popper-arrow]');
          let hideTimeout;

          const createPopper = window.Popper?.createPopper;
          if (!createPopper) return;

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

            if (isMobile) {
              // On mobile, use click/tap to show/hide
              reference.addEventListener('click', (e) => {
                e.preventDefault();
                if (tooltip.classList.contains('visible')) {
                  hide();
                } else {
                  show();
                }
              });
              // Optional: hide tooltip when tapping outside
              document.addEventListener('click', (e) => {
                if (!wrapper.contains(e.target) && tooltip.classList.contains('visible')) {
                  hide();
                }
              });
            } else {
              wrapper.addEventListener('mouseenter', show);
              wrapper.addEventListener('focus', show);
              wrapper.addEventListener('mouseleave', hide);
              wrapper.addEventListener('blur', hide);
              tooltip.addEventListener('mouseenter', show);
              tooltip.addEventListener('mouseleave', hide);
            }

            document.addEventListener('keydown', (event) => {
              if (event.key === 'Escape') {
                hide();
              }
            });
          });
        })
        .catch(err => {
          console.warn(`Glossary tooltip failed to load for UUID "${uuid}":`, err);

          // Check if user is authenticated
          const isLoggedIn = drupalSettings.user && drupalSettings.user.uid && drupalSettings.user.uid !== 0;
          if (isLoggedIn) {
            const missingSpan = document.createElement('span');
            missingSpan.classList.add('glossary-term-missing');
            missingSpan.title = 'Glossary term missing';
            missingSpan.textContent = link.textContent.trim();
            link.replaceWith(missingSpan);
          } else {
            // Replace the link with just its text content for anonymous users
            const textNode = document.createTextNode(link.textContent.trim());
            link.replaceWith(textNode);
          }
        });
    });
  }
};
