/*!
* kl/editor-manager/base.js
* License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
* Copyright 2017-2024 Lukas Wieditz
*/

(function (window, document, _undefined) {
	"use strict";

	const copyProperties = function (to, from) {
		for (const key in from) {
			if (Object.prototype.hasOwnProperty.call(from, key)) {
				to[key] = from[key];
			}
		}
	};

	document.addEventListener('editor:config', function(event, config, xfEditor) {
		let newConfig;
		
		// Add Link To Allowed Tags
		config.htmlAllowedTags.push('link');
		
		/* Load config overwrites */
		try {
			newConfig = JSON.parse(document.querySelector('.js-klEditorConfig').innerHTML) || {};
			copyProperties(config, newConfig);
			Object.assign(config, newConfig);
		} catch (e) {
			console.error(e);
		}
	});
}(window, document));