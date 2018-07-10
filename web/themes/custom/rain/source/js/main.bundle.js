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
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./source/_patterns/02-elements/accordion-item/accordion-item.js":
/*!***********************************************************************!*\
  !*** ./source/_patterns/02-elements/accordion-item/accordion-item.js ***!
  \***********************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


// UNCOMMENT IF DRUPAL - see components/_meta/_01-foot.twig for attachBehaviors
// Drupal.behaviors.accordion = {
//   attach: function (context, settings) {

(function () {
  // REMOVE IF DRUPAL

  'use strict';

  // Set 'document' to 'context' if Drupal

  var accordionTerm = document.querySelectorAll('.accordion-term');
  var accordionDef = document.querySelectorAll('.accordion-def');

  // If javascript, hide accordion definition on load
  function jsCheck() {
    for (var i = 0; i < accordionDef.length; i++) {
      if (accordionDef[i].classList) {
        accordionDef[i].classList.add('active');
        accordionDef[0].previousElementSibling.classList.add('is-active');
      } else {
        accordionDef[i].className += ' active';
        accordionDef[0].previousElementSibling.classList.add('is-active');
      }
    }
  }

  jsCheck();

  // Accordion Toggle
  // Mobile Click Menu Transition
  for (var i = 0; i < accordionTerm.length; i++) {
    accordionTerm[i].addEventListener('click', function (e) {
      var className = 'is-active';
      // Add is-active class
      if (this.classList) {
        this.classList.toggle(className);
      } else {
        var classes = this.className.split(' ');
        var existingIndex = classes.indexOf(className);

        if (existingIndex >= 0) {
          classes.splice(existingIndex, 1);
        } else {
          classes.push(className);
        }
        this.className = classes.join(' ');
      }
      e.preventDefault();
    });
  }
})(); // REMOVE IF DRUPAL

// UNCOMMENT IF DRUPAL
//   }
// };

/***/ }),

/***/ "./source/_patterns/02-elements/menus/main-menu/main-menu.js":
/*!*******************************************************************!*\
  !*** ./source/_patterns/02-elements/menus/main-menu/main-menu.js ***!
  \*******************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/**
 * @file
 * A JavaScript file containing the main menu functionality (small/large screen)
 *
 */

// JavaScript should be made compatible with libraries other than jQuery by
// wrapping it with an "anonymous closure". See:
// - https://drupal.org/node/1446420
// - http://www.adequatelygood.com/2010/3/JavaScript-Module-Pattern-In-Depth


// (function (Drupal) { // UNCOMMENT IF DRUPAL.
//
//   Drupal.behaviors.mainMenu = {
//     attach: function (context) {

(function () {
  // REMOVE IF DRUPAL.

  'use strict';

  // Use context instead of document IF DRUPAL.

  var toggle_expand = document.getElementById('toggle-expand');
  var menu = document.getElementById('main-nav');
  var expand_menu = menu.getElementsByClassName('expand-sub');

  // Mobile Menu Show/Hide.
  toggle_expand.addEventListener('click', function (e) {
    toggle_expand.classList.toggle('toggle-expand--open');
    menu.classList.toggle('main-nav--open');
  });

  // Expose mobile sub menu on click.
  for (var i = 0; i < expand_menu.length; i++) {
    expand_menu[i].addEventListener('click', function (e) {
      var menu_item = e.currentTarget;
      var sub_menu = menu_item.nextElementSibling;

      menu_item.classList.toggle('expand-sub--open');
      sub_menu.classList.toggle('main-menu--sub-open');
    });
  }
})(); // REMOVE IF DRUPAL.

// })(Drupal); // UNCOMMENT IF DRUPAL.

/***/ }),

/***/ "./source/_patterns/02-elements/menus/tabs/tabs.js":
/*!*********************************************************!*\
  !*** ./source/_patterns/02-elements/menus/tabs/tabs.js ***!
  \*********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


(function () {

  'use strict';

  var el = document.querySelectorAll('.tabs');
  var tabNavigationLinks = document.querySelectorAll('.tabs__link');
  var tabContentContainers = document.querySelectorAll('.tabs__tab');
  var activeIndex = 0;

  /**
   * handleClick
   * @description Handles click event listeners on each of the links in the
   *   tab navigation. Returns nothing.
   * @param {HTMLElement} link The link to listen for events on
   * @param {Number} index The index of that link
   */
  var handleClick = function handleClick(link, index) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      goToTab(index);
    });
  };

  /**
   * goToTab
   * @description Goes to a specific tab based on index. Returns nothing.
   * @param {Number} index The index of the tab to go to
   */
  var goToTab = function goToTab(index) {
    if (index !== activeIndex && index >= 0 && index <= tabNavigationLinks.length) {
      tabNavigationLinks[activeIndex].classList.remove('is-active');
      tabNavigationLinks[index].classList.add('is-active');
      tabContentContainers[activeIndex].classList.remove('is-active');
      tabContentContainers[index].classList.add('is-active');
      activeIndex = index;
    }
  };

  /**
   * init
   * @description Initializes the component by removing the no-js class from
   *   the component, and attaching event listeners to each of the nav items.
   *   Returns nothing.
   */
  for (var e = 0; e < el.length; e++) {
    el[e].classList.remove('no-js');
  }

  for (var i = 0; i < tabNavigationLinks.length; i++) {
    var link = tabNavigationLinks[i];
    handleClick(link, i);
  }
})();

/***/ }),

/***/ "./source/_patterns/index.js":
/*!***********************************!*\
  !*** ./source/_patterns/index.js ***!
  \***********************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


__webpack_require__(/*! ./style.scss */ "./source/_patterns/style.scss");

/***/ }),

/***/ "./source/_patterns/style.scss":
/*!*************************************!*\
  !*** ./source/_patterns/style.scss ***!
  \*************************************/
/*! no static exports found */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 0:
/*!*****************************************************************************************************************************************************************************************************************************************************!*\
  !*** multi ./source/_patterns/index.js ./source/_patterns/02-elements/accordion-item/accordion-item.js ./source/_patterns/02-elements/menus/main-menu/main-menu.js ./source/_patterns/02-elements/menus/tabs/tabs.js ./source/_patterns/style.scss ***!
  \*****************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__(/*! ./source/_patterns/index.js */"./source/_patterns/index.js");
__webpack_require__(/*! ./source/_patterns/02-elements/accordion-item/accordion-item.js */"./source/_patterns/02-elements/accordion-item/accordion-item.js");
__webpack_require__(/*! ./source/_patterns/02-elements/menus/main-menu/main-menu.js */"./source/_patterns/02-elements/menus/main-menu/main-menu.js");
__webpack_require__(/*! ./source/_patterns/02-elements/menus/tabs/tabs.js */"./source/_patterns/02-elements/menus/tabs/tabs.js");
module.exports = __webpack_require__(/*! ./source/_patterns/style.scss */"./source/_patterns/style.scss");


/***/ })

/******/ });
//# sourceMappingURL=main.bundle.js.map