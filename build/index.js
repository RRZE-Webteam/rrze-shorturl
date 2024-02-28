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





const Edit = ({
  attributes,
  setAttributes
}) => {
  const {
    valid_until: defaultValidUntil
  } = attributes;
  const [url, setUrl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [getparameter, setGetparameter] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [shortenedUrl, setShortenedUrl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [selfExplanatoryUri, setSelfExplanatoryUri] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [validUntil, setValidUntil] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(defaultValidUntil);
  const [selectedCategories, setSelectedCategories] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [selectedTags, setSelectedTags] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [errorMessage, setErrorMessage] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [qrCodeUrl, setQrCodeUrl] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [categoriesOptions, setCategoriesOptions] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [tagsOptions, setTagsOptions] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]);
  const [isLoading, setIsLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const onChangeValidUntil = newDate => {
    setValidUntil(newDate);
    setAttributes({
      valid_until: newDate
    });
  };
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (!validUntil) {
      const now = new Date();
      const nextYear = new Date(now.getFullYear() + 1, now.getMonth(), now.getDate());
      setValidUntil(nextYear);
      setAttributes({
        valid_until: nextYear
      });
    }
    fetch('/wp-json/wp/v2/shorturl_category?fields=id,name,parent').then(response => response.json()).then(data => {
      const categoriesOptions = data.map(term => ({
        label: term.name,
        value: term.id,
        parent: term.parent || 0
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
  const renderCategories = (categories, level = 0, renderedCategories = new Set()) => {
    return categories.map((category, index) => {
      if (renderedCategories.has(category.value)) {
        return null;
      }
      renderedCategories.add(category.value);
      const children = categoriesOptions.filter(child => child.parent === category.value);
      const isLastCategory = index === categories.length - 1;
      return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        key: category.value,
        style: {
          marginLeft: `${level * 20}px`,
          marginBottom: `0`
        }
      }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.CheckboxControl, {
        label: category.label,
        checked: selectedCategories.includes(category.value),
        onChange: isChecked => {
          if (isChecked) {
            setSelectedCategories([...selectedCategories, category.value]);
          } else {
            setSelectedCategories(selectedCategories.filter(cat => cat !== category.value));
          }
        }
      }), children.length > 0 && renderCategories(children, level + 1, renderedCategories));
    });
  };
  const renderTags = () => {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.FormTokenField, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Tags'),
      value: selectedTags,
      onChange: setSelectedTags,
      suggestions: tagsOptions?.map(tag => ({
        label: tag.label,
        value: tag.value
      })) || []
    });
  };
  const shortenUrl = () => {
    let isValid = true;
    if (selfExplanatoryUri.trim() !== '') {
      const uriWithoutSpaces = selfExplanatoryUri.replace(/\s/g, '');
      if (encodeURIComponent(selfExplanatoryUri) !== encodeURIComponent(uriWithoutSpaces)) {
        setErrorMessage('Error: Self-Explanatory URI is not valid');
        isValid = false;
      }
    }
    if (isValid) {
      let formattedUrl = url.trim();
      if (!/^https?:\/\//i.test(formattedUrl)) {
        formattedUrl = "https://" + formattedUrl;
      }
      const separator = formattedUrl.includes('?') ? '&' : '?';
      const finalUrl = getparameter ? `${formattedUrl}${separator}${getparameter}` : formattedUrl;
      const shortenParams = {
        url: finalUrl,
        uri: selfExplanatoryUri,
        valid_until: validUntil,
        category: selectedCategories.map(cat => parseInt(cat)),
        tags: selectedTags.map(tag => parseInt(tag))
      };
      fetch('/wp-json/short-url/v1/shorten', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(shortenParams)
      }).then(response => response.json()).then(shortenData => {
        console.log('My response:', shortenData);
        if (!shortenData.error) {
          setShortenedUrl(shortenData.txt);
          setErrorMessage('');
          generateQRCode(shortenData.txt);
        } else {
          setErrorMessage('Error: ' + shortenData.txt);
          setShortenedUrl('');
        }
      }).catch(error => console.error('Error:', error));
    }
  };
  const generateQRCode = text => {
    const qr = new QRious({
      element: document.getElementById('qrcode'),
      value: text,
      size: 150
    });
    setQrCodeUrl(qr.toDataURL());
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    ...(0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__.useBlockProps)()
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__.InspectorControls, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Self-Explanatory URI')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
    value: selfExplanatoryUri,
    onChange: setSelfExplanatoryUri
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Validity')
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.DateTimePicker, {
    currentDate: validUntil,
    onChange: onChangeValidUntil,
    is12Hour: false // Set to false to use 24-hour format
    ,
    minDate: new Date() // Minimum date is the current date
    ,
    maxDate: new Date(new Date().getFullYear() + 1, new Date().getMonth(), new Date().getDate()) // Maximum date is one year from now
    ,
    isInvalidDate: date => {
      const nextYear = new Date(new Date().getFullYear() + 1, new Date().getMonth(), new Date().getDate());
      return date > nextYear;
    }
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Categories')
  }, categoriesOptions.length > 0 && renderCategories(categoriesOptions))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)('Enter URL'),
    value: url,
    onChange: setUrl
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Button, {
    isPrimary: true,
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

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"create-block/rrze-shorturl","version":"0.1.22","title":"Shorten URL RRZE","description":"A block to shorten URLs.","category":"widgets","icon":"admin-links","keywords":["url","shorten"],"textdomain":"rrze-shorturl","editorScript":"file:./index.js","supports":{"align":true},"example":{},"attributes":{"url":"https://example.com","getparameter":""}}');

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