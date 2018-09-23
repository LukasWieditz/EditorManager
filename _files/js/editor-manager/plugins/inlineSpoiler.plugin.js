/*!
 * kl/editor-manager/plugins/inlineSpoiler.plugin.js
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

/*global console, jQuery, XF, setTimeout */
/*jshint loopfunc:true */

(function ($) {

	$.FE.DefineIcon('xfKLEMiSpoiler', { NAME: 'eye-slash'});
	$.FE.RegisterCommand('xfKLEMiSpoiler', {
		title: 'Inline Spoiler',
		icon: 'xfKLEMiSpoiler',
		undo: true,
		focus: true,
		callback: function() {
			var ed = this,
				beforeShow = function(overlay) {
					var $title = $('#editor_kl_em_ispoiler_title').val('');
					setTimeout(function() {$title.autofocus();}, 0);
				},
				init = function(overlay) {
					$('#editor_kl_em_ispoiler_submit').click(function(e) {
						e.preventDefault();
						ed.selection.restore();
						XF.EditorHelpers.insertKLEMiSpoiler(ed, $('#editor_kl_em_ispoiler_title').val());
						overlay.hide();
					});
				};

			XF.EditorHelpers.loadDialog(ed, 'ispoiler', init, beforeShow, true);
		}
	});

	/* Additional Helpers */
	XF.EditorHelpers.insertKLEMiSpoiler = function(ed, title) {
		var open;
		if (title) {
			open = '[ISPOILER="' + title + '"]';
		}
		else {
			open = '[ISPOILER]';
		}

		XF.EditorHelpers.wrapSelectionText(ed, open, '[/ISPOILER]', true);
	};
	
	
	$(document).on('editor:config', function(event, config, xfEditor) {
		XF.EditorDialogiSpoiler = XF.extend(XF.EditorDialog, {
			_beforeShow: function(overlay)
			{
				$('#editor_kl_em_ispoiler_title').val('');
			},

			_init: function(overlay)
			{
				$('#editor_kl_em_ispoiler_form').submit($.proxy(this, 'submit'));
			},

			submit: function(e)
			{
				e.preventDefault();

				var ed = this.ed,
					overlay = this.overlay;

				ed.selection.restore();
				XF.EditorHelpers.insertKLEMiSpoiler(ed, $('#editor_kl_em_ispoiler_title').val());

				overlay.hide();
			}
		});
		XF.EditorHelpers.dialogs.ispoiler = new XF.EditorDialogiSpoiler('ispoiler');
	});
})(jQuery);