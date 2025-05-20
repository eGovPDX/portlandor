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
            `</div>`
            : '';

          const tooltipId = `glossary-tooltip-${uuid}`;

          const pronunciationElement = pronunciation
            ? `<p class="term-pronunciation">${pronunciation}</p>`
            : '';

          const learnMoreButton = hasLongDefinition
            ? `<a class="learn-more button button--primary" href="${url}" rel="noopener noreferrer" aria-label="Learn more about glossary term ${termLabel}">Learn more</a>`
            : '';

          const closeButton = `
            <button class="glossary-close" aria-label="Close tooltip" type="button" style="display: none;">
              ×
            </button>`;

          const wrapper = document.createElement('span');
          wrapper.classList.add('glossary-term-wrapper');
          wrapper.setAttribute('tabindex', '0');
          wrapper.innerHTML = `
            <span class="glossary-popper" id="${tooltipId}" role="tooltip">
              <div class="glossary-content">
                ${closeButton}
                <strong class="term-title">${termLabel}</strong>
                ${pronunciationElement}
                <p class="term-definition">${description}</p>
                ${seeAlsoLinks}
                ${learnMoreButton}
              </div>
              <div class="popper-arrow" data-popper-arrow></div>
            </span>
          `;

          link.parentNode.insertBefore(wrapper, link);
          wrapper.appendChild(link);

          const reference = link;
          const tooltip = wrapper.querySelector('.glossary-popper');
          const arrow = tooltip.querySelector('[data-popper-arrow]');
          const closeBtn = tooltip.querySelector('.glossary-close');
          let hideTimeout;

          const createPopper = window.Popper?.createPopper;
          if (!createPopper) return;

          // Detect mobile/touch device
          const isMobileDevice = () =>
            window.matchMedia('(pointer: coarse)').matches ||
            'ontouchstart' in window ||
            /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

          const isMobile = isMobileDevice();

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
              tooltip.style.visibility = 'visible';
              popperInstance.update();
            }

            function hide() {
              hideTimeout = setTimeout(() => {
                if (tooltip.contains(document.activeElement)) {
                  reference.focus(); // Move focus back to the glossary term before hiding
                }
                tooltip.classList.remove('visible');
                tooltip.setAttribute('aria-hidden', 'true');
                tooltip.style.visibility = 'hidden';
              }, 300);
            }

            if (isMobile && closeBtn) {
              closeBtn.style.display = 'inline-block';
              closeBtn.onclick = (e) => {
                e.preventDefault();
                hide();
              };
            }

            tooltip.setAttribute('tabindex', '-1');
            tooltip.setAttribute('aria-hidden', 'true');
            tooltip.style.visibility = 'hidden';

            if (isMobile) {
              reference.addEventListener('click', (e) => {
                e.preventDefault();
                if (tooltip.classList.contains('visible')) {
                  hide();
                } else {
                  show();
                }
              });

              document.addEventListener('click', (e) => {
                if (!wrapper.contains(e.target) && tooltip.classList.contains('visible')) {
                  hide();
                }
              });
            } else {
              wrapper.addEventListener('mouseenter', show);
              wrapper.addEventListener('focusin', show);
              wrapper.addEventListener('mouseleave', hide);
              wrapper.addEventListener('focusout', (e) => {
                if (!wrapper.contains(e.relatedTarget)) {
                  hide();
                }
              });
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
          const isLoggedIn = drupalSettings.user?.uid && drupalSettings.user.uid !== 0;
          if (isLoggedIn) {
            const missingSpan = document.createElement('span');
            missingSpan.classList.add('glossary-term-missing');
            missingSpan.title = 'Glossary term missing';
            missingSpan.textContent = link.textContent.trim();
            link.replaceWith(missingSpan);
          } else {
            const textNode = document.createTextNode(link.textContent.trim());
            link.replaceWith(textNode);
          }
        });
    });
  }
};

function hideGlossaryTooltip(event) {
  event.preventDefault();
  const button = event.currentTarget;
  const tooltip = button.closest('.glossary-popper');
  const wrapper = button.closest('.glossary-term-wrapper');
  const reference = wrapper?.querySelector('a, span');

  // First blur the close button to remove focus from hidden content
  button.blur();

  if (tooltip) {
    tooltip.classList.remove('visible');

    // Delay setting aria-hidden to allow blur to take effect
    requestAnimationFrame(() => {
      tooltip.setAttribute('aria-hidden', 'true');
    });
  }

  if (reference) {
    reference.focus();
  }
}
