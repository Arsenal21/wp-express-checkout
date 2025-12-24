/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js ***!
  \*********************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ _arrayLikeToArray; }
/* harmony export */ });
function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;
  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];
  return arr2;
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js ***!
  \*******************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ _arrayWithHoles; }
/* harmony export */ });
function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js ***!
  \*********************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ _asyncToGenerator; }
/* harmony export */ });
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) {
  try {
    var info = gen[key](arg);
    var value = info.value;
  } catch (error) {
    reject(error);
    return;
  }
  if (info.done) {
    resolve(value);
  } else {
    Promise.resolve(value).then(_next, _throw);
  }
}
function _asyncToGenerator(fn) {
  return function () {
    var self = this,
      args = arguments;
    return new Promise(function (resolve, reject) {
      var gen = fn.apply(self, args);
      function _next(value) {
        asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value);
      }
      function _throw(err) {
        asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err);
      }
      _next(undefined);
    });
  };
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/classCallCheck.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/classCallCheck.js ***!
  \*******************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ _classCallCheck; }
/* harmony export */ });
function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/createClass.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/createClass.js ***!
  \****************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ _createClass; }
/* harmony export */ });
/* harmony import */ var _toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./toPropertyKey.js */ "./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js");

function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, (0,_toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__["default"])(descriptor.key), descriptor);
  }
}
function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  Object.defineProperty(Constructor, "prototype", {
    writable: false
  });
  return Constructor;
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js":
/*!*************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js ***!
  \*************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ _iterableToArrayLimit; }
/* harmony export */ });
function _iterableToArrayLimit(r, l) {
  var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"];
  if (null != t) {
    var e,
      n,
      i,
      u,
      a = [],
      f = !0,
      o = !1;
    try {
      if (i = (t = t.call(r)).next, 0 === l) {
        if (Object(t) !== t) return;
        f = !1;
      } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0);
    } catch (r) {
      o = !0, n = r;
    } finally {
      try {
        if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return;
      } finally {
        if (o) throw n;
      }
    }
    return a;
  }
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js":
/*!********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js ***!
  \********************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ _nonIterableRest; }
/* harmony export */ });
function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/slicedToArray.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/slicedToArray.js ***!
  \******************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ _slicedToArray; }
/* harmony export */ });
/* harmony import */ var _arrayWithHoles_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./arrayWithHoles.js */ "./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js");
/* harmony import */ var _iterableToArrayLimit_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./iterableToArrayLimit.js */ "./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js");
/* harmony import */ var _unsupportedIterableToArray_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./unsupportedIterableToArray.js */ "./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js");
/* harmony import */ var _nonIterableRest_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./nonIterableRest.js */ "./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js");




function _slicedToArray(arr, i) {
  return (0,_arrayWithHoles_js__WEBPACK_IMPORTED_MODULE_0__["default"])(arr) || (0,_iterableToArrayLimit_js__WEBPACK_IMPORTED_MODULE_1__["default"])(arr, i) || (0,_unsupportedIterableToArray_js__WEBPACK_IMPORTED_MODULE_2__["default"])(arr, i) || (0,_nonIterableRest_js__WEBPACK_IMPORTED_MODULE_3__["default"])();
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/toPrimitive.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toPrimitive.js ***!
  \****************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ toPrimitive; }
/* harmony export */ });
/* harmony import */ var _typeof_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./typeof.js */ "./node_modules/@babel/runtime/helpers/esm/typeof.js");

function toPrimitive(t, r) {
  if ("object" != (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(t) || !t) return t;
  var e = t[Symbol.toPrimitive];
  if (void 0 !== e) {
    var i = e.call(t, r || "default");
    if ("object" != (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(i)) return i;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return ("string" === r ? String : Number)(t);
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js ***!
  \******************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ toPropertyKey; }
/* harmony export */ });
/* harmony import */ var _typeof_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./typeof.js */ "./node_modules/@babel/runtime/helpers/esm/typeof.js");
/* harmony import */ var _toPrimitive_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./toPrimitive.js */ "./node_modules/@babel/runtime/helpers/esm/toPrimitive.js");


function toPropertyKey(t) {
  var i = (0,_toPrimitive_js__WEBPACK_IMPORTED_MODULE_1__["default"])(t, "string");
  return "symbol" == (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(i) ? i : i + "";
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/typeof.js":
/*!***********************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/typeof.js ***!
  \***********************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ _typeof; }
/* harmony export */ });
function _typeof(o) {
  "@babel/helpers - typeof";

  return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) {
    return typeof o;
  } : function (o) {
    return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
  }, _typeof(o);
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js":
/*!*******************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js ***!
  \*******************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ _unsupportedIterableToArray; }
/* harmony export */ });
/* harmony import */ var _arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./arrayLikeToArray.js */ "./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js");

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return (0,_arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__["default"])(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return (0,_arrayLikeToArray_js__WEBPACK_IMPORTED_MODULE_0__["default"])(o, minLen);
}

/***/ }),

/***/ "./src/blocks/wpec-payment-gateway-integration/payment-methods/paypal/Content.js":
/*!***************************************************************************************!*\
  !*** ./src/blocks/wpec-payment-gateway-integration/payment-methods/paypal/Content.js ***!
  \***************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ "./node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../utils */ "./src/blocks/wpec-payment-gateway-integration/utils.js");
/* harmony import */ var _WpecPaypalButtonHandler__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./WpecPaypalButtonHandler */ "./src/blocks/wpec-payment-gateway-integration/payment-methods/paypal/WpecPaypalButtonHandler.js");
/* harmony import */ var _Styles_module_css__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Styles.module.css */ "./src/blocks/wpec-payment-gateway-integration/payment-methods/paypal/Styles.module.css");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__);







/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var eventRegistration = _ref.eventRegistration;
  var ajaxUrl = (0,_utils__WEBPACK_IMPORTED_MODULE_3__.getPayPalSettings)('ajaxUrl');
  var popup_title = (0,_utils__WEBPACK_IMPORTED_MODULE_3__.getPayPalSettings)('popup_title');
  var renderButtonNonce = (0,_utils__WEBPACK_IMPORTED_MODULE_3__.getPayPalSettings)('renderButtonNonce');
  var onCheckoutSuccess = eventRegistration.onCheckoutSuccess;
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_2__.useState)(null),
    _useState2 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__["default"])(_useState, 2),
    btnData = _useState2[0],
    setBtnData = _useState2[1];
  var _useState3 = (0,react__WEBPACK_IMPORTED_MODULE_2__.useState)(''),
    _useState4 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__["default"])(_useState3, 2),
    priceTag = _useState4[0],
    setPriceTag = _useState4[1];
  var _useState5 = (0,react__WEBPACK_IMPORTED_MODULE_2__.useState)(false),
    _useState6 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__["default"])(_useState5, 2),
    showModal = _useState6[0],
    setShowModal = _useState6[1];
  var _useState7 = (0,react__WEBPACK_IMPORTED_MODULE_2__.useState)(false),
    _useState8 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__["default"])(_useState7, 2),
    sdkLoaded = _useState8[0],
    setSdkLoaded = _useState8[1];

  // console.log('sdk_args', getPayPalSettings('pp_sdk_args'));

  var toggleModal = function toggleModal() {
    setShowModal(!showModal);
  };
  (0,react__WEBPACK_IMPORTED_MODULE_2__.useEffect)(function () {
    onCheckoutSuccess(function (args) {
      var redirectUrl = args.redirectUrl,
        orderId = args.orderId,
        customerId = args.customerId,
        orderNotes = args.orderNotes,
        paymentResult = args.paymentResult;

      // Retrieve wpec paypal payment button data.
      fetch(ajaxUrl, {
        method: "post",
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          action: 'wpec_wc_block_payment_button_data',
          order_id: orderId,
          modal_title: popup_title,
          nonce: renderButtonNonce
        }).toString()
      }).then(function (response) {
        return response.json();
      }).then(function (response) {
        if (response.success !== true) {
          throw new Error(response.message);
        }

        // console.log(response.data);

        // Set the data related to paypal button generation.
        setBtnData(response.data);
        setPriceTag(response.data.price_tag);
      }).catch(function (error) {
        console.log(error.messages);
        alert(error.messages);
      });
    });
  }, [onCheckoutSuccess]);
  (0,react__WEBPACK_IMPORTED_MODULE_2__.useEffect)(function () {
    if (btnData) {
      var sdk_args = (0,_utils__WEBPACK_IMPORTED_MODULE_3__.getPayPalSettings)('pp_sdk_args');
      var sdk_args_query_param = new URLSearchParams(sdk_args).toString();
      var script = document.createElement('script');
      script.src = "https://www.paypal.com/sdk/js?".concat(sdk_args_query_param);
      script.setAttribute('data-partner-attribution-id', 'TipsandTricks_SP_PPCP');
      script.async = true;
      script.onload = function () {
        console.log('WPEC PayPal SDK For WooCommerce Blocks loaded!');
        setSdkLoaded(true);
      };
      document.body.appendChild(script);
      return function () {
        document.body.removeChild(script);
      };
    }
  }, [btnData]);
  (0,react__WEBPACK_IMPORTED_MODULE_2__.useEffect)(function () {
    if (sdkLoaded) {
      var ppHandler = new _WpecPaypalButtonHandler__WEBPACK_IMPORTED_MODULE_4__["default"](btnData, {
        ajaxUrl: ajaxUrl,
        renderTo: '#wpec_wc_paypal_button_container'
      });
      ppHandler.generate_ppec_woocommerce_button();

      // Display the modal with paypal button.
      setShowModal(true);
    }
  }, [sdkLoaded]);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
      className: "".concat(_Styles_module_css__WEBPACK_IMPORTED_MODULE_5__["default"].modal, " ").concat(showModal ? _Styles_module_css__WEBPACK_IMPORTED_MODULE_5__["default"].modalShow : ''),
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
        className: _Styles_module_css__WEBPACK_IMPORTED_MODULE_5__["default"].modalContent,
        onClick: function onClick(e) {
          return e.stopPropagation();
        },
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
          className: _Styles_module_css__WEBPACK_IMPORTED_MODULE_5__["default"].modalHeader,
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("h4", {
            children: popup_title
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("button", {
            type: "button",
            className: _Styles_module_css__WEBPACK_IMPORTED_MODULE_5__["default"].modalCloseBtn,
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("span", {
              className: _Styles_module_css__WEBPACK_IMPORTED_MODULE_5__["default"].modalCloseIcon,
              onClick: toggleModal,
              children: "\xD7"
            })
          })]
        }), priceTag && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("h4", {
            dangerouslySetInnerHTML: {
              __html: priceTag
            }
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("br", {})]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
          id: "wpec_wc_paypal_button_container"
        })]
      })
    }), (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1__.decodeEntities)((0,_utils__WEBPACK_IMPORTED_MODULE_3__.getPayPalSettings)('description', ''))]
  });
});

/***/ }),

/***/ "./src/blocks/wpec-payment-gateway-integration/payment-methods/paypal/Edit.js":
/*!************************************************************************************!*\
  !*** ./src/blocks/wpec-payment-gateway-integration/payment-methods/paypal/Edit.js ***!
  \************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utils */ "./src/blocks/wpec-payment-gateway-integration/utils.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);



/* harmony default export */ __webpack_exports__["default"] = (function () {
  var description = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__.decodeEntities)((0,_utils__WEBPACK_IMPORTED_MODULE_1__.getPayPalSettings)('description', ''));
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.Fragment, {
    children: description
  });
});

/***/ }),

/***/ "./src/blocks/wpec-payment-gateway-integration/payment-methods/paypal/Styles.module.css":
/*!**********************************************************************************************!*\
  !*** ./src/blocks/wpec-payment-gateway-integration/payment-methods/paypal/Styles.module.css ***!
  \**********************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin
/* harmony default export */ __webpack_exports__["default"] = ({"modal":"zU7Am26o6bV5B_RGfj8o","modalHeader":"qIjH7vIBztY1XQG21ROr","modalShow":"oHIS0fzElER3AI9YDAuI","modalContent":"iDLZWMzBND9t3hw4hZkV","modalCloseIcon":"zn_b_MdLPvQw9BHmkOdD","modalCloseBtn":"FHIaV5yVDXL_dlIPuNwB"});

/***/ }),

/***/ "./src/blocks/wpec-payment-gateway-integration/payment-methods/paypal/WpecPaypalButtonHandler.js":
/*!*******************************************************************************************************!*\
  !*** ./src/blocks/wpec-payment-gateway-integration/payment-methods/paypal/WpecPaypalButtonHandler.js ***!
  \*******************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ WpecPaypalButtonHandler; }
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/esm/createClass.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);



function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i.return) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }

var WpecPaypalButtonHandler = /*#__PURE__*/function () {
  function WpecPaypalButtonHandler(data, _ref) {
    var ajaxUrl = _ref.ajaxUrl,
      renderTo = _ref.renderTo;
    (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__["default"])(this, WpecPaypalButtonHandler);
    this.data = data;
    this.renderTo = renderTo;
    this.ajaxUrl = ajaxUrl;
  }
  return (0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__["default"])(WpecPaypalButtonHandler, [{
    key: "generate_ppec_woocommerce_button",
    value: function generate_ppec_woocommerce_button() {
      var parent = this;
      paypal.Buttons({
        /**
         * Optional styling for buttons.
         *
         * See documentation: https://developer.paypal.com/sdk/js/reference/#link-style
         */
        style: {
          color: parent.data.btnStyle.color,
          shape: parent.data.btnStyle.shape,
          height: parent.data.btnStyle.height,
          label: parent.data.btnStyle.label,
          layout: parent.data.btnStyle.layout
        },
        /**
         * OnInit is called when the button first renders.
         *
         * See documentation: https://developer.paypal.com/sdk/js/reference/#link-oninitonclick
         */
        onInit: function onInit(data, actions) {
          actions.enable();
        },
        /**
         * OnClick is called when the button is clicked
         *
         * See documentation: https://developer.paypal.com/sdk/js/reference/#link-oninitonclick
         */
        onClick: function onClick() {},
        /**
         * This is called when the buyer clicks the PayPal button, which launches the PayPal Checkout
         * window where the buyer logs in and approves the transaction on the paypal.com website.
         *
         * The server-side Create Order API is used to generate the Order. Then the Order-ID is returned.
         *
         * See documentation: https://developer.paypal.com/sdk/js/reference/#link-createorder
         */
        createOrder: function () {
          var _createOrder = (0,_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__["default"])(/*#__PURE__*/_regenerator().m(function _callee() {
            var price_amount, itemTotalValueRoundedAsNumber, order_data, wpec_data, response, _t;
            return _regenerator().w(function (_context) {
              while (1) switch (_context.p = _context.n) {
                case 0:
                  // Create order_data object to be sent to the server.
                  price_amount = parseFloat(parent.data.price); //round to 2 decimal places, to make sure that the API call dont fail.
                  price_amount = parseFloat(price_amount.toFixed(2));
                  itemTotalValueRoundedAsNumber = price_amount;
                  order_data = {
                    intent: 'CAPTURE',
                    payment_source: {
                      paypal: {
                        experience_context: {
                          payment_method_preference: 'IMMEDIATE_PAYMENT_REQUIRED',
                          shipping_preference: 'NO_SHIPPING',
                          user_action: 'PAY_NOW'
                        }
                      }
                    },
                    purchase_units: [{
                      amount: {
                        value: price_amount,
                        currency_code: parent.data.currency,
                        breakdown: {
                          item_total: {
                            currency_code: parent.data.currency,
                            value: itemTotalValueRoundedAsNumber
                          }
                        }
                      },
                      items: [{
                        name: parent.data.name,
                        quantity: parent.data.quantity,
                        unit_amount: {
                          value: price_amount,
                          currency_code: parent.data.currency
                        }
                      }]
                    }]
                  };
                  wpec_data = parent.data;
                  _context.p = 1;
                  _context.n = 2;
                  return fetch(parent.ajaxUrl, {
                    method: "post",
                    headers: {
                      'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                      action: 'wpec_woocommerce_pp_create_order',
                      data: JSON.stringify(order_data),
                      wpec_data: JSON.stringify(wpec_data),
                      _wpnonce: parent.data.nonce
                    }).toString()
                  });
                case 2:
                  response = _context.v;
                  _context.n = 3;
                  return response.json();
                case 3:
                  response = _context.v;
                  if (!response.order_id) {
                    _context.n = 4;
                    break;
                  }
                  console.log('Create-order API call to PayPal completed successfully.');
                  return _context.a(2, response.order_id);
                case 4:
                  console.error('Error occurred during create-order call to PayPal. ', response.message);
                  throw new Error(response.message);
                case 5:
                  _context.n = 7;
                  break;
                case 6:
                  _context.p = 6;
                  _t = _context.v;
                  console.error(_t.message);
                  alert((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Could not initiate PayPal Checkout...', 'wp-express-checkout') + '\n\n' + _t.message);
                case 7:
                  return _context.a(2);
              }
            }, _callee, null, [[1, 6]]);
          }));
          function createOrder() {
            return _createOrder.apply(this, arguments);
          }
          return createOrder;
        }(),
        /**
         * Captures the funds from the transaction and shows a message to the buyer to let them know the
         * transaction is successful. The method is called after the buyer approves the transaction on paypal.com.
         *
         * See documentation: https://developer.paypal.com/sdk/js/reference/#link-onapprove
         */
        onApprove: function () {
          var _onApprove = (0,_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__["default"])(/*#__PURE__*/_regenerator().m(function _callee2(data, actions) {
            var pp_bn_data, wpec_data, response, _t2;
            return _regenerator().w(function (_context2) {
              while (1) switch (_context2.p = _context2.n) {
                case 0:
                  // Create the data object to be sent to the server.
                  pp_bn_data = {}; // The orderID is the ID of the order that was created in the createOrder method.
                  pp_bn_data.order_id = data.orderID;
                  // parent.data is the data object that was passed to the constructor.
                  wpec_data = parent.data;
                  _context2.p = 1;
                  _context2.n = 2;
                  return fetch(parent.ajaxUrl, {
                    method: "post",
                    headers: {
                      'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                      action: 'wpec_woocommerce_pp_capture_order',
                      data: JSON.stringify(pp_bn_data),
                      wpec_data: JSON.stringify(wpec_data),
                      _wpnonce: parent.data.nonce
                    }).toString()
                  });
                case 2:
                  response = _context2.v;
                  _context2.n = 3;
                  return response.json();
                case 3:
                  response = _context2.v;
                  if (response.success) {
                    console.log('Capture-order API call to PayPal completed successfully.');
                    window.location.href = response.data.redirect_url;
                  }
                  _context2.n = 5;
                  break;
                case 4:
                  _context2.p = 4;
                  _t2 = _context2.v;
                  console.error(_t2.message);
                  alert((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('PayPal returned an error! Transaction could not be processed.', 'wp-express-checkout') + '\n\n' + _t2.message);
                case 5:
                  return _context2.a(2);
              }
            }, _callee2, null, [[1, 4]]);
          }));
          function onApprove(_x, _x2) {
            return _onApprove.apply(this, arguments);
          }
          return onApprove;
        }(),
        /**
         * If an error prevents buyer checkout, alert the user that an error has occurred with the buttons using this callback.
         *
         * See documentation: https://developer.paypal.com/sdk/js/reference/#link-onerror
         */
        onError: function onError(err) {
          console.error('An error prevented the user from checking out with PayPal. ', err.message);
          alert((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Error occurred during PayPal checkout process.', 'wp-express-checkout') + "\n\n" + err.message);
        },
        /**
         * Handles onCancel event.
         *
         * See documentation: https://developer.paypal.com/sdk/js/reference/#link-oncancel
         */
        onCancel: function onCancel(data) {
          console.log('Checkout operation canceled by the customer.');
        }
      }).render(this.renderTo).catch(function (err) {
        console.log('PayPal Buttons failed to render!', err.message);
      });
    }
  }]);
}();


/***/ }),

/***/ "./src/blocks/wpec-payment-gateway-integration/payment-methods/paypal/index.js":
/*!*************************************************************************************!*\
  !*** ./src/blocks/wpec-payment-gateway-integration/payment-methods/paypal/index.js ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   paypalConfig: function() { return /* binding */ paypalConfig; }
/* harmony export */ });
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Content__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Content */ "./src/blocks/wpec-payment-gateway-integration/payment-methods/paypal/Content.js");
/* harmony import */ var _Edit__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Edit */ "./src/blocks/wpec-payment-gateway-integration/payment-methods/paypal/Edit.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../utils */ "./src/blocks/wpec-payment-gateway-integration/utils.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__);





var label = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__.decodeEntities)((0,_utils__WEBPACK_IMPORTED_MODULE_3__.getPayPalSettings)('title'));
var Label = function Label(props) {
  var PaymentMethodLabel = props.components.PaymentMethodLabel;
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(PaymentMethodLabel, {
    text: label
  });
};
var paypalConfig = {
  name: "wp-express-checkout",
  label: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(Label, {}),
  content: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_Content__WEBPACK_IMPORTED_MODULE_1__["default"], {}),
  edit: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_Edit__WEBPACK_IMPORTED_MODULE_2__["default"], {}),
  canMakePayment: function canMakePayment() {
    return true;
  },
  ariaLabel: label,
  supports: {
    features: (0,_utils__WEBPACK_IMPORTED_MODULE_3__.getPayPalSettings)('supports', [])
  }
};

/***/ }),

/***/ "./src/blocks/wpec-payment-gateway-integration/payment-methods/stripe/Content.js":
/*!***************************************************************************************!*\
  !*** ./src/blocks/wpec-payment-gateway-integration/payment-methods/stripe/Content.js ***!
  \***************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utils */ "./src/blocks/wpec-payment-gateway-integration/utils.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);



/* harmony default export */ __webpack_exports__["default"] = (function (_ref) {
  var eventRegistration = _ref.eventRegistration;
  var description = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__.decodeEntities)((0,_utils__WEBPACK_IMPORTED_MODULE_1__.getStripeSettings)('description', ''));
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.Fragment, {
    children: description
  });
});

/***/ }),

/***/ "./src/blocks/wpec-payment-gateway-integration/payment-methods/stripe/Edit.js":
/*!************************************************************************************!*\
  !*** ./src/blocks/wpec-payment-gateway-integration/payment-methods/stripe/Edit.js ***!
  \************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utils */ "./src/blocks/wpec-payment-gateway-integration/utils.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);



/* harmony default export */ __webpack_exports__["default"] = (function () {
  var description = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__.decodeEntities)((0,_utils__WEBPACK_IMPORTED_MODULE_1__.getStripeSettings)('description', ''));
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.Fragment, {
    children: description
  });
});

/***/ }),

/***/ "./src/blocks/wpec-payment-gateway-integration/payment-methods/stripe/index.js":
/*!*************************************************************************************!*\
  !*** ./src/blocks/wpec-payment-gateway-integration/payment-methods/stripe/index.js ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   stripeConfig: function() { return /* binding */ stripeConfig; }
/* harmony export */ });
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Content__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Content */ "./src/blocks/wpec-payment-gateway-integration/payment-methods/stripe/Content.js");
/* harmony import */ var _Edit__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Edit */ "./src/blocks/wpec-payment-gateway-integration/payment-methods/stripe/Edit.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../utils */ "./src/blocks/wpec-payment-gateway-integration/utils.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__);





var title = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__.decodeEntities)((0,_utils__WEBPACK_IMPORTED_MODULE_3__.getStripeSettings)('title'));
var description = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_0__.decodeEntities)((0,_utils__WEBPACK_IMPORTED_MODULE_3__.getStripeSettings)('description', ''));
var Label = function Label(props) {
  var PaymentMethodLabel = props.components.PaymentMethodLabel;
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(PaymentMethodLabel, {
    text: title
  });
};
var stripeConfig = {
  name: 'wp-express-checkout-stripe',
  title: title,
  description: description,
  label: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(Label, {}),
  content: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_Content__WEBPACK_IMPORTED_MODULE_1__["default"], {}),
  edit: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_4__.jsx)(_Edit__WEBPACK_IMPORTED_MODULE_2__["default"], {}),
  canMakePayment: function canMakePayment() {
    return true;
  },
  ariaLabel: title,
  supports: {
    features: ['products']
  }
};

/***/ }),

/***/ "./src/blocks/wpec-payment-gateway-integration/utils.js":
/*!**************************************************************!*\
  !*** ./src/blocks/wpec-payment-gateway-integration/utils.js ***!
  \**************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getPayPalSettings: function() { return /* binding */ getPayPalSettings; },
/* harmony export */   getStripeSettings: function() { return /* binding */ getStripeSettings; }
/* harmony export */ });
var getSetting = window.wc.wcSettings.getSetting;
function getSettings(key, settingsGroup) {
  var defaultValue = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;
  var settings = getSetting(settingsGroup, {});
  return settings[key] || defaultValue;
}
function getPayPalSettings(key) {
  var defaultValue = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
  return getSettings(key, "wp-express-checkout_data", defaultValue);
}
function getStripeSettings(key) {
  var defaultValue = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
  return getSettings(key, "wp-express-checkout-stripe_data", defaultValue);
}

/***/ }),

/***/ "@wordpress/html-entities":
/*!**************************************!*\
  !*** external ["wp","htmlEntities"] ***!
  \**************************************/
/***/ (function(module) {

module.exports = window["wp"]["htmlEntities"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ (function(module) {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ (function(module) {

module.exports = window["React"];

/***/ }),

/***/ "react/jsx-runtime":
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
/***/ (function(module) {

module.exports = window["ReactJSXRuntime"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
!function() {
/*!**************************************************************!*\
  !*** ./src/blocks/wpec-payment-gateway-integration/index.js ***!
  \**************************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _payment_methods_paypal__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./payment-methods/paypal */ "./src/blocks/wpec-payment-gateway-integration/payment-methods/paypal/index.js");
/* harmony import */ var _payment_methods_stripe__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./payment-methods/stripe */ "./src/blocks/wpec-payment-gateway-integration/payment-methods/stripe/index.js");
var registerPaymentMethod = window.wc.wcBlocksRegistry.registerPaymentMethod;


registerPaymentMethod(_payment_methods_paypal__WEBPACK_IMPORTED_MODULE_0__.paypalConfig);
registerPaymentMethod(_payment_methods_stripe__WEBPACK_IMPORTED_MODULE_1__.stripeConfig);
}();
/******/ })()
;
//# sourceMappingURL=index.js.map