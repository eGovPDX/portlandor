// Our language overrides for Editoria11y
const PORTLAND_STRINGS = {
  
};

// By setting to true, the Editoria11y module calls this global function after options are initialized.
window.editoria11yOptionsOverride = true;
window.editoria11yOptions = (options) => {
  // Merge our custom strings on top of the default English strings.
  ed11yLang["portland"] = Object.assign({}, ed11yLang["en"], PORTLAND_STRINGS);
  options.lang = "portland";

  return options;
};
