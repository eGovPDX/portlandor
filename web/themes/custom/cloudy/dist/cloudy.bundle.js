/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/themes/custom/cloudy/dist/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./src/cloudy.js":
/*!***********************!*\
  !*** ./src/cloudy.js ***!
  \***********************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _js_libs_jquery_plugin_fitvids__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./js/libs/jquery-plugin-fitvids */ "./src/js/libs/jquery-plugin-fitvids.js");
/* harmony import */ var _js_libs_jquery_plugin_fitvids__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_js_libs_jquery_plugin_fitvids__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _components_dialog_dialog__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/dialog/dialog */ "./src/components/dialog/dialog.js");
/* harmony import */ var _components_tabs_tabs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/tabs/tabs */ "./src/components/tabs/tabs.js");
/* harmony import */ var _components_notification_notification__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/notification/notification */ "./src/components/notification/notification.js");
/* harmony import */ var _layouts_drawer_drawer__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./layouts/drawer/drawer */ "./src/layouts/drawer/drawer.js");






/***/ }),

/***/ "./src/cloudy.scss":
/*!*************************!*\
  !*** ./src/cloudy.scss ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "./src/components/dialog/dialog.js":
/*!*****************************************!*\
  !*** ./src/components/dialog/dialog.js ***!
  \*****************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var Drupal__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! Drupal */ "Drupal");
/* harmony import */ var Drupal__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(Drupal__WEBPACK_IMPORTED_MODULE_1__);


Drupal__WEBPACK_IMPORTED_MODULE_1___default.a.behaviors.dialog_handler = {
  attach: function attach(context, settings) {
    jquery__WEBPACK_IMPORTED_MODULE_0___default()(window).on('dialogcreate', function (e, dialog) {
      jquery__WEBPACK_IMPORTED_MODULE_0___default()('body').find('.ui-dialog-titlebar-close').once('fa-close-added').each(function () {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).append('<i class="fa fa-window-close"></i>');
      }); // Allow Linkit autocomplete selections to overflow outside of dialog window

      jquery__WEBPACK_IMPORTED_MODULE_0___default()('.ui-dialog .ui-dialog-content').has('.linkit-ui-autocomplete').css('overflow', 'inherit');
    });
  }
};

/***/ }),

/***/ "./src/components/notification/notification.js":
/*!*****************************************************!*\
  !*** ./src/components/notification/notification.js ***!
  \*****************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var Drupal__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! Drupal */ "Drupal");
/* harmony import */ var Drupal__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(Drupal__WEBPACK_IMPORTED_MODULE_1__);


var COOKIE_PREFIX = 'Drupal.visitor.cloudy_notification_dismissed.';
Drupal__WEBPACK_IMPORTED_MODULE_1___default.a.behaviors.notificatin_handler = {
  /**
   * @param {HTMLElement} context
   * @param settings
   */
  attach: function attach(context, settings) {
    // Compare each server side alert changed time with browser cookie values.
    // If the changed time doesn't match for that alert, display the alert.
    jquery__WEBPACK_IMPORTED_MODULE_0___default()('.cloudy-notification').once('alert-processed').each(function () {
      // If this alert has no nid it is not dismissible and did not set a cookie
      if (!jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data('nid')) return;
      var nid = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data('nid');
      var cookieChangedTimestamp = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.cookie(COOKIE_PREFIX + nid);
      var alertChangedTimestamp = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data('changed');

      if (cookieChangedTimestamp != alertChangedTimestamp) {
        // Only show the alert if dismiss button has not been clicked. The
        // element is hidden by default in order to prevent it from momentarily
        // flickering onscreen.
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).addClass('cloudy-notification--active-dismissible');
      }
    }); // Set the cookie value when dismiss button is clicked.

    jquery__WEBPACK_IMPORTED_MODULE_0___default()('.cloudy-notification__close').click(function (event) {
      event.preventDefault();
      var alertElement = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).closest('.cloudy-notification'); // Hide the alert

      alertElement.removeClass('cloudy-notification--active-dismissible'); // Set an alert cookie

      var nid = alertElement.data('nid');
      var lastChangedTimestamp = alertElement.data('changed');
      var path = drupalSettings && drupalSettings.path && drupalSettings.path.baseUrl || '/';
      jquery__WEBPACK_IMPORTED_MODULE_0___default.a.cookie(COOKIE_PREFIX + nid, lastChangedTimestamp, {
        path: path
      });
    });
  }
};

/***/ }),

/***/ "./src/components/tabs/tabs.js":
/*!*************************************!*\
  !*** ./src/components/tabs/tabs.js ***!
  \*************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var Drupal__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! Drupal */ "Drupal");
/* harmony import */ var Drupal__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(Drupal__WEBPACK_IMPORTED_MODULE_1__);


Drupal__WEBPACK_IMPORTED_MODULE_1___default.a.behaviors.tab_handler = {
  attach: function attach(context, settings) {
    var urlHash = window.location.hash;
    var selectedTabId = 0;
    var selectedTab;
    var focusedTab; // on initial load, check for tab navigation fragment in URL and activate indicated tab
    // hash no longer starts with #pane-

    if (urlHash.indexOf('#') == 0) {
      selectedTabId = urlHash.substr(1); // remove the pound sign

      selectedTab = jquery__WEBPACK_IMPORTED_MODULE_0___default()('#tab-' + selectedTabId);
      selectedTab.tab('show');
      selectTab(urlHash, selectedTab);
    }

    jquery__WEBPACK_IMPORTED_MODULE_0___default()('#serviceModes a.nav-link').click(function (event) {
      event.preventDefault(); // activate clicked tab using the link href

      selectTab(jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).attr('href'), jquery__WEBPACK_IMPORTED_MODULE_0___default()(this));
    });
    jquery__WEBPACK_IMPORTED_MODULE_0___default()('#serviceModes').keydown(function (event) {
      if (event.which == 39) {
        // focus tab to the right, if it exists
        var foundNext = focusedTab.parent().next().find('a');

        if (foundNext.attr('id')) {
          focusedTab = foundNext;
          focusedTab.focus();
        }
      } else if (event.which == 37) {
        // focus tab to the left
        var foundPrev = focusedTab.parent().prev().find('a');

        if (foundPrev.attr('id')) {
          focusedTab = foundPrev;
          focusedTab.focus();
        }
      }
    });

    function getSelectedTabIndex(key) {
      // search key for hyphen, and use rest of string as index.
      // we are assuming key will always be in format #tab-1 or #pane-1
      var idx = key.indexOf('-');
      return parseInt(key.substring(idx + 1, key.length));
    }

    function selectTab(linkHash, tab) {
      // add fragment to url; use replaceState to avoid filling up the history with tab changes;
      // we are not supporting browser navigation between tabs/url fragments at this time
      if (history.replaceState) {
        history.replaceState(null, null, linkHash);
      } else {
        location.hash = linkHash;
      } // toggle tabindex; should be -1 for all non-selected tabs for accessibility purposes


      jquery__WEBPACK_IMPORTED_MODULE_0___default()('#serviceModes a.nav-link').attr('tabindex', -1);
      tab.removeAttr('tabindex'); // aria-hidden should be true for all but visible pane; bootstrap doesn't handle this

      jquery__WEBPACK_IMPORTED_MODULE_0___default()('#serviceModesContent .tab-pane').attr('aria-hidden', 'true');
      var id = "#" + linkHash.substr(1);
      var panel = jquery__WEBPACK_IMPORTED_MODULE_0___default()(id);
      panel.attr('aria-hidden', false); // focus the active tab; necessary in the case of page reload or direct navigation

      tab.focus();
      focusedTab = tab;
    }
  }
};

/***/ }),

/***/ "./src/js/libs/jquery-plugin-fitvids.js":
/*!**********************************************!*\
  !*** ./src/js/libs/jquery-plugin-fitvids.js ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*!
* FitVids 1.0
*
* Copyright 2011, Chris Coyier - http://css-tricks.com + Dave Rupert - http://daverupert.com
* Credit to Thierry Koblentz - http://www.alistapart.com/articles/creating-intrinsic-ratios-for-video/
* Released under the WTFPL license - http://sam.zoy.org/wtfpl/
*
* Date: Thu Sept 01 18:00:00 2011 -0500
*/
(function ($) {
  $.fn.fitVids = function (options) {
    var settings = {
      customSelector: null
    };
    var div = document.createElement('div'),
        ref = document.getElementsByTagName('base')[0] || document.getElementsByTagName('script')[0];
    div.className = 'fit-vids-style';
    div.innerHTML = '&shy;<style>         \
      .fluid-width-video-wrapper {        \
         width: 100%;                     \
         position: relative;              \
         padding: 0;                      \
      }                                   \
                                          \
      .fluid-width-video-wrapper iframe,  \
      .fluid-width-video-wrapper object,  \
      .fluid-width-video-wrapper embed {  \
         position: absolute;              \
         top: 0;                          \
         left: 0;                         \
         width: 100%;                     \
         height: 100%;                    \
      }                                   \
    </style>';
    ref.parentNode.insertBefore(div, ref);

    if (options) {
      $.extend(settings, options);
    }

    return this.each(function () {
      var selectors = ["iframe[src*='player.vimeo.com']", "iframe[src*='www.youtube.com']", "iframe[src*='www.kickstarter.com']", 'object', 'embed'];

      if (settings.customSelector) {
        selectors.push(settings.customSelector);
      }

      var $allVideos = $(this).find(selectors.join(','));
      $allVideos.each(function () {
        var $this = $(this);

        if (this.tagName.toLowerCase() == 'embed' && $this.parent('object').length || $this.parent('.fluid-width-video-wrapper').length) {
          return;
        }

        var height = this.tagName.toLowerCase() == 'object' || $this.attr('height') ? $this.attr('height') : $this.height(),
            width = $this.attr('width') ? $this.attr('width') : $this.width(),
            aspectRatio = height / width;

        if (!$this.attr('id')) {
          var videoID = 'fitvid' + Math.floor(Math.random() * 999999);
          $this.attr('id', videoID);
        }

        $this.wrap('<div class="fluid-width-video-wrapper"></div>').parent('.fluid-width-video-wrapper').css('padding-top', aspectRatio * 100 + '%');
        $this.removeAttr('height').removeAttr('width');
      });
    });
  };
})(jQuery);

/***/ }),

/***/ "./src/layouts/drawer/drawer.js":
/*!**************************************!*\
  !*** ./src/layouts/drawer/drawer.js ***!
  \**************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var Drupal__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! Drupal */ "Drupal");
/* harmony import */ var Drupal__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(Drupal__WEBPACK_IMPORTED_MODULE_1__);


Drupal__WEBPACK_IMPORTED_MODULE_1___default.a.behaviors.drawer = {
  /**
   * @param {HTMLElement} context - HTML element to work within, always use `$('.my-class', context)`
   * @param {Object} settings - Drupal settings
   */
  attach: function attach(context, settings) {
    // Global Variables
    var openButton = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.drawer__open');
    var closeButton = jquery__WEBPACK_IMPORTED_MODULE_0___default()('.drawer__close'); // Add open to drawers

    jquery__WEBPACK_IMPORTED_MODULE_0___default()(document, context).once('drawerOpenHandlers').on('click', '.drawer__open', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var target = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data('target');
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(openButton).attr('aria-pressed', 'true');
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(openButton).attr('aria-expanded', 'true');
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(closeButton).attr('aria-pressed', 'false');
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(target).addClass('is-active');
    }); // Add close to drawers

    jquery__WEBPACK_IMPORTED_MODULE_0___default()(document, context).once('drawerCloseHandlers').on('click', '.drawer__close', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var target = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data('target');
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(openButton).attr('aria-pressed', 'false');
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(openButton).attr('aria-expanded', 'false');
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(closeButton).attr('aria-pressed', 'true');
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(target).removeClass('is-active');
    }); // Add close to overlay clicks

    jquery__WEBPACK_IMPORTED_MODULE_0___default()(document, context).once('drawerOverlayHandlers').on('click', '.drawer__overlay', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var target = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data('target');
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(openButton).attr('aria-pressed', 'false');
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(openButton).attr('aria-expanded', 'false');
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(closeButton).attr('aria-pressed', 'true');
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(target).removeClass('is-active');
    });
  }
};

/***/ }),

/***/ 0:
/*!***********************************************!*\
  !*** multi ./src/cloudy.scss ./src/cloudy.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__(/*! ./src/cloudy.scss */"./src/cloudy.scss");
module.exports = __webpack_require__(/*! ./src/cloudy.js */"./src/cloudy.js");


/***/ }),

/***/ "Drupal":
/*!*************************!*\
  !*** external "Drupal" ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = Drupal;

/***/ }),

/***/ "jquery":
/*!*************************!*\
  !*** external "jQuery" ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = jQuery;

/***/ })

/******/ });
//# sourceMappingURL=cloudy.bundle.js.map