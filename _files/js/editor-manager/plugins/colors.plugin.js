/*!
 * kl/editor-manager/plugins/colors.plugin.js
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

/*global console, jQuery, XF, setTimeout */
/*jshint loopfunc:true */

(function ($) {
	$(document).on('editor:init', function(event, ed, xfEditor) {
		/* Enable/Disable Color Swapper Toolbar */
		if(ed.opts.colorTypes.bgcolor && ed.opts.colorTypes.color) {
			ed.events.on('popups.show.colors.picker', function() {
				$(ed.popups.get('colors.picker')).find('.fr-colors-buttons').css('display', 'block');
			});
		}
		else if(!ed.opts.colorTypes.color) {
			ed.events.on('popups.show.colors.picker', function() {
				$(ed.popups.get('colors.picker')).find('.fr-colors-tab').toggleClass('fr-selected-tab');
				$(ed.popups.get('colors.picker')).find('.fr-color-set').toggle();
			});
		}
	});
})(jQuery);