/*!
 * kl/editor-manager/plugins/hide.plugin.js
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

/*global console, jQuery, XF, setTimeout */
/*jshint loopfunc:true */

(function ($) {
	$.FE.DefineIcon('klHide', { NAME: 'eye-slash'});

	$.FE.RegisterCommand('klEMHide', {
		title: 'Hide',
		icon: 'klHide',
		undo: true,
		focus: true,
		callback: function() {XF.EditorHelpers.wrapSelectionText(this,'[HIDE]','[/HIDE]',true);}
	});
	$.FE.RegisterCommand('klEMHidePosts', {
		title: 'Hide Posts',
		icon: 'klHide',
		undo: true,
		focus: true,
		callback: function() {XF.EditorHelpers.wrapSelectionText(this,'[HIDEPOSTS]','[/HIDEPOSTS]',true);}
	});
	$.FE.RegisterCommand('klEMHideThanks', {
		title: 'Hide Thanks',
		icon: 'klHide',
		undo: true,
		focus: true,
		callback: function() {XF.EditorHelpers.wrapSelectionText(this,'[HIDETHANKS]','[/HIDETHANKS]',true);}
	});
	$.FE.RegisterCommand('klEMHideReply', {
		title: 'Hide Reply',
		icon: 'klHide',
		undo: true,
		focus: true,
		callback: function() {XF.EditorHelpers.wrapSelectionText(this,'[HIDEREPLY]','[/HIDEREPLY]',true);}
	});
	$.FE.RegisterCommand('klEMHideReplyThanks', {
		title: 'Hide',
		icon: 'klHide',
		undo: true,
		focus: true,
		callback: function() {XF.EditorHelpers.wrapSelectionText(this,'[HIDEREPLYTHANKS]','[/HIDEREPLYTHANKS]',true);}
	});
	$.FE.RegisterCommand('klEMHideMembers', {
		title: 'Hide',
		icon: 'klHide',
		undo: true,
		focus: true,
		callback: function() {XF.EditorHelpers.wrapSelectionText(this,'[HIDEMEMBERS]','[/HIDEMEMBERS]',true);}
	});
})(jQuery);