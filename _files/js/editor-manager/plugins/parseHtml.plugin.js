/*!
 * kl/editor-manager/plugins/parsehtml.plugin.js
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020-2024 Lukas Wieditz
 */

/*global console, jQuery, XF, setTimeout */
/*jshint loopfunc:true */

(function () {
	XF.FE.DefineIcon('klEMParseHtml', { NAME: 'code'});
	XF.FE.RegisterCommand('klEMParseHtml', {
		title: 'HTML',
		icon: 'klEMParseHtml',
		undo: true,
		focus: true,
		callback: function() {
			XF.EditorHelpers.wrapSelectionText(this, '[PARSEHTML]', '[/PARSEHTML]', true);
		}
	});
})();