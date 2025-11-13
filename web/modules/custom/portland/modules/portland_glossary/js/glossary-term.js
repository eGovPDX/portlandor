Drupal.behaviors.dynamicGlossaryTooltip = {
  attach(context, drupalSettings) {
    const glossaryElements = once(
      'dynamicGlossaryTooltip',
      '[data-entity-substitution="glossary_term"]',
      context,
    );
    const uuids = glossaryElements.map((el) => el.getAttribute('data-entity-uuid')).filter(Boolean);
    if (uuids.length === 0) return;

    const path = `/api/glossary-term/${encodeURIComponent(uuids.join(','))}`;

    fetch(path)
      .then((res) => res.json())
      .then((data) => {
        if (typeof data !== 'object') {
          throw new Error('Invalid JSON data');
        }

        glossaryElements.forEach((link) => {
          const uuid = link.getAttribute('data-entity-uuid');
          const termData = data[uuid];
          if (!termData) {
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
            return;
          }

          const termLabel = termData.title || Drupal.t('Glossary Term');
          const pronunciation = termData.pronunciation || '';
          const description = termData.short_definition || Drupal.t('No description available.');
          const url = termData.url || '#';
          const hasLongDefinition = !!termData.has_long_definition;

          const seeAlsoLinks =
            Array.isArray(termData.see_also) && termData.see_also.length
              ? `<div class="term-see-also"><strong>${Drupal.t('See also:')}</strong> ` +
                termData.see_also.map((item) => `<a href="${item.url}">${item.title}</a>`).join('') +
                `</div>`
              : '';

          const tooltipId = `glossary-tooltip-${uuid}`;

          const pronunciationElement = pronunciation
            ? `<p class="term-pronunciation"><span class="visually-hidden">${Drupal.t('English Pronunciation')}</span> ${pronunciation}</p>`
            : '';

          const learnMoreButton = hasLongDefinition
            ? `<a class="learn-more button button--primary" href="${url}" aria-label="${Drupal.t('Learn more about glossary term @term', { '@term': termLabel })}">${Drupal.t('Learn more')}</a>`
            : '';

          const closeButton = `
            <button class="glossary-close" aria-label="${Drupal.t('Close tooltip')}" type="button" style="display: none;">
              <i class="fa-solid fa-close"></i>
            </button>`;

          const wrapper = document.createElement('span');
          let unpublishedBadge = '';
          if (!termData.published) {
            unpublishedBadge = ' <span class="badge text-bg-danger ms-2">Unpublished</span>';
            wrapper.classList.add('glossary-term-unpublished');
          }

          wrapper.classList.add('glossary-term-wrapper');
          wrapper.innerHTML = `
            <span class="glossary-popper" id="${tooltipId}" role="tooltip">
              <div class="glossary-content">
                ${closeButton}
                <strong class="term-title">${termLabel}${unpublishedBadge}</strong>
                ${pronunciationElement}
                <p class="term-definition">${description}</p>
                ${seeAlsoLinks}
                ${learnMoreButton}
              </div>
              <div class="popper-arrow" data-popper-arrow></div>
            </span>
          `;

          link.parentNode.insertBefore(wrapper, link);

          const isMobileDevice = () =>
            window.matchMedia('(pointer: coarse)').matches ||
            'ontouchstart' in window ||
            /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
          const isMobile = isMobileDevice();

          let reference;
          if (!hasLongDefinition) {
            // Replace <a> with <span> to keep appearance but remove link.
            // Also remove any trailing external-link icon markup that may
            // have been added by other processors so popups for short
            // definitions don't show link icons.
            reference = document.createElement('span');
            reference.className = link.className;
            // Clone inner HTML and strip any <span> that contains the
            // invisible figure-space character U+2007 or font-awesome icons.
            let inner = link.innerHTML;
            try {
              const tmp = document.createElement('div');
              tmp.innerHTML = inner;
              // Remove spans that look like the external-link icon.
              const spans = tmp.querySelectorAll('span');
              spans.forEach((s) => {
                const text = s.textContent || '';
                // Remove if contains the special invisible figure space (U+2007)
                // or if it has font-awesome like classes.
                if (text.includes('\u2007') || /(fa-|fa\s|fa-solid)/.test(s.className || '')) {
                  s.remove();
                }
              });
              inner = tmp.innerHTML;
            } catch (e) {
              // Fallback: use raw innerHTML if DOM operations fail.
            }
            reference.innerHTML = inner;
            reference.setAttribute('data-entity-substitution', 'glossary_term');
            reference.setAttribute('tabindex', '0');
            link.replaceWith(reference);
          } else {
            reference = link;
          }

          wrapper.prepend(reference);
          reference.setAttribute('aria-details', tooltipId);

          // If this glossary term is inside a Node Fetcher that requests
          // opening links in a new tab, ensure the learn-more link opens in
          // a new tab and has appropriate rel attributes. We avoid adding
          // the external-link icon to learn-more links.
          const openLinksAncestor = wrapper.closest('[data-open-links-in-new-tab="1"]');

          const tooltip = wrapper.querySelector('.glossary-popper');
          const arrow = tooltip.querySelector('[data-popper-arrow]');
          const closeBtn = tooltip.querySelector('.glossary-close');
          let hideTimeout;

          if (openLinksAncestor) {
            const learnMoreEl = wrapper.querySelector('.learn-more');
            if (learnMoreEl) {
              learnMoreEl.setAttribute('target', '_blank');
              // Merge or set rel attribute to include noopener noreferrer
              const existingRel = learnMoreEl.getAttribute('rel') || '';
              const relParts = existingRel.split(/\s+/).filter(Boolean);
              ['noopener','noreferrer'].forEach((r) => { if (!relParts.includes(r)) relParts.push(r); });
              learnMoreEl.setAttribute('rel', relParts.join(' '));
            }
          }

          const createPopper = window.Popper?.createPopper;
          if (!createPopper) return;

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

            ['scroll', 'resize'].forEach((event) =>
              window.addEventListener(event, () => popperInstance.update(), { passive: true }),
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
        });
      })
      .catch((err) => {
        console.error(`Error during glossary lookup. UUIDs: ${uuids.join(', ')}`, err);
      });
  },
};
