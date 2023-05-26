const observer = new MutationObserver((mutationList) => {
	for (const mutation of mutationList) {
    if (mutation.attributeName === "lang") {
      const langSwitcherBlock = document.querySelector(".block-portland-global-language-switcher-block nav");
      langSwitcherBlock.innerHTML = `<a class="nav-link text-white py-1" href="${document.querySelector("head base").href}"><i class="fas fa-arrow-left text-white"></i> Back to Portland.gov</a>`;
		}
	}
});

observer.observe(document.documentElement, { attributes: true });
