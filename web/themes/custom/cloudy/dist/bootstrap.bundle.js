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
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/js/bootstrap.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./node_modules/bootstrap/js/src/alert.js":
/*!************************************************!*\
  !*** ./node_modules/bootstrap/js/src/alert.js ***!
  \************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _util__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./util */ "./node_modules/bootstrap/js/src/util.js");
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

/**
 * --------------------------------------------------------------------------
 * Bootstrap (v4.5.0): alert.js
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * --------------------------------------------------------------------------
 */


/**
 * ------------------------------------------------------------------------
 * Constants
 * ------------------------------------------------------------------------
 */

var NAME = 'alert';
var VERSION = '4.5.0';
var DATA_KEY = 'bs.alert';
var EVENT_KEY = ".".concat(DATA_KEY);
var DATA_API_KEY = '.data-api';
var JQUERY_NO_CONFLICT = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME];
var SELECTOR_DISMISS = '[data-dismiss="alert"]';
var EVENT_CLOSE = "close".concat(EVENT_KEY);
var EVENT_CLOSED = "closed".concat(EVENT_KEY);
var EVENT_CLICK_DATA_API = "click".concat(EVENT_KEY).concat(DATA_API_KEY);
var CLASS_NAME_ALERT = 'alert';
var CLASS_NAME_FADE = 'fade';
var CLASS_NAME_SHOW = 'show';
/**
 * ------------------------------------------------------------------------
 * Class Definition
 * ------------------------------------------------------------------------
 */

var Alert = /*#__PURE__*/function () {
  function Alert(element) {
    _classCallCheck(this, Alert);

    this._element = element;
  } // Getters


  _createClass(Alert, [{
    key: "close",
    // Public
    value: function close(element) {
      var rootElement = this._element;

      if (element) {
        rootElement = this._getRootElement(element);
      }

      var customEvent = this._triggerCloseEvent(rootElement);

      if (customEvent.isDefaultPrevented()) {
        return;
      }

      this._removeElement(rootElement);
    }
  }, {
    key: "dispose",
    value: function dispose() {
      jquery__WEBPACK_IMPORTED_MODULE_0___default.a.removeData(this._element, DATA_KEY);
      this._element = null;
    } // Private

  }, {
    key: "_getRootElement",
    value: function _getRootElement(element) {
      var selector = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getSelectorFromElement(element);
      var parent = false;

      if (selector) {
        parent = document.querySelector(selector);
      }

      if (!parent) {
        parent = jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).closest(".".concat(CLASS_NAME_ALERT))[0];
      }

      return parent;
    }
  }, {
    key: "_triggerCloseEvent",
    value: function _triggerCloseEvent(element) {
      var closeEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_CLOSE);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).trigger(closeEvent);
      return closeEvent;
    }
  }, {
    key: "_removeElement",
    value: function _removeElement(element) {
      var _this = this;

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).removeClass(CLASS_NAME_SHOW);

      if (!jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).hasClass(CLASS_NAME_FADE)) {
        this._destroyElement(element);

        return;
      }

      var transitionDuration = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getTransitionDurationFromElement(element);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).one(_util__WEBPACK_IMPORTED_MODULE_1__["default"].TRANSITION_END, function (event) {
        return _this._destroyElement(element, event);
      }).emulateTransitionEnd(transitionDuration);
    }
  }, {
    key: "_destroyElement",
    value: function _destroyElement(element) {
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).detach().trigger(EVENT_CLOSED).remove();
    } // Static

  }], [{
    key: "_jQueryInterface",
    value: function _jQueryInterface(config) {
      return this.each(function () {
        var $element = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this);
        var data = $element.data(DATA_KEY);

        if (!data) {
          data = new Alert(this);
          $element.data(DATA_KEY, data);
        }

        if (config === 'close') {
          data[config](this);
        }
      });
    }
  }, {
    key: "_handleDismiss",
    value: function _handleDismiss(alertInstance) {
      return function (event) {
        if (event) {
          event.preventDefault();
        }

        alertInstance.close(this);
      };
    }
  }, {
    key: "VERSION",
    get: function get() {
      return VERSION;
    }
  }]);

  return Alert;
}();
/**
 * ------------------------------------------------------------------------
 * Data Api implementation
 * ------------------------------------------------------------------------
 */


jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).on(EVENT_CLICK_DATA_API, SELECTOR_DISMISS, Alert._handleDismiss(new Alert()));
/**
 * ------------------------------------------------------------------------
 * jQuery
 * ------------------------------------------------------------------------
 */

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = Alert._jQueryInterface;
jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].Constructor = Alert;

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].noConflict = function () {
  jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = JQUERY_NO_CONFLICT;
  return Alert._jQueryInterface;
};

/* harmony default export */ __webpack_exports__["default"] = (Alert);

/***/ }),

/***/ "./node_modules/bootstrap/js/src/button.js":
/*!*************************************************!*\
  !*** ./node_modules/bootstrap/js/src/button.js ***!
  \*************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

/**
 * --------------------------------------------------------------------------
 * Bootstrap (v4.5.0): button.js
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * --------------------------------------------------------------------------
 */

/**
 * ------------------------------------------------------------------------
 * Constants
 * ------------------------------------------------------------------------
 */

var NAME = 'button';
var VERSION = '4.5.0';
var DATA_KEY = 'bs.button';
var EVENT_KEY = ".".concat(DATA_KEY);
var DATA_API_KEY = '.data-api';
var JQUERY_NO_CONFLICT = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME];
var CLASS_NAME_ACTIVE = 'active';
var CLASS_NAME_BUTTON = 'btn';
var CLASS_NAME_FOCUS = 'focus';
var SELECTOR_DATA_TOGGLE_CARROT = '[data-toggle^="button"]';
var SELECTOR_DATA_TOGGLES = '[data-toggle="buttons"]';
var SELECTOR_DATA_TOGGLE = '[data-toggle="button"]';
var SELECTOR_DATA_TOGGLES_BUTTONS = '[data-toggle="buttons"] .btn';
var SELECTOR_INPUT = 'input:not([type="hidden"])';
var SELECTOR_ACTIVE = '.active';
var SELECTOR_BUTTON = '.btn';
var EVENT_CLICK_DATA_API = "click".concat(EVENT_KEY).concat(DATA_API_KEY);
var EVENT_FOCUS_BLUR_DATA_API = "focus".concat(EVENT_KEY).concat(DATA_API_KEY, " ") + "blur".concat(EVENT_KEY).concat(DATA_API_KEY);
var EVENT_LOAD_DATA_API = "load".concat(EVENT_KEY).concat(DATA_API_KEY);
/**
 * ------------------------------------------------------------------------
 * Class Definition
 * ------------------------------------------------------------------------
 */

var Button = /*#__PURE__*/function () {
  function Button(element) {
    _classCallCheck(this, Button);

    this._element = element;
  } // Getters


  _createClass(Button, [{
    key: "toggle",
    // Public
    value: function toggle() {
      var triggerChangeEvent = true;
      var addAriaPressed = true;
      var rootElement = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).closest(SELECTOR_DATA_TOGGLES)[0];

      if (rootElement) {
        var input = this._element.querySelector(SELECTOR_INPUT);

        if (input) {
          if (input.type === 'radio') {
            if (input.checked && this._element.classList.contains(CLASS_NAME_ACTIVE)) {
              triggerChangeEvent = false;
            } else {
              var activeElement = rootElement.querySelector(SELECTOR_ACTIVE);

              if (activeElement) {
                jquery__WEBPACK_IMPORTED_MODULE_0___default()(activeElement).removeClass(CLASS_NAME_ACTIVE);
              }
            }
          }

          if (triggerChangeEvent) {
            // if it's not a radio button or checkbox don't add a pointless/invalid checked property to the input
            if (input.type === 'checkbox' || input.type === 'radio') {
              input.checked = !this._element.classList.contains(CLASS_NAME_ACTIVE);
            }

            jquery__WEBPACK_IMPORTED_MODULE_0___default()(input).trigger('change');
          }

          input.focus();
          addAriaPressed = false;
        }
      }

      if (!(this._element.hasAttribute('disabled') || this._element.classList.contains('disabled'))) {
        if (addAriaPressed) {
          this._element.setAttribute('aria-pressed', !this._element.classList.contains(CLASS_NAME_ACTIVE));
        }

        if (triggerChangeEvent) {
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).toggleClass(CLASS_NAME_ACTIVE);
        }
      }
    }
  }, {
    key: "dispose",
    value: function dispose() {
      jquery__WEBPACK_IMPORTED_MODULE_0___default.a.removeData(this._element, DATA_KEY);
      this._element = null;
    } // Static

  }], [{
    key: "_jQueryInterface",
    value: function _jQueryInterface(config) {
      return this.each(function () {
        var data = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data(DATA_KEY);

        if (!data) {
          data = new Button(this);
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data(DATA_KEY, data);
        }

        if (config === 'toggle') {
          data[config]();
        }
      });
    }
  }, {
    key: "VERSION",
    get: function get() {
      return VERSION;
    }
  }]);

  return Button;
}();
/**
 * ------------------------------------------------------------------------
 * Data Api implementation
 * ------------------------------------------------------------------------
 */


jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).on(EVENT_CLICK_DATA_API, SELECTOR_DATA_TOGGLE_CARROT, function (event) {
  var button = event.target;
  var initialButton = button;

  if (!jquery__WEBPACK_IMPORTED_MODULE_0___default()(button).hasClass(CLASS_NAME_BUTTON)) {
    button = jquery__WEBPACK_IMPORTED_MODULE_0___default()(button).closest(SELECTOR_BUTTON)[0];
  }

  if (!button || button.hasAttribute('disabled') || button.classList.contains('disabled')) {
    event.preventDefault(); // work around Firefox bug #1540995
  } else {
    var inputBtn = button.querySelector(SELECTOR_INPUT);

    if (inputBtn && (inputBtn.hasAttribute('disabled') || inputBtn.classList.contains('disabled'))) {
      event.preventDefault(); // work around Firefox bug #1540995

      return;
    }

    if (initialButton.tagName === 'LABEL' && inputBtn && inputBtn.type === 'checkbox') {
      event.preventDefault(); // work around event sent to label and input
    }

    Button._jQueryInterface.call(jquery__WEBPACK_IMPORTED_MODULE_0___default()(button), 'toggle');
  }
}).on(EVENT_FOCUS_BLUR_DATA_API, SELECTOR_DATA_TOGGLE_CARROT, function (event) {
  var button = jquery__WEBPACK_IMPORTED_MODULE_0___default()(event.target).closest(SELECTOR_BUTTON)[0];
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(button).toggleClass(CLASS_NAME_FOCUS, /^focus(in)?$/.test(event.type));
});
jquery__WEBPACK_IMPORTED_MODULE_0___default()(window).on(EVENT_LOAD_DATA_API, function () {
  // ensure correct active class is set to match the controls' actual values/states
  // find all checkboxes/readio buttons inside data-toggle groups
  var buttons = [].slice.call(document.querySelectorAll(SELECTOR_DATA_TOGGLES_BUTTONS));

  for (var i = 0, len = buttons.length; i < len; i++) {
    var button = buttons[i];
    var input = button.querySelector(SELECTOR_INPUT);

    if (input.checked || input.hasAttribute('checked')) {
      button.classList.add(CLASS_NAME_ACTIVE);
    } else {
      button.classList.remove(CLASS_NAME_ACTIVE);
    }
  } // find all button toggles


  buttons = [].slice.call(document.querySelectorAll(SELECTOR_DATA_TOGGLE));

  for (var _i = 0, _len = buttons.length; _i < _len; _i++) {
    var _button = buttons[_i];

    if (_button.getAttribute('aria-pressed') === 'true') {
      _button.classList.add(CLASS_NAME_ACTIVE);
    } else {
      _button.classList.remove(CLASS_NAME_ACTIVE);
    }
  }
});
/**
 * ------------------------------------------------------------------------
 * jQuery
 * ------------------------------------------------------------------------
 */

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = Button._jQueryInterface;
jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].Constructor = Button;

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].noConflict = function () {
  jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = JQUERY_NO_CONFLICT;
  return Button._jQueryInterface;
};

/* harmony default export */ __webpack_exports__["default"] = (Button);

/***/ }),

/***/ "./node_modules/bootstrap/js/src/carousel.js":
/*!***************************************************!*\
  !*** ./node_modules/bootstrap/js/src/carousel.js ***!
  \***************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _util__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./util */ "./node_modules/bootstrap/js/src/util.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

/**
 * --------------------------------------------------------------------------
 * Bootstrap (v4.5.0): carousel.js
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * --------------------------------------------------------------------------
 */


/**
 * ------------------------------------------------------------------------
 * Constants
 * ------------------------------------------------------------------------
 */

var NAME = 'carousel';
var VERSION = '4.5.0';
var DATA_KEY = 'bs.carousel';
var EVENT_KEY = ".".concat(DATA_KEY);
var DATA_API_KEY = '.data-api';
var JQUERY_NO_CONFLICT = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME];
var ARROW_LEFT_KEYCODE = 37; // KeyboardEvent.which value for left arrow key

var ARROW_RIGHT_KEYCODE = 39; // KeyboardEvent.which value for right arrow key

var TOUCHEVENT_COMPAT_WAIT = 500; // Time for mouse compat events to fire after touch

var SWIPE_THRESHOLD = 40;
var Default = {
  interval: 5000,
  keyboard: true,
  slide: false,
  pause: 'hover',
  wrap: true,
  touch: true
};
var DefaultType = {
  interval: '(number|boolean)',
  keyboard: 'boolean',
  slide: '(boolean|string)',
  pause: '(string|boolean)',
  wrap: 'boolean',
  touch: 'boolean'
};
var DIRECTION_NEXT = 'next';
var DIRECTION_PREV = 'prev';
var DIRECTION_LEFT = 'left';
var DIRECTION_RIGHT = 'right';
var EVENT_SLIDE = "slide".concat(EVENT_KEY);
var EVENT_SLID = "slid".concat(EVENT_KEY);
var EVENT_KEYDOWN = "keydown".concat(EVENT_KEY);
var EVENT_MOUSEENTER = "mouseenter".concat(EVENT_KEY);
var EVENT_MOUSELEAVE = "mouseleave".concat(EVENT_KEY);
var EVENT_TOUCHSTART = "touchstart".concat(EVENT_KEY);
var EVENT_TOUCHMOVE = "touchmove".concat(EVENT_KEY);
var EVENT_TOUCHEND = "touchend".concat(EVENT_KEY);
var EVENT_POINTERDOWN = "pointerdown".concat(EVENT_KEY);
var EVENT_POINTERUP = "pointerup".concat(EVENT_KEY);
var EVENT_DRAG_START = "dragstart".concat(EVENT_KEY);
var EVENT_LOAD_DATA_API = "load".concat(EVENT_KEY).concat(DATA_API_KEY);
var EVENT_CLICK_DATA_API = "click".concat(EVENT_KEY).concat(DATA_API_KEY);
var CLASS_NAME_CAROUSEL = 'carousel';
var CLASS_NAME_ACTIVE = 'active';
var CLASS_NAME_SLIDE = 'slide';
var CLASS_NAME_RIGHT = 'carousel-item-right';
var CLASS_NAME_LEFT = 'carousel-item-left';
var CLASS_NAME_NEXT = 'carousel-item-next';
var CLASS_NAME_PREV = 'carousel-item-prev';
var CLASS_NAME_POINTER_EVENT = 'pointer-event';
var SELECTOR_ACTIVE = '.active';
var SELECTOR_ACTIVE_ITEM = '.active.carousel-item';
var SELECTOR_ITEM = '.carousel-item';
var SELECTOR_ITEM_IMG = '.carousel-item img';
var SELECTOR_NEXT_PREV = '.carousel-item-next, .carousel-item-prev';
var SELECTOR_INDICATORS = '.carousel-indicators';
var SELECTOR_DATA_SLIDE = '[data-slide], [data-slide-to]';
var SELECTOR_DATA_RIDE = '[data-ride="carousel"]';
var PointerType = {
  TOUCH: 'touch',
  PEN: 'pen'
};
/**
 * ------------------------------------------------------------------------
 * Class Definition
 * ------------------------------------------------------------------------
 */

var Carousel = /*#__PURE__*/function () {
  function Carousel(element, config) {
    _classCallCheck(this, Carousel);

    this._items = null;
    this._interval = null;
    this._activeElement = null;
    this._isPaused = false;
    this._isSliding = false;
    this.touchTimeout = null;
    this.touchStartX = 0;
    this.touchDeltaX = 0;
    this._config = this._getConfig(config);
    this._element = element;
    this._indicatorsElement = this._element.querySelector(SELECTOR_INDICATORS);
    this._touchSupported = 'ontouchstart' in document.documentElement || navigator.maxTouchPoints > 0;
    this._pointerEvent = Boolean(window.PointerEvent || window.MSPointerEvent);

    this._addEventListeners();
  } // Getters


  _createClass(Carousel, [{
    key: "next",
    // Public
    value: function next() {
      if (!this._isSliding) {
        this._slide(DIRECTION_NEXT);
      }
    }
  }, {
    key: "nextWhenVisible",
    value: function nextWhenVisible() {
      // Don't call next when the page isn't visible
      // or the carousel or its parent isn't visible
      if (!document.hidden && jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).is(':visible') && jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).css('visibility') !== 'hidden') {
        this.next();
      }
    }
  }, {
    key: "prev",
    value: function prev() {
      if (!this._isSliding) {
        this._slide(DIRECTION_PREV);
      }
    }
  }, {
    key: "pause",
    value: function pause(event) {
      if (!event) {
        this._isPaused = true;
      }

      if (this._element.querySelector(SELECTOR_NEXT_PREV)) {
        _util__WEBPACK_IMPORTED_MODULE_1__["default"].triggerTransitionEnd(this._element);
        this.cycle(true);
      }

      clearInterval(this._interval);
      this._interval = null;
    }
  }, {
    key: "cycle",
    value: function cycle(event) {
      if (!event) {
        this._isPaused = false;
      }

      if (this._interval) {
        clearInterval(this._interval);
        this._interval = null;
      }

      if (this._config.interval && !this._isPaused) {
        this._interval = setInterval((document.visibilityState ? this.nextWhenVisible : this.next).bind(this), this._config.interval);
      }
    }
  }, {
    key: "to",
    value: function to(index) {
      var _this = this;

      this._activeElement = this._element.querySelector(SELECTOR_ACTIVE_ITEM);

      var activeIndex = this._getItemIndex(this._activeElement);

      if (index > this._items.length - 1 || index < 0) {
        return;
      }

      if (this._isSliding) {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).one(EVENT_SLID, function () {
          return _this.to(index);
        });
        return;
      }

      if (activeIndex === index) {
        this.pause();
        this.cycle();
        return;
      }

      var direction = index > activeIndex ? DIRECTION_NEXT : DIRECTION_PREV;

      this._slide(direction, this._items[index]);
    }
  }, {
    key: "dispose",
    value: function dispose() {
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).off(EVENT_KEY);
      jquery__WEBPACK_IMPORTED_MODULE_0___default.a.removeData(this._element, DATA_KEY);
      this._items = null;
      this._config = null;
      this._element = null;
      this._interval = null;
      this._isPaused = null;
      this._isSliding = null;
      this._activeElement = null;
      this._indicatorsElement = null;
    } // Private

  }, {
    key: "_getConfig",
    value: function _getConfig(config) {
      config = _objectSpread(_objectSpread({}, Default), config);
      _util__WEBPACK_IMPORTED_MODULE_1__["default"].typeCheckConfig(NAME, config, DefaultType);
      return config;
    }
  }, {
    key: "_handleSwipe",
    value: function _handleSwipe() {
      var absDeltax = Math.abs(this.touchDeltaX);

      if (absDeltax <= SWIPE_THRESHOLD) {
        return;
      }

      var direction = absDeltax / this.touchDeltaX;
      this.touchDeltaX = 0; // swipe left

      if (direction > 0) {
        this.prev();
      } // swipe right


      if (direction < 0) {
        this.next();
      }
    }
  }, {
    key: "_addEventListeners",
    value: function _addEventListeners() {
      var _this2 = this;

      if (this._config.keyboard) {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).on(EVENT_KEYDOWN, function (event) {
          return _this2._keydown(event);
        });
      }

      if (this._config.pause === 'hover') {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).on(EVENT_MOUSEENTER, function (event) {
          return _this2.pause(event);
        }).on(EVENT_MOUSELEAVE, function (event) {
          return _this2.cycle(event);
        });
      }

      if (this._config.touch) {
        this._addTouchEventListeners();
      }
    }
  }, {
    key: "_addTouchEventListeners",
    value: function _addTouchEventListeners() {
      var _this3 = this;

      if (!this._touchSupported) {
        return;
      }

      var start = function start(event) {
        if (_this3._pointerEvent && PointerType[event.originalEvent.pointerType.toUpperCase()]) {
          _this3.touchStartX = event.originalEvent.clientX;
        } else if (!_this3._pointerEvent) {
          _this3.touchStartX = event.originalEvent.touches[0].clientX;
        }
      };

      var move = function move(event) {
        // ensure swiping with one touch and not pinching
        if (event.originalEvent.touches && event.originalEvent.touches.length > 1) {
          _this3.touchDeltaX = 0;
        } else {
          _this3.touchDeltaX = event.originalEvent.touches[0].clientX - _this3.touchStartX;
        }
      };

      var end = function end(event) {
        if (_this3._pointerEvent && PointerType[event.originalEvent.pointerType.toUpperCase()]) {
          _this3.touchDeltaX = event.originalEvent.clientX - _this3.touchStartX;
        }

        _this3._handleSwipe();

        if (_this3._config.pause === 'hover') {
          // If it's a touch-enabled device, mouseenter/leave are fired as
          // part of the mouse compatibility events on first tap - the carousel
          // would stop cycling until user tapped out of it;
          // here, we listen for touchend, explicitly pause the carousel
          // (as if it's the second time we tap on it, mouseenter compat event
          // is NOT fired) and after a timeout (to allow for mouse compatibility
          // events to fire) we explicitly restart cycling
          _this3.pause();

          if (_this3.touchTimeout) {
            clearTimeout(_this3.touchTimeout);
          }

          _this3.touchTimeout = setTimeout(function (event) {
            return _this3.cycle(event);
          }, TOUCHEVENT_COMPAT_WAIT + _this3._config.interval);
        }
      };

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element.querySelectorAll(SELECTOR_ITEM_IMG)).on(EVENT_DRAG_START, function (e) {
        return e.preventDefault();
      });

      if (this._pointerEvent) {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).on(EVENT_POINTERDOWN, function (event) {
          return start(event);
        });
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).on(EVENT_POINTERUP, function (event) {
          return end(event);
        });

        this._element.classList.add(CLASS_NAME_POINTER_EVENT);
      } else {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).on(EVENT_TOUCHSTART, function (event) {
          return start(event);
        });
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).on(EVENT_TOUCHMOVE, function (event) {
          return move(event);
        });
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).on(EVENT_TOUCHEND, function (event) {
          return end(event);
        });
      }
    }
  }, {
    key: "_keydown",
    value: function _keydown(event) {
      if (/input|textarea/i.test(event.target.tagName)) {
        return;
      }

      switch (event.which) {
        case ARROW_LEFT_KEYCODE:
          event.preventDefault();
          this.prev();
          break;

        case ARROW_RIGHT_KEYCODE:
          event.preventDefault();
          this.next();
          break;

        default:
      }
    }
  }, {
    key: "_getItemIndex",
    value: function _getItemIndex(element) {
      this._items = element && element.parentNode ? [].slice.call(element.parentNode.querySelectorAll(SELECTOR_ITEM)) : [];
      return this._items.indexOf(element);
    }
  }, {
    key: "_getItemByDirection",
    value: function _getItemByDirection(direction, activeElement) {
      var isNextDirection = direction === DIRECTION_NEXT;
      var isPrevDirection = direction === DIRECTION_PREV;

      var activeIndex = this._getItemIndex(activeElement);

      var lastItemIndex = this._items.length - 1;
      var isGoingToWrap = isPrevDirection && activeIndex === 0 || isNextDirection && activeIndex === lastItemIndex;

      if (isGoingToWrap && !this._config.wrap) {
        return activeElement;
      }

      var delta = direction === DIRECTION_PREV ? -1 : 1;
      var itemIndex = (activeIndex + delta) % this._items.length;
      return itemIndex === -1 ? this._items[this._items.length - 1] : this._items[itemIndex];
    }
  }, {
    key: "_triggerSlideEvent",
    value: function _triggerSlideEvent(relatedTarget, eventDirectionName) {
      var targetIndex = this._getItemIndex(relatedTarget);

      var fromIndex = this._getItemIndex(this._element.querySelector(SELECTOR_ACTIVE_ITEM));

      var slideEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_SLIDE, {
        relatedTarget: relatedTarget,
        direction: eventDirectionName,
        from: fromIndex,
        to: targetIndex
      });
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).trigger(slideEvent);
      return slideEvent;
    }
  }, {
    key: "_setActiveIndicatorElement",
    value: function _setActiveIndicatorElement(element) {
      if (this._indicatorsElement) {
        var indicators = [].slice.call(this._indicatorsElement.querySelectorAll(SELECTOR_ACTIVE));
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(indicators).removeClass(CLASS_NAME_ACTIVE);

        var nextIndicator = this._indicatorsElement.children[this._getItemIndex(element)];

        if (nextIndicator) {
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(nextIndicator).addClass(CLASS_NAME_ACTIVE);
        }
      }
    }
  }, {
    key: "_slide",
    value: function _slide(direction, element) {
      var _this4 = this;

      var activeElement = this._element.querySelector(SELECTOR_ACTIVE_ITEM);

      var activeElementIndex = this._getItemIndex(activeElement);

      var nextElement = element || activeElement && this._getItemByDirection(direction, activeElement);

      var nextElementIndex = this._getItemIndex(nextElement);

      var isCycling = Boolean(this._interval);
      var directionalClassName;
      var orderClassName;
      var eventDirectionName;

      if (direction === DIRECTION_NEXT) {
        directionalClassName = CLASS_NAME_LEFT;
        orderClassName = CLASS_NAME_NEXT;
        eventDirectionName = DIRECTION_LEFT;
      } else {
        directionalClassName = CLASS_NAME_RIGHT;
        orderClassName = CLASS_NAME_PREV;
        eventDirectionName = DIRECTION_RIGHT;
      }

      if (nextElement && jquery__WEBPACK_IMPORTED_MODULE_0___default()(nextElement).hasClass(CLASS_NAME_ACTIVE)) {
        this._isSliding = false;
        return;
      }

      var slideEvent = this._triggerSlideEvent(nextElement, eventDirectionName);

      if (slideEvent.isDefaultPrevented()) {
        return;
      }

      if (!activeElement || !nextElement) {
        // Some weirdness is happening, so we bail
        return;
      }

      this._isSliding = true;

      if (isCycling) {
        this.pause();
      }

      this._setActiveIndicatorElement(nextElement);

      var slidEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_SLID, {
        relatedTarget: nextElement,
        direction: eventDirectionName,
        from: activeElementIndex,
        to: nextElementIndex
      });

      if (jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).hasClass(CLASS_NAME_SLIDE)) {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(nextElement).addClass(orderClassName);
        _util__WEBPACK_IMPORTED_MODULE_1__["default"].reflow(nextElement);
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(activeElement).addClass(directionalClassName);
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(nextElement).addClass(directionalClassName);
        var nextElementInterval = parseInt(nextElement.getAttribute('data-interval'), 10);

        if (nextElementInterval) {
          this._config.defaultInterval = this._config.defaultInterval || this._config.interval;
          this._config.interval = nextElementInterval;
        } else {
          this._config.interval = this._config.defaultInterval || this._config.interval;
        }

        var transitionDuration = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getTransitionDurationFromElement(activeElement);
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(activeElement).one(_util__WEBPACK_IMPORTED_MODULE_1__["default"].TRANSITION_END, function () {
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(nextElement).removeClass("".concat(directionalClassName, " ").concat(orderClassName)).addClass(CLASS_NAME_ACTIVE);
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(activeElement).removeClass("".concat(CLASS_NAME_ACTIVE, " ").concat(orderClassName, " ").concat(directionalClassName));
          _this4._isSliding = false;
          setTimeout(function () {
            return jquery__WEBPACK_IMPORTED_MODULE_0___default()(_this4._element).trigger(slidEvent);
          }, 0);
        }).emulateTransitionEnd(transitionDuration);
      } else {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(activeElement).removeClass(CLASS_NAME_ACTIVE);
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(nextElement).addClass(CLASS_NAME_ACTIVE);
        this._isSliding = false;
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).trigger(slidEvent);
      }

      if (isCycling) {
        this.cycle();
      }
    } // Static

  }], [{
    key: "_jQueryInterface",
    value: function _jQueryInterface(config) {
      return this.each(function () {
        var data = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data(DATA_KEY);

        var _config = _objectSpread(_objectSpread({}, Default), jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data());

        if (_typeof(config) === 'object') {
          _config = _objectSpread(_objectSpread({}, _config), config);
        }

        var action = typeof config === 'string' ? config : _config.slide;

        if (!data) {
          data = new Carousel(this, _config);
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data(DATA_KEY, data);
        }

        if (typeof config === 'number') {
          data.to(config);
        } else if (typeof action === 'string') {
          if (typeof data[action] === 'undefined') {
            throw new TypeError("No method named \"".concat(action, "\""));
          }

          data[action]();
        } else if (_config.interval && _config.ride) {
          data.pause();
          data.cycle();
        }
      });
    }
  }, {
    key: "_dataApiClickHandler",
    value: function _dataApiClickHandler(event) {
      var selector = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getSelectorFromElement(this);

      if (!selector) {
        return;
      }

      var target = jquery__WEBPACK_IMPORTED_MODULE_0___default()(selector)[0];

      if (!target || !jquery__WEBPACK_IMPORTED_MODULE_0___default()(target).hasClass(CLASS_NAME_CAROUSEL)) {
        return;
      }

      var config = _objectSpread(_objectSpread({}, jquery__WEBPACK_IMPORTED_MODULE_0___default()(target).data()), jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data());

      var slideIndex = this.getAttribute('data-slide-to');

      if (slideIndex) {
        config.interval = false;
      }

      Carousel._jQueryInterface.call(jquery__WEBPACK_IMPORTED_MODULE_0___default()(target), config);

      if (slideIndex) {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(target).data(DATA_KEY).to(slideIndex);
      }

      event.preventDefault();
    }
  }, {
    key: "VERSION",
    get: function get() {
      return VERSION;
    }
  }, {
    key: "Default",
    get: function get() {
      return Default;
    }
  }]);

  return Carousel;
}();
/**
 * ------------------------------------------------------------------------
 * Data Api implementation
 * ------------------------------------------------------------------------
 */


jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).on(EVENT_CLICK_DATA_API, SELECTOR_DATA_SLIDE, Carousel._dataApiClickHandler);
jquery__WEBPACK_IMPORTED_MODULE_0___default()(window).on(EVENT_LOAD_DATA_API, function () {
  var carousels = [].slice.call(document.querySelectorAll(SELECTOR_DATA_RIDE));

  for (var i = 0, len = carousels.length; i < len; i++) {
    var $carousel = jquery__WEBPACK_IMPORTED_MODULE_0___default()(carousels[i]);

    Carousel._jQueryInterface.call($carousel, $carousel.data());
  }
});
/**
 * ------------------------------------------------------------------------
 * jQuery
 * ------------------------------------------------------------------------
 */

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = Carousel._jQueryInterface;
jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].Constructor = Carousel;

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].noConflict = function () {
  jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = JQUERY_NO_CONFLICT;
  return Carousel._jQueryInterface;
};

/* harmony default export */ __webpack_exports__["default"] = (Carousel);

/***/ }),

/***/ "./node_modules/bootstrap/js/src/collapse.js":
/*!***************************************************!*\
  !*** ./node_modules/bootstrap/js/src/collapse.js ***!
  \***************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _util__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./util */ "./node_modules/bootstrap/js/src/util.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

/**
 * --------------------------------------------------------------------------
 * Bootstrap (v4.5.0): collapse.js
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * --------------------------------------------------------------------------
 */


/**
 * ------------------------------------------------------------------------
 * Constants
 * ------------------------------------------------------------------------
 */

var NAME = 'collapse';
var VERSION = '4.5.0';
var DATA_KEY = 'bs.collapse';
var EVENT_KEY = ".".concat(DATA_KEY);
var DATA_API_KEY = '.data-api';
var JQUERY_NO_CONFLICT = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME];
var Default = {
  toggle: true,
  parent: ''
};
var DefaultType = {
  toggle: 'boolean',
  parent: '(string|element)'
};
var EVENT_SHOW = "show".concat(EVENT_KEY);
var EVENT_SHOWN = "shown".concat(EVENT_KEY);
var EVENT_HIDE = "hide".concat(EVENT_KEY);
var EVENT_HIDDEN = "hidden".concat(EVENT_KEY);
var EVENT_CLICK_DATA_API = "click".concat(EVENT_KEY).concat(DATA_API_KEY);
var CLASS_NAME_SHOW = 'show';
var CLASS_NAME_COLLAPSE = 'collapse';
var CLASS_NAME_COLLAPSING = 'collapsing';
var CLASS_NAME_COLLAPSED = 'collapsed';
var DIMENSION_WIDTH = 'width';
var DIMENSION_HEIGHT = 'height';
var SELECTOR_ACTIVES = '.show, .collapsing';
var SELECTOR_DATA_TOGGLE = '[data-toggle="collapse"]';
/**
 * ------------------------------------------------------------------------
 * Class Definition
 * ------------------------------------------------------------------------
 */

var Collapse = /*#__PURE__*/function () {
  function Collapse(element, config) {
    _classCallCheck(this, Collapse);

    this._isTransitioning = false;
    this._element = element;
    this._config = this._getConfig(config);
    this._triggerArray = [].slice.call(document.querySelectorAll("[data-toggle=\"collapse\"][href=\"#".concat(element.id, "\"],") + "[data-toggle=\"collapse\"][data-target=\"#".concat(element.id, "\"]")));
    var toggleList = [].slice.call(document.querySelectorAll(SELECTOR_DATA_TOGGLE));

    for (var i = 0, len = toggleList.length; i < len; i++) {
      var elem = toggleList[i];
      var selector = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getSelectorFromElement(elem);
      var filterElement = [].slice.call(document.querySelectorAll(selector)).filter(function (foundElem) {
        return foundElem === element;
      });

      if (selector !== null && filterElement.length > 0) {
        this._selector = selector;

        this._triggerArray.push(elem);
      }
    }

    this._parent = this._config.parent ? this._getParent() : null;

    if (!this._config.parent) {
      this._addAriaAndCollapsedClass(this._element, this._triggerArray);
    }

    if (this._config.toggle) {
      this.toggle();
    }
  } // Getters


  _createClass(Collapse, [{
    key: "toggle",
    // Public
    value: function toggle() {
      if (jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).hasClass(CLASS_NAME_SHOW)) {
        this.hide();
      } else {
        this.show();
      }
    }
  }, {
    key: "show",
    value: function show() {
      var _this = this;

      if (this._isTransitioning || jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).hasClass(CLASS_NAME_SHOW)) {
        return;
      }

      var actives;
      var activesData;

      if (this._parent) {
        actives = [].slice.call(this._parent.querySelectorAll(SELECTOR_ACTIVES)).filter(function (elem) {
          if (typeof _this._config.parent === 'string') {
            return elem.getAttribute('data-parent') === _this._config.parent;
          }

          return elem.classList.contains(CLASS_NAME_COLLAPSE);
        });

        if (actives.length === 0) {
          actives = null;
        }
      }

      if (actives) {
        activesData = jquery__WEBPACK_IMPORTED_MODULE_0___default()(actives).not(this._selector).data(DATA_KEY);

        if (activesData && activesData._isTransitioning) {
          return;
        }
      }

      var startEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_SHOW);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).trigger(startEvent);

      if (startEvent.isDefaultPrevented()) {
        return;
      }

      if (actives) {
        Collapse._jQueryInterface.call(jquery__WEBPACK_IMPORTED_MODULE_0___default()(actives).not(this._selector), 'hide');

        if (!activesData) {
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(actives).data(DATA_KEY, null);
        }
      }

      var dimension = this._getDimension();

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).removeClass(CLASS_NAME_COLLAPSE).addClass(CLASS_NAME_COLLAPSING);
      this._element.style[dimension] = 0;

      if (this._triggerArray.length) {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._triggerArray).removeClass(CLASS_NAME_COLLAPSED).attr('aria-expanded', true);
      }

      this.setTransitioning(true);

      var complete = function complete() {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(_this._element).removeClass(CLASS_NAME_COLLAPSING).addClass("".concat(CLASS_NAME_COLLAPSE, " ").concat(CLASS_NAME_SHOW));
        _this._element.style[dimension] = '';

        _this.setTransitioning(false);

        jquery__WEBPACK_IMPORTED_MODULE_0___default()(_this._element).trigger(EVENT_SHOWN);
      };

      var capitalizedDimension = dimension[0].toUpperCase() + dimension.slice(1);
      var scrollSize = "scroll".concat(capitalizedDimension);
      var transitionDuration = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getTransitionDurationFromElement(this._element);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).one(_util__WEBPACK_IMPORTED_MODULE_1__["default"].TRANSITION_END, complete).emulateTransitionEnd(transitionDuration);
      this._element.style[dimension] = "".concat(this._element[scrollSize], "px");
    }
  }, {
    key: "hide",
    value: function hide() {
      var _this2 = this;

      if (this._isTransitioning || !jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).hasClass(CLASS_NAME_SHOW)) {
        return;
      }

      var startEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_HIDE);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).trigger(startEvent);

      if (startEvent.isDefaultPrevented()) {
        return;
      }

      var dimension = this._getDimension();

      this._element.style[dimension] = "".concat(this._element.getBoundingClientRect()[dimension], "px");
      _util__WEBPACK_IMPORTED_MODULE_1__["default"].reflow(this._element);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).addClass(CLASS_NAME_COLLAPSING).removeClass("".concat(CLASS_NAME_COLLAPSE, " ").concat(CLASS_NAME_SHOW));
      var triggerArrayLength = this._triggerArray.length;

      if (triggerArrayLength > 0) {
        for (var i = 0; i < triggerArrayLength; i++) {
          var trigger = this._triggerArray[i];
          var selector = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getSelectorFromElement(trigger);

          if (selector !== null) {
            var $elem = jquery__WEBPACK_IMPORTED_MODULE_0___default()([].slice.call(document.querySelectorAll(selector)));

            if (!$elem.hasClass(CLASS_NAME_SHOW)) {
              jquery__WEBPACK_IMPORTED_MODULE_0___default()(trigger).addClass(CLASS_NAME_COLLAPSED).attr('aria-expanded', false);
            }
          }
        }
      }

      this.setTransitioning(true);

      var complete = function complete() {
        _this2.setTransitioning(false);

        jquery__WEBPACK_IMPORTED_MODULE_0___default()(_this2._element).removeClass(CLASS_NAME_COLLAPSING).addClass(CLASS_NAME_COLLAPSE).trigger(EVENT_HIDDEN);
      };

      this._element.style[dimension] = '';
      var transitionDuration = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getTransitionDurationFromElement(this._element);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).one(_util__WEBPACK_IMPORTED_MODULE_1__["default"].TRANSITION_END, complete).emulateTransitionEnd(transitionDuration);
    }
  }, {
    key: "setTransitioning",
    value: function setTransitioning(isTransitioning) {
      this._isTransitioning = isTransitioning;
    }
  }, {
    key: "dispose",
    value: function dispose() {
      jquery__WEBPACK_IMPORTED_MODULE_0___default.a.removeData(this._element, DATA_KEY);
      this._config = null;
      this._parent = null;
      this._element = null;
      this._triggerArray = null;
      this._isTransitioning = null;
    } // Private

  }, {
    key: "_getConfig",
    value: function _getConfig(config) {
      config = _objectSpread(_objectSpread({}, Default), config);
      config.toggle = Boolean(config.toggle); // Coerce string values

      _util__WEBPACK_IMPORTED_MODULE_1__["default"].typeCheckConfig(NAME, config, DefaultType);
      return config;
    }
  }, {
    key: "_getDimension",
    value: function _getDimension() {
      var hasWidth = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).hasClass(DIMENSION_WIDTH);
      return hasWidth ? DIMENSION_WIDTH : DIMENSION_HEIGHT;
    }
  }, {
    key: "_getParent",
    value: function _getParent() {
      var _this3 = this;

      var parent;

      if (_util__WEBPACK_IMPORTED_MODULE_1__["default"].isElement(this._config.parent)) {
        parent = this._config.parent; // It's a jQuery object

        if (typeof this._config.parent.jquery !== 'undefined') {
          parent = this._config.parent[0];
        }
      } else {
        parent = document.querySelector(this._config.parent);
      }

      var selector = "[data-toggle=\"collapse\"][data-parent=\"".concat(this._config.parent, "\"]");
      var children = [].slice.call(parent.querySelectorAll(selector));
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(children).each(function (i, element) {
        _this3._addAriaAndCollapsedClass(Collapse._getTargetFromElement(element), [element]);
      });
      return parent;
    }
  }, {
    key: "_addAriaAndCollapsedClass",
    value: function _addAriaAndCollapsedClass(element, triggerArray) {
      var isOpen = jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).hasClass(CLASS_NAME_SHOW);

      if (triggerArray.length) {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(triggerArray).toggleClass(CLASS_NAME_COLLAPSED, !isOpen).attr('aria-expanded', isOpen);
      }
    } // Static

  }], [{
    key: "_getTargetFromElement",
    value: function _getTargetFromElement(element) {
      var selector = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getSelectorFromElement(element);
      return selector ? document.querySelector(selector) : null;
    }
  }, {
    key: "_jQueryInterface",
    value: function _jQueryInterface(config) {
      return this.each(function () {
        var $this = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this);
        var data = $this.data(DATA_KEY);

        var _config = _objectSpread(_objectSpread(_objectSpread({}, Default), $this.data()), _typeof(config) === 'object' && config ? config : {});

        if (!data && _config.toggle && typeof config === 'string' && /show|hide/.test(config)) {
          _config.toggle = false;
        }

        if (!data) {
          data = new Collapse(this, _config);
          $this.data(DATA_KEY, data);
        }

        if (typeof config === 'string') {
          if (typeof data[config] === 'undefined') {
            throw new TypeError("No method named \"".concat(config, "\""));
          }

          data[config]();
        }
      });
    }
  }, {
    key: "VERSION",
    get: function get() {
      return VERSION;
    }
  }, {
    key: "Default",
    get: function get() {
      return Default;
    }
  }]);

  return Collapse;
}();
/**
 * ------------------------------------------------------------------------
 * Data Api implementation
 * ------------------------------------------------------------------------
 */


jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).on(EVENT_CLICK_DATA_API, SELECTOR_DATA_TOGGLE, function (event) {
  // preventDefault only for <a> elements (which change the URL) not inside the collapsible element
  if (event.currentTarget.tagName === 'A') {
    event.preventDefault();
  }

  var $trigger = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this);
  var selector = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getSelectorFromElement(this);
  var selectors = [].slice.call(document.querySelectorAll(selector));
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(selectors).each(function () {
    var $target = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this);
    var data = $target.data(DATA_KEY);
    var config = data ? 'toggle' : $trigger.data();

    Collapse._jQueryInterface.call($target, config);
  });
});
/**
 * ------------------------------------------------------------------------
 * jQuery
 * ------------------------------------------------------------------------
 */

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = Collapse._jQueryInterface;
jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].Constructor = Collapse;

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].noConflict = function () {
  jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = JQUERY_NO_CONFLICT;
  return Collapse._jQueryInterface;
};

/* harmony default export */ __webpack_exports__["default"] = (Collapse);

/***/ }),

/***/ "./node_modules/bootstrap/js/src/dropdown.js":
/*!***************************************************!*\
  !*** ./node_modules/bootstrap/js/src/dropdown.js ***!
  \***************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var popper_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! popper.js */ "./node_modules/popper.js/dist/esm/popper.js");
/* harmony import */ var _util__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./util */ "./node_modules/bootstrap/js/src/util.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

/**
 * --------------------------------------------------------------------------
 * Bootstrap (v4.5.0): dropdown.js
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * --------------------------------------------------------------------------
 */



/**
 * ------------------------------------------------------------------------
 * Constants
 * ------------------------------------------------------------------------
 */

var NAME = 'dropdown';
var VERSION = '4.5.0';
var DATA_KEY = 'bs.dropdown';
var EVENT_KEY = ".".concat(DATA_KEY);
var DATA_API_KEY = '.data-api';
var JQUERY_NO_CONFLICT = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME];
var ESCAPE_KEYCODE = 27; // KeyboardEvent.which value for Escape (Esc) key

var SPACE_KEYCODE = 32; // KeyboardEvent.which value for space key

var TAB_KEYCODE = 9; // KeyboardEvent.which value for tab key

var ARROW_UP_KEYCODE = 38; // KeyboardEvent.which value for up arrow key

var ARROW_DOWN_KEYCODE = 40; // KeyboardEvent.which value for down arrow key

var RIGHT_MOUSE_BUTTON_WHICH = 3; // MouseEvent.which value for the right button (assuming a right-handed mouse)

var REGEXP_KEYDOWN = new RegExp("".concat(ARROW_UP_KEYCODE, "|").concat(ARROW_DOWN_KEYCODE, "|").concat(ESCAPE_KEYCODE));
var EVENT_HIDE = "hide".concat(EVENT_KEY);
var EVENT_HIDDEN = "hidden".concat(EVENT_KEY);
var EVENT_SHOW = "show".concat(EVENT_KEY);
var EVENT_SHOWN = "shown".concat(EVENT_KEY);
var EVENT_CLICK = "click".concat(EVENT_KEY);
var EVENT_CLICK_DATA_API = "click".concat(EVENT_KEY).concat(DATA_API_KEY);
var EVENT_KEYDOWN_DATA_API = "keydown".concat(EVENT_KEY).concat(DATA_API_KEY);
var EVENT_KEYUP_DATA_API = "keyup".concat(EVENT_KEY).concat(DATA_API_KEY);
var CLASS_NAME_DISABLED = 'disabled';
var CLASS_NAME_SHOW = 'show';
var CLASS_NAME_DROPUP = 'dropup';
var CLASS_NAME_DROPRIGHT = 'dropright';
var CLASS_NAME_DROPLEFT = 'dropleft';
var CLASS_NAME_MENURIGHT = 'dropdown-menu-right';
var CLASS_NAME_POSITION_STATIC = 'position-static';
var SELECTOR_DATA_TOGGLE = '[data-toggle="dropdown"]';
var SELECTOR_FORM_CHILD = '.dropdown form';
var SELECTOR_MENU = '.dropdown-menu';
var SELECTOR_NAVBAR_NAV = '.navbar-nav';
var SELECTOR_VISIBLE_ITEMS = '.dropdown-menu .dropdown-item:not(.disabled):not(:disabled)';
var PLACEMENT_TOP = 'top-start';
var PLACEMENT_TOPEND = 'top-end';
var PLACEMENT_BOTTOM = 'bottom-start';
var PLACEMENT_BOTTOMEND = 'bottom-end';
var PLACEMENT_RIGHT = 'right-start';
var PLACEMENT_LEFT = 'left-start';
var Default = {
  offset: 0,
  flip: true,
  boundary: 'scrollParent',
  reference: 'toggle',
  display: 'dynamic',
  popperConfig: null
};
var DefaultType = {
  offset: '(number|string|function)',
  flip: 'boolean',
  boundary: '(string|element)',
  reference: '(string|element)',
  display: 'string',
  popperConfig: '(null|object)'
};
/**
 * ------------------------------------------------------------------------
 * Class Definition
 * ------------------------------------------------------------------------
 */

var Dropdown = /*#__PURE__*/function () {
  function Dropdown(element, config) {
    _classCallCheck(this, Dropdown);

    this._element = element;
    this._popper = null;
    this._config = this._getConfig(config);
    this._menu = this._getMenuElement();
    this._inNavbar = this._detectNavbar();

    this._addEventListeners();
  } // Getters


  _createClass(Dropdown, [{
    key: "toggle",
    // Public
    value: function toggle() {
      if (this._element.disabled || jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).hasClass(CLASS_NAME_DISABLED)) {
        return;
      }

      var isActive = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._menu).hasClass(CLASS_NAME_SHOW);

      Dropdown._clearMenus();

      if (isActive) {
        return;
      }

      this.show(true);
    }
  }, {
    key: "show",
    value: function show() {
      var usePopper = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;

      if (this._element.disabled || jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).hasClass(CLASS_NAME_DISABLED) || jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._menu).hasClass(CLASS_NAME_SHOW)) {
        return;
      }

      var relatedTarget = {
        relatedTarget: this._element
      };
      var showEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_SHOW, relatedTarget);

      var parent = Dropdown._getParentFromElement(this._element);

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(parent).trigger(showEvent);

      if (showEvent.isDefaultPrevented()) {
        return;
      } // Disable totally Popper.js for Dropdown in Navbar


      if (!this._inNavbar && usePopper) {
        /**
         * Check for Popper dependency
         * Popper - https://popper.js.org
         */
        if (typeof popper_js__WEBPACK_IMPORTED_MODULE_1__["default"] === 'undefined') {
          throw new TypeError('Bootstrap\'s dropdowns require Popper.js (https://popper.js.org/)');
        }

        var referenceElement = this._element;

        if (this._config.reference === 'parent') {
          referenceElement = parent;
        } else if (_util__WEBPACK_IMPORTED_MODULE_2__["default"].isElement(this._config.reference)) {
          referenceElement = this._config.reference; // Check if it's jQuery element

          if (typeof this._config.reference.jquery !== 'undefined') {
            referenceElement = this._config.reference[0];
          }
        } // If boundary is not `scrollParent`, then set position to `static`
        // to allow the menu to "escape" the scroll parent's boundaries
        // https://github.com/twbs/bootstrap/issues/24251


        if (this._config.boundary !== 'scrollParent') {
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(parent).addClass(CLASS_NAME_POSITION_STATIC);
        }

        this._popper = new popper_js__WEBPACK_IMPORTED_MODULE_1__["default"](referenceElement, this._menu, this._getPopperConfig());
      } // If this is a touch-enabled device we add extra
      // empty mouseover listeners to the body's immediate children;
      // only needed because of broken event delegation on iOS
      // https://www.quirksmode.org/blog/archives/2014/02/mouse_event_bub.html


      if ('ontouchstart' in document.documentElement && jquery__WEBPACK_IMPORTED_MODULE_0___default()(parent).closest(SELECTOR_NAVBAR_NAV).length === 0) {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(document.body).children().on('mouseover', null, jquery__WEBPACK_IMPORTED_MODULE_0___default.a.noop);
      }

      this._element.focus();

      this._element.setAttribute('aria-expanded', true);

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._menu).toggleClass(CLASS_NAME_SHOW);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(parent).toggleClass(CLASS_NAME_SHOW).trigger(jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_SHOWN, relatedTarget));
    }
  }, {
    key: "hide",
    value: function hide() {
      if (this._element.disabled || jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).hasClass(CLASS_NAME_DISABLED) || !jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._menu).hasClass(CLASS_NAME_SHOW)) {
        return;
      }

      var relatedTarget = {
        relatedTarget: this._element
      };
      var hideEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_HIDE, relatedTarget);

      var parent = Dropdown._getParentFromElement(this._element);

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(parent).trigger(hideEvent);

      if (hideEvent.isDefaultPrevented()) {
        return;
      }

      if (this._popper) {
        this._popper.destroy();
      }

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._menu).toggleClass(CLASS_NAME_SHOW);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(parent).toggleClass(CLASS_NAME_SHOW).trigger(jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_HIDDEN, relatedTarget));
    }
  }, {
    key: "dispose",
    value: function dispose() {
      jquery__WEBPACK_IMPORTED_MODULE_0___default.a.removeData(this._element, DATA_KEY);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).off(EVENT_KEY);
      this._element = null;
      this._menu = null;

      if (this._popper !== null) {
        this._popper.destroy();

        this._popper = null;
      }
    }
  }, {
    key: "update",
    value: function update() {
      this._inNavbar = this._detectNavbar();

      if (this._popper !== null) {
        this._popper.scheduleUpdate();
      }
    } // Private

  }, {
    key: "_addEventListeners",
    value: function _addEventListeners() {
      var _this = this;

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).on(EVENT_CLICK, function (event) {
        event.preventDefault();
        event.stopPropagation();

        _this.toggle();
      });
    }
  }, {
    key: "_getConfig",
    value: function _getConfig(config) {
      config = _objectSpread(_objectSpread(_objectSpread({}, this.constructor.Default), jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).data()), config);
      _util__WEBPACK_IMPORTED_MODULE_2__["default"].typeCheckConfig(NAME, config, this.constructor.DefaultType);
      return config;
    }
  }, {
    key: "_getMenuElement",
    value: function _getMenuElement() {
      if (!this._menu) {
        var parent = Dropdown._getParentFromElement(this._element);

        if (parent) {
          this._menu = parent.querySelector(SELECTOR_MENU);
        }
      }

      return this._menu;
    }
  }, {
    key: "_getPlacement",
    value: function _getPlacement() {
      var $parentDropdown = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element.parentNode);
      var placement = PLACEMENT_BOTTOM; // Handle dropup

      if ($parentDropdown.hasClass(CLASS_NAME_DROPUP)) {
        placement = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._menu).hasClass(CLASS_NAME_MENURIGHT) ? PLACEMENT_TOPEND : PLACEMENT_TOP;
      } else if ($parentDropdown.hasClass(CLASS_NAME_DROPRIGHT)) {
        placement = PLACEMENT_RIGHT;
      } else if ($parentDropdown.hasClass(CLASS_NAME_DROPLEFT)) {
        placement = PLACEMENT_LEFT;
      } else if (jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._menu).hasClass(CLASS_NAME_MENURIGHT)) {
        placement = PLACEMENT_BOTTOMEND;
      }

      return placement;
    }
  }, {
    key: "_detectNavbar",
    value: function _detectNavbar() {
      return jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).closest('.navbar').length > 0;
    }
  }, {
    key: "_getOffset",
    value: function _getOffset() {
      var _this2 = this;

      var offset = {};

      if (typeof this._config.offset === 'function') {
        offset.fn = function (data) {
          data.offsets = _objectSpread(_objectSpread({}, data.offsets), _this2._config.offset(data.offsets, _this2._element) || {});
          return data;
        };
      } else {
        offset.offset = this._config.offset;
      }

      return offset;
    }
  }, {
    key: "_getPopperConfig",
    value: function _getPopperConfig() {
      var popperConfig = {
        placement: this._getPlacement(),
        modifiers: {
          offset: this._getOffset(),
          flip: {
            enabled: this._config.flip
          },
          preventOverflow: {
            boundariesElement: this._config.boundary
          }
        }
      }; // Disable Popper.js if we have a static display

      if (this._config.display === 'static') {
        popperConfig.modifiers.applyStyle = {
          enabled: false
        };
      }

      return _objectSpread(_objectSpread({}, popperConfig), this._config.popperConfig);
    } // Static

  }], [{
    key: "_jQueryInterface",
    value: function _jQueryInterface(config) {
      return this.each(function () {
        var data = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data(DATA_KEY);

        var _config = _typeof(config) === 'object' ? config : null;

        if (!data) {
          data = new Dropdown(this, _config);
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data(DATA_KEY, data);
        }

        if (typeof config === 'string') {
          if (typeof data[config] === 'undefined') {
            throw new TypeError("No method named \"".concat(config, "\""));
          }

          data[config]();
        }
      });
    }
  }, {
    key: "_clearMenus",
    value: function _clearMenus(event) {
      if (event && (event.which === RIGHT_MOUSE_BUTTON_WHICH || event.type === 'keyup' && event.which !== TAB_KEYCODE)) {
        return;
      }

      var toggles = [].slice.call(document.querySelectorAll(SELECTOR_DATA_TOGGLE));

      for (var i = 0, len = toggles.length; i < len; i++) {
        var parent = Dropdown._getParentFromElement(toggles[i]);

        var context = jquery__WEBPACK_IMPORTED_MODULE_0___default()(toggles[i]).data(DATA_KEY);
        var relatedTarget = {
          relatedTarget: toggles[i]
        };

        if (event && event.type === 'click') {
          relatedTarget.clickEvent = event;
        }

        if (!context) {
          continue;
        }

        var dropdownMenu = context._menu;

        if (!jquery__WEBPACK_IMPORTED_MODULE_0___default()(parent).hasClass(CLASS_NAME_SHOW)) {
          continue;
        }

        if (event && (event.type === 'click' && /input|textarea/i.test(event.target.tagName) || event.type === 'keyup' && event.which === TAB_KEYCODE) && jquery__WEBPACK_IMPORTED_MODULE_0___default.a.contains(parent, event.target)) {
          continue;
        }

        var hideEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_HIDE, relatedTarget);
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(parent).trigger(hideEvent);

        if (hideEvent.isDefaultPrevented()) {
          continue;
        } // If this is a touch-enabled device we remove the extra
        // empty mouseover listeners we added for iOS support


        if ('ontouchstart' in document.documentElement) {
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(document.body).children().off('mouseover', null, jquery__WEBPACK_IMPORTED_MODULE_0___default.a.noop);
        }

        toggles[i].setAttribute('aria-expanded', 'false');

        if (context._popper) {
          context._popper.destroy();
        }

        jquery__WEBPACK_IMPORTED_MODULE_0___default()(dropdownMenu).removeClass(CLASS_NAME_SHOW);
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(parent).removeClass(CLASS_NAME_SHOW).trigger(jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_HIDDEN, relatedTarget));
      }
    }
  }, {
    key: "_getParentFromElement",
    value: function _getParentFromElement(element) {
      var parent;
      var selector = _util__WEBPACK_IMPORTED_MODULE_2__["default"].getSelectorFromElement(element);

      if (selector) {
        parent = document.querySelector(selector);
      }

      return parent || element.parentNode;
    } // eslint-disable-next-line complexity

  }, {
    key: "_dataApiKeydownHandler",
    value: function _dataApiKeydownHandler(event) {
      // If not input/textarea:
      //  - And not a key in REGEXP_KEYDOWN => not a dropdown command
      // If input/textarea:
      //  - If space key => not a dropdown command
      //  - If key is other than escape
      //    - If key is not up or down => not a dropdown command
      //    - If trigger inside the menu => not a dropdown command
      if (/input|textarea/i.test(event.target.tagName) ? event.which === SPACE_KEYCODE || event.which !== ESCAPE_KEYCODE && (event.which !== ARROW_DOWN_KEYCODE && event.which !== ARROW_UP_KEYCODE || jquery__WEBPACK_IMPORTED_MODULE_0___default()(event.target).closest(SELECTOR_MENU).length) : !REGEXP_KEYDOWN.test(event.which)) {
        return;
      }

      if (this.disabled || jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).hasClass(CLASS_NAME_DISABLED)) {
        return;
      }

      var parent = Dropdown._getParentFromElement(this);

      var isActive = jquery__WEBPACK_IMPORTED_MODULE_0___default()(parent).hasClass(CLASS_NAME_SHOW);

      if (!isActive && event.which === ESCAPE_KEYCODE) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();

      if (!isActive || isActive && (event.which === ESCAPE_KEYCODE || event.which === SPACE_KEYCODE)) {
        if (event.which === ESCAPE_KEYCODE) {
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(parent.querySelector(SELECTOR_DATA_TOGGLE)).trigger('focus');
        }

        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).trigger('click');
        return;
      }

      var items = [].slice.call(parent.querySelectorAll(SELECTOR_VISIBLE_ITEMS)).filter(function (item) {
        return jquery__WEBPACK_IMPORTED_MODULE_0___default()(item).is(':visible');
      });

      if (items.length === 0) {
        return;
      }

      var index = items.indexOf(event.target);

      if (event.which === ARROW_UP_KEYCODE && index > 0) {
        // Up
        index--;
      }

      if (event.which === ARROW_DOWN_KEYCODE && index < items.length - 1) {
        // Down
        index++;
      }

      if (index < 0) {
        index = 0;
      }

      items[index].focus();
    }
  }, {
    key: "VERSION",
    get: function get() {
      return VERSION;
    }
  }, {
    key: "Default",
    get: function get() {
      return Default;
    }
  }, {
    key: "DefaultType",
    get: function get() {
      return DefaultType;
    }
  }]);

  return Dropdown;
}();
/**
 * ------------------------------------------------------------------------
 * Data Api implementation
 * ------------------------------------------------------------------------
 */


jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).on(EVENT_KEYDOWN_DATA_API, SELECTOR_DATA_TOGGLE, Dropdown._dataApiKeydownHandler).on(EVENT_KEYDOWN_DATA_API, SELECTOR_MENU, Dropdown._dataApiKeydownHandler).on("".concat(EVENT_CLICK_DATA_API, " ").concat(EVENT_KEYUP_DATA_API), Dropdown._clearMenus).on(EVENT_CLICK_DATA_API, SELECTOR_DATA_TOGGLE, function (event) {
  event.preventDefault();
  event.stopPropagation();

  Dropdown._jQueryInterface.call(jquery__WEBPACK_IMPORTED_MODULE_0___default()(this), 'toggle');
}).on(EVENT_CLICK_DATA_API, SELECTOR_FORM_CHILD, function (e) {
  e.stopPropagation();
});
/**
 * ------------------------------------------------------------------------
 * jQuery
 * ------------------------------------------------------------------------
 */

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = Dropdown._jQueryInterface;
jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].Constructor = Dropdown;

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].noConflict = function () {
  jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = JQUERY_NO_CONFLICT;
  return Dropdown._jQueryInterface;
};

/* harmony default export */ __webpack_exports__["default"] = (Dropdown);

/***/ }),

/***/ "./node_modules/bootstrap/js/src/modal.js":
/*!************************************************!*\
  !*** ./node_modules/bootstrap/js/src/modal.js ***!
  \************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _util__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./util */ "./node_modules/bootstrap/js/src/util.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

/**
 * --------------------------------------------------------------------------
 * Bootstrap (v4.5.0): modal.js
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * --------------------------------------------------------------------------
 */


/**
 * ------------------------------------------------------------------------
 * Constants
 * ------------------------------------------------------------------------
 */

var NAME = 'modal';
var VERSION = '4.5.0';
var DATA_KEY = 'bs.modal';
var EVENT_KEY = ".".concat(DATA_KEY);
var DATA_API_KEY = '.data-api';
var JQUERY_NO_CONFLICT = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME];
var ESCAPE_KEYCODE = 27; // KeyboardEvent.which value for Escape (Esc) key

var Default = {
  backdrop: true,
  keyboard: true,
  focus: true,
  show: true
};
var DefaultType = {
  backdrop: '(boolean|string)',
  keyboard: 'boolean',
  focus: 'boolean',
  show: 'boolean'
};
var EVENT_HIDE = "hide".concat(EVENT_KEY);
var EVENT_HIDE_PREVENTED = "hidePrevented".concat(EVENT_KEY);
var EVENT_HIDDEN = "hidden".concat(EVENT_KEY);
var EVENT_SHOW = "show".concat(EVENT_KEY);
var EVENT_SHOWN = "shown".concat(EVENT_KEY);
var EVENT_FOCUSIN = "focusin".concat(EVENT_KEY);
var EVENT_RESIZE = "resize".concat(EVENT_KEY);
var EVENT_CLICK_DISMISS = "click.dismiss".concat(EVENT_KEY);
var EVENT_KEYDOWN_DISMISS = "keydown.dismiss".concat(EVENT_KEY);
var EVENT_MOUSEUP_DISMISS = "mouseup.dismiss".concat(EVENT_KEY);
var EVENT_MOUSEDOWN_DISMISS = "mousedown.dismiss".concat(EVENT_KEY);
var EVENT_CLICK_DATA_API = "click".concat(EVENT_KEY).concat(DATA_API_KEY);
var CLASS_NAME_SCROLLABLE = 'modal-dialog-scrollable';
var CLASS_NAME_SCROLLBAR_MEASURER = 'modal-scrollbar-measure';
var CLASS_NAME_BACKDROP = 'modal-backdrop';
var CLASS_NAME_OPEN = 'modal-open';
var CLASS_NAME_FADE = 'fade';
var CLASS_NAME_SHOW = 'show';
var CLASS_NAME_STATIC = 'modal-static';
var SELECTOR_DIALOG = '.modal-dialog';
var SELECTOR_MODAL_BODY = '.modal-body';
var SELECTOR_DATA_TOGGLE = '[data-toggle="modal"]';
var SELECTOR_DATA_DISMISS = '[data-dismiss="modal"]';
var SELECTOR_FIXED_CONTENT = '.fixed-top, .fixed-bottom, .is-fixed, .sticky-top';
var SELECTOR_STICKY_CONTENT = '.sticky-top';
/**
 * ------------------------------------------------------------------------
 * Class Definition
 * ------------------------------------------------------------------------
 */

var Modal = /*#__PURE__*/function () {
  function Modal(element, config) {
    _classCallCheck(this, Modal);

    this._config = this._getConfig(config);
    this._element = element;
    this._dialog = element.querySelector(SELECTOR_DIALOG);
    this._backdrop = null;
    this._isShown = false;
    this._isBodyOverflowing = false;
    this._ignoreBackdropClick = false;
    this._isTransitioning = false;
    this._scrollbarWidth = 0;
  } // Getters


  _createClass(Modal, [{
    key: "toggle",
    // Public
    value: function toggle(relatedTarget) {
      return this._isShown ? this.hide() : this.show(relatedTarget);
    }
  }, {
    key: "show",
    value: function show(relatedTarget) {
      var _this = this;

      if (this._isShown || this._isTransitioning) {
        return;
      }

      if (jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).hasClass(CLASS_NAME_FADE)) {
        this._isTransitioning = true;
      }

      var showEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_SHOW, {
        relatedTarget: relatedTarget
      });
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).trigger(showEvent);

      if (this._isShown || showEvent.isDefaultPrevented()) {
        return;
      }

      this._isShown = true;

      this._checkScrollbar();

      this._setScrollbar();

      this._adjustDialog();

      this._setEscapeEvent();

      this._setResizeEvent();

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).on(EVENT_CLICK_DISMISS, SELECTOR_DATA_DISMISS, function (event) {
        return _this.hide(event);
      });
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._dialog).on(EVENT_MOUSEDOWN_DISMISS, function () {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(_this._element).one(EVENT_MOUSEUP_DISMISS, function (event) {
          if (jquery__WEBPACK_IMPORTED_MODULE_0___default()(event.target).is(_this._element)) {
            _this._ignoreBackdropClick = true;
          }
        });
      });

      this._showBackdrop(function () {
        return _this._showElement(relatedTarget);
      });
    }
  }, {
    key: "hide",
    value: function hide(event) {
      var _this2 = this;

      if (event) {
        event.preventDefault();
      }

      if (!this._isShown || this._isTransitioning) {
        return;
      }

      var hideEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_HIDE);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).trigger(hideEvent);

      if (!this._isShown || hideEvent.isDefaultPrevented()) {
        return;
      }

      this._isShown = false;
      var transition = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).hasClass(CLASS_NAME_FADE);

      if (transition) {
        this._isTransitioning = true;
      }

      this._setEscapeEvent();

      this._setResizeEvent();

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).off(EVENT_FOCUSIN);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).removeClass(CLASS_NAME_SHOW);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).off(EVENT_CLICK_DISMISS);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._dialog).off(EVENT_MOUSEDOWN_DISMISS);

      if (transition) {
        var transitionDuration = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getTransitionDurationFromElement(this._element);
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).one(_util__WEBPACK_IMPORTED_MODULE_1__["default"].TRANSITION_END, function (event) {
          return _this2._hideModal(event);
        }).emulateTransitionEnd(transitionDuration);
      } else {
        this._hideModal();
      }
    }
  }, {
    key: "dispose",
    value: function dispose() {
      [window, this._element, this._dialog].forEach(function (htmlElement) {
        return jquery__WEBPACK_IMPORTED_MODULE_0___default()(htmlElement).off(EVENT_KEY);
      });
      /**
       * `document` has 2 events `EVENT_FOCUSIN` and `EVENT_CLICK_DATA_API`
       * Do not move `document` in `htmlElements` array
       * It will remove `EVENT_CLICK_DATA_API` event that should remain
       */

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).off(EVENT_FOCUSIN);
      jquery__WEBPACK_IMPORTED_MODULE_0___default.a.removeData(this._element, DATA_KEY);
      this._config = null;
      this._element = null;
      this._dialog = null;
      this._backdrop = null;
      this._isShown = null;
      this._isBodyOverflowing = null;
      this._ignoreBackdropClick = null;
      this._isTransitioning = null;
      this._scrollbarWidth = null;
    }
  }, {
    key: "handleUpdate",
    value: function handleUpdate() {
      this._adjustDialog();
    } // Private

  }, {
    key: "_getConfig",
    value: function _getConfig(config) {
      config = _objectSpread(_objectSpread({}, Default), config);
      _util__WEBPACK_IMPORTED_MODULE_1__["default"].typeCheckConfig(NAME, config, DefaultType);
      return config;
    }
  }, {
    key: "_triggerBackdropTransition",
    value: function _triggerBackdropTransition() {
      var _this3 = this;

      if (this._config.backdrop === 'static') {
        var hideEventPrevented = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_HIDE_PREVENTED);
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).trigger(hideEventPrevented);

        if (hideEventPrevented.defaultPrevented) {
          return;
        }

        this._element.classList.add(CLASS_NAME_STATIC);

        var modalTransitionDuration = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getTransitionDurationFromElement(this._element);
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).one(_util__WEBPACK_IMPORTED_MODULE_1__["default"].TRANSITION_END, function () {
          _this3._element.classList.remove(CLASS_NAME_STATIC);
        }).emulateTransitionEnd(modalTransitionDuration);

        this._element.focus();
      } else {
        this.hide();
      }
    }
  }, {
    key: "_showElement",
    value: function _showElement(relatedTarget) {
      var _this4 = this;

      var transition = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).hasClass(CLASS_NAME_FADE);
      var modalBody = this._dialog ? this._dialog.querySelector(SELECTOR_MODAL_BODY) : null;

      if (!this._element.parentNode || this._element.parentNode.nodeType !== Node.ELEMENT_NODE) {
        // Don't move modal's DOM position
        document.body.appendChild(this._element);
      }

      this._element.style.display = 'block';

      this._element.removeAttribute('aria-hidden');

      this._element.setAttribute('aria-modal', true);

      if (jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._dialog).hasClass(CLASS_NAME_SCROLLABLE) && modalBody) {
        modalBody.scrollTop = 0;
      } else {
        this._element.scrollTop = 0;
      }

      if (transition) {
        _util__WEBPACK_IMPORTED_MODULE_1__["default"].reflow(this._element);
      }

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).addClass(CLASS_NAME_SHOW);

      if (this._config.focus) {
        this._enforceFocus();
      }

      var shownEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_SHOWN, {
        relatedTarget: relatedTarget
      });

      var transitionComplete = function transitionComplete() {
        if (_this4._config.focus) {
          _this4._element.focus();
        }

        _this4._isTransitioning = false;
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(_this4._element).trigger(shownEvent);
      };

      if (transition) {
        var transitionDuration = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getTransitionDurationFromElement(this._dialog);
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._dialog).one(_util__WEBPACK_IMPORTED_MODULE_1__["default"].TRANSITION_END, transitionComplete).emulateTransitionEnd(transitionDuration);
      } else {
        transitionComplete();
      }
    }
  }, {
    key: "_enforceFocus",
    value: function _enforceFocus() {
      var _this5 = this;

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).off(EVENT_FOCUSIN) // Guard against infinite focus loop
      .on(EVENT_FOCUSIN, function (event) {
        if (document !== event.target && _this5._element !== event.target && jquery__WEBPACK_IMPORTED_MODULE_0___default()(_this5._element).has(event.target).length === 0) {
          _this5._element.focus();
        }
      });
    }
  }, {
    key: "_setEscapeEvent",
    value: function _setEscapeEvent() {
      var _this6 = this;

      if (this._isShown) {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).on(EVENT_KEYDOWN_DISMISS, function (event) {
          if (_this6._config.keyboard && event.which === ESCAPE_KEYCODE) {
            event.preventDefault();

            _this6.hide();
          } else if (!_this6._config.keyboard && event.which === ESCAPE_KEYCODE) {
            _this6._triggerBackdropTransition();
          }
        });
      } else if (!this._isShown) {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).off(EVENT_KEYDOWN_DISMISS);
      }
    }
  }, {
    key: "_setResizeEvent",
    value: function _setResizeEvent() {
      var _this7 = this;

      if (this._isShown) {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(window).on(EVENT_RESIZE, function (event) {
          return _this7.handleUpdate(event);
        });
      } else {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(window).off(EVENT_RESIZE);
      }
    }
  }, {
    key: "_hideModal",
    value: function _hideModal() {
      var _this8 = this;

      this._element.style.display = 'none';

      this._element.setAttribute('aria-hidden', true);

      this._element.removeAttribute('aria-modal');

      this._isTransitioning = false;

      this._showBackdrop(function () {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(document.body).removeClass(CLASS_NAME_OPEN);

        _this8._resetAdjustments();

        _this8._resetScrollbar();

        jquery__WEBPACK_IMPORTED_MODULE_0___default()(_this8._element).trigger(EVENT_HIDDEN);
      });
    }
  }, {
    key: "_removeBackdrop",
    value: function _removeBackdrop() {
      if (this._backdrop) {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._backdrop).remove();
        this._backdrop = null;
      }
    }
  }, {
    key: "_showBackdrop",
    value: function _showBackdrop(callback) {
      var _this9 = this;

      var animate = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).hasClass(CLASS_NAME_FADE) ? CLASS_NAME_FADE : '';

      if (this._isShown && this._config.backdrop) {
        this._backdrop = document.createElement('div');
        this._backdrop.className = CLASS_NAME_BACKDROP;

        if (animate) {
          this._backdrop.classList.add(animate);
        }

        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._backdrop).appendTo(document.body);
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).on(EVENT_CLICK_DISMISS, function (event) {
          if (_this9._ignoreBackdropClick) {
            _this9._ignoreBackdropClick = false;
            return;
          }

          if (event.target !== event.currentTarget) {
            return;
          }

          _this9._triggerBackdropTransition();
        });

        if (animate) {
          _util__WEBPACK_IMPORTED_MODULE_1__["default"].reflow(this._backdrop);
        }

        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._backdrop).addClass(CLASS_NAME_SHOW);

        if (!callback) {
          return;
        }

        if (!animate) {
          callback();
          return;
        }

        var backdropTransitionDuration = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getTransitionDurationFromElement(this._backdrop);
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._backdrop).one(_util__WEBPACK_IMPORTED_MODULE_1__["default"].TRANSITION_END, callback).emulateTransitionEnd(backdropTransitionDuration);
      } else if (!this._isShown && this._backdrop) {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._backdrop).removeClass(CLASS_NAME_SHOW);

        var callbackRemove = function callbackRemove() {
          _this9._removeBackdrop();

          if (callback) {
            callback();
          }
        };

        if (jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).hasClass(CLASS_NAME_FADE)) {
          var _backdropTransitionDuration = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getTransitionDurationFromElement(this._backdrop);

          jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._backdrop).one(_util__WEBPACK_IMPORTED_MODULE_1__["default"].TRANSITION_END, callbackRemove).emulateTransitionEnd(_backdropTransitionDuration);
        } else {
          callbackRemove();
        }
      } else if (callback) {
        callback();
      }
    } // ----------------------------------------------------------------------
    // the following methods are used to handle overflowing modals
    // todo (fat): these should probably be refactored out of modal.js
    // ----------------------------------------------------------------------

  }, {
    key: "_adjustDialog",
    value: function _adjustDialog() {
      var isModalOverflowing = this._element.scrollHeight > document.documentElement.clientHeight;

      if (!this._isBodyOverflowing && isModalOverflowing) {
        this._element.style.paddingLeft = "".concat(this._scrollbarWidth, "px");
      }

      if (this._isBodyOverflowing && !isModalOverflowing) {
        this._element.style.paddingRight = "".concat(this._scrollbarWidth, "px");
      }
    }
  }, {
    key: "_resetAdjustments",
    value: function _resetAdjustments() {
      this._element.style.paddingLeft = '';
      this._element.style.paddingRight = '';
    }
  }, {
    key: "_checkScrollbar",
    value: function _checkScrollbar() {
      var rect = document.body.getBoundingClientRect();
      this._isBodyOverflowing = Math.round(rect.left + rect.right) < window.innerWidth;
      this._scrollbarWidth = this._getScrollbarWidth();
    }
  }, {
    key: "_setScrollbar",
    value: function _setScrollbar() {
      var _this10 = this;

      if (this._isBodyOverflowing) {
        // Note: DOMNode.style.paddingRight returns the actual value or '' if not set
        //   while $(DOMNode).css('padding-right') returns the calculated value or 0 if not set
        var fixedContent = [].slice.call(document.querySelectorAll(SELECTOR_FIXED_CONTENT));
        var stickyContent = [].slice.call(document.querySelectorAll(SELECTOR_STICKY_CONTENT)); // Adjust fixed content padding

        jquery__WEBPACK_IMPORTED_MODULE_0___default()(fixedContent).each(function (index, element) {
          var actualPadding = element.style.paddingRight;
          var calculatedPadding = jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).css('padding-right');
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).data('padding-right', actualPadding).css('padding-right', "".concat(parseFloat(calculatedPadding) + _this10._scrollbarWidth, "px"));
        }); // Adjust sticky content margin

        jquery__WEBPACK_IMPORTED_MODULE_0___default()(stickyContent).each(function (index, element) {
          var actualMargin = element.style.marginRight;
          var calculatedMargin = jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).css('margin-right');
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).data('margin-right', actualMargin).css('margin-right', "".concat(parseFloat(calculatedMargin) - _this10._scrollbarWidth, "px"));
        }); // Adjust body padding

        var actualPadding = document.body.style.paddingRight;
        var calculatedPadding = jquery__WEBPACK_IMPORTED_MODULE_0___default()(document.body).css('padding-right');
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(document.body).data('padding-right', actualPadding).css('padding-right', "".concat(parseFloat(calculatedPadding) + this._scrollbarWidth, "px"));
      }

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(document.body).addClass(CLASS_NAME_OPEN);
    }
  }, {
    key: "_resetScrollbar",
    value: function _resetScrollbar() {
      // Restore fixed content padding
      var fixedContent = [].slice.call(document.querySelectorAll(SELECTOR_FIXED_CONTENT));
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(fixedContent).each(function (index, element) {
        var padding = jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).data('padding-right');
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).removeData('padding-right');
        element.style.paddingRight = padding ? padding : '';
      }); // Restore sticky content

      var elements = [].slice.call(document.querySelectorAll("".concat(SELECTOR_STICKY_CONTENT)));
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(elements).each(function (index, element) {
        var margin = jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).data('margin-right');

        if (typeof margin !== 'undefined') {
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).css('margin-right', margin).removeData('margin-right');
        }
      }); // Restore body padding

      var padding = jquery__WEBPACK_IMPORTED_MODULE_0___default()(document.body).data('padding-right');
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(document.body).removeData('padding-right');
      document.body.style.paddingRight = padding ? padding : '';
    }
  }, {
    key: "_getScrollbarWidth",
    value: function _getScrollbarWidth() {
      // thx d.walsh
      var scrollDiv = document.createElement('div');
      scrollDiv.className = CLASS_NAME_SCROLLBAR_MEASURER;
      document.body.appendChild(scrollDiv);
      var scrollbarWidth = scrollDiv.getBoundingClientRect().width - scrollDiv.clientWidth;
      document.body.removeChild(scrollDiv);
      return scrollbarWidth;
    } // Static

  }], [{
    key: "_jQueryInterface",
    value: function _jQueryInterface(config, relatedTarget) {
      return this.each(function () {
        var data = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data(DATA_KEY);

        var _config = _objectSpread(_objectSpread(_objectSpread({}, Default), jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data()), _typeof(config) === 'object' && config ? config : {});

        if (!data) {
          data = new Modal(this, _config);
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data(DATA_KEY, data);
        }

        if (typeof config === 'string') {
          if (typeof data[config] === 'undefined') {
            throw new TypeError("No method named \"".concat(config, "\""));
          }

          data[config](relatedTarget);
        } else if (_config.show) {
          data.show(relatedTarget);
        }
      });
    }
  }, {
    key: "VERSION",
    get: function get() {
      return VERSION;
    }
  }, {
    key: "Default",
    get: function get() {
      return Default;
    }
  }]);

  return Modal;
}();
/**
 * ------------------------------------------------------------------------
 * Data Api implementation
 * ------------------------------------------------------------------------
 */


jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).on(EVENT_CLICK_DATA_API, SELECTOR_DATA_TOGGLE, function (event) {
  var _this11 = this;

  var target;
  var selector = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getSelectorFromElement(this);

  if (selector) {
    target = document.querySelector(selector);
  }

  var config = jquery__WEBPACK_IMPORTED_MODULE_0___default()(target).data(DATA_KEY) ? 'toggle' : _objectSpread(_objectSpread({}, jquery__WEBPACK_IMPORTED_MODULE_0___default()(target).data()), jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data());

  if (this.tagName === 'A' || this.tagName === 'AREA') {
    event.preventDefault();
  }

  var $target = jquery__WEBPACK_IMPORTED_MODULE_0___default()(target).one(EVENT_SHOW, function (showEvent) {
    if (showEvent.isDefaultPrevented()) {
      // Only register focus restorer if modal will actually get shown
      return;
    }

    $target.one(EVENT_HIDDEN, function () {
      if (jquery__WEBPACK_IMPORTED_MODULE_0___default()(_this11).is(':visible')) {
        _this11.focus();
      }
    });
  });

  Modal._jQueryInterface.call(jquery__WEBPACK_IMPORTED_MODULE_0___default()(target), config, this);
});
/**
 * ------------------------------------------------------------------------
 * jQuery
 * ------------------------------------------------------------------------
 */

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = Modal._jQueryInterface;
jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].Constructor = Modal;

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].noConflict = function () {
  jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = JQUERY_NO_CONFLICT;
  return Modal._jQueryInterface;
};

/* harmony default export */ __webpack_exports__["default"] = (Modal);

/***/ }),

/***/ "./node_modules/bootstrap/js/src/popover.js":
/*!**************************************************!*\
  !*** ./node_modules/bootstrap/js/src/popover.js ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _tooltip__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./tooltip */ "./node_modules/bootstrap/js/src/tooltip.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/**
 * --------------------------------------------------------------------------
 * Bootstrap (v4.5.0): popover.js
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * --------------------------------------------------------------------------
 */


/**
 * ------------------------------------------------------------------------
 * Constants
 * ------------------------------------------------------------------------
 */

var NAME = 'popover';
var VERSION = '4.5.0';
var DATA_KEY = 'bs.popover';
var EVENT_KEY = ".".concat(DATA_KEY);
var JQUERY_NO_CONFLICT = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME];
var CLASS_PREFIX = 'bs-popover';
var BSCLS_PREFIX_REGEX = new RegExp("(^|\\s)".concat(CLASS_PREFIX, "\\S+"), 'g');

var Default = _objectSpread(_objectSpread({}, _tooltip__WEBPACK_IMPORTED_MODULE_1__["default"].Default), {}, {
  placement: 'right',
  trigger: 'click',
  content: '',
  template: '<div class="popover" role="tooltip">' + '<div class="arrow"></div>' + '<h3 class="popover-header"></h3>' + '<div class="popover-body"></div></div>'
});

var DefaultType = _objectSpread(_objectSpread({}, _tooltip__WEBPACK_IMPORTED_MODULE_1__["default"].DefaultType), {}, {
  content: '(string|element|function)'
});

var CLASS_NAME_FADE = 'fade';
var CLASS_NAME_SHOW = 'show';
var SELECTOR_TITLE = '.popover-header';
var SELECTOR_CONTENT = '.popover-body';
var Event = {
  HIDE: "hide".concat(EVENT_KEY),
  HIDDEN: "hidden".concat(EVENT_KEY),
  SHOW: "show".concat(EVENT_KEY),
  SHOWN: "shown".concat(EVENT_KEY),
  INSERTED: "inserted".concat(EVENT_KEY),
  CLICK: "click".concat(EVENT_KEY),
  FOCUSIN: "focusin".concat(EVENT_KEY),
  FOCUSOUT: "focusout".concat(EVENT_KEY),
  MOUSEENTER: "mouseenter".concat(EVENT_KEY),
  MOUSELEAVE: "mouseleave".concat(EVENT_KEY)
};
/**
 * ------------------------------------------------------------------------
 * Class Definition
 * ------------------------------------------------------------------------
 */

var Popover = /*#__PURE__*/function (_Tooltip) {
  _inherits(Popover, _Tooltip);

  var _super = _createSuper(Popover);

  function Popover() {
    _classCallCheck(this, Popover);

    return _super.apply(this, arguments);
  }

  _createClass(Popover, [{
    key: "isWithContent",
    // Overrides
    value: function isWithContent() {
      return this.getTitle() || this._getContent();
    }
  }, {
    key: "addAttachmentClass",
    value: function addAttachmentClass(attachment) {
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this.getTipElement()).addClass("".concat(CLASS_PREFIX, "-").concat(attachment));
    }
  }, {
    key: "getTipElement",
    value: function getTipElement() {
      this.tip = this.tip || jquery__WEBPACK_IMPORTED_MODULE_0___default()(this.config.template)[0];
      return this.tip;
    }
  }, {
    key: "setContent",
    value: function setContent() {
      var $tip = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this.getTipElement()); // We use append for html objects to maintain js events

      this.setElementContent($tip.find(SELECTOR_TITLE), this.getTitle());

      var content = this._getContent();

      if (typeof content === 'function') {
        content = content.call(this.element);
      }

      this.setElementContent($tip.find(SELECTOR_CONTENT), content);
      $tip.removeClass("".concat(CLASS_NAME_FADE, " ").concat(CLASS_NAME_SHOW));
    } // Private

  }, {
    key: "_getContent",
    value: function _getContent() {
      return this.element.getAttribute('data-content') || this.config.content;
    }
  }, {
    key: "_cleanTipClass",
    value: function _cleanTipClass() {
      var $tip = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this.getTipElement());
      var tabClass = $tip.attr('class').match(BSCLS_PREFIX_REGEX);

      if (tabClass !== null && tabClass.length > 0) {
        $tip.removeClass(tabClass.join(''));
      }
    } // Static

  }], [{
    key: "_jQueryInterface",
    value: function _jQueryInterface(config) {
      return this.each(function () {
        var data = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data(DATA_KEY);

        var _config = _typeof(config) === 'object' ? config : null;

        if (!data && /dispose|hide/.test(config)) {
          return;
        }

        if (!data) {
          data = new Popover(this, _config);
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data(DATA_KEY, data);
        }

        if (typeof config === 'string') {
          if (typeof data[config] === 'undefined') {
            throw new TypeError("No method named \"".concat(config, "\""));
          }

          data[config]();
        }
      });
    }
  }, {
    key: "VERSION",
    // Getters
    get: function get() {
      return VERSION;
    }
  }, {
    key: "Default",
    get: function get() {
      return Default;
    }
  }, {
    key: "NAME",
    get: function get() {
      return NAME;
    }
  }, {
    key: "DATA_KEY",
    get: function get() {
      return DATA_KEY;
    }
  }, {
    key: "Event",
    get: function get() {
      return Event;
    }
  }, {
    key: "EVENT_KEY",
    get: function get() {
      return EVENT_KEY;
    }
  }, {
    key: "DefaultType",
    get: function get() {
      return DefaultType;
    }
  }]);

  return Popover;
}(_tooltip__WEBPACK_IMPORTED_MODULE_1__["default"]);
/**
 * ------------------------------------------------------------------------
 * jQuery
 * ------------------------------------------------------------------------
 */


jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = Popover._jQueryInterface;
jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].Constructor = Popover;

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].noConflict = function () {
  jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = JQUERY_NO_CONFLICT;
  return Popover._jQueryInterface;
};

/* harmony default export */ __webpack_exports__["default"] = (Popover);

/***/ }),

/***/ "./node_modules/bootstrap/js/src/scrollspy.js":
/*!****************************************************!*\
  !*** ./node_modules/bootstrap/js/src/scrollspy.js ***!
  \****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _util__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./util */ "./node_modules/bootstrap/js/src/util.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

/**
 * --------------------------------------------------------------------------
 * Bootstrap (v4.5.0): scrollspy.js
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * --------------------------------------------------------------------------
 */


/**
 * ------------------------------------------------------------------------
 * Constants
 * ------------------------------------------------------------------------
 */

var NAME = 'scrollspy';
var VERSION = '4.5.0';
var DATA_KEY = 'bs.scrollspy';
var EVENT_KEY = ".".concat(DATA_KEY);
var DATA_API_KEY = '.data-api';
var JQUERY_NO_CONFLICT = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME];
var Default = {
  offset: 10,
  method: 'auto',
  target: ''
};
var DefaultType = {
  offset: 'number',
  method: 'string',
  target: '(string|element)'
};
var EVENT_ACTIVATE = "activate".concat(EVENT_KEY);
var EVENT_SCROLL = "scroll".concat(EVENT_KEY);
var EVENT_LOAD_DATA_API = "load".concat(EVENT_KEY).concat(DATA_API_KEY);
var CLASS_NAME_DROPDOWN_ITEM = 'dropdown-item';
var CLASS_NAME_ACTIVE = 'active';
var SELECTOR_DATA_SPY = '[data-spy="scroll"]';
var SELECTOR_NAV_LIST_GROUP = '.nav, .list-group';
var SELECTOR_NAV_LINKS = '.nav-link';
var SELECTOR_NAV_ITEMS = '.nav-item';
var SELECTOR_LIST_ITEMS = '.list-group-item';
var SELECTOR_DROPDOWN = '.dropdown';
var SELECTOR_DROPDOWN_ITEMS = '.dropdown-item';
var SELECTOR_DROPDOWN_TOGGLE = '.dropdown-toggle';
var METHOD_OFFSET = 'offset';
var METHOD_POSITION = 'position';
/**
 * ------------------------------------------------------------------------
 * Class Definition
 * ------------------------------------------------------------------------
 */

var ScrollSpy = /*#__PURE__*/function () {
  function ScrollSpy(element, config) {
    var _this = this;

    _classCallCheck(this, ScrollSpy);

    this._element = element;
    this._scrollElement = element.tagName === 'BODY' ? window : element;
    this._config = this._getConfig(config);
    this._selector = "".concat(this._config.target, " ").concat(SELECTOR_NAV_LINKS, ",") + "".concat(this._config.target, " ").concat(SELECTOR_LIST_ITEMS, ",") + "".concat(this._config.target, " ").concat(SELECTOR_DROPDOWN_ITEMS);
    this._offsets = [];
    this._targets = [];
    this._activeTarget = null;
    this._scrollHeight = 0;
    jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._scrollElement).on(EVENT_SCROLL, function (event) {
      return _this._process(event);
    });
    this.refresh();

    this._process();
  } // Getters


  _createClass(ScrollSpy, [{
    key: "refresh",
    // Public
    value: function refresh() {
      var _this2 = this;

      var autoMethod = this._scrollElement === this._scrollElement.window ? METHOD_OFFSET : METHOD_POSITION;
      var offsetMethod = this._config.method === 'auto' ? autoMethod : this._config.method;
      var offsetBase = offsetMethod === METHOD_POSITION ? this._getScrollTop() : 0;
      this._offsets = [];
      this._targets = [];
      this._scrollHeight = this._getScrollHeight();
      var targets = [].slice.call(document.querySelectorAll(this._selector));
      targets.map(function (element) {
        var target;
        var targetSelector = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getSelectorFromElement(element);

        if (targetSelector) {
          target = document.querySelector(targetSelector);
        }

        if (target) {
          var targetBCR = target.getBoundingClientRect();

          if (targetBCR.width || targetBCR.height) {
            // TODO (fat): remove sketch reliance on jQuery position/offset
            return [jquery__WEBPACK_IMPORTED_MODULE_0___default()(target)[offsetMethod]().top + offsetBase, targetSelector];
          }
        }

        return null;
      }).filter(function (item) {
        return item;
      }).sort(function (a, b) {
        return a[0] - b[0];
      }).forEach(function (item) {
        _this2._offsets.push(item[0]);

        _this2._targets.push(item[1]);
      });
    }
  }, {
    key: "dispose",
    value: function dispose() {
      jquery__WEBPACK_IMPORTED_MODULE_0___default.a.removeData(this._element, DATA_KEY);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._scrollElement).off(EVENT_KEY);
      this._element = null;
      this._scrollElement = null;
      this._config = null;
      this._selector = null;
      this._offsets = null;
      this._targets = null;
      this._activeTarget = null;
      this._scrollHeight = null;
    } // Private

  }, {
    key: "_getConfig",
    value: function _getConfig(config) {
      config = _objectSpread(_objectSpread({}, Default), _typeof(config) === 'object' && config ? config : {});

      if (typeof config.target !== 'string' && _util__WEBPACK_IMPORTED_MODULE_1__["default"].isElement(config.target)) {
        var id = jquery__WEBPACK_IMPORTED_MODULE_0___default()(config.target).attr('id');

        if (!id) {
          id = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getUID(NAME);
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(config.target).attr('id', id);
        }

        config.target = "#".concat(id);
      }

      _util__WEBPACK_IMPORTED_MODULE_1__["default"].typeCheckConfig(NAME, config, DefaultType);
      return config;
    }
  }, {
    key: "_getScrollTop",
    value: function _getScrollTop() {
      return this._scrollElement === window ? this._scrollElement.pageYOffset : this._scrollElement.scrollTop;
    }
  }, {
    key: "_getScrollHeight",
    value: function _getScrollHeight() {
      return this._scrollElement.scrollHeight || Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
    }
  }, {
    key: "_getOffsetHeight",
    value: function _getOffsetHeight() {
      return this._scrollElement === window ? window.innerHeight : this._scrollElement.getBoundingClientRect().height;
    }
  }, {
    key: "_process",
    value: function _process() {
      var scrollTop = this._getScrollTop() + this._config.offset;

      var scrollHeight = this._getScrollHeight();

      var maxScroll = this._config.offset + scrollHeight - this._getOffsetHeight();

      if (this._scrollHeight !== scrollHeight) {
        this.refresh();
      }

      if (scrollTop >= maxScroll) {
        var target = this._targets[this._targets.length - 1];

        if (this._activeTarget !== target) {
          this._activate(target);
        }

        return;
      }

      if (this._activeTarget && scrollTop < this._offsets[0] && this._offsets[0] > 0) {
        this._activeTarget = null;

        this._clear();

        return;
      }

      for (var i = this._offsets.length; i--;) {
        var isActiveTarget = this._activeTarget !== this._targets[i] && scrollTop >= this._offsets[i] && (typeof this._offsets[i + 1] === 'undefined' || scrollTop < this._offsets[i + 1]);

        if (isActiveTarget) {
          this._activate(this._targets[i]);
        }
      }
    }
  }, {
    key: "_activate",
    value: function _activate(target) {
      this._activeTarget = target;

      this._clear();

      var queries = this._selector.split(',').map(function (selector) {
        return "".concat(selector, "[data-target=\"").concat(target, "\"],").concat(selector, "[href=\"").concat(target, "\"]");
      });

      var $link = jquery__WEBPACK_IMPORTED_MODULE_0___default()([].slice.call(document.querySelectorAll(queries.join(','))));

      if ($link.hasClass(CLASS_NAME_DROPDOWN_ITEM)) {
        $link.closest(SELECTOR_DROPDOWN).find(SELECTOR_DROPDOWN_TOGGLE).addClass(CLASS_NAME_ACTIVE);
        $link.addClass(CLASS_NAME_ACTIVE);
      } else {
        // Set triggered link as active
        $link.addClass(CLASS_NAME_ACTIVE); // Set triggered links parents as active
        // With both <ul> and <nav> markup a parent is the previous sibling of any nav ancestor

        $link.parents(SELECTOR_NAV_LIST_GROUP).prev("".concat(SELECTOR_NAV_LINKS, ", ").concat(SELECTOR_LIST_ITEMS)).addClass(CLASS_NAME_ACTIVE); // Handle special case when .nav-link is inside .nav-item

        $link.parents(SELECTOR_NAV_LIST_GROUP).prev(SELECTOR_NAV_ITEMS).children(SELECTOR_NAV_LINKS).addClass(CLASS_NAME_ACTIVE);
      }

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._scrollElement).trigger(EVENT_ACTIVATE, {
        relatedTarget: target
      });
    }
  }, {
    key: "_clear",
    value: function _clear() {
      [].slice.call(document.querySelectorAll(this._selector)).filter(function (node) {
        return node.classList.contains(CLASS_NAME_ACTIVE);
      }).forEach(function (node) {
        return node.classList.remove(CLASS_NAME_ACTIVE);
      });
    } // Static

  }], [{
    key: "_jQueryInterface",
    value: function _jQueryInterface(config) {
      return this.each(function () {
        var data = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data(DATA_KEY);

        var _config = _typeof(config) === 'object' && config;

        if (!data) {
          data = new ScrollSpy(this, _config);
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).data(DATA_KEY, data);
        }

        if (typeof config === 'string') {
          if (typeof data[config] === 'undefined') {
            throw new TypeError("No method named \"".concat(config, "\""));
          }

          data[config]();
        }
      });
    }
  }, {
    key: "VERSION",
    get: function get() {
      return VERSION;
    }
  }, {
    key: "Default",
    get: function get() {
      return Default;
    }
  }]);

  return ScrollSpy;
}();
/**
 * ------------------------------------------------------------------------
 * Data Api implementation
 * ------------------------------------------------------------------------
 */


jquery__WEBPACK_IMPORTED_MODULE_0___default()(window).on(EVENT_LOAD_DATA_API, function () {
  var scrollSpys = [].slice.call(document.querySelectorAll(SELECTOR_DATA_SPY));
  var scrollSpysLength = scrollSpys.length;

  for (var i = scrollSpysLength; i--;) {
    var $spy = jquery__WEBPACK_IMPORTED_MODULE_0___default()(scrollSpys[i]);

    ScrollSpy._jQueryInterface.call($spy, $spy.data());
  }
});
/**
 * ------------------------------------------------------------------------
 * jQuery
 * ------------------------------------------------------------------------
 */

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = ScrollSpy._jQueryInterface;
jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].Constructor = ScrollSpy;

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].noConflict = function () {
  jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = JQUERY_NO_CONFLICT;
  return ScrollSpy._jQueryInterface;
};

/* harmony default export */ __webpack_exports__["default"] = (ScrollSpy);

/***/ }),

/***/ "./node_modules/bootstrap/js/src/tab.js":
/*!**********************************************!*\
  !*** ./node_modules/bootstrap/js/src/tab.js ***!
  \**********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _util__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./util */ "./node_modules/bootstrap/js/src/util.js");
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

/**
 * --------------------------------------------------------------------------
 * Bootstrap (v4.5.0): tab.js
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * --------------------------------------------------------------------------
 */


/**
 * ------------------------------------------------------------------------
 * Constants
 * ------------------------------------------------------------------------
 */

var NAME = 'tab';
var VERSION = '4.5.0';
var DATA_KEY = 'bs.tab';
var EVENT_KEY = ".".concat(DATA_KEY);
var DATA_API_KEY = '.data-api';
var JQUERY_NO_CONFLICT = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME];
var EVENT_HIDE = "hide".concat(EVENT_KEY);
var EVENT_HIDDEN = "hidden".concat(EVENT_KEY);
var EVENT_SHOW = "show".concat(EVENT_KEY);
var EVENT_SHOWN = "shown".concat(EVENT_KEY);
var EVENT_CLICK_DATA_API = "click".concat(EVENT_KEY).concat(DATA_API_KEY);
var CLASS_NAME_DROPDOWN_MENU = 'dropdown-menu';
var CLASS_NAME_ACTIVE = 'active';
var CLASS_NAME_DISABLED = 'disabled';
var CLASS_NAME_FADE = 'fade';
var CLASS_NAME_SHOW = 'show';
var SELECTOR_DROPDOWN = '.dropdown';
var SELECTOR_NAV_LIST_GROUP = '.nav, .list-group';
var SELECTOR_ACTIVE = '.active';
var SELECTOR_ACTIVE_UL = '> li > .active';
var SELECTOR_DATA_TOGGLE = '[data-toggle="tab"], [data-toggle="pill"], [data-toggle="list"]';
var SELECTOR_DROPDOWN_TOGGLE = '.dropdown-toggle';
var SELECTOR_DROPDOWN_ACTIVE_CHILD = '> .dropdown-menu .active';
/**
 * ------------------------------------------------------------------------
 * Class Definition
 * ------------------------------------------------------------------------
 */

var Tab = /*#__PURE__*/function () {
  function Tab(element) {
    _classCallCheck(this, Tab);

    this._element = element;
  } // Getters


  _createClass(Tab, [{
    key: "show",
    // Public
    value: function show() {
      var _this = this;

      if (this._element.parentNode && this._element.parentNode.nodeType === Node.ELEMENT_NODE && jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).hasClass(CLASS_NAME_ACTIVE) || jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).hasClass(CLASS_NAME_DISABLED)) {
        return;
      }

      var target;
      var previous;
      var listElement = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).closest(SELECTOR_NAV_LIST_GROUP)[0];
      var selector = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getSelectorFromElement(this._element);

      if (listElement) {
        var itemSelector = listElement.nodeName === 'UL' || listElement.nodeName === 'OL' ? SELECTOR_ACTIVE_UL : SELECTOR_ACTIVE;
        previous = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.makeArray(jquery__WEBPACK_IMPORTED_MODULE_0___default()(listElement).find(itemSelector));
        previous = previous[previous.length - 1];
      }

      var hideEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_HIDE, {
        relatedTarget: this._element
      });
      var showEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_SHOW, {
        relatedTarget: previous
      });

      if (previous) {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(previous).trigger(hideEvent);
      }

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).trigger(showEvent);

      if (showEvent.isDefaultPrevented() || hideEvent.isDefaultPrevented()) {
        return;
      }

      if (selector) {
        target = document.querySelector(selector);
      }

      this._activate(this._element, listElement);

      var complete = function complete() {
        var hiddenEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_HIDDEN, {
          relatedTarget: _this._element
        });
        var shownEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_SHOWN, {
          relatedTarget: previous
        });
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(previous).trigger(hiddenEvent);
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(_this._element).trigger(shownEvent);
      };

      if (target) {
        this._activate(target, target.parentNode, complete);
      } else {
        complete();
      }
    }
  }, {
    key: "dispose",
    value: function dispose() {
      jquery__WEBPACK_IMPORTED_MODULE_0___default.a.removeData(this._element, DATA_KEY);
      this._element = null;
    } // Private

  }, {
    key: "_activate",
    value: function _activate(element, container, callback) {
      var _this2 = this;

      var activeElements = container && (container.nodeName === 'UL' || container.nodeName === 'OL') ? jquery__WEBPACK_IMPORTED_MODULE_0___default()(container).find(SELECTOR_ACTIVE_UL) : jquery__WEBPACK_IMPORTED_MODULE_0___default()(container).children(SELECTOR_ACTIVE);
      var active = activeElements[0];
      var isTransitioning = callback && active && jquery__WEBPACK_IMPORTED_MODULE_0___default()(active).hasClass(CLASS_NAME_FADE);

      var complete = function complete() {
        return _this2._transitionComplete(element, active, callback);
      };

      if (active && isTransitioning) {
        var transitionDuration = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getTransitionDurationFromElement(active);
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(active).removeClass(CLASS_NAME_SHOW).one(_util__WEBPACK_IMPORTED_MODULE_1__["default"].TRANSITION_END, complete).emulateTransitionEnd(transitionDuration);
      } else {
        complete();
      }
    }
  }, {
    key: "_transitionComplete",
    value: function _transitionComplete(element, active, callback) {
      if (active) {
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(active).removeClass(CLASS_NAME_ACTIVE);
        var dropdownChild = jquery__WEBPACK_IMPORTED_MODULE_0___default()(active.parentNode).find(SELECTOR_DROPDOWN_ACTIVE_CHILD)[0];

        if (dropdownChild) {
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(dropdownChild).removeClass(CLASS_NAME_ACTIVE);
        }

        if (active.getAttribute('role') === 'tab') {
          active.setAttribute('aria-selected', false);
        }
      }

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).addClass(CLASS_NAME_ACTIVE);

      if (element.getAttribute('role') === 'tab') {
        element.setAttribute('aria-selected', true);
      }

      _util__WEBPACK_IMPORTED_MODULE_1__["default"].reflow(element);

      if (element.classList.contains(CLASS_NAME_FADE)) {
        element.classList.add(CLASS_NAME_SHOW);
      }

      if (element.parentNode && jquery__WEBPACK_IMPORTED_MODULE_0___default()(element.parentNode).hasClass(CLASS_NAME_DROPDOWN_MENU)) {
        var dropdownElement = jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).closest(SELECTOR_DROPDOWN)[0];

        if (dropdownElement) {
          var dropdownToggleList = [].slice.call(dropdownElement.querySelectorAll(SELECTOR_DROPDOWN_TOGGLE));
          jquery__WEBPACK_IMPORTED_MODULE_0___default()(dropdownToggleList).addClass(CLASS_NAME_ACTIVE);
        }

        element.setAttribute('aria-expanded', true);
      }

      if (callback) {
        callback();
      }
    } // Static

  }], [{
    key: "_jQueryInterface",
    value: function _jQueryInterface(config) {
      return this.each(function () {
        var $this = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this);
        var data = $this.data(DATA_KEY);

        if (!data) {
          data = new Tab(this);
          $this.data(DATA_KEY, data);
        }

        if (typeof config === 'string') {
          if (typeof data[config] === 'undefined') {
            throw new TypeError("No method named \"".concat(config, "\""));
          }

          data[config]();
        }
      });
    }
  }, {
    key: "VERSION",
    get: function get() {
      return VERSION;
    }
  }]);

  return Tab;
}();
/**
 * ------------------------------------------------------------------------
 * Data Api implementation
 * ------------------------------------------------------------------------
 */


jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).on(EVENT_CLICK_DATA_API, SELECTOR_DATA_TOGGLE, function (event) {
  event.preventDefault();

  Tab._jQueryInterface.call(jquery__WEBPACK_IMPORTED_MODULE_0___default()(this), 'show');
});
/**
 * ------------------------------------------------------------------------
 * jQuery
 * ------------------------------------------------------------------------
 */

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = Tab._jQueryInterface;
jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].Constructor = Tab;

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].noConflict = function () {
  jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = JQUERY_NO_CONFLICT;
  return Tab._jQueryInterface;
};

/* harmony default export */ __webpack_exports__["default"] = (Tab);

/***/ }),

/***/ "./node_modules/bootstrap/js/src/toast.js":
/*!************************************************!*\
  !*** ./node_modules/bootstrap/js/src/toast.js ***!
  \************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _util__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./util */ "./node_modules/bootstrap/js/src/util.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

/**
 * --------------------------------------------------------------------------
 * Bootstrap (v4.5.0): toast.js
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * --------------------------------------------------------------------------
 */


/**
 * ------------------------------------------------------------------------
 * Constants
 * ------------------------------------------------------------------------
 */

var NAME = 'toast';
var VERSION = '4.5.0';
var DATA_KEY = 'bs.toast';
var EVENT_KEY = ".".concat(DATA_KEY);
var JQUERY_NO_CONFLICT = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME];
var EVENT_CLICK_DISMISS = "click.dismiss".concat(EVENT_KEY);
var EVENT_HIDE = "hide".concat(EVENT_KEY);
var EVENT_HIDDEN = "hidden".concat(EVENT_KEY);
var EVENT_SHOW = "show".concat(EVENT_KEY);
var EVENT_SHOWN = "shown".concat(EVENT_KEY);
var CLASS_NAME_FADE = 'fade';
var CLASS_NAME_HIDE = 'hide';
var CLASS_NAME_SHOW = 'show';
var CLASS_NAME_SHOWING = 'showing';
var DefaultType = {
  animation: 'boolean',
  autohide: 'boolean',
  delay: 'number'
};
var Default = {
  animation: true,
  autohide: true,
  delay: 500
};
var SELECTOR_DATA_DISMISS = '[data-dismiss="toast"]';
/**
 * ------------------------------------------------------------------------
 * Class Definition
 * ------------------------------------------------------------------------
 */

var Toast = /*#__PURE__*/function () {
  function Toast(element, config) {
    _classCallCheck(this, Toast);

    this._element = element;
    this._config = this._getConfig(config);
    this._timeout = null;

    this._setListeners();
  } // Getters


  _createClass(Toast, [{
    key: "show",
    // Public
    value: function show() {
      var _this = this;

      var showEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_SHOW);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).trigger(showEvent);

      if (showEvent.isDefaultPrevented()) {
        return;
      }

      if (this._config.animation) {
        this._element.classList.add(CLASS_NAME_FADE);
      }

      var complete = function complete() {
        _this._element.classList.remove(CLASS_NAME_SHOWING);

        _this._element.classList.add(CLASS_NAME_SHOW);

        jquery__WEBPACK_IMPORTED_MODULE_0___default()(_this._element).trigger(EVENT_SHOWN);

        if (_this._config.autohide) {
          _this._timeout = setTimeout(function () {
            _this.hide();
          }, _this._config.delay);
        }
      };

      this._element.classList.remove(CLASS_NAME_HIDE);

      _util__WEBPACK_IMPORTED_MODULE_1__["default"].reflow(this._element);

      this._element.classList.add(CLASS_NAME_SHOWING);

      if (this._config.animation) {
        var transitionDuration = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getTransitionDurationFromElement(this._element);
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).one(_util__WEBPACK_IMPORTED_MODULE_1__["default"].TRANSITION_END, complete).emulateTransitionEnd(transitionDuration);
      } else {
        complete();
      }
    }
  }, {
    key: "hide",
    value: function hide() {
      if (!this._element.classList.contains(CLASS_NAME_SHOW)) {
        return;
      }

      var hideEvent = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.Event(EVENT_HIDE);
      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).trigger(hideEvent);

      if (hideEvent.isDefaultPrevented()) {
        return;
      }

      this._close();
    }
  }, {
    key: "dispose",
    value: function dispose() {
      clearTimeout(this._timeout);
      this._timeout = null;

      if (this._element.classList.contains(CLASS_NAME_SHOW)) {
        this._element.classList.remove(CLASS_NAME_SHOW);
      }

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).off(EVENT_CLICK_DISMISS);
      jquery__WEBPACK_IMPORTED_MODULE_0___default.a.removeData(this._element, DATA_KEY);
      this._element = null;
      this._config = null;
    } // Private

  }, {
    key: "_getConfig",
    value: function _getConfig(config) {
      config = _objectSpread(_objectSpread(_objectSpread({}, Default), jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).data()), _typeof(config) === 'object' && config ? config : {});
      _util__WEBPACK_IMPORTED_MODULE_1__["default"].typeCheckConfig(NAME, config, this.constructor.DefaultType);
      return config;
    }
  }, {
    key: "_setListeners",
    value: function _setListeners() {
      var _this2 = this;

      jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).on(EVENT_CLICK_DISMISS, SELECTOR_DATA_DISMISS, function () {
        return _this2.hide();
      });
    }
  }, {
    key: "_close",
    value: function _close() {
      var _this3 = this;

      var complete = function complete() {
        _this3._element.classList.add(CLASS_NAME_HIDE);

        jquery__WEBPACK_IMPORTED_MODULE_0___default()(_this3._element).trigger(EVENT_HIDDEN);
      };

      this._element.classList.remove(CLASS_NAME_SHOW);

      if (this._config.animation) {
        var transitionDuration = _util__WEBPACK_IMPORTED_MODULE_1__["default"].getTransitionDurationFromElement(this._element);
        jquery__WEBPACK_IMPORTED_MODULE_0___default()(this._element).one(_util__WEBPACK_IMPORTED_MODULE_1__["default"].TRANSITION_END, complete).emulateTransitionEnd(transitionDuration);
      } else {
        complete();
      }
    } // Static

  }], [{
    key: "_jQueryInterface",
    value: function _jQueryInterface(config) {
      return this.each(function () {
        var $element = jquery__WEBPACK_IMPORTED_MODULE_0___default()(this);
        var data = $element.data(DATA_KEY);

        var _config = _typeof(config) === 'object' && config;

        if (!data) {
          data = new Toast(this, _config);
          $element.data(DATA_KEY, data);
        }

        if (typeof config === 'string') {
          if (typeof data[config] === 'undefined') {
            throw new TypeError("No method named \"".concat(config, "\""));
          }

          data[config](this);
        }
      });
    }
  }, {
    key: "VERSION",
    get: function get() {
      return VERSION;
    }
  }, {
    key: "DefaultType",
    get: function get() {
      return DefaultType;
    }
  }, {
    key: "Default",
    get: function get() {
      return Default;
    }
  }]);

  return Toast;
}();
/**
 * ------------------------------------------------------------------------
 * jQuery
 * ------------------------------------------------------------------------
 */


jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = Toast._jQueryInterface;
jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].Constructor = Toast;

jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME].noConflict = function () {
  jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn[NAME] = JQUERY_NO_CONFLICT;
  return Toast._jQueryInterface;
};

/* harmony default export */ __webpack_exports__["default"] = (Toast);

/***/ }),

/***/ "./node_modules/bootstrap/js/src/tools/sanitizer.js":
/*!**********************************************************!*\
  !*** ./node_modules/bootstrap/js/src/tools/sanitizer.js ***!
  \**********************************************************/
/*! exports provided: DefaultWhitelist, sanitizeHtml */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DefaultWhitelist", function() { return DefaultWhitelist; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "sanitizeHtml", function() { return sanitizeHtml; });
/**
 * --------------------------------------------------------------------------
 * Bootstrap (v4.5.0): tools/sanitizer.js
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * --------------------------------------------------------------------------
 */
var uriAttrs = ['background', 'cite', 'href', 'itemtype', 'longdesc', 'poster', 'src', 'xlink:href'];
var ARIA_ATTRIBUTE_PATTERN = /^aria-[\w-]*$/i;
var DefaultWhitelist = {
  // Global attributes allowed on any supplied element below.
  '*': ['class', 'dir', 'id', 'lang', 'role', ARIA_ATTRIBUTE_PATTERN],
  a: ['target', 'href', 'title', 'rel'],
  area: [],
  b: [],
  br: [],
  col: [],
  code: [],
  div: [],
  em: [],
  hr: [],
  h1: [],
  h2: [],
  h3: [],
  h4: [],
  h5: [],
  h6: [],
  i: [],
  img: ['src', 'srcset', 'alt', 'title', 'width', 'height'],
  li: [],
  ol: [],
  p: [],
  pre: [],
  s: [],
  small: [],
  span: [],
  sub: [],
  sup: [],
  strong: [],
  u: [],
  ul: []
};
/**
 * A pattern that recognizes a commonly useful subset of URLs that are safe.
 *
 * Shoutout to Angular 7 https://github.com/angular/angular/blob/7.2.4/packages/core/src/sanitization/url_sanitizer.ts
 */

var SAFE_URL_PATTERN = /^(?:(?:https?|mailto|ftp|tel|file):|[^#&/:?]*(?:[#/?]|$))/gi;
/**
 * A pattern that matches safe data URLs. Only matches image, video and audio types.
 *
 * Shoutout to Angular 7 https://github.com/angular/angular/blob/7.2.4/packages/core/src/sanitization/url_sanitizer.ts
 */

var DATA_URL_PATTERN = /^data:(?:image\/(?:bmp|gif|jpeg|jpg|png|tiff|webp)|video\/(?:mpeg|mp4|ogg|webm)|audio\/(?:mp3|oga|ogg|opus));base64,[\d+/a-z]+=*$/i;

function allowedAttribute(attr, allowedAttributeList) {
  var attrName = attr.nodeName.toLowerCase();

  if (allowedAttributeList.indexOf(attrName) !== -1) {
    if (uriAttrs.indexOf(attrName) !== -1) {
      return Boolean(attr.nodeValue.match(SAFE_URL_PATTERN) || attr.nodeValue.match(DATA_URL_PATTERN));
    }

    return true;
  }

  var regExp = allowedAttributeList.filter(function (attrRegex) {
    return attrRegex instanceof RegExp;
  }); // Check if a regular expression validates the attribute.

  for (var i = 0, len = regExp.length; i < len; i++) {
    if (attrName.match(regExp[i])) {
      return true;
    }
  }

  return false;
}

function sanitizeHtml(unsafeHtml, whiteList, sanitizeFn) {
  if (unsafeHtml.length === 0) {
    return unsafeHtml;
  }

  if (sanitizeFn && typeof sanitizeFn === 'function') {
    return sanitizeFn(unsafeHtml);
  }

  var domParser = new window.DOMParser();
  var createdDocument = domParser.parseFromString(unsafeHtml, 'text/html');
  var whitelistKeys = Object.keys(whiteList);
  var elements = [].slice.call(createdDocument.body.querySelectorAll('*'));

  var _loop = function _loop(i, len) {
    var el = elements[i];
    var elName = el.nodeName.toLowerCase();

    if (whitelistKeys.indexOf(el.nodeName.toLowerCase()) === -1) {
      el.parentNode.removeChild(el);
      return "continue";
    }

    var attributeList = [].slice.call(el.attributes);
    var whitelistedAttributes = [].concat(whiteList['*'] || [], whiteList[elName] || []);
    attributeList.forEach(function (attr) {
      if (!allowedAttribute(attr, whitelistedAttributes)) {
        el.removeAttribute(attr.nodeName);
      }
    });
  };

  for (var i = 0, len = elements.length; i < len; i++) {
    var _ret = _loop(i, len);

    if (_ret === "continue") continue;
  }

  return createdDocument.body.innerHTML;
}

/***/ }),

/***/ "./node_modules/bootstrap/js/src/tooltip.js":
/*!**************************************************!*\
  !*** ./node_modules/bootstrap/js/src/tooltip.js ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _tools_sanitizer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./tools/sanitizer */ "./node_modules/bootstrap/js/src/tools/sanitizer.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var popper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! popper.js */ "./node_modules/popper.js/dist/esm/popper.js");
/* harmony import */ var _util__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./util */ "./node_modules/bootstrap/js/src/util.js");
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

/**
 * --------------------------------------------------------------------------
 * Bootstrap (v4.5.0): tooltip.js
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * --------------------------------------------------------------------------
 */




/**
 * ------------------------------------------------------------------------
 * Constants
 * ------------------------------------------------------------------------
 */

var NAME = 'tooltip';
var VERSION = '4.5.0';
var DATA_KEY = 'bs.tooltip';
var EVENT_KEY = ".".concat(DATA_KEY);
var JQUERY_NO_CONFLICT = jquery__WEBPACK_IMPORTED_MODULE_1___default.a.fn[NAME];
var CLASS_PREFIX = 'bs-tooltip';
var BSCLS_PREFIX_REGEX = new RegExp("(^|\\s)".concat(CLASS_PREFIX, "\\S+"), 'g');
var DISALLOWED_ATTRIBUTES = ['sanitize', 'whiteList', 'sanitizeFn'];
var DefaultType = {
  animation: 'boolean',
  template: 'string',
  title: '(string|element|function)',
  trigger: 'string',
  delay: '(number|object)',
  html: 'boolean',
  selector: '(string|boolean)',
  placement: '(string|function)',
  offset: '(number|string|function)',
  container: '(string|element|boolean)',
  fallbackPlacement: '(string|array)',
  boundary: '(string|element)',
  sanitize: 'boolean',
  sanitizeFn: '(null|function)',
  whiteList: 'object',
  popperConfig: '(null|object)'
};
var AttachmentMap = {
  AUTO: 'auto',
  TOP: 'top',
  RIGHT: 'right',
  BOTTOM: 'bottom',
  LEFT: 'left'
};
var Default = {
  animation: true,
  template: '<div class="tooltip" role="tooltip">' + '<div class="arrow"></div>' + '<div class="tooltip-inner"></div></div>',
  trigger: 'hover focus',
  title: '',
  delay: 0,
  html: false,
  selector: false,
  placement: 'top',
  offset: 0,
  container: false,
  fallbackPlacement: 'flip',
  boundary: 'scrollParent',
  sanitize: true,
  sanitizeFn: null,
  whiteList: _tools_sanitizer__WEBPACK_IMPORTED_MODULE_0__["DefaultWhitelist"],
  popperConfig: null
};
var HOVER_STATE_SHOW = 'show';
var HOVER_STATE_OUT = 'out';
var Event = {
  HIDE: "hide".concat(EVENT_KEY),
  HIDDEN: "hidden".concat(EVENT_KEY),
  SHOW: "show".concat(EVENT_KEY),
  SHOWN: "shown".concat(EVENT_KEY),
  INSERTED: "inserted".concat(EVENT_KEY),
  CLICK: "click".concat(EVENT_KEY),
  FOCUSIN: "focusin".concat(EVENT_KEY),
  FOCUSOUT: "focusout".concat(EVENT_KEY),
  MOUSEENTER: "mouseenter".concat(EVENT_KEY),
  MOUSELEAVE: "mouseleave".concat(EVENT_KEY)
};
var CLASS_NAME_FADE = 'fade';
var CLASS_NAME_SHOW = 'show';
var SELECTOR_TOOLTIP_INNER = '.tooltip-inner';
var SELECTOR_ARROW = '.arrow';
var TRIGGER_HOVER = 'hover';
var TRIGGER_FOCUS = 'focus';
var TRIGGER_CLICK = 'click';
var TRIGGER_MANUAL = 'manual';
/**
 * ------------------------------------------------------------------------
 * Class Definition
 * ------------------------------------------------------------------------
 */

var Tooltip = /*#__PURE__*/function () {
  function Tooltip(element, config) {
    _classCallCheck(this, Tooltip);

    if (typeof popper_js__WEBPACK_IMPORTED_MODULE_2__["default"] === 'undefined') {
      throw new TypeError('Bootstrap\'s tooltips require Popper.js (https://popper.js.org/)');
    } // private


    this._isEnabled = true;
    this._timeout = 0;
    this._hoverState = '';
    this._activeTrigger = {};
    this._popper = null; // Protected

    this.element = element;
    this.config = this._getConfig(config);
    this.tip = null;

    this._setListeners();
  } // Getters


  _createClass(Tooltip, [{
    key: "enable",
    // Public
    value: function enable() {
      this._isEnabled = true;
    }
  }, {
    key: "disable",
    value: function disable() {
      this._isEnabled = false;
    }
  }, {
    key: "toggleEnabled",
    value: function toggleEnabled() {
      this._isEnabled = !this._isEnabled;
    }
  }, {
    key: "toggle",
    value: function toggle(event) {
      if (!this._isEnabled) {
        return;
      }

      if (event) {
        var dataKey = this.constructor.DATA_KEY;
        var context = jquery__WEBPACK_IMPORTED_MODULE_1___default()(event.currentTarget).data(dataKey);

        if (!context) {
          context = new this.constructor(event.currentTarget, this._getDelegateConfig());
          jquery__WEBPACK_IMPORTED_MODULE_1___default()(event.currentTarget).data(dataKey, context);
        }

        context._activeTrigger.click = !context._activeTrigger.click;

        if (context._isWithActiveTrigger()) {
          context._enter(null, context);
        } else {
          context._leave(null, context);
        }
      } else {
        if (jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.getTipElement()).hasClass(CLASS_NAME_SHOW)) {
          this._leave(null, this);

          return;
        }

        this._enter(null, this);
      }
    }
  }, {
    key: "dispose",
    value: function dispose() {
      clearTimeout(this._timeout);
      jquery__WEBPACK_IMPORTED_MODULE_1___default.a.removeData(this.element, this.constructor.DATA_KEY);
      jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.element).off(this.constructor.EVENT_KEY);
      jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.element).closest('.modal').off('hide.bs.modal', this._hideModalHandler);

      if (this.tip) {
        jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.tip).remove();
      }

      this._isEnabled = null;
      this._timeout = null;
      this._hoverState = null;
      this._activeTrigger = null;

      if (this._popper) {
        this._popper.destroy();
      }

      this._popper = null;
      this.element = null;
      this.config = null;
      this.tip = null;
    }
  }, {
    key: "show",
    value: function show() {
      var _this = this;

      if (jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.element).css('display') === 'none') {
        throw new Error('Please use show on visible elements');
      }

      var showEvent = jquery__WEBPACK_IMPORTED_MODULE_1___default.a.Event(this.constructor.Event.SHOW);

      if (this.isWithContent() && this._isEnabled) {
        jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.element).trigger(showEvent);
        var shadowRoot = _util__WEBPACK_IMPORTED_MODULE_3__["default"].findShadowRoot(this.element);
        var isInTheDom = jquery__WEBPACK_IMPORTED_MODULE_1___default.a.contains(shadowRoot !== null ? shadowRoot : this.element.ownerDocument.documentElement, this.element);

        if (showEvent.isDefaultPrevented() || !isInTheDom) {
          return;
        }

        var tip = this.getTipElement();
        var tipId = _util__WEBPACK_IMPORTED_MODULE_3__["default"].getUID(this.constructor.NAME);
        tip.setAttribute('id', tipId);
        this.element.setAttribute('aria-describedby', tipId);
        this.setContent();

        if (this.config.animation) {
          jquery__WEBPACK_IMPORTED_MODULE_1___default()(tip).addClass(CLASS_NAME_FADE);
        }

        var placement = typeof this.config.placement === 'function' ? this.config.placement.call(this, tip, this.element) : this.config.placement;

        var attachment = this._getAttachment(placement);

        this.addAttachmentClass(attachment);

        var container = this._getContainer();

        jquery__WEBPACK_IMPORTED_MODULE_1___default()(tip).data(this.constructor.DATA_KEY, this);

        if (!jquery__WEBPACK_IMPORTED_MODULE_1___default.a.contains(this.element.ownerDocument.documentElement, this.tip)) {
          jquery__WEBPACK_IMPORTED_MODULE_1___default()(tip).appendTo(container);
        }

        jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.element).trigger(this.constructor.Event.INSERTED);
        this._popper = new popper_js__WEBPACK_IMPORTED_MODULE_2__["default"](this.element, tip, this._getPopperConfig(attachment));
        jquery__WEBPACK_IMPORTED_MODULE_1___default()(tip).addClass(CLASS_NAME_SHOW); // If this is a touch-enabled device we add extra
        // empty mouseover listeners to the body's immediate children;
        // only needed because of broken event delegation on iOS
        // https://www.quirksmode.org/blog/archives/2014/02/mouse_event_bub.html

        if ('ontouchstart' in document.documentElement) {
          jquery__WEBPACK_IMPORTED_MODULE_1___default()(document.body).children().on('mouseover', null, jquery__WEBPACK_IMPORTED_MODULE_1___default.a.noop);
        }

        var complete = function complete() {
          if (_this.config.animation) {
            _this._fixTransition();
          }

          var prevHoverState = _this._hoverState;
          _this._hoverState = null;
          jquery__WEBPACK_IMPORTED_MODULE_1___default()(_this.element).trigger(_this.constructor.Event.SHOWN);

          if (prevHoverState === HOVER_STATE_OUT) {
            _this._leave(null, _this);
          }
        };

        if (jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.tip).hasClass(CLASS_NAME_FADE)) {
          var transitionDuration = _util__WEBPACK_IMPORTED_MODULE_3__["default"].getTransitionDurationFromElement(this.tip);
          jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.tip).one(_util__WEBPACK_IMPORTED_MODULE_3__["default"].TRANSITION_END, complete).emulateTransitionEnd(transitionDuration);
        } else {
          complete();
        }
      }
    }
  }, {
    key: "hide",
    value: function hide(callback) {
      var _this2 = this;

      var tip = this.getTipElement();
      var hideEvent = jquery__WEBPACK_IMPORTED_MODULE_1___default.a.Event(this.constructor.Event.HIDE);

      var complete = function complete() {
        if (_this2._hoverState !== HOVER_STATE_SHOW && tip.parentNode) {
          tip.parentNode.removeChild(tip);
        }

        _this2._cleanTipClass();

        _this2.element.removeAttribute('aria-describedby');

        jquery__WEBPACK_IMPORTED_MODULE_1___default()(_this2.element).trigger(_this2.constructor.Event.HIDDEN);

        if (_this2._popper !== null) {
          _this2._popper.destroy();
        }

        if (callback) {
          callback();
        }
      };

      jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.element).trigger(hideEvent);

      if (hideEvent.isDefaultPrevented()) {
        return;
      }

      jquery__WEBPACK_IMPORTED_MODULE_1___default()(tip).removeClass(CLASS_NAME_SHOW); // If this is a touch-enabled device we remove the extra
      // empty mouseover listeners we added for iOS support

      if ('ontouchstart' in document.documentElement) {
        jquery__WEBPACK_IMPORTED_MODULE_1___default()(document.body).children().off('mouseover', null, jquery__WEBPACK_IMPORTED_MODULE_1___default.a.noop);
      }

      this._activeTrigger[TRIGGER_CLICK] = false;
      this._activeTrigger[TRIGGER_FOCUS] = false;
      this._activeTrigger[TRIGGER_HOVER] = false;

      if (jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.tip).hasClass(CLASS_NAME_FADE)) {
        var transitionDuration = _util__WEBPACK_IMPORTED_MODULE_3__["default"].getTransitionDurationFromElement(tip);
        jquery__WEBPACK_IMPORTED_MODULE_1___default()(tip).one(_util__WEBPACK_IMPORTED_MODULE_3__["default"].TRANSITION_END, complete).emulateTransitionEnd(transitionDuration);
      } else {
        complete();
      }

      this._hoverState = '';
    }
  }, {
    key: "update",
    value: function update() {
      if (this._popper !== null) {
        this._popper.scheduleUpdate();
      }
    } // Protected

  }, {
    key: "isWithContent",
    value: function isWithContent() {
      return Boolean(this.getTitle());
    }
  }, {
    key: "addAttachmentClass",
    value: function addAttachmentClass(attachment) {
      jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.getTipElement()).addClass("".concat(CLASS_PREFIX, "-").concat(attachment));
    }
  }, {
    key: "getTipElement",
    value: function getTipElement() {
      this.tip = this.tip || jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.config.template)[0];
      return this.tip;
    }
  }, {
    key: "setContent",
    value: function setContent() {
      var tip = this.getTipElement();
      this.setElementContent(jquery__WEBPACK_IMPORTED_MODULE_1___default()(tip.querySelectorAll(SELECTOR_TOOLTIP_INNER)), this.getTitle());
      jquery__WEBPACK_IMPORTED_MODULE_1___default()(tip).removeClass("".concat(CLASS_NAME_FADE, " ").concat(CLASS_NAME_SHOW));
    }
  }, {
    key: "setElementContent",
    value: function setElementContent($element, content) {
      if (_typeof(content) === 'object' && (content.nodeType || content.jquery)) {
        // Content is a DOM node or a jQuery
        if (this.config.html) {
          if (!jquery__WEBPACK_IMPORTED_MODULE_1___default()(content).parent().is($element)) {
            $element.empty().append(content);
          }
        } else {
          $element.text(jquery__WEBPACK_IMPORTED_MODULE_1___default()(content).text());
        }

        return;
      }

      if (this.config.html) {
        if (this.config.sanitize) {
          content = Object(_tools_sanitizer__WEBPACK_IMPORTED_MODULE_0__["sanitizeHtml"])(content, this.config.whiteList, this.config.sanitizeFn);
        }

        $element.html(content);
      } else {
        $element.text(content);
      }
    }
  }, {
    key: "getTitle",
    value: function getTitle() {
      var title = this.element.getAttribute('data-original-title');

      if (!title) {
        title = typeof this.config.title === 'function' ? this.config.title.call(this.element) : this.config.title;
      }

      return title;
    } // Private

  }, {
    key: "_getPopperConfig",
    value: function _getPopperConfig(attachment) {
      var _this3 = this;

      var defaultBsConfig = {
        placement: attachment,
        modifiers: {
          offset: this._getOffset(),
          flip: {
            behavior: this.config.fallbackPlacement
          },
          arrow: {
            element: SELECTOR_ARROW
          },
          preventOverflow: {
            boundariesElement: this.config.boundary
          }
        },
        onCreate: function onCreate(data) {
          if (data.originalPlacement !== data.placement) {
            _this3._handlePopperPlacementChange(data);
          }
        },
        onUpdate: function onUpdate(data) {
          return _this3._handlePopperPlacementChange(data);
        }
      };
      return _objectSpread(_objectSpread({}, defaultBsConfig), this.config.popperConfig);
    }
  }, {
    key: "_getOffset",
    value: function _getOffset() {
      var _this4 = this;

      var offset = {};

      if (typeof this.config.offset === 'function') {
        offset.fn = function (data) {
          data.offsets = _objectSpread(_objectSpread({}, data.offsets), _this4.config.offset(data.offsets, _this4.element) || {});
          return data;
        };
      } else {
        offset.offset = this.config.offset;
      }

      return offset;
    }
  }, {
    key: "_getContainer",
    value: function _getContainer() {
      if (this.config.container === false) {
        return document.body;
      }

      if (_util__WEBPACK_IMPORTED_MODULE_3__["default"].isElement(this.config.container)) {
        return jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.config.container);
      }

      return jquery__WEBPACK_IMPORTED_MODULE_1___default()(document).find(this.config.container);
    }
  }, {
    key: "_getAttachment",
    value: function _getAttachment(placement) {
      return AttachmentMap[placement.toUpperCase()];
    }
  }, {
    key: "_setListeners",
    value: function _setListeners() {
      var _this5 = this;

      var triggers = this.config.trigger.split(' ');
      triggers.forEach(function (trigger) {
        if (trigger === 'click') {
          jquery__WEBPACK_IMPORTED_MODULE_1___default()(_this5.element).on(_this5.constructor.Event.CLICK, _this5.config.selector, function (event) {
            return _this5.toggle(event);
          });
        } else if (trigger !== TRIGGER_MANUAL) {
          var eventIn = trigger === TRIGGER_HOVER ? _this5.constructor.Event.MOUSEENTER : _this5.constructor.Event.FOCUSIN;
          var eventOut = trigger === TRIGGER_HOVER ? _this5.constructor.Event.MOUSELEAVE : _this5.constructor.Event.FOCUSOUT;
          jquery__WEBPACK_IMPORTED_MODULE_1___default()(_this5.element).on(eventIn, _this5.config.selector, function (event) {
            return _this5._enter(event);
          }).on(eventOut, _this5.config.selector, function (event) {
            return _this5._leave(event);
          });
        }
      });

      this._hideModalHandler = function () {
        if (_this5.element) {
          _this5.hide();
        }
      };

      jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.element).closest('.modal').on('hide.bs.modal', this._hideModalHandler);

      if (this.config.selector) {
        this.config = _objectSpread(_objectSpread({}, this.config), {}, {
          trigger: 'manual',
          selector: ''
        });
      } else {
        this._fixTitle();
      }
    }
  }, {
    key: "_fixTitle",
    value: function _fixTitle() {
      var titleType = _typeof(this.element.getAttribute('data-original-title'));

      if (this.element.getAttribute('title') || titleType !== 'string') {
        this.element.setAttribute('data-original-title', this.element.getAttribute('title') || '');
        this.element.setAttribute('title', '');
      }
    }
  }, {
    key: "_enter",
    value: function _enter(event, context) {
      var dataKey = this.constructor.DATA_KEY;
      context = context || jquery__WEBPACK_IMPORTED_MODULE_1___default()(event.currentTarget).data(dataKey);

      if (!context) {
        context = new this.constructor(event.currentTarget, this._getDelegateConfig());
        jquery__WEBPACK_IMPORTED_MODULE_1___default()(event.currentTarget).data(dataKey, context);
      }

      if (event) {
        context._activeTrigger[event.type === 'focusin' ? TRIGGER_FOCUS : TRIGGER_HOVER] = true;
      }

      if (jquery__WEBPACK_IMPORTED_MODULE_1___default()(context.getTipElement()).hasClass(CLASS_NAME_SHOW) || context._hoverState === HOVER_STATE_SHOW) {
        context._hoverState = HOVER_STATE_SHOW;
        return;
      }

      clearTimeout(context._timeout);
      context._hoverState = HOVER_STATE_SHOW;

      if (!context.config.delay || !context.config.delay.show) {
        context.show();
        return;
      }

      context._timeout = setTimeout(function () {
        if (context._hoverState === HOVER_STATE_SHOW) {
          context.show();
        }
      }, context.config.delay.show);
    }
  }, {
    key: "_leave",
    value: function _leave(event, context) {
      var dataKey = this.constructor.DATA_KEY;
      context = context || jquery__WEBPACK_IMPORTED_MODULE_1___default()(event.currentTarget).data(dataKey);

      if (!context) {
        context = new this.constructor(event.currentTarget, this._getDelegateConfig());
        jquery__WEBPACK_IMPORTED_MODULE_1___default()(event.currentTarget).data(dataKey, context);
      }

      if (event) {
        context._activeTrigger[event.type === 'focusout' ? TRIGGER_FOCUS : TRIGGER_HOVER] = false;
      }

      if (context._isWithActiveTrigger()) {
        return;
      }

      clearTimeout(context._timeout);
      context._hoverState = HOVER_STATE_OUT;

      if (!context.config.delay || !context.config.delay.hide) {
        context.hide();
        return;
      }

      context._timeout = setTimeout(function () {
        if (context._hoverState === HOVER_STATE_OUT) {
          context.hide();
        }
      }, context.config.delay.hide);
    }
  }, {
    key: "_isWithActiveTrigger",
    value: function _isWithActiveTrigger() {
      for (var trigger in this._activeTrigger) {
        if (this._activeTrigger[trigger]) {
          return true;
        }
      }

      return false;
    }
  }, {
    key: "_getConfig",
    value: function _getConfig(config) {
      var dataAttributes = jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.element).data();
      Object.keys(dataAttributes).forEach(function (dataAttr) {
        if (DISALLOWED_ATTRIBUTES.indexOf(dataAttr) !== -1) {
          delete dataAttributes[dataAttr];
        }
      });
      config = _objectSpread(_objectSpread(_objectSpread({}, this.constructor.Default), dataAttributes), _typeof(config) === 'object' && config ? config : {});

      if (typeof config.delay === 'number') {
        config.delay = {
          show: config.delay,
          hide: config.delay
        };
      }

      if (typeof config.title === 'number') {
        config.title = config.title.toString();
      }

      if (typeof config.content === 'number') {
        config.content = config.content.toString();
      }

      _util__WEBPACK_IMPORTED_MODULE_3__["default"].typeCheckConfig(NAME, config, this.constructor.DefaultType);

      if (config.sanitize) {
        config.template = Object(_tools_sanitizer__WEBPACK_IMPORTED_MODULE_0__["sanitizeHtml"])(config.template, config.whiteList, config.sanitizeFn);
      }

      return config;
    }
  }, {
    key: "_getDelegateConfig",
    value: function _getDelegateConfig() {
      var config = {};

      if (this.config) {
        for (var key in this.config) {
          if (this.constructor.Default[key] !== this.config[key]) {
            config[key] = this.config[key];
          }
        }
      }

      return config;
    }
  }, {
    key: "_cleanTipClass",
    value: function _cleanTipClass() {
      var $tip = jquery__WEBPACK_IMPORTED_MODULE_1___default()(this.getTipElement());
      var tabClass = $tip.attr('class').match(BSCLS_PREFIX_REGEX);

      if (tabClass !== null && tabClass.length) {
        $tip.removeClass(tabClass.join(''));
      }
    }
  }, {
    key: "_handlePopperPlacementChange",
    value: function _handlePopperPlacementChange(popperData) {
      this.tip = popperData.instance.popper;

      this._cleanTipClass();

      this.addAttachmentClass(this._getAttachment(popperData.placement));
    }
  }, {
    key: "_fixTransition",
    value: function _fixTransition() {
      var tip = this.getTipElement();
      var initConfigAnimation = this.config.animation;

      if (tip.getAttribute('x-placement') !== null) {
        return;
      }

      jquery__WEBPACK_IMPORTED_MODULE_1___default()(tip).removeClass(CLASS_NAME_FADE);
      this.config.animation = false;
      this.hide();
      this.show();
      this.config.animation = initConfigAnimation;
    } // Static

  }], [{
    key: "_jQueryInterface",
    value: function _jQueryInterface(config) {
      return this.each(function () {
        var data = jquery__WEBPACK_IMPORTED_MODULE_1___default()(this).data(DATA_KEY);

        var _config = _typeof(config) === 'object' && config;

        if (!data && /dispose|hide/.test(config)) {
          return;
        }

        if (!data) {
          data = new Tooltip(this, _config);
          jquery__WEBPACK_IMPORTED_MODULE_1___default()(this).data(DATA_KEY, data);
        }

        if (typeof config === 'string') {
          if (typeof data[config] === 'undefined') {
            throw new TypeError("No method named \"".concat(config, "\""));
          }

          data[config]();
        }
      });
    }
  }, {
    key: "VERSION",
    get: function get() {
      return VERSION;
    }
  }, {
    key: "Default",
    get: function get() {
      return Default;
    }
  }, {
    key: "NAME",
    get: function get() {
      return NAME;
    }
  }, {
    key: "DATA_KEY",
    get: function get() {
      return DATA_KEY;
    }
  }, {
    key: "Event",
    get: function get() {
      return Event;
    }
  }, {
    key: "EVENT_KEY",
    get: function get() {
      return EVENT_KEY;
    }
  }, {
    key: "DefaultType",
    get: function get() {
      return DefaultType;
    }
  }]);

  return Tooltip;
}();
/**
 * ------------------------------------------------------------------------
 * jQuery
 * ------------------------------------------------------------------------
 */


jquery__WEBPACK_IMPORTED_MODULE_1___default.a.fn[NAME] = Tooltip._jQueryInterface;
jquery__WEBPACK_IMPORTED_MODULE_1___default.a.fn[NAME].Constructor = Tooltip;

jquery__WEBPACK_IMPORTED_MODULE_1___default.a.fn[NAME].noConflict = function () {
  jquery__WEBPACK_IMPORTED_MODULE_1___default.a.fn[NAME] = JQUERY_NO_CONFLICT;
  return Tooltip._jQueryInterface;
};

/* harmony default export */ __webpack_exports__["default"] = (Tooltip);

/***/ }),

/***/ "./node_modules/bootstrap/js/src/util.js":
/*!***********************************************!*\
  !*** ./node_modules/bootstrap/js/src/util.js ***!
  \***********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/**
 * --------------------------------------------------------------------------
 * Bootstrap (v4.5.0): util.js
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * --------------------------------------------------------------------------
 */

/**
 * ------------------------------------------------------------------------
 * Private TransitionEnd Helpers
 * ------------------------------------------------------------------------
 */

var TRANSITION_END = 'transitionend';
var MAX_UID = 1000000;
var MILLISECONDS_MULTIPLIER = 1000; // Shoutout AngusCroll (https://goo.gl/pxwQGp)

function toType(obj) {
  if (obj === null || typeof obj === 'undefined') {
    return "".concat(obj);
  }

  return {}.toString.call(obj).match(/\s([a-z]+)/i)[1].toLowerCase();
}

function getSpecialTransitionEndEvent() {
  return {
    bindType: TRANSITION_END,
    delegateType: TRANSITION_END,
    handle: function handle(event) {
      if (jquery__WEBPACK_IMPORTED_MODULE_0___default()(event.target).is(this)) {
        return event.handleObj.handler.apply(this, arguments); // eslint-disable-line prefer-rest-params
      }

      return undefined;
    }
  };
}

function transitionEndEmulator(duration) {
  var _this = this;

  var called = false;
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(this).one(Util.TRANSITION_END, function () {
    called = true;
  });
  setTimeout(function () {
    if (!called) {
      Util.triggerTransitionEnd(_this);
    }
  }, duration);
  return this;
}

function setTransitionEndSupport() {
  jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn.emulateTransitionEnd = transitionEndEmulator;
  jquery__WEBPACK_IMPORTED_MODULE_0___default.a.event.special[Util.TRANSITION_END] = getSpecialTransitionEndEvent();
}
/**
 * --------------------------------------------------------------------------
 * Public Util Api
 * --------------------------------------------------------------------------
 */


var Util = {
  TRANSITION_END: 'bsTransitionEnd',
  getUID: function getUID(prefix) {
    do {
      // eslint-disable-next-line no-bitwise
      prefix += ~~(Math.random() * MAX_UID); // "~~" acts like a faster Math.floor() here
    } while (document.getElementById(prefix));

    return prefix;
  },
  getSelectorFromElement: function getSelectorFromElement(element) {
    var selector = element.getAttribute('data-target');

    if (!selector || selector === '#') {
      var hrefAttr = element.getAttribute('href');
      selector = hrefAttr && hrefAttr !== '#' ? hrefAttr.trim() : '';
    }

    try {
      return document.querySelector(selector) ? selector : null;
    } catch (err) {
      return null;
    }
  },
  getTransitionDurationFromElement: function getTransitionDurationFromElement(element) {
    if (!element) {
      return 0;
    } // Get transition-duration of the element


    var transitionDuration = jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).css('transition-duration');
    var transitionDelay = jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).css('transition-delay');
    var floatTransitionDuration = parseFloat(transitionDuration);
    var floatTransitionDelay = parseFloat(transitionDelay); // Return 0 if element or transition duration is not found

    if (!floatTransitionDuration && !floatTransitionDelay) {
      return 0;
    } // If multiple durations are defined, take the first


    transitionDuration = transitionDuration.split(',')[0];
    transitionDelay = transitionDelay.split(',')[0];
    return (parseFloat(transitionDuration) + parseFloat(transitionDelay)) * MILLISECONDS_MULTIPLIER;
  },
  reflow: function reflow(element) {
    return element.offsetHeight;
  },
  triggerTransitionEnd: function triggerTransitionEnd(element) {
    jquery__WEBPACK_IMPORTED_MODULE_0___default()(element).trigger(TRANSITION_END);
  },
  // TODO: Remove in v5
  supportsTransitionEnd: function supportsTransitionEnd() {
    return Boolean(TRANSITION_END);
  },
  isElement: function isElement(obj) {
    return (obj[0] || obj).nodeType;
  },
  typeCheckConfig: function typeCheckConfig(componentName, config, configTypes) {
    for (var property in configTypes) {
      if (Object.prototype.hasOwnProperty.call(configTypes, property)) {
        var expectedTypes = configTypes[property];
        var value = config[property];
        var valueType = value && Util.isElement(value) ? 'element' : toType(value);

        if (!new RegExp(expectedTypes).test(valueType)) {
          throw new Error("".concat(componentName.toUpperCase(), ": ") + "Option \"".concat(property, "\" provided type \"").concat(valueType, "\" ") + "but expected type \"".concat(expectedTypes, "\"."));
        }
      }
    }
  },
  findShadowRoot: function findShadowRoot(element) {
    if (!document.documentElement.attachShadow) {
      return null;
    } // Can find the shadow root otherwise it'll return the document


    if (typeof element.getRootNode === 'function') {
      var root = element.getRootNode();
      return root instanceof ShadowRoot ? root : null;
    }

    if (element instanceof ShadowRoot) {
      return element;
    } // when we don't find a shadow root


    if (!element.parentNode) {
      return null;
    }

    return Util.findShadowRoot(element.parentNode);
  },
  jQueryDetection: function jQueryDetection() {
    if (typeof jquery__WEBPACK_IMPORTED_MODULE_0___default.a === 'undefined') {
      throw new TypeError('Bootstrap\'s JavaScript requires jQuery. jQuery must be included before Bootstrap\'s JavaScript.');
    }

    var version = jquery__WEBPACK_IMPORTED_MODULE_0___default.a.fn.jquery.split(' ')[0].split('.');
    var minMajor = 1;
    var ltMajor = 2;
    var minMinor = 9;
    var minPatch = 1;
    var maxMajor = 4;

    if (version[0] < ltMajor && version[1] < minMinor || version[0] === minMajor && version[1] === minMinor && version[2] < minPatch || version[0] >= maxMajor) {
      throw new Error('Bootstrap\'s JavaScript requires at least jQuery v1.9.1 but less than v4.0.0');
    }
  }
};
Util.jQueryDetection();
setTransitionEndSupport();
/* harmony default export */ __webpack_exports__["default"] = (Util);

/***/ }),

/***/ "./node_modules/popper.js/dist/esm/popper.js":
/*!***************************************************!*\
  !*** ./node_modules/popper.js/dist/esm/popper.js ***!
  \***************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function(global) {/**!
 * @fileOverview Kickass library to create and place poppers near their reference elements.
 * @version 1.16.1
 * @license
 * Copyright (c) 2016 Federico Zivolo and contributors
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
var n="undefined"!=typeof window&&"undefined"!=typeof document&&"undefined"!=typeof navigator,o=function(){for(var e=["Edge","Trident","Firefox"],t=0;t<e.length;t+=1)if(n&&navigator.userAgent.indexOf(e[t])>=0)return 1;return 0}();var i=n&&window.Promise?function(e){var t=!1;return function(){t||(t=!0,window.Promise.resolve().then((function(){t=!1,e()})))}}:function(e){var t=!1;return function(){t||(t=!0,setTimeout((function(){t=!1,e()}),o))}};function r(e){return e&&"[object Function]"==={}.toString.call(e)}function a(e,t){if(1!==e.nodeType)return[];var n=e.ownerDocument.defaultView.getComputedStyle(e,null);return t?n[t]:n}function s(e){return"HTML"===e.nodeName?e:e.parentNode||e.host}function l(e){if(!e)return document.body;switch(e.nodeName){case"HTML":case"BODY":return e.ownerDocument.body;case"#document":return e.body}var t=a(e),n=t.overflow,o=t.overflowX,i=t.overflowY;return/(auto|scroll|overlay)/.test(n+i+o)?e:l(s(e))}function c(e){return e&&e.referenceNode?e.referenceNode:e}var u=n&&!(!window.MSInputMethodContext||!document.documentMode),f=n&&/MSIE 10/.test(navigator.userAgent);function h(e){return 11===e?u:10===e?f:u||f}function d(e){if(!e)return document.documentElement;for(var t=h(10)?document.body:null,n=e.offsetParent||null;n===t&&e.nextElementSibling;)n=(e=e.nextElementSibling).offsetParent;var o=n&&n.nodeName;return o&&"BODY"!==o&&"HTML"!==o?-1!==["TH","TD","TABLE"].indexOf(n.nodeName)&&"static"===a(n,"position")?d(n):n:e?e.ownerDocument.documentElement:document.documentElement}function p(e){return null!==e.parentNode?p(e.parentNode):e}function m(e,t){if(!(e&&e.nodeType&&t&&t.nodeType))return document.documentElement;var n=e.compareDocumentPosition(t)&Node.DOCUMENT_POSITION_FOLLOWING,o=n?e:t,i=n?t:e,r=document.createRange();r.setStart(o,0),r.setEnd(i,0);var a,s,l=r.commonAncestorContainer;if(e!==l&&t!==l||o.contains(i))return"BODY"===(s=(a=l).nodeName)||"HTML"!==s&&d(a.firstElementChild)!==a?d(l):l;var c=p(e);return c.host?m(c.host,t):m(e,p(t).host)}function g(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"top",n="top"===t?"scrollTop":"scrollLeft",o=e.nodeName;if("BODY"===o||"HTML"===o){var i=e.ownerDocument.documentElement,r=e.ownerDocument.scrollingElement||i;return r[n]}return e[n]}function v(e,t){var n=arguments.length>2&&void 0!==arguments[2]&&arguments[2],o=g(t,"top"),i=g(t,"left"),r=n?-1:1;return e.top+=o*r,e.bottom+=o*r,e.left+=i*r,e.right+=i*r,e}function y(e,t){var n="x"===t?"Left":"Top",o="Left"===n?"Right":"Bottom";return parseFloat(e["border"+n+"Width"])+parseFloat(e["border"+o+"Width"])}function _(e,t,n,o){return Math.max(t["offset"+e],t["scroll"+e],n["client"+e],n["offset"+e],n["scroll"+e],h(10)?parseInt(n["offset"+e])+parseInt(o["margin"+("Height"===e?"Top":"Left")])+parseInt(o["margin"+("Height"===e?"Bottom":"Right")]):0)}function b(e){var t=e.body,n=e.documentElement,o=h(10)&&getComputedStyle(n);return{height:_("Height",t,n,o),width:_("Width",t,n,o)}}var w=function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")},E=function(){function e(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}return function(t,n,o){return n&&e(t.prototype,n),o&&e(t,o),t}}(),k=function(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e},O=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var o in n)Object.prototype.hasOwnProperty.call(n,o)&&(e[o]=n[o])}return e};function T(e){return O({},e,{right:e.left+e.width,bottom:e.top+e.height})}function C(e){var t={};try{if(h(10)){t=e.getBoundingClientRect();var n=g(e,"top"),o=g(e,"left");t.top+=n,t.left+=o,t.bottom+=n,t.right+=o}else t=e.getBoundingClientRect()}catch(e){}var i={left:t.left,top:t.top,width:t.right-t.left,height:t.bottom-t.top},r="HTML"===e.nodeName?b(e.ownerDocument):{},s=r.width||e.clientWidth||i.width,l=r.height||e.clientHeight||i.height,c=e.offsetWidth-s,u=e.offsetHeight-l;if(c||u){var f=a(e);c-=y(f,"x"),u-=y(f,"y"),i.width-=c,i.height-=u}return T(i)}function S(e,t){var n=arguments.length>2&&void 0!==arguments[2]&&arguments[2],o=h(10),i="HTML"===t.nodeName,r=C(e),s=C(t),c=l(e),u=a(t),f=parseFloat(u.borderTopWidth),d=parseFloat(u.borderLeftWidth);n&&i&&(s.top=Math.max(s.top,0),s.left=Math.max(s.left,0));var p=T({top:r.top-s.top-f,left:r.left-s.left-d,width:r.width,height:r.height});if(p.marginTop=0,p.marginLeft=0,!o&&i){var m=parseFloat(u.marginTop),g=parseFloat(u.marginLeft);p.top-=f-m,p.bottom-=f-m,p.left-=d-g,p.right-=d-g,p.marginTop=m,p.marginLeft=g}return(o&&!n?t.contains(c):t===c&&"BODY"!==c.nodeName)&&(p=v(p,t)),p}function j(e){var t=arguments.length>1&&void 0!==arguments[1]&&arguments[1],n=e.ownerDocument.documentElement,o=S(e,n),i=Math.max(n.clientWidth,window.innerWidth||0),r=Math.max(n.clientHeight,window.innerHeight||0),a=t?0:g(n),s=t?0:g(n,"left"),l={top:a-o.top+o.marginTop,left:s-o.left+o.marginLeft,width:i,height:r};return T(l)}function D(e){var t=e.nodeName;if("BODY"===t||"HTML"===t)return!1;if("fixed"===a(e,"position"))return!0;var n=s(e);return!!n&&D(n)}function N(e){if(!e||!e.parentElement||h())return document.documentElement;for(var t=e.parentElement;t&&"none"===a(t,"transform");)t=t.parentElement;return t||document.documentElement}function P(e,t,n,o){var i=arguments.length>4&&void 0!==arguments[4]&&arguments[4],r={top:0,left:0},a=i?N(e):m(e,c(t));if("viewport"===o)r=j(a,i);else{var u=void 0;"scrollParent"===o?"BODY"===(u=l(s(t))).nodeName&&(u=e.ownerDocument.documentElement):u="window"===o?e.ownerDocument.documentElement:o;var f=S(u,a,i);if("HTML"!==u.nodeName||D(a))r=f;else{var h=b(e.ownerDocument),d=h.height,p=h.width;r.top+=f.top-f.marginTop,r.bottom=d+f.top,r.left+=f.left-f.marginLeft,r.right=p+f.left}}var g="number"==typeof(n=n||0);return r.left+=g?n:n.left||0,r.top+=g?n:n.top||0,r.right-=g?n:n.right||0,r.bottom-=g?n:n.bottom||0,r}function A(e){return e.width*e.height}function I(e,t,n,o,i){var r=arguments.length>5&&void 0!==arguments[5]?arguments[5]:0;if(-1===e.indexOf("auto"))return e;var a=P(n,o,r,i),s={top:{width:a.width,height:t.top-a.top},right:{width:a.right-t.right,height:a.height},bottom:{width:a.width,height:a.bottom-t.bottom},left:{width:t.left-a.left,height:a.height}},l=Object.keys(s).map((function(e){return O({key:e},s[e],{area:A(s[e])})})).sort((function(e,t){return t.area-e.area})),c=l.filter((function(e){var t=e.width,o=e.height;return t>=n.clientWidth&&o>=n.clientHeight})),u=c.length>0?c[0].key:l[0].key,f=e.split("-")[1];return u+(f?"-"+f:"")}function x(e,t,n){var o=arguments.length>3&&void 0!==arguments[3]?arguments[3]:null,i=o?N(t):m(t,c(n));return S(n,i,o)}function L(e){var t=e.ownerDocument.defaultView.getComputedStyle(e),n=parseFloat(t.marginTop||0)+parseFloat(t.marginBottom||0),o=parseFloat(t.marginLeft||0)+parseFloat(t.marginRight||0);return{width:e.offsetWidth+o,height:e.offsetHeight+n}}function F(e){var t={left:"right",right:"left",bottom:"top",top:"bottom"};return e.replace(/left|right|bottom|top/g,(function(e){return t[e]}))}function R(e,t,n){n=n.split("-")[0];var o=L(e),i={width:o.width,height:o.height},r=-1!==["right","left"].indexOf(n),a=r?"top":"left",s=r?"left":"top",l=r?"height":"width",c=r?"width":"height";return i[a]=t[a]+t[l]/2-o[l]/2,i[s]=n===s?t[s]-o[c]:t[F(s)],i}function M(e,t){return Array.prototype.find?e.find(t):e.filter(t)[0]}function B(e,t,n){return(void 0===n?e:e.slice(0,function(e,t,n){if(Array.prototype.findIndex)return e.findIndex((function(e){return e[t]===n}));var o=M(e,(function(e){return e[t]===n}));return e.indexOf(o)}(e,"name",n))).forEach((function(e){e.function&&console.warn("`modifier.function` is deprecated, use `modifier.fn`!");var n=e.function||e.fn;e.enabled&&r(n)&&(t.offsets.popper=T(t.offsets.popper),t.offsets.reference=T(t.offsets.reference),t=n(t,e))})),t}function H(){if(!this.state.isDestroyed){var e={instance:this,styles:{},arrowStyles:{},attributes:{},flipped:!1,offsets:{}};e.offsets.reference=x(this.state,this.popper,this.reference,this.options.positionFixed),e.placement=I(this.options.placement,e.offsets.reference,this.popper,this.reference,this.options.modifiers.flip.boundariesElement,this.options.modifiers.flip.padding),e.originalPlacement=e.placement,e.positionFixed=this.options.positionFixed,e.offsets.popper=R(this.popper,e.offsets.reference,e.placement),e.offsets.popper.position=this.options.positionFixed?"fixed":"absolute",e=B(this.modifiers,e),this.state.isCreated?this.options.onUpdate(e):(this.state.isCreated=!0,this.options.onCreate(e))}}function q(e,t){return e.some((function(e){var n=e.name;return e.enabled&&n===t}))}function Q(e){for(var t=[!1,"ms","Webkit","Moz","O"],n=e.charAt(0).toUpperCase()+e.slice(1),o=0;o<t.length;o++){var i=t[o],r=i?""+i+n:e;if(void 0!==document.body.style[r])return r}return null}function W(){return this.state.isDestroyed=!0,q(this.modifiers,"applyStyle")&&(this.popper.removeAttribute("x-placement"),this.popper.style.position="",this.popper.style.top="",this.popper.style.left="",this.popper.style.right="",this.popper.style.bottom="",this.popper.style.willChange="",this.popper.style[Q("transform")]=""),this.disableEventListeners(),this.options.removeOnDestroy&&this.popper.parentNode.removeChild(this.popper),this}function U(e){var t=e.ownerDocument;return t?t.defaultView:window}function V(e,t,n,o){n.updateBound=o,U(e).addEventListener("resize",n.updateBound,{passive:!0});var i=l(e);return function e(t,n,o,i){var r="BODY"===t.nodeName,a=r?t.ownerDocument.defaultView:t;a.addEventListener(n,o,{passive:!0}),r||e(l(a.parentNode),n,o,i),i.push(a)}(i,"scroll",n.updateBound,n.scrollParents),n.scrollElement=i,n.eventsEnabled=!0,n}function Y(){this.state.eventsEnabled||(this.state=V(this.reference,this.options,this.state,this.scheduleUpdate))}function z(){var e,t;this.state.eventsEnabled&&(cancelAnimationFrame(this.scheduleUpdate),this.state=(e=this.reference,t=this.state,U(e).removeEventListener("resize",t.updateBound),t.scrollParents.forEach((function(e){e.removeEventListener("scroll",t.updateBound)})),t.updateBound=null,t.scrollParents=[],t.scrollElement=null,t.eventsEnabled=!1,t))}function X(e){return""!==e&&!isNaN(parseFloat(e))&&isFinite(e)}function K(e,t){Object.keys(t).forEach((function(n){var o="";-1!==["width","height","top","right","bottom","left"].indexOf(n)&&X(t[n])&&(o="px"),e.style[n]=t[n]+o}))}var G=n&&/Firefox/i.test(navigator.userAgent);function $(e,t,n){var o=M(e,(function(e){return e.name===t})),i=!!o&&e.some((function(e){return e.name===n&&e.enabled&&e.order<o.order}));if(!i){var r="`"+t+"`",a="`"+n+"`";console.warn(a+" modifier is required by "+r+" modifier in order to work, be sure to include it before "+r+"!")}return i}var J=["auto-start","auto","auto-end","top-start","top","top-end","right-start","right","right-end","bottom-end","bottom","bottom-start","left-end","left","left-start"],Z=J.slice(3);function ee(e){var t=arguments.length>1&&void 0!==arguments[1]&&arguments[1],n=Z.indexOf(e),o=Z.slice(n+1).concat(Z.slice(0,n));return t?o.reverse():o}var te="flip",ne="clockwise",oe="counterclockwise";function ie(e,t,n,o){var i=[0,0],r=-1!==["right","left"].indexOf(o),a=e.split(/(\+|\-)/).map((function(e){return e.trim()})),s=a.indexOf(M(a,(function(e){return-1!==e.search(/,|\s/)})));a[s]&&-1===a[s].indexOf(",")&&console.warn("Offsets separated by white space(s) are deprecated, use a comma (,) instead.");var l=/\s*,\s*|\s+/,c=-1!==s?[a.slice(0,s).concat([a[s].split(l)[0]]),[a[s].split(l)[1]].concat(a.slice(s+1))]:[a];return(c=c.map((function(e,o){var i=(1===o?!r:r)?"height":"width",a=!1;return e.reduce((function(e,t){return""===e[e.length-1]&&-1!==["+","-"].indexOf(t)?(e[e.length-1]=t,a=!0,e):a?(e[e.length-1]+=t,a=!1,e):e.concat(t)}),[]).map((function(e){return function(e,t,n,o){var i=e.match(/((?:\-|\+)?\d*\.?\d*)(.*)/),r=+i[1],a=i[2];if(!r)return e;if(0===a.indexOf("%")){var s=void 0;switch(a){case"%p":s=n;break;case"%":case"%r":default:s=o}return T(s)[t]/100*r}if("vh"===a||"vw"===a){return("vh"===a?Math.max(document.documentElement.clientHeight,window.innerHeight||0):Math.max(document.documentElement.clientWidth,window.innerWidth||0))/100*r}return r}(e,i,t,n)}))}))).forEach((function(e,t){e.forEach((function(n,o){X(n)&&(i[t]+=n*("-"===e[o-1]?-1:1))}))})),i}var re={placement:"bottom",positionFixed:!1,eventsEnabled:!0,removeOnDestroy:!1,onCreate:function(){},onUpdate:function(){},modifiers:{shift:{order:100,enabled:!0,fn:function(e){var t=e.placement,n=t.split("-")[0],o=t.split("-")[1];if(o){var i=e.offsets,r=i.reference,a=i.popper,s=-1!==["bottom","top"].indexOf(n),l=s?"left":"top",c=s?"width":"height",u={start:k({},l,r[l]),end:k({},l,r[l]+r[c]-a[c])};e.offsets.popper=O({},a,u[o])}return e}},offset:{order:200,enabled:!0,fn:function(e,t){var n=t.offset,o=e.placement,i=e.offsets,r=i.popper,a=i.reference,s=o.split("-")[0],l=void 0;return l=X(+n)?[+n,0]:ie(n,r,a,s),"left"===s?(r.top+=l[0],r.left-=l[1]):"right"===s?(r.top+=l[0],r.left+=l[1]):"top"===s?(r.left+=l[0],r.top-=l[1]):"bottom"===s&&(r.left+=l[0],r.top+=l[1]),e.popper=r,e},offset:0},preventOverflow:{order:300,enabled:!0,fn:function(e,t){var n=t.boundariesElement||d(e.instance.popper);e.instance.reference===n&&(n=d(n));var o=Q("transform"),i=e.instance.popper.style,r=i.top,a=i.left,s=i[o];i.top="",i.left="",i[o]="";var l=P(e.instance.popper,e.instance.reference,t.padding,n,e.positionFixed);i.top=r,i.left=a,i[o]=s,t.boundaries=l;var c=t.priority,u=e.offsets.popper,f={primary:function(e){var n=u[e];return u[e]<l[e]&&!t.escapeWithReference&&(n=Math.max(u[e],l[e])),k({},e,n)},secondary:function(e){var n="right"===e?"left":"top",o=u[n];return u[e]>l[e]&&!t.escapeWithReference&&(o=Math.min(u[n],l[e]-("right"===e?u.width:u.height))),k({},n,o)}};return c.forEach((function(e){var t=-1!==["left","top"].indexOf(e)?"primary":"secondary";u=O({},u,f[t](e))})),e.offsets.popper=u,e},priority:["left","right","top","bottom"],padding:5,boundariesElement:"scrollParent"},keepTogether:{order:400,enabled:!0,fn:function(e){var t=e.offsets,n=t.popper,o=t.reference,i=e.placement.split("-")[0],r=Math.floor,a=-1!==["top","bottom"].indexOf(i),s=a?"right":"bottom",l=a?"left":"top",c=a?"width":"height";return n[s]<r(o[l])&&(e.offsets.popper[l]=r(o[l])-n[c]),n[l]>r(o[s])&&(e.offsets.popper[l]=r(o[s])),e}},arrow:{order:500,enabled:!0,fn:function(e,t){var n;if(!$(e.instance.modifiers,"arrow","keepTogether"))return e;var o=t.element;if("string"==typeof o){if(!(o=e.instance.popper.querySelector(o)))return e}else if(!e.instance.popper.contains(o))return console.warn("WARNING: `arrow.element` must be child of its popper element!"),e;var i=e.placement.split("-")[0],r=e.offsets,s=r.popper,l=r.reference,c=-1!==["left","right"].indexOf(i),u=c?"height":"width",f=c?"Top":"Left",h=f.toLowerCase(),d=c?"left":"top",p=c?"bottom":"right",m=L(o)[u];l[p]-m<s[h]&&(e.offsets.popper[h]-=s[h]-(l[p]-m)),l[h]+m>s[p]&&(e.offsets.popper[h]+=l[h]+m-s[p]),e.offsets.popper=T(e.offsets.popper);var g=l[h]+l[u]/2-m/2,v=a(e.instance.popper),y=parseFloat(v["margin"+f]),_=parseFloat(v["border"+f+"Width"]),b=g-e.offsets.popper[h]-y-_;return b=Math.max(Math.min(s[u]-m,b),0),e.arrowElement=o,e.offsets.arrow=(k(n={},h,Math.round(b)),k(n,d,""),n),e},element:"[x-arrow]"},flip:{order:600,enabled:!0,fn:function(e,t){if(q(e.instance.modifiers,"inner"))return e;if(e.flipped&&e.placement===e.originalPlacement)return e;var n=P(e.instance.popper,e.instance.reference,t.padding,t.boundariesElement,e.positionFixed),o=e.placement.split("-")[0],i=F(o),r=e.placement.split("-")[1]||"",a=[];switch(t.behavior){case te:a=[o,i];break;case ne:a=ee(o);break;case oe:a=ee(o,!0);break;default:a=t.behavior}return a.forEach((function(s,l){if(o!==s||a.length===l+1)return e;o=e.placement.split("-")[0],i=F(o);var c=e.offsets.popper,u=e.offsets.reference,f=Math.floor,h="left"===o&&f(c.right)>f(u.left)||"right"===o&&f(c.left)<f(u.right)||"top"===o&&f(c.bottom)>f(u.top)||"bottom"===o&&f(c.top)<f(u.bottom),d=f(c.left)<f(n.left),p=f(c.right)>f(n.right),m=f(c.top)<f(n.top),g=f(c.bottom)>f(n.bottom),v="left"===o&&d||"right"===o&&p||"top"===o&&m||"bottom"===o&&g,y=-1!==["top","bottom"].indexOf(o),_=!!t.flipVariations&&(y&&"start"===r&&d||y&&"end"===r&&p||!y&&"start"===r&&m||!y&&"end"===r&&g),b=!!t.flipVariationsByContent&&(y&&"start"===r&&p||y&&"end"===r&&d||!y&&"start"===r&&g||!y&&"end"===r&&m),w=_||b;(h||v||w)&&(e.flipped=!0,(h||v)&&(o=a[l+1]),w&&(r=function(e){return"end"===e?"start":"start"===e?"end":e}(r)),e.placement=o+(r?"-"+r:""),e.offsets.popper=O({},e.offsets.popper,R(e.instance.popper,e.offsets.reference,e.placement)),e=B(e.instance.modifiers,e,"flip"))})),e},behavior:"flip",padding:5,boundariesElement:"viewport",flipVariations:!1,flipVariationsByContent:!1},inner:{order:700,enabled:!1,fn:function(e){var t=e.placement,n=t.split("-")[0],o=e.offsets,i=o.popper,r=o.reference,a=-1!==["left","right"].indexOf(n),s=-1===["top","left"].indexOf(n);return i[a?"left":"top"]=r[n]-(s?i[a?"width":"height"]:0),e.placement=F(t),e.offsets.popper=T(i),e}},hide:{order:800,enabled:!0,fn:function(e){if(!$(e.instance.modifiers,"hide","preventOverflow"))return e;var t=e.offsets.reference,n=M(e.instance.modifiers,(function(e){return"preventOverflow"===e.name})).boundaries;if(t.bottom<n.top||t.left>n.right||t.top>n.bottom||t.right<n.left){if(!0===e.hide)return e;e.hide=!0,e.attributes["x-out-of-boundaries"]=""}else{if(!1===e.hide)return e;e.hide=!1,e.attributes["x-out-of-boundaries"]=!1}return e}},computeStyle:{order:850,enabled:!0,fn:function(e,t){var n=t.x,o=t.y,i=e.offsets.popper,r=M(e.instance.modifiers,(function(e){return"applyStyle"===e.name})).gpuAcceleration;void 0!==r&&console.warn("WARNING: `gpuAcceleration` option moved to `computeStyle` modifier and will not be supported in future versions of Popper.js!");var a=void 0!==r?r:t.gpuAcceleration,s=d(e.instance.popper),l=C(s),c={position:i.position},u=function(e,t){var n=e.offsets,o=n.popper,i=n.reference,r=Math.round,a=Math.floor,s=function(e){return e},l=r(i.width),c=r(o.width),u=-1!==["left","right"].indexOf(e.placement),f=-1!==e.placement.indexOf("-"),h=t?u||f||l%2==c%2?r:a:s,d=t?r:s;return{left:h(l%2==1&&c%2==1&&!f&&t?o.left-1:o.left),top:d(o.top),bottom:d(o.bottom),right:h(o.right)}}(e,window.devicePixelRatio<2||!G),f="bottom"===n?"top":"bottom",h="right"===o?"left":"right",p=Q("transform"),m=void 0,g=void 0;if(g="bottom"===f?"HTML"===s.nodeName?-s.clientHeight+u.bottom:-l.height+u.bottom:u.top,m="right"===h?"HTML"===s.nodeName?-s.clientWidth+u.right:-l.width+u.right:u.left,a&&p)c[p]="translate3d("+m+"px, "+g+"px, 0)",c[f]=0,c[h]=0,c.willChange="transform";else{var v="bottom"===f?-1:1,y="right"===h?-1:1;c[f]=g*v,c[h]=m*y,c.willChange=f+", "+h}var _={"x-placement":e.placement};return e.attributes=O({},_,e.attributes),e.styles=O({},c,e.styles),e.arrowStyles=O({},e.offsets.arrow,e.arrowStyles),e},gpuAcceleration:!0,x:"bottom",y:"right"},applyStyle:{order:900,enabled:!0,fn:function(e){var t,n;return K(e.instance.popper,e.styles),t=e.instance.popper,n=e.attributes,Object.keys(n).forEach((function(e){!1!==n[e]?t.setAttribute(e,n[e]):t.removeAttribute(e)})),e.arrowElement&&Object.keys(e.arrowStyles).length&&K(e.arrowElement,e.arrowStyles),e},onLoad:function(e,t,n,o,i){var r=x(i,t,e,n.positionFixed),a=I(n.placement,r,t,e,n.modifiers.flip.boundariesElement,n.modifiers.flip.padding);return t.setAttribute("x-placement",a),K(t,{position:n.positionFixed?"fixed":"absolute"}),n},gpuAcceleration:void 0}}},ae=function(){function e(t,n){var o=this,a=arguments.length>2&&void 0!==arguments[2]?arguments[2]:{};w(this,e),this.scheduleUpdate=function(){return requestAnimationFrame(o.update)},this.update=i(this.update.bind(this)),this.options=O({},e.Defaults,a),this.state={isDestroyed:!1,isCreated:!1,scrollParents:[]},this.reference=t&&t.jquery?t[0]:t,this.popper=n&&n.jquery?n[0]:n,this.options.modifiers={},Object.keys(O({},e.Defaults.modifiers,a.modifiers)).forEach((function(t){o.options.modifiers[t]=O({},e.Defaults.modifiers[t]||{},a.modifiers?a.modifiers[t]:{})})),this.modifiers=Object.keys(this.options.modifiers).map((function(e){return O({name:e},o.options.modifiers[e])})).sort((function(e,t){return e.order-t.order})),this.modifiers.forEach((function(e){e.enabled&&r(e.onLoad)&&e.onLoad(o.reference,o.popper,o.options,e,o.state)})),this.update();var s=this.options.eventsEnabled;s&&this.enableEventListeners(),this.state.eventsEnabled=s}return E(e,[{key:"update",value:function(){return H.call(this)}},{key:"destroy",value:function(){return W.call(this)}},{key:"enableEventListeners",value:function(){return Y.call(this)}},{key:"disableEventListeners",value:function(){return z.call(this)}}]),e}();ae.Utils=("undefined"!=typeof window?window:e).PopperUtils,ae.placements=J,ae.Defaults=re,t.a=ae}).call(this,n(6))},,,,function(e,t){function n(e){return(n="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}var o;o=function(){return this}();try{o=o||new Function("return this")()}catch(e){"object"===("undefined"==typeof window?"undefined":n(window))&&(o=window)}e.exports=o},,,,,function(e,t,n){"use strict";n.r(t);var o=n(0),i=n.n(o);function r(e){var t=this,n=!1;return i()(this).one(a.TRANSITION_END,(function(){n=!0})),setTimeout((function(){n||a.triggerTransitionEnd(t)}),e),this}var a={TRANSITION_END:"bsTransitionEnd",getUID:function(e){do{e+=~~(1e6*Math.random())}while(document.getElementById(e));return e},getSelectorFromElement:function(e){var t=e.getAttribute("data-target");if(!t||"#"===t){var n=e.getAttribute("href");t=n&&"#"!==n?n.trim():""}try{return document.querySelector(t)?t:null}catch(e){return null}},getTransitionDurationFromElement:function(e){if(!e)return 0;var t=i()(e).css("transition-duration"),n=i()(e).css("transition-delay"),o=parseFloat(t),r=parseFloat(n);return o||r?(t=t.split(",")[0],n=n.split(",")[0],1e3*(parseFloat(t)+parseFloat(n))):0},reflow:function(e){return e.offsetHeight},triggerTransitionEnd:function(e){i()(e).trigger("transitionend")},supportsTransitionEnd:function(){return Boolean("transitionend")},isElement:function(e){return(e[0]||e).nodeType},typeCheckConfig:function(e,t,n){for(var o in n)if(Object.prototype.hasOwnProperty.call(n,o)){var i=n[o],r=t[o],s=r&&a.isElement(r)?"element":null==(l=r)?"".concat(l):{}.toString.call(l).match(/\s([a-z]+)/i)[1].toLowerCase();if(!new RegExp(i).test(s))throw new Error("".concat(e.toUpperCase(),": ")+'Option "'.concat(o,'" provided type "').concat(s,'" ')+'but expected type "'.concat(i,'".'))}var l},findShadowRoot:function(e){if(!document.documentElement.attachShadow)return null;if("function"==typeof e.getRootNode){var t=e.getRootNode();return t instanceof ShadowRoot?t:null}return e instanceof ShadowRoot?e:e.parentNode?a.findShadowRoot(e.parentNode):null},jQueryDetection:function(){if(void 0===i.a)throw new TypeError("Bootstrap's JavaScript requires jQuery. jQuery must be included before Bootstrap's JavaScript.");var e=i.a.fn.jquery.split(" ")[0].split(".");if(e[0]<2&&e[1]<9||1===e[0]&&9===e[1]&&e[2]<1||e[0]>=4)throw new Error("Bootstrap's JavaScript requires at least jQuery v1.9.1 but less than v4.0.0")}};a.jQueryDetection(),i.a.fn.emulateTransitionEnd=r,i.a.event.special[a.TRANSITION_END]={bindType:"transitionend",delegateType:"transitionend",handle:function(e){if(i()(e.target).is(this))return e.handleObj.handler.apply(this,arguments)}};var s=a;function l(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}var c="alert",u=".".concat("bs.alert"),f=i.a.fn[c],h="close".concat(u),d="closed".concat(u),p="click".concat(u).concat(".data-api"),m=function(){function e(t){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this._element=t}var t,n,o;return t=e,o=[{key:"VERSION",get:function(){return"4.6.0"}},{key:"_jQueryInterface",value:function(t){return this.each((function(){var n=i()(this),o=n.data("bs.alert");o||(o=new e(this),n.data("bs.alert",o)),"close"===t&&o[t](this)}))}},{key:"_handleDismiss",value:function(e){return function(t){t&&t.preventDefault(),e.close(this)}}}],(n=[{key:"close",value:function(e){var t=this._element;e&&(t=this._getRootElement(e)),this._triggerCloseEvent(t).isDefaultPrevented()||this._removeElement(t)}},{key:"dispose",value:function(){i.a.removeData(this._element,"bs.alert"),this._element=null}},{key:"_getRootElement",value:function(e){var t=s.getSelectorFromElement(e),n=!1;return t&&(n=document.querySelector(t)),n||(n=i()(e).closest(".".concat("alert"))[0]),n}},{key:"_triggerCloseEvent",value:function(e){var t=i.a.Event(h);return i()(e).trigger(t),t}},{key:"_removeElement",value:function(e){var t=this;if(i()(e).removeClass("show"),i()(e).hasClass("fade")){var n=s.getTransitionDurationFromElement(e);i()(e).one(s.TRANSITION_END,(function(n){return t._destroyElement(e,n)})).emulateTransitionEnd(n)}else this._destroyElement(e)}},{key:"_destroyElement",value:function(e){i()(e).detach().trigger(d).remove()}}])&&l(t.prototype,n),o&&l(t,o),e}();i()(document).on(p,'[data-dismiss="alert"]',m._handleDismiss(new m)),i.a.fn[c]=m._jQueryInterface,i.a.fn[c].Constructor=m,i.a.fn[c].noConflict=function(){return i.a.fn[c]=f,m._jQueryInterface};function g(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}var v=".".concat("bs.button"),y=i.a.fn.button,_="click".concat(v).concat(".data-api"),b="focus".concat(v).concat(".data-api"," ")+"blur".concat(v).concat(".data-api"),w="load".concat(v).concat(".data-api"),E=function(){function e(t){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this._element=t,this.shouldAvoidTriggerChange=!1}var t,n,o;return t=e,o=[{key:"VERSION",get:function(){return"4.6.0"}},{key:"_jQueryInterface",value:function(t,n){return this.each((function(){var o=i()(this),r=o.data("bs.button");r||(r=new e(this),o.data("bs.button",r)),r.shouldAvoidTriggerChange=n,"toggle"===t&&r[t]()}))}}],(n=[{key:"toggle",value:function(){var e=!0,t=!0,n=i()(this._element).closest('[data-toggle="buttons"]')[0];if(n){var o=this._element.querySelector('input:not([type="hidden"])');if(o){if("radio"===o.type)if(o.checked&&this._element.classList.contains("active"))e=!1;else{var r=n.querySelector(".active");r&&i()(r).removeClass("active")}e&&("checkbox"!==o.type&&"radio"!==o.type||(o.checked=!this._element.classList.contains("active")),this.shouldAvoidTriggerChange||i()(o).trigger("change")),o.focus(),t=!1}}this._element.hasAttribute("disabled")||this._element.classList.contains("disabled")||(t&&this._element.setAttribute("aria-pressed",!this._element.classList.contains("active")),e&&i()(this._element).toggleClass("active"))}},{key:"dispose",value:function(){i.a.removeData(this._element,"bs.button"),this._element=null}}])&&g(t.prototype,n),o&&g(t,o),e}();i()(document).on(_,'[data-toggle^="button"]',(function(e){var t=e.target,n=t;if(i()(t).hasClass("btn")||(t=i()(t).closest(".btn")[0]),!t||t.hasAttribute("disabled")||t.classList.contains("disabled"))e.preventDefault();else{var o=t.querySelector('input:not([type="hidden"])');if(o&&(o.hasAttribute("disabled")||o.classList.contains("disabled")))return void e.preventDefault();"INPUT"!==n.tagName&&"LABEL"===t.tagName||E._jQueryInterface.call(i()(t),"toggle","INPUT"===n.tagName)}})).on(b,'[data-toggle^="button"]',(function(e){var t=i()(e.target).closest(".btn")[0];i()(t).toggleClass("focus",/^focus(in)?$/.test(e.type))})),i()(window).on(w,(function(){for(var e=[].slice.call(document.querySelectorAll('[data-toggle="buttons"] .btn')),t=0,n=e.length;t<n;t++){var o=e[t],i=o.querySelector('input:not([type="hidden"])');i.checked||i.hasAttribute("checked")?o.classList.add("active"):o.classList.remove("active")}for(var r=0,a=(e=[].slice.call(document.querySelectorAll('[data-toggle="button"]'))).length;r<a;r++){var s=e[r];"true"===s.getAttribute("aria-pressed")?s.classList.add("active"):s.classList.remove("active")}})),i.a.fn.button=E._jQueryInterface,i.a.fn.button.Constructor=E,i.a.fn.button.noConflict=function(){return i.a.fn.button=y,E._jQueryInterface};function k(e){return(k="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function O(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);t&&(o=o.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,o)}return n}function T(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?O(Object(n),!0).forEach((function(t){C(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):O(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function C(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function S(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}var j=".".concat("bs.carousel"),D=i.a.fn.carousel,N={interval:5e3,keyboard:!0,slide:!1,pause:"hover",wrap:!0,touch:!0},P={interval:"(number|boolean)",keyboard:"boolean",slide:"(boolean|string)",pause:"(string|boolean)",wrap:"boolean",touch:"boolean"},A="slide".concat(j),I="slid".concat(j),x="keydown".concat(j),L="mouseenter".concat(j),F="mouseleave".concat(j),R="touchstart".concat(j),M="touchmove".concat(j),B="touchend".concat(j),H="pointerdown".concat(j),q="pointerup".concat(j),Q="dragstart".concat(j),W="load".concat(j).concat(".data-api"),U="click".concat(j).concat(".data-api"),V={TOUCH:"touch",PEN:"pen"},Y=function(){function e(t,n){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this._items=null,this._interval=null,this._activeElement=null,this._isPaused=!1,this._isSliding=!1,this.touchTimeout=null,this.touchStartX=0,this.touchDeltaX=0,this._config=this._getConfig(n),this._element=t,this._indicatorsElement=this._element.querySelector(".carousel-indicators"),this._touchSupported="ontouchstart"in document.documentElement||navigator.maxTouchPoints>0,this._pointerEvent=Boolean(window.PointerEvent||window.MSPointerEvent),this._addEventListeners()}var t,n,o;return t=e,o=[{key:"VERSION",get:function(){return"4.6.0"}},{key:"Default",get:function(){return N}},{key:"_jQueryInterface",value:function(t){return this.each((function(){var n=i()(this).data("bs.carousel"),o=T(T({},N),i()(this).data());"object"===k(t)&&(o=T(T({},o),t));var r="string"==typeof t?t:o.slide;if(n||(n=new e(this,o),i()(this).data("bs.carousel",n)),"number"==typeof t)n.to(t);else if("string"==typeof r){if(void 0===n[r])throw new TypeError('No method named "'.concat(r,'"'));n[r]()}else o.interval&&o.ride&&(n.pause(),n.cycle())}))}},{key:"_dataApiClickHandler",value:function(t){var n=s.getSelectorFromElement(this);if(n){var o=i()(n)[0];if(o&&i()(o).hasClass("carousel")){var r=T(T({},i()(o).data()),i()(this).data()),a=this.getAttribute("data-slide-to");a&&(r.interval=!1),e._jQueryInterface.call(i()(o),r),a&&i()(o).data("bs.carousel").to(a),t.preventDefault()}}}}],(n=[{key:"next",value:function(){this._isSliding||this._slide("next")}},{key:"nextWhenVisible",value:function(){var e=i()(this._element);!document.hidden&&e.is(":visible")&&"hidden"!==e.css("visibility")&&this.next()}},{key:"prev",value:function(){this._isSliding||this._slide("prev")}},{key:"pause",value:function(e){e||(this._isPaused=!0),this._element.querySelector(".carousel-item-next, .carousel-item-prev")&&(s.triggerTransitionEnd(this._element),this.cycle(!0)),clearInterval(this._interval),this._interval=null}},{key:"cycle",value:function(e){e||(this._isPaused=!1),this._interval&&(clearInterval(this._interval),this._interval=null),this._config.interval&&!this._isPaused&&(this._updateInterval(),this._interval=setInterval((document.visibilityState?this.nextWhenVisible:this.next).bind(this),this._config.interval))}},{key:"to",value:function(e){var t=this;this._activeElement=this._element.querySelector(".active.carousel-item");var n=this._getItemIndex(this._activeElement);if(!(e>this._items.length-1||e<0))if(this._isSliding)i()(this._element).one(I,(function(){return t.to(e)}));else{if(n===e)return this.pause(),void this.cycle();var o=e>n?"next":"prev";this._slide(o,this._items[e])}}},{key:"dispose",value:function(){i()(this._element).off(j),i.a.removeData(this._element,"bs.carousel"),this._items=null,this._config=null,this._element=null,this._interval=null,this._isPaused=null,this._isSliding=null,this._activeElement=null,this._indicatorsElement=null}},{key:"_getConfig",value:function(e){return e=T(T({},N),e),s.typeCheckConfig("carousel",e,P),e}},{key:"_handleSwipe",value:function(){var e=Math.abs(this.touchDeltaX);if(!(e<=40)){var t=e/this.touchDeltaX;this.touchDeltaX=0,t>0&&this.prev(),t<0&&this.next()}}},{key:"_addEventListeners",value:function(){var e=this;this._config.keyboard&&i()(this._element).on(x,(function(t){return e._keydown(t)})),"hover"===this._config.pause&&i()(this._element).on(L,(function(t){return e.pause(t)})).on(F,(function(t){return e.cycle(t)})),this._config.touch&&this._addTouchEventListeners()}},{key:"_addTouchEventListeners",value:function(){var e=this;if(this._touchSupported){var t=function(t){e._pointerEvent&&V[t.originalEvent.pointerType.toUpperCase()]?e.touchStartX=t.originalEvent.clientX:e._pointerEvent||(e.touchStartX=t.originalEvent.touches[0].clientX)},n=function(t){e._pointerEvent&&V[t.originalEvent.pointerType.toUpperCase()]&&(e.touchDeltaX=t.originalEvent.clientX-e.touchStartX),e._handleSwipe(),"hover"===e._config.pause&&(e.pause(),e.touchTimeout&&clearTimeout(e.touchTimeout),e.touchTimeout=setTimeout((function(t){return e.cycle(t)}),500+e._config.interval))};i()(this._element.querySelectorAll(".carousel-item img")).on(Q,(function(e){return e.preventDefault()})),this._pointerEvent?(i()(this._element).on(H,(function(e){return t(e)})),i()(this._element).on(q,(function(e){return n(e)})),this._element.classList.add("pointer-event")):(i()(this._element).on(R,(function(e){return t(e)})),i()(this._element).on(M,(function(t){return function(t){t.originalEvent.touches&&t.originalEvent.touches.length>1?e.touchDeltaX=0:e.touchDeltaX=t.originalEvent.touches[0].clientX-e.touchStartX}(t)})),i()(this._element).on(B,(function(e){return n(e)})))}}},{key:"_keydown",value:function(e){if(!/input|textarea/i.test(e.target.tagName))switch(e.which){case 37:e.preventDefault(),this.prev();break;case 39:e.preventDefault(),this.next()}}},{key:"_getItemIndex",value:function(e){return this._items=e&&e.parentNode?[].slice.call(e.parentNode.querySelectorAll(".carousel-item")):[],this._items.indexOf(e)}},{key:"_getItemByDirection",value:function(e,t){var n="next"===e,o="prev"===e,i=this._getItemIndex(t),r=this._items.length-1;if((o&&0===i||n&&i===r)&&!this._config.wrap)return t;var a=(i+("prev"===e?-1:1))%this._items.length;return-1===a?this._items[this._items.length-1]:this._items[a]}},{key:"_triggerSlideEvent",value:function(e,t){var n=this._getItemIndex(e),o=this._getItemIndex(this._element.querySelector(".active.carousel-item")),r=i.a.Event(A,{relatedTarget:e,direction:t,from:o,to:n});return i()(this._element).trigger(r),r}},{key:"_setActiveIndicatorElement",value:function(e){if(this._indicatorsElement){var t=[].slice.call(this._indicatorsElement.querySelectorAll(".active"));i()(t).removeClass("active");var n=this._indicatorsElement.children[this._getItemIndex(e)];n&&i()(n).addClass("active")}}},{key:"_updateInterval",value:function(){var e=this._activeElement||this._element.querySelector(".active.carousel-item");if(e){var t=parseInt(e.getAttribute("data-interval"),10);t?(this._config.defaultInterval=this._config.defaultInterval||this._config.interval,this._config.interval=t):this._config.interval=this._config.defaultInterval||this._config.interval}}},{key:"_slide",value:function(e,t){var n,o,r,a=this,l=this._element.querySelector(".active.carousel-item"),c=this._getItemIndex(l),u=t||l&&this._getItemByDirection(e,l),f=this._getItemIndex(u),h=Boolean(this._interval);if("next"===e?(n="carousel-item-left",o="carousel-item-next",r="left"):(n="carousel-item-right",o="carousel-item-prev",r="right"),u&&i()(u).hasClass("active"))this._isSliding=!1;else if(!this._triggerSlideEvent(u,r).isDefaultPrevented()&&l&&u){this._isSliding=!0,h&&this.pause(),this._setActiveIndicatorElement(u),this._activeElement=u;var d=i.a.Event(I,{relatedTarget:u,direction:r,from:c,to:f});if(i()(this._element).hasClass("slide")){i()(u).addClass(o),s.reflow(u),i()(l).addClass(n),i()(u).addClass(n);var p=s.getTransitionDurationFromElement(l);i()(l).one(s.TRANSITION_END,(function(){i()(u).removeClass("".concat(n," ").concat(o)).addClass("active"),i()(l).removeClass("".concat("active"," ").concat(o," ").concat(n)),a._isSliding=!1,setTimeout((function(){return i()(a._element).trigger(d)}),0)})).emulateTransitionEnd(p)}else i()(l).removeClass("active"),i()(u).addClass("active"),this._isSliding=!1,i()(this._element).trigger(d);h&&this.cycle()}}}])&&S(t.prototype,n),o&&S(t,o),e}();i()(document).on(U,"[data-slide], [data-slide-to]",Y._dataApiClickHandler),i()(window).on(W,(function(){for(var e=[].slice.call(document.querySelectorAll('[data-ride="carousel"]')),t=0,n=e.length;t<n;t++){var o=i()(e[t]);Y._jQueryInterface.call(o,o.data())}})),i.a.fn.carousel=Y._jQueryInterface,i.a.fn.carousel.Constructor=Y,i.a.fn.carousel.noConflict=function(){return i.a.fn.carousel=D,Y._jQueryInterface};function z(e){return(z="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function X(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);t&&(o=o.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,o)}return n}function K(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?X(Object(n),!0).forEach((function(t){G(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):X(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function G(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function $(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}var J=".".concat("bs.collapse"),Z=i.a.fn.collapse,ee={toggle:!0,parent:""},te={toggle:"boolean",parent:"(string|element)"},ne="show".concat(J),oe="shown".concat(J),ie="hide".concat(J),re="hidden".concat(J),ae="click".concat(J).concat(".data-api"),se=function(){function e(t,n){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this._isTransitioning=!1,this._element=t,this._config=this._getConfig(n),this._triggerArray=[].slice.call(document.querySelectorAll('[data-toggle="collapse"][href="#'.concat(t.id,'"],')+'[data-toggle="collapse"][data-target="#'.concat(t.id,'"]')));for(var o=[].slice.call(document.querySelectorAll('[data-toggle="collapse"]')),i=0,r=o.length;i<r;i++){var a=o[i],l=s.getSelectorFromElement(a),c=[].slice.call(document.querySelectorAll(l)).filter((function(e){return e===t}));null!==l&&c.length>0&&(this._selector=l,this._triggerArray.push(a))}this._parent=this._config.parent?this._getParent():null,this._config.parent||this._addAriaAndCollapsedClass(this._element,this._triggerArray),this._config.toggle&&this.toggle()}var t,n,o;return t=e,o=[{key:"VERSION",get:function(){return"4.6.0"}},{key:"Default",get:function(){return ee}},{key:"_getTargetFromElement",value:function(e){var t=s.getSelectorFromElement(e);return t?document.querySelector(t):null}},{key:"_jQueryInterface",value:function(t){return this.each((function(){var n=i()(this),o=n.data("bs.collapse"),r=K(K(K({},ee),n.data()),"object"===z(t)&&t?t:{});if(!o&&r.toggle&&"string"==typeof t&&/show|hide/.test(t)&&(r.toggle=!1),o||(o=new e(this,r),n.data("bs.collapse",o)),"string"==typeof t){if(void 0===o[t])throw new TypeError('No method named "'.concat(t,'"'));o[t]()}}))}}],(n=[{key:"toggle",value:function(){i()(this._element).hasClass("show")?this.hide():this.show()}},{key:"show",value:function(){var t,n,o=this;if(!(this._isTransitioning||i()(this._element).hasClass("show")||(this._parent&&0===(t=[].slice.call(this._parent.querySelectorAll(".show, .collapsing")).filter((function(e){return"string"==typeof o._config.parent?e.getAttribute("data-parent")===o._config.parent:e.classList.contains("collapse")}))).length&&(t=null),t&&(n=i()(t).not(this._selector).data("bs.collapse"))&&n._isTransitioning))){var r=i.a.Event(ne);if(i()(this._element).trigger(r),!r.isDefaultPrevented()){t&&(e._jQueryInterface.call(i()(t).not(this._selector),"hide"),n||i()(t).data("bs.collapse",null));var a=this._getDimension();i()(this._element).removeClass("collapse").addClass("collapsing"),this._element.style[a]=0,this._triggerArray.length&&i()(this._triggerArray).removeClass("collapsed").attr("aria-expanded",!0),this.setTransitioning(!0);var l=a[0].toUpperCase()+a.slice(1),c="scroll".concat(l),u=s.getTransitionDurationFromElement(this._element);i()(this._element).one(s.TRANSITION_END,(function(){i()(o._element).removeClass("collapsing").addClass("".concat("collapse"," ").concat("show")),o._element.style[a]="",o.setTransitioning(!1),i()(o._element).trigger(oe)})).emulateTransitionEnd(u),this._element.style[a]="".concat(this._element[c],"px")}}}},{key:"hide",value:function(){var e=this;if(!this._isTransitioning&&i()(this._element).hasClass("show")){var t=i.a.Event(ie);if(i()(this._element).trigger(t),!t.isDefaultPrevented()){var n=this._getDimension();this._element.style[n]="".concat(this._element.getBoundingClientRect()[n],"px"),s.reflow(this._element),i()(this._element).addClass("collapsing").removeClass("".concat("collapse"," ").concat("show"));var o=this._triggerArray.length;if(o>0)for(var r=0;r<o;r++){var a=this._triggerArray[r],l=s.getSelectorFromElement(a);null!==l&&(i()([].slice.call(document.querySelectorAll(l))).hasClass("show")||i()(a).addClass("collapsed").attr("aria-expanded",!1))}this.setTransitioning(!0),this._element.style[n]="";var c=s.getTransitionDurationFromElement(this._element);i()(this._element).one(s.TRANSITION_END,(function(){e.setTransitioning(!1),i()(e._element).removeClass("collapsing").addClass("collapse").trigger(re)})).emulateTransitionEnd(c)}}}},{key:"setTransitioning",value:function(e){this._isTransitioning=e}},{key:"dispose",value:function(){i.a.removeData(this._element,"bs.collapse"),this._config=null,this._parent=null,this._element=null,this._triggerArray=null,this._isTransitioning=null}},{key:"_getConfig",value:function(e){return(e=K(K({},ee),e)).toggle=Boolean(e.toggle),s.typeCheckConfig("collapse",e,te),e}},{key:"_getDimension",value:function(){return i()(this._element).hasClass("width")?"width":"height"}},{key:"_getParent",value:function(){var t,n=this;s.isElement(this._config.parent)?(t=this._config.parent,void 0!==this._config.parent.jquery&&(t=this._config.parent[0])):t=document.querySelector(this._config.parent);var o='[data-toggle="collapse"][data-parent="'.concat(this._config.parent,'"]'),r=[].slice.call(t.querySelectorAll(o));return i()(r).each((function(t,o){n._addAriaAndCollapsedClass(e._getTargetFromElement(o),[o])})),t}},{key:"_addAriaAndCollapsedClass",value:function(e,t){var n=i()(e).hasClass("show");t.length&&i()(t).toggleClass("collapsed",!n).attr("aria-expanded",n)}}])&&$(t.prototype,n),o&&$(t,o),e}();i()(document).on(ae,'[data-toggle="collapse"]',(function(e){"A"===e.currentTarget.tagName&&e.preventDefault();var t=i()(this),n=s.getSelectorFromElement(this),o=[].slice.call(document.querySelectorAll(n));i()(o).each((function(){var e=i()(this),n=e.data("bs.collapse")?"toggle":t.data();se._jQueryInterface.call(e,n)}))})),i.a.fn.collapse=se._jQueryInterface,i.a.fn.collapse.Constructor=se,i.a.fn.collapse.noConflict=function(){return i.a.fn.collapse=Z,se._jQueryInterface};var le=n(2);function ce(e){return(ce="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function ue(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);t&&(o=o.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,o)}return n}function fe(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?ue(Object(n),!0).forEach((function(t){he(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):ue(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function he(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function de(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}var pe=".".concat("bs.dropdown"),me=i.a.fn.dropdown,ge=new RegExp("".concat(38,"|").concat(40,"|").concat(27)),ve="hide".concat(pe),ye="hidden".concat(pe),_e="show".concat(pe),be="shown".concat(pe),we="click".concat(pe),Ee="click".concat(pe).concat(".data-api"),ke="keydown".concat(pe).concat(".data-api"),Oe="keyup".concat(pe).concat(".data-api"),Te={offset:0,flip:!0,boundary:"scrollParent",reference:"toggle",display:"dynamic",popperConfig:null},Ce={offset:"(number|string|function)",flip:"boolean",boundary:"(string|element)",reference:"(string|element)",display:"string",popperConfig:"(null|object)"},Se=function(){function e(t,n){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this._element=t,this._popper=null,this._config=this._getConfig(n),this._menu=this._getMenuElement(),this._inNavbar=this._detectNavbar(),this._addEventListeners()}var t,n,o;return t=e,o=[{key:"VERSION",get:function(){return"4.6.0"}},{key:"Default",get:function(){return Te}},{key:"DefaultType",get:function(){return Ce}},{key:"_jQueryInterface",value:function(t){return this.each((function(){var n=i()(this).data("bs.dropdown"),o="object"===ce(t)?t:null;if(n||(n=new e(this,o),i()(this).data("bs.dropdown",n)),"string"==typeof t){if(void 0===n[t])throw new TypeError('No method named "'.concat(t,'"'));n[t]()}}))}},{key:"_clearMenus",value:function(t){if(!t||3!==t.which&&("keyup"!==t.type||9===t.which))for(var n=[].slice.call(document.querySelectorAll('[data-toggle="dropdown"]')),o=0,r=n.length;o<r;o++){var a=e._getParentFromElement(n[o]),s=i()(n[o]).data("bs.dropdown"),l={relatedTarget:n[o]};if(t&&"click"===t.type&&(l.clickEvent=t),s){var c=s._menu;if(i()(a).hasClass("show")&&!(t&&("click"===t.type&&/input|textarea/i.test(t.target.tagName)||"keyup"===t.type&&9===t.which)&&i.a.contains(a,t.target))){var u=i.a.Event(ve,l);i()(a).trigger(u),u.isDefaultPrevented()||("ontouchstart"in document.documentElement&&i()(document.body).children().off("mouseover",null,i.a.noop),n[o].setAttribute("aria-expanded","false"),s._popper&&s._popper.destroy(),i()(c).removeClass("show"),i()(a).removeClass("show").trigger(i.a.Event(ye,l)))}}}}},{key:"_getParentFromElement",value:function(e){var t,n=s.getSelectorFromElement(e);return n&&(t=document.querySelector(n)),t||e.parentNode}},{key:"_dataApiKeydownHandler",value:function(t){if(!(/input|textarea/i.test(t.target.tagName)?32===t.which||27!==t.which&&(40!==t.which&&38!==t.which||i()(t.target).closest(".dropdown-menu").length):!ge.test(t.which))&&!this.disabled&&!i()(this).hasClass("disabled")){var n=e._getParentFromElement(this),o=i()(n).hasClass("show");if(o||27!==t.which){if(t.preventDefault(),t.stopPropagation(),!o||27===t.which||32===t.which)return 27===t.which&&i()(n.querySelector('[data-toggle="dropdown"]')).trigger("focus"),void i()(this).trigger("click");var r=[].slice.call(n.querySelectorAll(".dropdown-menu .dropdown-item:not(.disabled):not(:disabled)")).filter((function(e){return i()(e).is(":visible")}));if(0!==r.length){var a=r.indexOf(t.target);38===t.which&&a>0&&a--,40===t.which&&a<r.length-1&&a++,a<0&&(a=0),r[a].focus()}}}}}],(n=[{key:"toggle",value:function(){if(!this._element.disabled&&!i()(this._element).hasClass("disabled")){var t=i()(this._menu).hasClass("show");e._clearMenus(),t||this.show(!0)}}},{key:"show",value:function(){var t=arguments.length>0&&void 0!==arguments[0]&&arguments[0];if(!(this._element.disabled||i()(this._element).hasClass("disabled")||i()(this._menu).hasClass("show"))){var n={relatedTarget:this._element},o=i.a.Event(_e,n),r=e._getParentFromElement(this._element);if(i()(r).trigger(o),!o.isDefaultPrevented()){if(!this._inNavbar&&t){if(void 0===le.a)throw new TypeError("Bootstrap's dropdowns require Popper (https://popper.js.org)");var a=this._element;"parent"===this._config.reference?a=r:s.isElement(this._config.reference)&&(a=this._config.reference,void 0!==this._config.reference.jquery&&(a=this._config.reference[0])),"scrollParent"!==this._config.boundary&&i()(r).addClass("position-static"),this._popper=new le.a(a,this._menu,this._getPopperConfig())}"ontouchstart"in document.documentElement&&0===i()(r).closest(".navbar-nav").length&&i()(document.body).children().on("mouseover",null,i.a.noop),this._element.focus(),this._element.setAttribute("aria-expanded",!0),i()(this._menu).toggleClass("show"),i()(r).toggleClass("show").trigger(i.a.Event(be,n))}}}},{key:"hide",value:function(){if(!this._element.disabled&&!i()(this._element).hasClass("disabled")&&i()(this._menu).hasClass("show")){var t={relatedTarget:this._element},n=i.a.Event(ve,t),o=e._getParentFromElement(this._element);i()(o).trigger(n),n.isDefaultPrevented()||(this._popper&&this._popper.destroy(),i()(this._menu).toggleClass("show"),i()(o).toggleClass("show").trigger(i.a.Event(ye,t)))}}},{key:"dispose",value:function(){i.a.removeData(this._element,"bs.dropdown"),i()(this._element).off(pe),this._element=null,this._menu=null,null!==this._popper&&(this._popper.destroy(),this._popper=null)}},{key:"update",value:function(){this._inNavbar=this._detectNavbar(),null!==this._popper&&this._popper.scheduleUpdate()}},{key:"_addEventListeners",value:function(){var e=this;i()(this._element).on(we,(function(t){t.preventDefault(),t.stopPropagation(),e.toggle()}))}},{key:"_getConfig",value:function(e){return e=fe(fe(fe({},this.constructor.Default),i()(this._element).data()),e),s.typeCheckConfig("dropdown",e,this.constructor.DefaultType),e}},{key:"_getMenuElement",value:function(){if(!this._menu){var t=e._getParentFromElement(this._element);t&&(this._menu=t.querySelector(".dropdown-menu"))}return this._menu}},{key:"_getPlacement",value:function(){var e=i()(this._element.parentNode),t="bottom-start";return e.hasClass("dropup")?t=i()(this._menu).hasClass("dropdown-menu-right")?"top-end":"top-start":e.hasClass("dropright")?t="right-start":e.hasClass("dropleft")?t="left-start":i()(this._menu).hasClass("dropdown-menu-right")&&(t="bottom-end"),t}},{key:"_detectNavbar",value:function(){return i()(this._element).closest(".navbar").length>0}},{key:"_getOffset",value:function(){var e=this,t={};return"function"==typeof this._config.offset?t.fn=function(t){return t.offsets=fe(fe({},t.offsets),e._config.offset(t.offsets,e._element)||{}),t}:t.offset=this._config.offset,t}},{key:"_getPopperConfig",value:function(){var e={placement:this._getPlacement(),modifiers:{offset:this._getOffset(),flip:{enabled:this._config.flip},preventOverflow:{boundariesElement:this._config.boundary}}};return"static"===this._config.display&&(e.modifiers.applyStyle={enabled:!1}),fe(fe({},e),this._config.popperConfig)}}])&&de(t.prototype,n),o&&de(t,o),e}();i()(document).on(ke,'[data-toggle="dropdown"]',Se._dataApiKeydownHandler).on(ke,".dropdown-menu",Se._dataApiKeydownHandler).on("".concat(Ee," ").concat(Oe),Se._clearMenus).on(Ee,'[data-toggle="dropdown"]',(function(e){e.preventDefault(),e.stopPropagation(),Se._jQueryInterface.call(i()(this),"toggle")})).on(Ee,".dropdown form",(function(e){e.stopPropagation()})),i.a.fn.dropdown=Se._jQueryInterface,i.a.fn.dropdown.Constructor=Se,i.a.fn.dropdown.noConflict=function(){return i.a.fn.dropdown=me,Se._jQueryInterface};function je(e){return(je="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function De(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);t&&(o=o.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,o)}return n}function Ne(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?De(Object(n),!0).forEach((function(t){Pe(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):De(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function Pe(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function Ae(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}var Ie=".".concat("bs.modal"),xe=i.a.fn.modal,Le={backdrop:!0,keyboard:!0,focus:!0,show:!0},Fe={backdrop:"(boolean|string)",keyboard:"boolean",focus:"boolean",show:"boolean"},Re="hide".concat(Ie),Me="hidePrevented".concat(Ie),Be="hidden".concat(Ie),He="show".concat(Ie),qe="shown".concat(Ie),Qe="focusin".concat(Ie),We="resize".concat(Ie),Ue="click.dismiss".concat(Ie),Ve="keydown.dismiss".concat(Ie),Ye="mouseup.dismiss".concat(Ie),ze="mousedown.dismiss".concat(Ie),Xe="click".concat(Ie).concat(".data-api"),Ke=function(){function e(t,n){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this._config=this._getConfig(n),this._element=t,this._dialog=t.querySelector(".modal-dialog"),this._backdrop=null,this._isShown=!1,this._isBodyOverflowing=!1,this._ignoreBackdropClick=!1,this._isTransitioning=!1,this._scrollbarWidth=0}var t,n,o;return t=e,o=[{key:"VERSION",get:function(){return"4.6.0"}},{key:"Default",get:function(){return Le}},{key:"_jQueryInterface",value:function(t,n){return this.each((function(){var o=i()(this).data("bs.modal"),r=Ne(Ne(Ne({},Le),i()(this).data()),"object"===je(t)&&t?t:{});if(o||(o=new e(this,r),i()(this).data("bs.modal",o)),"string"==typeof t){if(void 0===o[t])throw new TypeError('No method named "'.concat(t,'"'));o[t](n)}else r.show&&o.show(n)}))}}],(n=[{key:"toggle",value:function(e){return this._isShown?this.hide():this.show(e)}},{key:"show",value:function(e){var t=this;if(!this._isShown&&!this._isTransitioning){i()(this._element).hasClass("fade")&&(this._isTransitioning=!0);var n=i.a.Event(He,{relatedTarget:e});i()(this._element).trigger(n),this._isShown||n.isDefaultPrevented()||(this._isShown=!0,this._checkScrollbar(),this._setScrollbar(),this._adjustDialog(),this._setEscapeEvent(),this._setResizeEvent(),i()(this._element).on(Ue,'[data-dismiss="modal"]',(function(e){return t.hide(e)})),i()(this._dialog).on(ze,(function(){i()(t._element).one(Ye,(function(e){i()(e.target).is(t._element)&&(t._ignoreBackdropClick=!0)}))})),this._showBackdrop((function(){return t._showElement(e)})))}}},{key:"hide",value:function(e){var t=this;if(e&&e.preventDefault(),this._isShown&&!this._isTransitioning){var n=i.a.Event(Re);if(i()(this._element).trigger(n),this._isShown&&!n.isDefaultPrevented()){this._isShown=!1;var o=i()(this._element).hasClass("fade");if(o&&(this._isTransitioning=!0),this._setEscapeEvent(),this._setResizeEvent(),i()(document).off(Qe),i()(this._element).removeClass("show"),i()(this._element).off(Ue),i()(this._dialog).off(ze),o){var r=s.getTransitionDurationFromElement(this._element);i()(this._element).one(s.TRANSITION_END,(function(e){return t._hideModal(e)})).emulateTransitionEnd(r)}else this._hideModal()}}}},{key:"dispose",value:function(){[window,this._element,this._dialog].forEach((function(e){return i()(e).off(Ie)})),i()(document).off(Qe),i.a.removeData(this._element,"bs.modal"),this._config=null,this._element=null,this._dialog=null,this._backdrop=null,this._isShown=null,this._isBodyOverflowing=null,this._ignoreBackdropClick=null,this._isTransitioning=null,this._scrollbarWidth=null}},{key:"handleUpdate",value:function(){this._adjustDialog()}},{key:"_getConfig",value:function(e){return e=Ne(Ne({},Le),e),s.typeCheckConfig("modal",e,Fe),e}},{key:"_triggerBackdropTransition",value:function(){var e=this,t=i.a.Event(Me);if(i()(this._element).trigger(t),!t.isDefaultPrevented()){var n=this._element.scrollHeight>document.documentElement.clientHeight;n||(this._element.style.overflowY="hidden"),this._element.classList.add("modal-static");var o=s.getTransitionDurationFromElement(this._dialog);i()(this._element).off(s.TRANSITION_END),i()(this._element).one(s.TRANSITION_END,(function(){e._element.classList.remove("modal-static"),n||i()(e._element).one(s.TRANSITION_END,(function(){e._element.style.overflowY=""})).emulateTransitionEnd(e._element,o)})).emulateTransitionEnd(o),this._element.focus()}}},{key:"_showElement",value:function(e){var t=this,n=i()(this._element).hasClass("fade"),o=this._dialog?this._dialog.querySelector(".modal-body"):null;this._element.parentNode&&this._element.parentNode.nodeType===Node.ELEMENT_NODE||document.body.appendChild(this._element),this._element.style.display="block",this._element.removeAttribute("aria-hidden"),this._element.setAttribute("aria-modal",!0),this._element.setAttribute("role","dialog"),i()(this._dialog).hasClass("modal-dialog-scrollable")&&o?o.scrollTop=0:this._element.scrollTop=0,n&&s.reflow(this._element),i()(this._element).addClass("show"),this._config.focus&&this._enforceFocus();var r=i.a.Event(qe,{relatedTarget:e}),a=function(){t._config.focus&&t._element.focus(),t._isTransitioning=!1,i()(t._element).trigger(r)};if(n){var l=s.getTransitionDurationFromElement(this._dialog);i()(this._dialog).one(s.TRANSITION_END,a).emulateTransitionEnd(l)}else a()}},{key:"_enforceFocus",value:function(){var e=this;i()(document).off(Qe).on(Qe,(function(t){document!==t.target&&e._element!==t.target&&0===i()(e._element).has(t.target).length&&e._element.focus()}))}},{key:"_setEscapeEvent",value:function(){var e=this;this._isShown?i()(this._element).on(Ve,(function(t){e._config.keyboard&&27===t.which?(t.preventDefault(),e.hide()):e._config.keyboard||27!==t.which||e._triggerBackdropTransition()})):this._isShown||i()(this._element).off(Ve)}},{key:"_setResizeEvent",value:function(){var e=this;this._isShown?i()(window).on(We,(function(t){return e.handleUpdate(t)})):i()(window).off(We)}},{key:"_hideModal",value:function(){var e=this;this._element.style.display="none",this._element.setAttribute("aria-hidden",!0),this._element.removeAttribute("aria-modal"),this._element.removeAttribute("role"),this._isTransitioning=!1,this._showBackdrop((function(){i()(document.body).removeClass("modal-open"),e._resetAdjustments(),e._resetScrollbar(),i()(e._element).trigger(Be)}))}},{key:"_removeBackdrop",value:function(){this._backdrop&&(i()(this._backdrop).remove(),this._backdrop=null)}},{key:"_showBackdrop",value:function(e){var t=this,n=i()(this._element).hasClass("fade")?"fade":"";if(this._isShown&&this._config.backdrop){if(this._backdrop=document.createElement("div"),this._backdrop.className="modal-backdrop",n&&this._backdrop.classList.add(n),i()(this._backdrop).appendTo(document.body),i()(this._element).on(Ue,(function(e){t._ignoreBackdropClick?t._ignoreBackdropClick=!1:e.target===e.currentTarget&&("static"===t._config.backdrop?t._triggerBackdropTransition():t.hide())})),n&&s.reflow(this._backdrop),i()(this._backdrop).addClass("show"),!e)return;if(!n)return void e();var o=s.getTransitionDurationFromElement(this._backdrop);i()(this._backdrop).one(s.TRANSITION_END,e).emulateTransitionEnd(o)}else if(!this._isShown&&this._backdrop){i()(this._backdrop).removeClass("show");var r=function(){t._removeBackdrop(),e&&e()};if(i()(this._element).hasClass("fade")){var a=s.getTransitionDurationFromElement(this._backdrop);i()(this._backdrop).one(s.TRANSITION_END,r).emulateTransitionEnd(a)}else r()}else e&&e()}},{key:"_adjustDialog",value:function(){var e=this._element.scrollHeight>document.documentElement.clientHeight;!this._isBodyOverflowing&&e&&(this._element.style.paddingLeft="".concat(this._scrollbarWidth,"px")),this._isBodyOverflowing&&!e&&(this._element.style.paddingRight="".concat(this._scrollbarWidth,"px"))}},{key:"_resetAdjustments",value:function(){this._element.style.paddingLeft="",this._element.style.paddingRight=""}},{key:"_checkScrollbar",value:function(){var e=document.body.getBoundingClientRect();this._isBodyOverflowing=Math.round(e.left+e.right)<window.innerWidth,this._scrollbarWidth=this._getScrollbarWidth()}},{key:"_setScrollbar",value:function(){var e=this;if(this._isBodyOverflowing){var t=[].slice.call(document.querySelectorAll(".fixed-top, .fixed-bottom, .is-fixed, .sticky-top")),n=[].slice.call(document.querySelectorAll(".sticky-top"));i()(t).each((function(t,n){var o=n.style.paddingRight,r=i()(n).css("padding-right");i()(n).data("padding-right",o).css("padding-right","".concat(parseFloat(r)+e._scrollbarWidth,"px"))})),i()(n).each((function(t,n){var o=n.style.marginRight,r=i()(n).css("margin-right");i()(n).data("margin-right",o).css("margin-right","".concat(parseFloat(r)-e._scrollbarWidth,"px"))}));var o=document.body.style.paddingRight,r=i()(document.body).css("padding-right");i()(document.body).data("padding-right",o).css("padding-right","".concat(parseFloat(r)+this._scrollbarWidth,"px"))}i()(document.body).addClass("modal-open")}},{key:"_resetScrollbar",value:function(){var e=[].slice.call(document.querySelectorAll(".fixed-top, .fixed-bottom, .is-fixed, .sticky-top"));i()(e).each((function(e,t){var n=i()(t).data("padding-right");i()(t).removeData("padding-right"),t.style.paddingRight=n||""}));var t=[].slice.call(document.querySelectorAll("".concat(".sticky-top")));i()(t).each((function(e,t){var n=i()(t).data("margin-right");void 0!==n&&i()(t).css("margin-right",n).removeData("margin-right")}));var n=i()(document.body).data("padding-right");i()(document.body).removeData("padding-right"),document.body.style.paddingRight=n||""}},{key:"_getScrollbarWidth",value:function(){var e=document.createElement("div");e.className="modal-scrollbar-measure",document.body.appendChild(e);var t=e.getBoundingClientRect().width-e.clientWidth;return document.body.removeChild(e),t}}])&&Ae(t.prototype,n),o&&Ae(t,o),e}();i()(document).on(Xe,'[data-toggle="modal"]',(function(e){var t,n=this,o=s.getSelectorFromElement(this);o&&(t=document.querySelector(o));var r=i()(t).data("bs.modal")?"toggle":Ne(Ne({},i()(t).data()),i()(this).data());"A"!==this.tagName&&"AREA"!==this.tagName||e.preventDefault();var a=i()(t).one(He,(function(e){e.isDefaultPrevented()||a.one(Be,(function(){i()(n).is(":visible")&&n.focus()}))}));Ke._jQueryInterface.call(i()(t),r,this)})),i.a.fn.modal=Ke._jQueryInterface,i.a.fn.modal.Constructor=Ke,i.a.fn.modal.noConflict=function(){return i.a.fn.modal=xe,Ke._jQueryInterface};var Ge=["background","cite","href","itemtype","longdesc","poster","src","xlink:href"],$e={"*":["class","dir","id","lang","role",/^aria-[\w-]*$/i],a:["target","href","title","rel"],area:[],b:[],br:[],col:[],code:[],div:[],em:[],hr:[],h1:[],h2:[],h3:[],h4:[],h5:[],h6:[],i:[],img:["src","srcset","alt","title","width","height"],li:[],ol:[],p:[],pre:[],s:[],small:[],span:[],sub:[],sup:[],strong:[],u:[],ul:[]},Je=/^(?:(?:https?|mailto|ftp|tel|file):|[^#&/:?]*(?:[#/?]|$))/gi,Ze=/^data:(?:image\/(?:bmp|gif|jpeg|jpg|png|tiff|webp)|video\/(?:mpeg|mp4|ogg|webm)|audio\/(?:mp3|oga|ogg|opus));base64,[\d+/a-z]+=*$/i;function et(e,t,n){if(0===e.length)return e;if(n&&"function"==typeof n)return n(e);for(var o=(new window.DOMParser).parseFromString(e,"text/html"),i=Object.keys(t),r=[].slice.call(o.body.querySelectorAll("*")),a=function(e,n){var o=r[e],a=o.nodeName.toLowerCase();if(-1===i.indexOf(o.nodeName.toLowerCase()))return o.parentNode.removeChild(o),"continue";var s=[].slice.call(o.attributes),l=[].concat(t["*"]||[],t[a]||[]);s.forEach((function(e){(function(e,t){var n=e.nodeName.toLowerCase();if(-1!==t.indexOf(n))return-1===Ge.indexOf(n)||Boolean(e.nodeValue.match(Je)||e.nodeValue.match(Ze));for(var o=t.filter((function(e){return e instanceof RegExp})),i=0,r=o.length;i<r;i++)if(n.match(o[i]))return!0;return!1})(e,l)||o.removeAttribute(e.nodeName)}))},s=0,l=r.length;s<l;s++)a(s);return o.body.innerHTML}function tt(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);t&&(o=o.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,o)}return n}function nt(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?tt(Object(n),!0).forEach((function(t){ot(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):tt(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function ot(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function it(e){return(it="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function rt(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}var at=".".concat("bs.tooltip"),st=i.a.fn.tooltip,lt=new RegExp("(^|\\s)".concat("bs-tooltip","\\S+"),"g"),ct=["sanitize","whiteList","sanitizeFn"],ut={animation:"boolean",template:"string",title:"(string|element|function)",trigger:"string",delay:"(number|object)",html:"boolean",selector:"(string|boolean)",placement:"(string|function)",offset:"(number|string|function)",container:"(string|element|boolean)",fallbackPlacement:"(string|array)",boundary:"(string|element)",customClass:"(string|function)",sanitize:"boolean",sanitizeFn:"(null|function)",whiteList:"object",popperConfig:"(null|object)"},ft={AUTO:"auto",TOP:"top",RIGHT:"right",BOTTOM:"bottom",LEFT:"left"},ht={animation:!0,template:'<div class="tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>',trigger:"hover focus",title:"",delay:0,html:!1,selector:!1,placement:"top",offset:0,container:!1,fallbackPlacement:"flip",boundary:"scrollParent",customClass:"",sanitize:!0,sanitizeFn:null,whiteList:$e,popperConfig:null},dt={HIDE:"hide".concat(at),HIDDEN:"hidden".concat(at),SHOW:"show".concat(at),SHOWN:"shown".concat(at),INSERTED:"inserted".concat(at),CLICK:"click".concat(at),FOCUSIN:"focusin".concat(at),FOCUSOUT:"focusout".concat(at),MOUSEENTER:"mouseenter".concat(at),MOUSELEAVE:"mouseleave".concat(at)},pt=function(){function e(t,n){if(function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),void 0===le.a)throw new TypeError("Bootstrap's tooltips require Popper (https://popper.js.org)");this._isEnabled=!0,this._timeout=0,this._hoverState="",this._activeTrigger={},this._popper=null,this.element=t,this.config=this._getConfig(n),this.tip=null,this._setListeners()}var t,n,o;return t=e,o=[{key:"VERSION",get:function(){return"4.6.0"}},{key:"Default",get:function(){return ht}},{key:"NAME",get:function(){return"tooltip"}},{key:"DATA_KEY",get:function(){return"bs.tooltip"}},{key:"Event",get:function(){return dt}},{key:"EVENT_KEY",get:function(){return at}},{key:"DefaultType",get:function(){return ut}},{key:"_jQueryInterface",value:function(t){return this.each((function(){var n=i()(this),o=n.data("bs.tooltip"),r="object"===it(t)&&t;if((o||!/dispose|hide/.test(t))&&(o||(o=new e(this,r),n.data("bs.tooltip",o)),"string"==typeof t)){if(void 0===o[t])throw new TypeError('No method named "'.concat(t,'"'));o[t]()}}))}}],(n=[{key:"enable",value:function(){this._isEnabled=!0}},{key:"disable",value:function(){this._isEnabled=!1}},{key:"toggleEnabled",value:function(){this._isEnabled=!this._isEnabled}},{key:"toggle",value:function(e){if(this._isEnabled)if(e){var t=this.constructor.DATA_KEY,n=i()(e.currentTarget).data(t);n||(n=new this.constructor(e.currentTarget,this._getDelegateConfig()),i()(e.currentTarget).data(t,n)),n._activeTrigger.click=!n._activeTrigger.click,n._isWithActiveTrigger()?n._enter(null,n):n._leave(null,n)}else{if(i()(this.getTipElement()).hasClass("show"))return void this._leave(null,this);this._enter(null,this)}}},{key:"dispose",value:function(){clearTimeout(this._timeout),i.a.removeData(this.element,this.constructor.DATA_KEY),i()(this.element).off(this.constructor.EVENT_KEY),i()(this.element).closest(".modal").off("hide.bs.modal",this._hideModalHandler),this.tip&&i()(this.tip).remove(),this._isEnabled=null,this._timeout=null,this._hoverState=null,this._activeTrigger=null,this._popper&&this._popper.destroy(),this._popper=null,this.element=null,this.config=null,this.tip=null}},{key:"show",value:function(){var e=this;if("none"===i()(this.element).css("display"))throw new Error("Please use show on visible elements");var t=i.a.Event(this.constructor.Event.SHOW);if(this.isWithContent()&&this._isEnabled){i()(this.element).trigger(t);var n=s.findShadowRoot(this.element),o=i.a.contains(null!==n?n:this.element.ownerDocument.documentElement,this.element);if(t.isDefaultPrevented()||!o)return;var r=this.getTipElement(),a=s.getUID(this.constructor.NAME);r.setAttribute("id",a),this.element.setAttribute("aria-describedby",a),this.setContent(),this.config.animation&&i()(r).addClass("fade");var l="function"==typeof this.config.placement?this.config.placement.call(this,r,this.element):this.config.placement,c=this._getAttachment(l);this.addAttachmentClass(c);var u=this._getContainer();i()(r).data(this.constructor.DATA_KEY,this),i.a.contains(this.element.ownerDocument.documentElement,this.tip)||i()(r).appendTo(u),i()(this.element).trigger(this.constructor.Event.INSERTED),this._popper=new le.a(this.element,r,this._getPopperConfig(c)),i()(r).addClass("show"),i()(r).addClass(this.config.customClass),"ontouchstart"in document.documentElement&&i()(document.body).children().on("mouseover",null,i.a.noop);var f=function(){e.config.animation&&e._fixTransition();var t=e._hoverState;e._hoverState=null,i()(e.element).trigger(e.constructor.Event.SHOWN),"out"===t&&e._leave(null,e)};if(i()(this.tip).hasClass("fade")){var h=s.getTransitionDurationFromElement(this.tip);i()(this.tip).one(s.TRANSITION_END,f).emulateTransitionEnd(h)}else f()}}},{key:"hide",value:function(e){var t=this,n=this.getTipElement(),o=i.a.Event(this.constructor.Event.HIDE),r=function(){"show"!==t._hoverState&&n.parentNode&&n.parentNode.removeChild(n),t._cleanTipClass(),t.element.removeAttribute("aria-describedby"),i()(t.element).trigger(t.constructor.Event.HIDDEN),null!==t._popper&&t._popper.destroy(),e&&e()};if(i()(this.element).trigger(o),!o.isDefaultPrevented()){if(i()(n).removeClass("show"),"ontouchstart"in document.documentElement&&i()(document.body).children().off("mouseover",null,i.a.noop),this._activeTrigger.click=!1,this._activeTrigger.focus=!1,this._activeTrigger.hover=!1,i()(this.tip).hasClass("fade")){var a=s.getTransitionDurationFromElement(n);i()(n).one(s.TRANSITION_END,r).emulateTransitionEnd(a)}else r();this._hoverState=""}}},{key:"update",value:function(){null!==this._popper&&this._popper.scheduleUpdate()}},{key:"isWithContent",value:function(){return Boolean(this.getTitle())}},{key:"addAttachmentClass",value:function(e){i()(this.getTipElement()).addClass("".concat("bs-tooltip","-").concat(e))}},{key:"getTipElement",value:function(){return this.tip=this.tip||i()(this.config.template)[0],this.tip}},{key:"setContent",value:function(){var e=this.getTipElement();this.setElementContent(i()(e.querySelectorAll(".tooltip-inner")),this.getTitle()),i()(e).removeClass("".concat("fade"," ").concat("show"))}},{key:"setElementContent",value:function(e,t){"object"!==it(t)||!t.nodeType&&!t.jquery?this.config.html?(this.config.sanitize&&(t=et(t,this.config.whiteList,this.config.sanitizeFn)),e.html(t)):e.text(t):this.config.html?i()(t).parent().is(e)||e.empty().append(t):e.text(i()(t).text())}},{key:"getTitle",value:function(){var e=this.element.getAttribute("data-original-title");return e||(e="function"==typeof this.config.title?this.config.title.call(this.element):this.config.title),e}},{key:"_getPopperConfig",value:function(e){var t=this;return nt(nt({},{placement:e,modifiers:{offset:this._getOffset(),flip:{behavior:this.config.fallbackPlacement},arrow:{element:".arrow"},preventOverflow:{boundariesElement:this.config.boundary}},onCreate:function(e){e.originalPlacement!==e.placement&&t._handlePopperPlacementChange(e)},onUpdate:function(e){return t._handlePopperPlacementChange(e)}}),this.config.popperConfig)}},{key:"_getOffset",value:function(){var e=this,t={};return"function"==typeof this.config.offset?t.fn=function(t){return t.offsets=nt(nt({},t.offsets),e.config.offset(t.offsets,e.element)||{}),t}:t.offset=this.config.offset,t}},{key:"_getContainer",value:function(){return!1===this.config.container?document.body:s.isElement(this.config.container)?i()(this.config.container):i()(document).find(this.config.container)}},{key:"_getAttachment",value:function(e){return ft[e.toUpperCase()]}},{key:"_setListeners",value:function(){var e=this;this.config.trigger.split(" ").forEach((function(t){if("click"===t)i()(e.element).on(e.constructor.Event.CLICK,e.config.selector,(function(t){return e.toggle(t)}));else if("manual"!==t){var n="hover"===t?e.constructor.Event.MOUSEENTER:e.constructor.Event.FOCUSIN,o="hover"===t?e.constructor.Event.MOUSELEAVE:e.constructor.Event.FOCUSOUT;i()(e.element).on(n,e.config.selector,(function(t){return e._enter(t)})).on(o,e.config.selector,(function(t){return e._leave(t)}))}})),this._hideModalHandler=function(){e.element&&e.hide()},i()(this.element).closest(".modal").on("hide.bs.modal",this._hideModalHandler),this.config.selector?this.config=nt(nt({},this.config),{},{trigger:"manual",selector:""}):this._fixTitle()}},{key:"_fixTitle",value:function(){var e=it(this.element.getAttribute("data-original-title"));(this.element.getAttribute("title")||"string"!==e)&&(this.element.setAttribute("data-original-title",this.element.getAttribute("title")||""),this.element.setAttribute("title",""))}},{key:"_enter",value:function(e,t){var n=this.constructor.DATA_KEY;(t=t||i()(e.currentTarget).data(n))||(t=new this.constructor(e.currentTarget,this._getDelegateConfig()),i()(e.currentTarget).data(n,t)),e&&(t._activeTrigger["focusin"===e.type?"focus":"hover"]=!0),i()(t.getTipElement()).hasClass("show")||"show"===t._hoverState?t._hoverState="show":(clearTimeout(t._timeout),t._hoverState="show",t.config.delay&&t.config.delay.show?t._timeout=setTimeout((function(){"show"===t._hoverState&&t.show()}),t.config.delay.show):t.show())}},{key:"_leave",value:function(e,t){var n=this.constructor.DATA_KEY;(t=t||i()(e.currentTarget).data(n))||(t=new this.constructor(e.currentTarget,this._getDelegateConfig()),i()(e.currentTarget).data(n,t)),e&&(t._activeTrigger["focusout"===e.type?"focus":"hover"]=!1),t._isWithActiveTrigger()||(clearTimeout(t._timeout),t._hoverState="out",t.config.delay&&t.config.delay.hide?t._timeout=setTimeout((function(){"out"===t._hoverState&&t.hide()}),t.config.delay.hide):t.hide())}},{key:"_isWithActiveTrigger",value:function(){for(var e in this._activeTrigger)if(this._activeTrigger[e])return!0;return!1}},{key:"_getConfig",value:function(e){var t=i()(this.element).data();return Object.keys(t).forEach((function(e){-1!==ct.indexOf(e)&&delete t[e]})),"number"==typeof(e=nt(nt(nt({},this.constructor.Default),t),"object"===it(e)&&e?e:{})).delay&&(e.delay={show:e.delay,hide:e.delay}),"number"==typeof e.title&&(e.title=e.title.toString()),"number"==typeof e.content&&(e.content=e.content.toString()),s.typeCheckConfig("tooltip",e,this.constructor.DefaultType),e.sanitize&&(e.template=et(e.template,e.whiteList,e.sanitizeFn)),e}},{key:"_getDelegateConfig",value:function(){var e={};if(this.config)for(var t in this.config)this.constructor.Default[t]!==this.config[t]&&(e[t]=this.config[t]);return e}},{key:"_cleanTipClass",value:function(){var e=i()(this.getTipElement()),t=e.attr("class").match(lt);null!==t&&t.length&&e.removeClass(t.join(""))}},{key:"_handlePopperPlacementChange",value:function(e){this.tip=e.instance.popper,this._cleanTipClass(),this.addAttachmentClass(this._getAttachment(e.placement))}},{key:"_fixTransition",value:function(){var e=this.getTipElement(),t=this.config.animation;null===e.getAttribute("x-placement")&&(i()(e).removeClass("fade"),this.config.animation=!1,this.hide(),this.show(),this.config.animation=t)}}])&&rt(t.prototype,n),o&&rt(t,o),e}();i.a.fn.tooltip=pt._jQueryInterface,i.a.fn.tooltip.Constructor=pt,i.a.fn.tooltip.noConflict=function(){return i.a.fn.tooltip=st,pt._jQueryInterface};var mt=pt;function gt(e){return(gt="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function vt(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function yt(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}function _t(e,t){return(_t=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e})(e,t)}function bt(e){var t=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){}))),!0}catch(e){return!1}}();return function(){var n,o=Et(e);if(t){var i=Et(this).constructor;n=Reflect.construct(o,arguments,i)}else n=o.apply(this,arguments);return wt(this,n)}}function wt(e,t){if(t&&("object"===gt(t)||"function"==typeof t))return t;if(void 0!==t)throw new TypeError("Derived constructors may only return object or undefined");return function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e)}function Et(e){return(Et=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}function kt(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);t&&(o=o.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,o)}return n}function Ot(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?kt(Object(n),!0).forEach((function(t){Tt(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):kt(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function Tt(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}var Ct=".".concat("bs.popover"),St=i.a.fn.popover,jt=new RegExp("(^|\\s)".concat("bs-popover","\\S+"),"g"),Dt=Ot(Ot({},mt.Default),{},{placement:"right",trigger:"click",content:"",template:'<div class="popover" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'}),Nt=Ot(Ot({},mt.DefaultType),{},{content:"(string|element|function)"}),Pt={HIDE:"hide".concat(Ct),HIDDEN:"hidden".concat(Ct),SHOW:"show".concat(Ct),SHOWN:"shown".concat(Ct),INSERTED:"inserted".concat(Ct),CLICK:"click".concat(Ct),FOCUSIN:"focusin".concat(Ct),FOCUSOUT:"focusout".concat(Ct),MOUSEENTER:"mouseenter".concat(Ct),MOUSELEAVE:"mouseleave".concat(Ct)},At=function(e){!function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),t&&_t(e,t)}(a,e);var t,n,o,r=bt(a);function a(){return vt(this,a),r.apply(this,arguments)}return t=a,o=[{key:"VERSION",get:function(){return"4.6.0"}},{key:"Default",get:function(){return Dt}},{key:"NAME",get:function(){return"popover"}},{key:"DATA_KEY",get:function(){return"bs.popover"}},{key:"Event",get:function(){return Pt}},{key:"EVENT_KEY",get:function(){return Ct}},{key:"DefaultType",get:function(){return Nt}},{key:"_jQueryInterface",value:function(e){return this.each((function(){var t=i()(this).data("bs.popover"),n="object"===gt(e)?e:null;if((t||!/dispose|hide/.test(e))&&(t||(t=new a(this,n),i()(this).data("bs.popover",t)),"string"==typeof e)){if(void 0===t[e])throw new TypeError('No method named "'.concat(e,'"'));t[e]()}}))}}],(n=[{key:"isWithContent",value:function(){return this.getTitle()||this._getContent()}},{key:"addAttachmentClass",value:function(e){i()(this.getTipElement()).addClass("".concat("bs-popover","-").concat(e))}},{key:"getTipElement",value:function(){return this.tip=this.tip||i()(this.config.template)[0],this.tip}},{key:"setContent",value:function(){var e=i()(this.getTipElement());this.setElementContent(e.find(".popover-header"),this.getTitle());var t=this._getContent();"function"==typeof t&&(t=t.call(this.element)),this.setElementContent(e.find(".popover-body"),t),e.removeClass("".concat("fade"," ").concat("show"))}},{key:"_getContent",value:function(){return this.element.getAttribute("data-content")||this.config.content}},{key:"_cleanTipClass",value:function(){var e=i()(this.getTipElement()),t=e.attr("class").match(jt);null!==t&&t.length>0&&e.removeClass(t.join(""))}}])&&yt(t.prototype,n),o&&yt(t,o),a}(mt);i.a.fn.popover=At._jQueryInterface,i.a.fn.popover.Constructor=At,i.a.fn.popover.noConflict=function(){return i.a.fn.popover=St,At._jQueryInterface};function It(e){return(It="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function xt(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);t&&(o=o.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,o)}return n}function Lt(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?xt(Object(n),!0).forEach((function(t){Ft(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):xt(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function Ft(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function Rt(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}var Mt=".".concat("bs.scrollspy"),Bt=i.a.fn.scrollspy,Ht={offset:10,method:"auto",target:""},qt={offset:"number",method:"string",target:"(string|element)"},Qt="activate".concat(Mt),Wt="scroll".concat(Mt),Ut="load".concat(Mt).concat(".data-api"),Vt=function(){function e(t,n){var o=this;!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this._element=t,this._scrollElement="BODY"===t.tagName?window:t,this._config=this._getConfig(n),this._selector="".concat(this._config.target," ").concat(".nav-link",",")+"".concat(this._config.target," ").concat(".list-group-item",",")+"".concat(this._config.target," ").concat(".dropdown-item"),this._offsets=[],this._targets=[],this._activeTarget=null,this._scrollHeight=0,i()(this._scrollElement).on(Wt,(function(e){return o._process(e)})),this.refresh(),this._process()}var t,n,o;return t=e,o=[{key:"VERSION",get:function(){return"4.6.0"}},{key:"Default",get:function(){return Ht}},{key:"_jQueryInterface",value:function(t){return this.each((function(){var n=i()(this).data("bs.scrollspy"),o="object"===It(t)&&t;if(n||(n=new e(this,o),i()(this).data("bs.scrollspy",n)),"string"==typeof t){if(void 0===n[t])throw new TypeError('No method named "'.concat(t,'"'));n[t]()}}))}}],(n=[{key:"refresh",value:function(){var e=this,t=this._scrollElement===this._scrollElement.window?"offset":"position",n="auto"===this._config.method?t:this._config.method,o="position"===n?this._getScrollTop():0;this._offsets=[],this._targets=[],this._scrollHeight=this._getScrollHeight(),[].slice.call(document.querySelectorAll(this._selector)).map((function(e){var t,r=s.getSelectorFromElement(e);if(r&&(t=document.querySelector(r)),t){var a=t.getBoundingClientRect();if(a.width||a.height)return[i()(t)[n]().top+o,r]}return null})).filter((function(e){return e})).sort((function(e,t){return e[0]-t[0]})).forEach((function(t){e._offsets.push(t[0]),e._targets.push(t[1])}))}},{key:"dispose",value:function(){i.a.removeData(this._element,"bs.scrollspy"),i()(this._scrollElement).off(Mt),this._element=null,this._scrollElement=null,this._config=null,this._selector=null,this._offsets=null,this._targets=null,this._activeTarget=null,this._scrollHeight=null}},{key:"_getConfig",value:function(e){if("string"!=typeof(e=Lt(Lt({},Ht),"object"===It(e)&&e?e:{})).target&&s.isElement(e.target)){var t=i()(e.target).attr("id");t||(t=s.getUID("scrollspy"),i()(e.target).attr("id",t)),e.target="#".concat(t)}return s.typeCheckConfig("scrollspy",e,qt),e}},{key:"_getScrollTop",value:function(){return this._scrollElement===window?this._scrollElement.pageYOffset:this._scrollElement.scrollTop}},{key:"_getScrollHeight",value:function(){return this._scrollElement.scrollHeight||Math.max(document.body.scrollHeight,document.documentElement.scrollHeight)}},{key:"_getOffsetHeight",value:function(){return this._scrollElement===window?window.innerHeight:this._scrollElement.getBoundingClientRect().height}},{key:"_process",value:function(){var e=this._getScrollTop()+this._config.offset,t=this._getScrollHeight(),n=this._config.offset+t-this._getOffsetHeight();if(this._scrollHeight!==t&&this.refresh(),e>=n){var o=this._targets[this._targets.length-1];this._activeTarget!==o&&this._activate(o)}else{if(this._activeTarget&&e<this._offsets[0]&&this._offsets[0]>0)return this._activeTarget=null,void this._clear();for(var i=this._offsets.length;i--;)this._activeTarget!==this._targets[i]&&e>=this._offsets[i]&&(void 0===this._offsets[i+1]||e<this._offsets[i+1])&&this._activate(this._targets[i])}}},{key:"_activate",value:function(e){this._activeTarget=e,this._clear();var t=this._selector.split(",").map((function(t){return"".concat(t,'[data-target="').concat(e,'"],').concat(t,'[href="').concat(e,'"]')})),n=i()([].slice.call(document.querySelectorAll(t.join(","))));n.hasClass("dropdown-item")?(n.closest(".dropdown").find(".dropdown-toggle").addClass("active"),n.addClass("active")):(n.addClass("active"),n.parents(".nav, .list-group").prev("".concat(".nav-link",", ").concat(".list-group-item")).addClass("active"),n.parents(".nav, .list-group").prev(".nav-item").children(".nav-link").addClass("active")),i()(this._scrollElement).trigger(Qt,{relatedTarget:e})}},{key:"_clear",value:function(){[].slice.call(document.querySelectorAll(this._selector)).filter((function(e){return e.classList.contains("active")})).forEach((function(e){return e.classList.remove("active")}))}}])&&Rt(t.prototype,n),o&&Rt(t,o),e}();i()(window).on(Ut,(function(){for(var e=[].slice.call(document.querySelectorAll('[data-spy="scroll"]')),t=e.length;t--;){var n=i()(e[t]);Vt._jQueryInterface.call(n,n.data())}})),i.a.fn.scrollspy=Vt._jQueryInterface,i.a.fn.scrollspy.Constructor=Vt,i.a.fn.scrollspy.noConflict=function(){return i.a.fn.scrollspy=Bt,Vt._jQueryInterface};function Yt(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}var zt=".".concat("bs.tab"),Xt=i.a.fn.tab,Kt="hide".concat(zt),Gt="hidden".concat(zt),$t="show".concat(zt),Jt="shown".concat(zt),Zt="click".concat(zt).concat(".data-api"),en=function(){function e(t){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this._element=t}var t,n,o;return t=e,o=[{key:"VERSION",get:function(){return"4.6.0"}},{key:"_jQueryInterface",value:function(t){return this.each((function(){var n=i()(this),o=n.data("bs.tab");if(o||(o=new e(this),n.data("bs.tab",o)),"string"==typeof t){if(void 0===o[t])throw new TypeError('No method named "'.concat(t,'"'));o[t]()}}))}}],(n=[{key:"show",value:function(){var e=this;if(!(this._element.parentNode&&this._element.parentNode.nodeType===Node.ELEMENT_NODE&&i()(this._element).hasClass("active")||i()(this._element).hasClass("disabled"))){var t,n,o=i()(this._element).closest(".nav, .list-group")[0],r=s.getSelectorFromElement(this._element);if(o){var a="UL"===o.nodeName||"OL"===o.nodeName?"> li > .active":".active";n=(n=i.a.makeArray(i()(o).find(a)))[n.length-1]}var l=i.a.Event(Kt,{relatedTarget:this._element}),c=i.a.Event($t,{relatedTarget:n});if(n&&i()(n).trigger(l),i()(this._element).trigger(c),!c.isDefaultPrevented()&&!l.isDefaultPrevented()){r&&(t=document.querySelector(r)),this._activate(this._element,o);var u=function(){var t=i.a.Event(Gt,{relatedTarget:e._element}),o=i.a.Event(Jt,{relatedTarget:n});i()(n).trigger(t),i()(e._element).trigger(o)};t?this._activate(t,t.parentNode,u):u()}}}},{key:"dispose",value:function(){i.a.removeData(this._element,"bs.tab"),this._element=null}},{key:"_activate",value:function(e,t,n){var o=this,r=(!t||"UL"!==t.nodeName&&"OL"!==t.nodeName?i()(t).children(".active"):i()(t).find("> li > .active"))[0],a=n&&r&&i()(r).hasClass("fade"),l=function(){return o._transitionComplete(e,r,n)};if(r&&a){var c=s.getTransitionDurationFromElement(r);i()(r).removeClass("show").one(s.TRANSITION_END,l).emulateTransitionEnd(c)}else l()}},{key:"_transitionComplete",value:function(e,t,n){if(t){i()(t).removeClass("active");var o=i()(t.parentNode).find("> .dropdown-menu .active")[0];o&&i()(o).removeClass("active"),"tab"===t.getAttribute("role")&&t.setAttribute("aria-selected",!1)}if(i()(e).addClass("active"),"tab"===e.getAttribute("role")&&e.setAttribute("aria-selected",!0),s.reflow(e),e.classList.contains("fade")&&e.classList.add("show"),e.parentNode&&i()(e.parentNode).hasClass("dropdown-menu")){var r=i()(e).closest(".dropdown")[0];if(r){var a=[].slice.call(r.querySelectorAll(".dropdown-toggle"));i()(a).addClass("active")}e.setAttribute("aria-expanded",!0)}n&&n()}}])&&Yt(t.prototype,n),o&&Yt(t,o),e}();i()(document).on(Zt,'[data-toggle="tab"], [data-toggle="pill"], [data-toggle="list"]',(function(e){e.preventDefault(),en._jQueryInterface.call(i()(this),"show")})),i.a.fn.tab=en._jQueryInterface,i.a.fn.tab.Constructor=en,i.a.fn.tab.noConflict=function(){return i.a.fn.tab=Xt,en._jQueryInterface};function tn(e){return(tn="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function nn(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);t&&(o=o.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,o)}return n}function on(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?nn(Object(n),!0).forEach((function(t){rn(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):nn(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function rn(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function an(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}var sn=".".concat("bs.toast"),ln=i.a.fn.toast,cn="click.dismiss".concat(sn),un="hide".concat(sn),fn="hidden".concat(sn),hn="show".concat(sn),dn="shown".concat(sn),pn={animation:"boolean",autohide:"boolean",delay:"number"},mn={animation:!0,autohide:!0,delay:500},gn=function(){function e(t,n){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this._element=t,this._config=this._getConfig(n),this._timeout=null,this._setListeners()}var t,n,o;return t=e,o=[{key:"VERSION",get:function(){return"4.6.0"}},{key:"DefaultType",get:function(){return pn}},{key:"Default",get:function(){return mn}},{key:"_jQueryInterface",value:function(t){return this.each((function(){var n=i()(this),o=n.data("bs.toast"),r="object"===tn(t)&&t;if(o||(o=new e(this,r),n.data("bs.toast",o)),"string"==typeof t){if(void 0===o[t])throw new TypeError('No method named "'.concat(t,'"'));o[t](this)}}))}}],(n=[{key:"show",value:function(){var e=this,t=i.a.Event(hn);if(i()(this._element).trigger(t),!t.isDefaultPrevented()){this._clearTimeout(),this._config.animation&&this._element.classList.add("fade");var n=function(){e._element.classList.remove("showing"),e._element.classList.add("show"),i()(e._element).trigger(dn),e._config.autohide&&(e._timeout=setTimeout((function(){e.hide()}),e._config.delay))};if(this._element.classList.remove("hide"),s.reflow(this._element),this._element.classList.add("showing"),this._config.animation){var o=s.getTransitionDurationFromElement(this._element);i()(this._element).one(s.TRANSITION_END,n).emulateTransitionEnd(o)}else n()}}},{key:"hide",value:function(){if(this._element.classList.contains("show")){var e=i.a.Event(un);i()(this._element).trigger(e),e.isDefaultPrevented()||this._close()}}},{key:"dispose",value:function(){this._clearTimeout(),this._element.classList.contains("show")&&this._element.classList.remove("show"),i()(this._element).off(cn),i.a.removeData(this._element,"bs.toast"),this._element=null,this._config=null}},{key:"_getConfig",value:function(e){return e=on(on(on({},mn),i()(this._element).data()),"object"===tn(e)&&e?e:{}),s.typeCheckConfig("toast",e,this.constructor.DefaultType),e}},{key:"_setListeners",value:function(){var e=this;i()(this._element).on(cn,'[data-dismiss="toast"]',(function(){return e.hide()}))}},{key:"_close",value:function(){var e=this,t=function(){e._element.classList.add("hide"),i()(e._element).trigger(fn)};if(this._element.classList.remove("show"),this._config.animation){var n=s.getTransitionDurationFromElement(this._element);i()(this._element).one(s.TRANSITION_END,t).emulateTransitionEnd(n)}else t()}},{key:"_clearTimeout",value:function(){clearTimeout(this._timeout),this._timeout=null}}])&&an(t.prototype,n),o&&an(t,o),e}();i.a.fn.toast=gn._jQueryInterface,i.a.fn.toast.Constructor=gn,i.a.fn.toast.noConflict=function(){return i.a.fn.toast=ln,gn._jQueryInterface}}]);
//# sourceMappingURL=bootstrap.bundle.js.map