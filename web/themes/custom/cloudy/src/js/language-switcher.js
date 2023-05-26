const observer = new MutationObserver((mutationList) => {
	for (const mutation of mutationList) {
	  	if (mutation.attributeName === "lang") {
			const newLang = mutation.target.getAttribute("lang");
			const newLangEl = document.querySelector(`.block-portland-global-language-switcher-block [data-langcode="${newLang}"`);
			if (newLangEl) {
				for (const el of document.querySelectorAll(".block-portland-global-language-switcher-block [data-langcode]")) {
					el.classList.remove("border-3", "border-bottom", "border-white");
				}

				newLangEl.classList.add("border-3", "border-bottom", "border-white");
			}
		}
	}
});
observer.observe(document.documentElement, { attributes: true });
