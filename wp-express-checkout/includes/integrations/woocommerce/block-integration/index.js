/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/blocks/wpec-payment-gateway-integration/FrontEndContent.js":
/*!************************************************************************!*\
  !*** ./src/blocks/wpec-payment-gateway-integration/FrontEndContent.js ***!
  \************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ "./node_modules/@babel/runtime/helpers/esm/slicedToArray.js");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _Utils__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Utils */ "./src/blocks/wpec-payment-gateway-integration/Utils.js");
/* harmony import */ var _WpecPaypalButtonHandler__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./WpecPaypalButtonHandler */ "./src/blocks/wpec-payment-gateway-integration/WpecPaypalButtonHandler.js");
/* harmony import */ var _FrontEndContent_module_css__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./FrontEndContent.module.css */ "./src/blocks/wpec-payment-gateway-integration/FrontEndContent.module.css");







var FrontEndContent = function FrontEndContent(_ref) {
  var eventRegistration = _ref.eventRegistration;
  var ajaxUrl = (0,_Utils__WEBPACK_IMPORTED_MODULE_3__.getSettings)('ajaxUrl');
  var popup_title = (0,_Utils__WEBPACK_IMPORTED_MODULE_3__.getSettings)('popup_title');
  var renderButtonNonce = (0,_Utils__WEBPACK_IMPORTED_MODULE_3__.getSettings)('renderButtonNonce');
  var onCheckoutSuccess = eventRegistration.onCheckoutSuccess;
  var _useState = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(null),
    _useState2 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__["default"])(_useState, 2),
    btnData = _useState2[0],
    setBtnData = _useState2[1];
  var _useState3 = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(''),
    _useState4 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__["default"])(_useState3, 2),
    priceTag = _useState4[0],
    setPriceTag = _useState4[1];
  var _useState5 = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(false),
    _useState6 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__["default"])(_useState5, 2),
    showModal = _useState6[0],
    setShowModal = _useState6[1];
  var _useState7 = (0,react__WEBPACK_IMPORTED_MODULE_1__.useState)(false),
    _useState8 = (0,_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__["default"])(_useState7, 2),
    sdkLoaded = _useState8[0],
    setSdkLoaded = _useState8[1];

  // console.log('sdk_args', getSettings('pp_sdk_args'));

  var toggleModal = function toggleModal() {
    setShowModal(!showModal);
  };
  (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(function () {
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
  (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(function () {
    if (btnData) {
      var sdk_args = (0,_Utils__WEBPACK_IMPORTED_MODULE_3__.getSettings)('pp_sdk_args');
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
  (0,react__WEBPACK_IMPORTED_MODULE_1__.useEffect)(function () {
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
  return (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(react__WEBPACK_IMPORTED_MODULE_1__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
    className: "".concat(_FrontEndContent_module_css__WEBPACK_IMPORTED_MODULE_5__["default"].modal, " ").concat(showModal ? _FrontEndContent_module_css__WEBPACK_IMPORTED_MODULE_5__["default"].modalShow : '')
  }, (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
    className: _FrontEndContent_module_css__WEBPACK_IMPORTED_MODULE_5__["default"].modalContent,
    onClick: function onClick(e) {
      return e.stopPropagation();
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
    className: _FrontEndContent_module_css__WEBPACK_IMPORTED_MODULE_5__["default"].modalHeader
  }, (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("h4", null, popup_title), (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("button", {
    type: "button",
    className: _FrontEndContent_module_css__WEBPACK_IMPORTED_MODULE_5__["default"].modalCloseBtn
  }, (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("span", {
    className: _FrontEndContent_module_css__WEBPACK_IMPORTED_MODULE_5__["default"].modalCloseIcon,
    onClick: toggleModal
  }, "\xD7"))), priceTag && (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)(react__WEBPACK_IMPORTED_MODULE_1__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("h4", {
    dangerouslySetInnerHTML: {
      __html: priceTag
    }
  }), (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("br", null)), (0,react__WEBPACK_IMPORTED_MODULE_1__.createElement)("div", {
    id: "wpec_wc_paypal_button_container"
  }))), (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2__.decodeEntities)((0,_Utils__WEBPACK_IMPORTED_MODULE_3__.getSettings)('description', '')));
};
/* harmony default export */ __webpack_exports__["default"] = (FrontEndContent);

/***/ }),

/***/ "./src/blocks/wpec-payment-gateway-integration/Utils.js":
/*!**************************************************************!*\
  !*** ./src/blocks/wpec-payment-gateway-integration/Utils.js ***!
  \**************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getSettings: function() { return /* binding */ getSettings; }
/* harmony export */ });
var getSetting = window.wc.wcSettings.getSetting;
function getSettings(key) {
  var defaultValue = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
  var settings = getSetting('wp-express-checkout_data', {});
  return settings[key] || defaultValue;
}

/***/ }),

/***/ "./src/blocks/wpec-payment-gateway-integration/WpecPaypalButtonHandler.js":
/*!********************************************************************************!*\
  !*** ./src/blocks/wpec-payment-gateway-integration/WpecPaypalButtonHandler.js ***!
  \********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ WpecPaypalButtonHandler; }
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/asyncToGenerator */ "./node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/esm/createClass.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/regenerator */ "@babel/runtime/regenerator");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);





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
      return paypal.Buttons({
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
          var _createOrder = (0,_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__["default"])( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_3___default().mark(function _callee() {
            var price_amount, itemTotalValueRoundedAsNumber, order_data, wpec_data, response;
            return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_3___default().wrap(function _callee$(_context) {
              while (1) switch (_context.prev = _context.next) {
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
                  _context.prev = 5;
                  _context.next = 8;
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
                case 8:
                  response = _context.sent;
                  _context.next = 11;
                  return response.json();
                case 11:
                  response = _context.sent;
                  if (!response.order_id) {
                    _context.next = 17;
                    break;
                  }
                  console.log('Create-order API call to PayPal completed successfully.');
                  return _context.abrupt("return", response.order_id);
                case 17:
                  console.error('Error occurred during create-order call to PayPal. ', response.message);
                  throw new Error(response.message);
                case 19:
                  _context.next = 25;
                  break;
                case 21:
                  _context.prev = 21;
                  _context.t0 = _context["catch"](5);
                  console.error(_context.t0.message);
                  alert((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Could not initiate PayPal Checkout...', 'wp-express-checkout') + '\n\n' + _context.t0.message);
                case 25:
                case "end":
                  return _context.stop();
              }
            }, _callee, null, [[5, 21]]);
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
          var _onApprove = (0,_babel_runtime_helpers_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__["default"])( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_3___default().mark(function _callee2(data, actions) {
            var pp_bn_data, wpec_data, response;
            return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_3___default().wrap(function _callee2$(_context2) {
              while (1) switch (_context2.prev = _context2.next) {
                case 0:
                  // Create the data object to be sent to the server.
                  pp_bn_data = {}; // The orderID is the ID of the order that was created in the createOrder method.
                  pp_bn_data.order_id = data.orderID;
                  // parent.data is the data object that was passed to the constructor.
                  wpec_data = parent.data;
                  _context2.prev = 3;
                  _context2.next = 6;
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
                case 6:
                  response = _context2.sent;
                  _context2.next = 9;
                  return response.json();
                case 9:
                  response = _context2.sent;
                  if (response.success) {
                    console.log('Capture-order API call to PayPal completed successfully.');
                    window.location.href = response.data.redirect_url;
                  }
                  _context2.next = 17;
                  break;
                case 13:
                  _context2.prev = 13;
                  _context2.t0 = _context2["catch"](3);
                  console.error(_context2.t0.message);
                  alert((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('PayPal returned an error! Transaction could not be processed.', 'wp-express-checkout') + '\n\n' + _context2.t0.message);
                case 17:
                case "end":
                  return _context2.stop();
              }
            }, _callee2, null, [[3, 13]]);
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
          alert((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Error occurred during PayPal checkout process.', 'wp-express-checkout') + "\n\n" + err.message);
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

/***/ "./src/blocks/wpec-payment-gateway-integration/FrontEndContent.module.css":
/*!********************************************************************************!*\
  !*** ./src/blocks/wpec-payment-gateway-integration/FrontEndContent.module.css ***!
  \********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin
/* harmony default export */ __webpack_exports__["default"] = ({"modal":"irAUBTLdCxpkOtz5gVXW","modalHeader":"tDOXF0SmLibBXak_A8Gj","modalShow":"X7BzTCTMwFKliO7DVzWa","modalContent":"n8m9Whu76Vh1l6wKzK7f","modalCloseIcon":"hZ_W3LPfn7tpNQcoesTc","modalCloseBtn":"hVa5HE2OdRlut0YLHMi6"});

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ (function(module) {

module.exports = window["React"];

/***/ }),

/***/ "@babel/runtime/regenerator":
/*!*************************************!*\
  !*** external "regeneratorRuntime" ***!
  \*************************************/
/***/ (function(module) {

module.exports = window["regeneratorRuntime"];

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
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
!function() {
/*!**************************************************************!*\
  !*** ./src/blocks/wpec-payment-gateway-integration/index.js ***!
  \**************************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _FrontEndContent__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FrontEndContent */ "./src/blocks/wpec-payment-gateway-integration/FrontEndContent.js");
/* harmony import */ var _Utils__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Utils */ "./src/blocks/wpec-payment-gateway-integration/Utils.js");



var registerPaymentMethod = window.wc.wcBlocksRegistry.registerPaymentMethod;


// console.log("WP Express Checkout gateway bBlock script loaded");

var label = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1__.decodeEntities)((0,_Utils__WEBPACK_IMPORTED_MODULE_3__.getSettings)('title'));
var EditPageContent = function EditPageContent() {
  return (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1__.decodeEntities)((0,_Utils__WEBPACK_IMPORTED_MODULE_3__.getSettings)('description', ''));
};
var Label = function Label(props) {
  var PaymentMethodLabel = props.components.PaymentMethodLabel;
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(PaymentMethodLabel, {
    text: label
  });
};
registerPaymentMethod({
  name: "wp-express-checkout",
  label: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Label, null),
  content: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_FrontEndContent__WEBPACK_IMPORTED_MODULE_2__["default"], null),
  edit: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(EditPageContent, null),
  canMakePayment: function canMakePayment() {
    return true;
  },
  ariaLabel: label,
  supports: {
    features: (0,_Utils__WEBPACK_IMPORTED_MODULE_3__.getSettings)('supports', [])
  }
});
}();
/******/ })()
;
//# sourceMappingURL=index.js.map