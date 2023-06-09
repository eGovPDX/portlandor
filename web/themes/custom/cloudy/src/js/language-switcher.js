const observer = new MutationObserver((mutationList) => {
	for (const mutation of mutationList) {
    if (mutation.attributeName === "lang") {
      const langSwitcherBlock = document.querySelector(".block-portland-global-language-switcher-block .dropdown-menu");
      langSwitcherBlock.innerHTML = `<li class="dropdown-item"><a class="nav-link" href="${document.querySelector("head base").href}"><i class="fas fa-arrow-left"></i> Back to Portland.gov</a></li>`;
		}
	}
});

observer.observe(document.documentElement, { attributes: true });

if (document.cookie.includes("STYXKEY_google_widget=1")) {
  document.querySelector(".google-widget").classList.remove("d-none");
  document.querySelector(".language-menu").classList.add("d-none");
}
