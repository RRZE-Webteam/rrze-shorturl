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

const {
  useState,
  useEffect
} = wp.element;
const {
  TextControl,
  Button,
  SelectControl
} = wp.components;
const {
  InspectorControls
} = wp.editor;
const {
  PanelBody
} = wp.components;
const {
  __
} = wp.i18n;

// Define the Edit component
const Edit = ({
  attributes,
  setAttributes
}) => {
  const [url, setUrl] = useState('');
  const [getparameter, setGetparameter] = useState('');
  const [shortenedUrl, setShortenedUrl] = useState('');
  const [selfExplanatoryUri, setSelfExplanatoryUri] = useState('');
  const [validUntil, setValidUntil] = useState('');
  const [selectedCategories, setSelectedCategories] = useState('');
  const [selectedTags, setSelectedTags] = useState([]);
  const [errorMessage, setErrorMessage] = useState('');
  const [qrCodeUrl, setQrCodeUrl] = useState('');
  const [categoriesOptions, setCategoriesOptions] = useState([]);
  const [tagsOptions, setTagsOptions] = useState([]);
  const [isLoading, setIsLoading] = useState(true); // Add loading state

  useEffect(() => {
    // Fetch categories from the shorturl_category taxonomy
    const categories = wp.data.select('core').getEntityRecords('taxonomy', 'shorturl_category');
    // Fetch tags from the shorturl_tag taxonomy
    const tags = wp.data.select('core').getEntityRecords('taxonomy', 'shorturl_tag');
    Promise.all([categories, tags]).then(([categories, tags]) => {
      // Check if categories or tags are null
      if (categories === null || tags === null) {
        // No categories or tags found, set options to empty arrays
        setCategoriesOptions([]);
        setTagsOptions([]);
        setIsLoading(false); // Set loading state to false
        return;
      }

      // Categories found, format them and set categoriesOptions
      const categoriesOptions = categories.map(category => ({
        label: category.name,
        value: category.id.toString()
      }));
      setCategoriesOptions(categoriesOptions);

      // Tags found, format them and set tagsOptions
      const tagsOptions = tags.map(tag => ({
        label: tag.name,
        value: tag.id.toString()
      }));
      setTagsOptions(tagsOptions);
      setIsLoading(false); // Set loading state to false
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
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(InspectorControls, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(PanelBody, {
    title: __('URL Shortener Settings')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(TextControl, {
    label: __('GET Parameter'),
    value: getparameter,
    onChange: setGetparameter
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(TextControl, {
    label: __('Self-Explanatory URI'),
    value: selfExplanatoryUri,
    onChange: setSelfExplanatoryUri
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(TextControl, {
    label: __('Valid until'),
    type: "date",
    value: validUntil,
    onChange: setValidUntil,
    min: new Date().toISOString().split('T')[0] // Set minimum date to today
    ,
    max: new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0] // Set maximum date to one year from today
  }), isLoading ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, __('Loading categories and tags...')) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(SelectControl, {
    label: __('Categories'),
    value: selectedCategories,
    onChange: category => setSelectedCategories(category),
    options: categoriesOptions // Provide options for the SelectControl
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(SelectControl, {
    label: __('Tags'),
    multiple: true,
    value: selectedTags,
    onChange: tags => setSelectedTags(tags),
    options: tagsOptions // Provide options for the SelectControl
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(TextControl, {
    label: __('Enter URL'),
    value: url,
    onChange: setUrl
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Button, {
    onClick: shortenUrl
  }, __('Shorten URL')), errorMessage && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    style: {
      color: 'red'
    }
  }, errorMessage), shortenedUrl && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, __('Shortened URL'), ": ", shortenedUrl), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
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

/***/ "@wordpress/blocks":
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
/***/ ((module) => {

module.exports = window["wp"]["blocks"];

/***/ }),

/***/ "./src/block.json":
/*!************************!*\
  !*** ./src/block.json ***!
  \************************/
/***/ ((module) => {

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"create-block/rrze-shorturl","version":"0.1.11","title":"Shorten URL RRZE","description":"A block to shorten URLs.","category":"widgets","icon":"admin-links","keywords":["url","shorten"],"textdomain":"rrze-shorturl","editorScript":"file:./index.js","supports":{"align":true},"example":{},"attributes":{"url":"https://example.com","getparameter":""}}');

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