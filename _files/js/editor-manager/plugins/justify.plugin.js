/*!
 * kl/editor-manager/plugins/justify.plugin.js
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

/*global console, jQuery, XF, setTimeout */
/*jshint loopfunc:true */

(function ($) {
	$(document).on('editor:init', function(event, ed, xfEditor) {
		/* Re-Enable Justify Alignment Button */
		ed.$tb.find('[data-cmd=align][data-param1=justify]').closest('li').css('display', 'block');
	});
})(jQuery);