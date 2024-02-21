/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/edit.js":
/*!*********************!*\
  !*** ./src/edit.js ***!
  \*********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);






// Define the Edit component
const Edit = ({
  attributes,
  setAttributes
}) => {
  const [url, setUrl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [getparameter, setGetparameter] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [shortenedUrl, setShortenedUrl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [selfExplanatoryUri, setSelfExplanatoryUri] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [validUntil, setValidUntil] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [selectedCategories, setSelectedCategories] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [selectedTags, setSelectedTags] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [errorMessage, setErrorMessage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [qrCodeUrl, setQrCodeUrl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [categoriesOptions, setCategoriesOptions] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [tagsOptions, setTagsOptions] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [isLoading, setIsLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true); // Add loading state

  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    // Fetch categories from the shorturl_category taxonomy
    // const categories = wp.data.select('core').getEntityRecords('taxonomy', 'shorturl_category'); // DOES NOT WORK EVEN NOT WITH Promise.all BECAUSE OF DELAY, therefore fetch via REST-API
    fetch('/wp-json/wp/v2/shorturl_category?fields=id,name').then(response => response.json()).then(data => {
      const categoriesOptions = data.map(term => ({
        label: term.name,
        value: term.id.toString()
      }));
      setCategoriesOptions(categoriesOptions);
    }).catch(error => {
      console.error('Error fetching shorturl_category terms:', error);
    });
    fetch('/wp-json/wp/v2/shorturl_tag?fields=id,name').then(response => response.json()).then(data => {
      const tagsOptions = data.map(term => ({
        label: term.name,
        value: term.id.toString()
      }));
      setTagsOptions(tagsOptions);
    }).catch(error => {
      console.error('Error fetching shorturl_tag terms:', error);
    });
  }, []);
  const shortenUrl = () => {
    let isValid = true;

    // Check if self-explanatory URI is not empty
    if (selfExplanatoryUri.trim() !== '') {
      // Remove spaces from the URI
      const uriWithoutSpaces = selfExplanatoryUri.replace(/\s/g, '');

      // Check if encodeURIComponent returns the same value for the URI
      if (encodeURIComponent(selfExplanatoryUri) !== encodeURIComponent(uriWithoutSpaces)) {
        setErrorMessage('Error: Self-Explanatory URI is not valid');
        isValid = false;
      }
    }
    if (isValid) {
      // Proceed with URL shortening
      fetch('/wp-json/short-url/v1/shorten', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          url,
          getparameter,
          uri: selfExplanatoryUri,
          valid_until: validUntil,
          // Include valid_until date in the request body
          categories: selectedCategories,
          // Include selected categories
          tags: selectedTags // Include selected tags
        })
      }).then(response => response.json()).then(shortenData => {
        console.log('Response:', shortenData);
        if (!shortenData.error) {
          setShortenedUrl(shortenData.txt);
          setErrorMessage('');
          generateQRCode(shortenData.txt); // Generate QR code after getting shortened URL
        } else {
          setErrorMessage('Error: ' + shortenData.txt);
          setShortenedUrl('');
        }
      }).catch(error => console.error('Error:', error));
    }
  };
  const generateQRCode = text => {
    // Generate QR code using qrious library
    const qr = new QRious({
      element: document.getElementById('qrcode'),
      value: text,
      size: 150 // Adjust the size as per your requirement
    });
    setQrCodeUrl(qr.toDataURL()); // Set the QR code image URL
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    ...(0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__.useBlockProps)()
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__.InspectorControls, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('URL Shortener Settings')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('GET Parameter'),
    value: getparameter,
    onChange: setGetparameter
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Self-Explanatory URI'),
    value: selfExplanatoryUri,
    onChange: setSelfExplanatoryUri
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Valid until'),
    type: "date",
    value: validUntil,
    onChange: setValidUntil,
    min: new Date().toISOString().split('T')[0] // Set minimum date to today
    ,
    max: new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0] // Set maximum date to one year from today
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Categories'),
    value: selectedCategories,
    onChange: category => setSelectedCategories(category),
    options: categoriesOptions // Provide options for the SelectControl
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Tags'),
    multiple: true,
    value: selectedTags,
    onChange: tags => setSelectedTags(tags),
    options: tagsOptions // Provide options for the SelectControl
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Enter URL'),
    value: url,
    onChange: setUrl
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Button, {
    onClick: shortenUrl
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Shorten URL')), errorMessage && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    style: {
      color: 'red'
    }
  }, errorMessage), shortenedUrl && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Shortened URL'), ": ", shortenedUrl), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    src: qrCodeUrl,
    alt: "QR Code"
  })));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Edit);

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ }),

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/***/ ((module) => {

module.exports = window["wp"]["blockEditor"];

/***/ }),

/***/ "@wordpress/blocks":
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
/***/ ((module) => {

module.exports = window["wp"]["blocks"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "./src/block.json":
/*!************************!*\
  !*** ./src/block.json ***!
  \************************/
/***/ ((module) => {

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"create-block/rrze-shorturl","version":"0.1.14","title":"Shorten URL RRZE","description":"A block to shorten URLs.","category":"widgets","icon":"admin-links","keywords":["url","shorten"],"textdomain":"rrze-shorturl","editorScript":"file:./index.js","supports":{"align":true},"example":{},"attributes":{"url":"https://example.com","getparameter":""}}');

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
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./block.json */ "./src/block.json");
/* harmony import */ var _edit__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./edit */ "./src/edit.js");
// Import necessary modules


// Import Edit and Save components


// import Save from './save'; // Using Edit component for Save as requested

// Register block type
(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.registerBlockType)(_block_json__WEBPACK_IMPORTED_MODULE_1__.name, {
  edit: _edit__WEBPACK_IMPORTED_MODULE_2__["default"],
  // Use the Edit component for editing
  save: () => null // Empty save function
});
})();

/******/ })()
;
//# sourceMappingURL=index.js.map