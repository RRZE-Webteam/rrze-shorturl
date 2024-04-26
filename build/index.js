(()=>{var e={576:function(e){var t;t=function(){return function(){var e={686:function(e,t,n){"use strict";n.d(t,{default:function(){return w}});var r=n(279),o=n.n(r),a=n(370),i=n.n(a),c=n(817),u=n.n(c);function l(e){try{return document.execCommand(e)}catch(e){return!1}}var s=function(e){var t=u()(e);return l("cut"),t},f=function(e,t){var n=function(e){var t="rtl"===document.documentElement.getAttribute("dir"),n=document.createElement("textarea");n.style.fontSize="12pt",n.style.border="0",n.style.padding="0",n.style.margin="0",n.style.position="absolute",n.style[t?"right":"left"]="-9999px";var r=window.pageYOffset||document.documentElement.scrollTop;return n.style.top="".concat(r,"px"),n.setAttribute("readonly",""),n.value=e,n}(e);t.container.appendChild(n);var r=u()(n);return l("copy"),n.remove(),r},d=function(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{container:document.body},n="";return"string"==typeof e?n=f(e,t):e instanceof HTMLInputElement&&!["text","search","url","tel","password"].includes(null==e?void 0:e.type)?n=f(e.value,t):(n=u()(e),l("copy")),n};function p(e){return p="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},p(e)}function y(e){return y="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},y(e)}function h(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}function v(e,t){return v=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e},v(e,t)}function m(e){return m=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)},m(e)}function g(e,t){var n="data-clipboard-".concat(e);if(t.hasAttribute(n))return t.getAttribute(n)}var b=function(e){!function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),t&&v(e,t)}(u,e);var t,n,r,o,a,c=(o=u,a=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Date.prototype.toString.call(Reflect.construct(Date,[],(function(){}))),!0}catch(e){return!1}}(),function(){var e,t,n=m(o);if(a){var r=m(this).constructor;e=Reflect.construct(n,arguments,r)}else e=n.apply(this,arguments);return!(t=e)||"object"!==y(t)&&"function"!=typeof t?function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(this):t});function u(e,t){var n;return function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,u),(n=c.call(this)).resolveOptions(t),n.listenClick(e),n}return t=u,n=[{key:"resolveOptions",value:function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};this.action="function"==typeof e.action?e.action:this.defaultAction,this.target="function"==typeof e.target?e.target:this.defaultTarget,this.text="function"==typeof e.text?e.text:this.defaultText,this.container="object"===y(e.container)?e.container:document.body}},{key:"listenClick",value:function(e){var t=this;this.listener=i()(e,"click",(function(e){return t.onClick(e)}))}},{key:"onClick",value:function(e){var t=e.delegateTarget||e.currentTarget,n=this.action(t)||"copy",r=function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{},t=e.action,n=void 0===t?"copy":t,r=e.container,o=e.target,a=e.text;if("copy"!==n&&"cut"!==n)throw new Error('Invalid "action" value, use either "copy" or "cut"');if(void 0!==o){if(!o||"object"!==p(o)||1!==o.nodeType)throw new Error('Invalid "target" value, use a valid Element');if("copy"===n&&o.hasAttribute("disabled"))throw new Error('Invalid "target" attribute. Please use "readonly" instead of "disabled" attribute');if("cut"===n&&(o.hasAttribute("readonly")||o.hasAttribute("disabled")))throw new Error('Invalid "target" attribute. You can\'t cut text from elements with "readonly" or "disabled" attributes')}return a?d(a,{container:r}):o?"cut"===n?s(o):d(o,{container:r}):void 0}({action:n,container:this.container,target:this.target(t),text:this.text(t)});this.emit(r?"success":"error",{action:n,text:r,trigger:t,clearSelection:function(){t&&t.focus(),window.getSelection().removeAllRanges()}})}},{key:"defaultAction",value:function(e){return g("action",e)}},{key:"defaultTarget",value:function(e){var t=g("target",e);if(t)return document.querySelector(t)}},{key:"defaultText",value:function(e){return g("text",e)}},{key:"destroy",value:function(){this.listener.destroy()}}],r=[{key:"copy",value:function(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{container:document.body};return d(e,t)}},{key:"cut",value:function(e){return s(e)}},{key:"isSupported",value:function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:["copy","cut"],t="string"==typeof e?[e]:e,n=!!document.queryCommandSupported;return t.forEach((function(e){n=n&&!!document.queryCommandSupported(e)})),n}}],n&&h(t.prototype,n),r&&h(t,r),u}(o()),w=b},828:function(e){if("undefined"!=typeof Element&&!Element.prototype.matches){var t=Element.prototype;t.matches=t.matchesSelector||t.mozMatchesSelector||t.msMatchesSelector||t.oMatchesSelector||t.webkitMatchesSelector}e.exports=function(e,t){for(;e&&9!==e.nodeType;){if("function"==typeof e.matches&&e.matches(t))return e;e=e.parentNode}}},438:function(e,t,n){var r=n(828);function o(e,t,n,r,o){var i=a.apply(this,arguments);return e.addEventListener(n,i,o),{destroy:function(){e.removeEventListener(n,i,o)}}}function a(e,t,n,o){return function(n){n.delegateTarget=r(n.target,t),n.delegateTarget&&o.call(e,n)}}e.exports=function(e,t,n,r,a){return"function"==typeof e.addEventListener?o.apply(null,arguments):"function"==typeof n?o.bind(null,document).apply(null,arguments):("string"==typeof e&&(e=document.querySelectorAll(e)),Array.prototype.map.call(e,(function(e){return o(e,t,n,r,a)})))}},879:function(e,t){t.node=function(e){return void 0!==e&&e instanceof HTMLElement&&1===e.nodeType},t.nodeList=function(e){var n=Object.prototype.toString.call(e);return void 0!==e&&("[object NodeList]"===n||"[object HTMLCollection]"===n)&&"length"in e&&(0===e.length||t.node(e[0]))},t.string=function(e){return"string"==typeof e||e instanceof String},t.fn=function(e){return"[object Function]"===Object.prototype.toString.call(e)}},370:function(e,t,n){var r=n(879),o=n(438);e.exports=function(e,t,n){if(!e&&!t&&!n)throw new Error("Missing required arguments");if(!r.string(t))throw new TypeError("Second argument must be a String");if(!r.fn(n))throw new TypeError("Third argument must be a Function");if(r.node(e))return function(e,t,n){return e.addEventListener(t,n),{destroy:function(){e.removeEventListener(t,n)}}}(e,t,n);if(r.nodeList(e))return function(e,t,n){return Array.prototype.forEach.call(e,(function(e){e.addEventListener(t,n)})),{destroy:function(){Array.prototype.forEach.call(e,(function(e){e.removeEventListener(t,n)}))}}}(e,t,n);if(r.string(e))return function(e,t,n){return o(document.body,e,t,n)}(e,t,n);throw new TypeError("First argument must be a String, HTMLElement, HTMLCollection, or NodeList")}},817:function(e){e.exports=function(e){var t;if("SELECT"===e.nodeName)e.focus(),t=e.value;else if("INPUT"===e.nodeName||"TEXTAREA"===e.nodeName){var n=e.hasAttribute("readonly");n||e.setAttribute("readonly",""),e.select(),e.setSelectionRange(0,e.value.length),n||e.removeAttribute("readonly"),t=e.value}else{e.hasAttribute("contenteditable")&&e.focus();var r=window.getSelection(),o=document.createRange();o.selectNodeContents(e),r.removeAllRanges(),r.addRange(o),t=r.toString()}return t}},279:function(e){function t(){}t.prototype={on:function(e,t,n){var r=this.e||(this.e={});return(r[e]||(r[e]=[])).push({fn:t,ctx:n}),this},once:function(e,t,n){var r=this;function o(){r.off(e,o),t.apply(n,arguments)}return o._=t,this.on(e,o,n)},emit:function(e){for(var t=[].slice.call(arguments,1),n=((this.e||(this.e={}))[e]||[]).slice(),r=0,o=n.length;r<o;r++)n[r].fn.apply(n[r].ctx,t);return this},off:function(e,t){var n=this.e||(this.e={}),r=n[e],o=[];if(r&&t)for(var a=0,i=r.length;a<i;a++)r[a].fn!==t&&r[a].fn._!==t&&o.push(r[a]);return o.length?n[e]=o:delete n[e],this}},e.exports=t,e.exports.TinyEmitter=t}},t={};function n(r){if(t[r])return t[r].exports;var o=t[r]={exports:{}};return e[r](o,o.exports,n),o.exports}return n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,{a:t}),t},n.d=function(e,t){for(var r in t)n.o(t,r)&&!n.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n(686)}().default},e.exports=t()}},t={};function n(r){var o=t[r];if(void 0!==o)return o.exports;var a=t[r]={exports:{}};return e[r].call(a.exports,a,a.exports,n),a.exports}n.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return n.d(t,{a:t}),t},n.d=(e,t)=>{for(var r in t)n.o(t,r)&&!n.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{"use strict";const e=window.wp.blocks,t=JSON.parse('{"UU":"create-block/rrze-shorturl","DD":"Shorten URL RRZE"}'),r=window.React,o=window.wp.element,a=window.wp.components,i=window.wp.blockEditor,c=window.wp.i18n;var u=n(576),l=n.n(u);(0,e.registerBlockType)(t.UU,{title:t.DD,edit:({attributes:e,setAttributes:t})=>{const{valid_until:n}=e,[u,s]=(0,o.useState)(""),[f,d]=(0,o.useState)(""),[p,y]=(0,o.useState)(""),[h,v]=(0,o.useState)(n),[m,g]=(0,o.useState)([]),[b,w]=(0,o.useState)(""),[E,S]=(0,o.useState)(""),[x,C]=(0,o.useState)([]),[T,k]=(0,o.useState)(!1);let _;return(0,o.useEffect)((()=>{if(!h){const e=new Date,n=new Date(e.getFullYear()+1,e.getMonth(),e.getDate());v(n),t({valid_until:n})}return _=new(l())(".btn",{text:function(){return f}}),_.on("success",(function(e){k(!0),e.clearSelection()})),_.on("error",(function(e){console.error("Copy failed:",e.action)})),()=>{_&&_.destroy()}}),[f]),(0,r.createElement)("div",{...(0,i.useBlockProps)()},(0,r.createElement)(i.InspectorControls,null,(0,r.createElement)(a.PanelBody,{title:(0,c.__)("Self-Explanatory URI")},(0,r.createElement)(a.TextControl,{value:p,onChange:y})),(0,r.createElement)(a.PanelBody,{title:(0,c.__)("Validity")},(0,r.createElement)(a.DateTimePicker,{currentDate:h,onChange:e=>{v(e),t({valid_until:e})},timeFormat:!1,minDate:new Date,maxDate:new Date((new Date).getFullYear()+1,(new Date).getMonth(),(new Date).getDate()),isInvalidDate:e=>e>new Date((new Date).getFullYear()+1,(new Date).getMonth(),(new Date).getDate())})),(0,r.createElement)(a.PanelBody,{title:(0,c.__)("Categories")},x.map((e=>(0,r.createElement)("div",{key:e.value},(0,r.createElement)("input",{type:"checkbox",value:e.value,checked:m.includes(e.value),onChange:t=>{const n=t.target.checked;g(n?[...m,e.value]:m.filter((t=>t!==e.value)))}})," ",(0,r.createElement)("label",null,e.label),(0,r.createElement)("br",null)))),(0,r.createElement)("a",{href:"#",onClick:()=>{const e=prompt("Enter the label of the new category:");e&&fetch("/wp-json/short-url/v1/add-category",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({label:e})}).then((e=>{if(!e.ok)throw new Error("Failed to add category");return e.json()})).then((e=>{const t=[...x,{label:e.label,value:e.id}];C(t)})).catch((e=>{console.error("Error adding category:",e)}))}},"Add New Category"))),(0,r.createElement)(a.TextControl,{label:(0,c.__)("Enter URL"),value:u,onChange:s}),(0,r.createElement)(a.Button,{isPrimary:!0,onClick:()=>{let e=!0;if(""!==p.trim()){const t=p.replace(/\s/g,"");encodeURIComponent(p)!==encodeURIComponent(t)&&(w("Error: Self-Explanatory URI is not valid"),e=!1)}}},(0,c.__)("Shorten URL")),b&&(0,r.createElement)("p",{class:"shorturl-error-msg"},b),f&&(0,r.createElement)("div",null,(0,r.createElement)("p",null,(0,c.__)("Shortened URL"),": ",f,"  ",(0,r.createElement)("button",{class:"btn","data-clipboard-target":"#foo"},(0,r.createElement)("img",{src:"data:image/svg+xml,%3Csvg height='1024' width='896' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M128 768h256v64H128v-64z m320-384H128v64h320v-64z m128 192V448L384 640l192 192V704h320V576H576z m-288-64H128v64h160v-64zM128 704h160v-64H128v64z m576 64h64v128c-1 18-7 33-19 45s-27 18-45 19H64c-35 0-64-29-64-64V192c0-35 29-64 64-64h192C256 57 313 0 384 0s128 57 128 128h192c35 0 64 29 64 64v320h-64V320H64v576h640V768zM128 256h512c0-35-29-64-64-64h-64c-35 0-64-29-64-64s-29-64-64-64-64 29-64 64-29 64-64 64h-64c-35 0-64 29-64 64z'/%3E%3C/svg%3E",alt:"Copy to clipboard",onClick:()=>{if(console.log("handleCopy clicked"),f)if(navigator.clipboard)navigator.clipboard.writeText(f).then((()=>{k(!0)})).catch((e=>{console.error("Copy failed:",e)}));else{const e=document.createElement("textarea");e.value=f,document.body.appendChild(e),e.focus(),e.select();try{document.execCommand("copy"),k(!0)}catch(e){console.error("Copy failed:",e)}document.body.removeChild(e)}},class:"shorturl-copy-img"}))," ",T&&(0,r.createElement)("span",null,"URL copied!")),(0,r.createElement)("img",{src:E,alt:"QR Code"})))},save:()=>null})})()})();