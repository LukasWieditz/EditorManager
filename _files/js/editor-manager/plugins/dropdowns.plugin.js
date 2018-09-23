/*!
 * kl/editor-manager/plugins/dropdowns.plugin.js
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

/*global console, jQuery, XF, setTimeout */
/*jshint loopfunc:true */

(function ($) {
	$(document).on('editor:config', function(event, config, xfEditor) {
		var dropdowns;

		/* Load dropdowns */
		try {
			dropdowns = $.parseJSON($('.js-klEditorDropdowns').first().html()) || {};
		} catch (e) {
			console.error(e);
			dropdowns = [];
		}

		/* Register dropdowns */
		for(var i in dropdowns) {
			if(dropdowns[i].buttons.length) {
				(function(dropdown, name)
				 {
					$.FE.DefineIcon(name, { NAME: dropdown.icon });

					$.FE.RegisterCommand(name, {
						type: 'dropdown',
						title: dropdown.title,
						icon: name,
						undo: false,
						focus: true,
						id: i,
						html: function()
						{
							var o = '<ul class="fr-dropdown-list">',
								options = dropdown.buttons,
								c, info;
							for (var j in options)
							{
								c = options[j];
								info = $.FE.COMMANDS[c];
								o += '<li><a class="fr-command" data-cmd="' + c + '">' + this.icon.create(info.icon || c) + '&nbsp;&nbsp;' + this.language.translate(info.title) + '</a></li>';
							}
							o += '</ul>';

							return o;
						}
					});
				})(dropdowns[i], i);
			}
		}
	});
})(jQuery);