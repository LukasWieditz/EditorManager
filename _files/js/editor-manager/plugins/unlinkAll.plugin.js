/*!
 * kl/editor-manager/plugins/unlinkAll.plugin.js
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

/*global console, jQuery, XF, setTimeout */
/*jshint loopfunc:true */

(function ($) {
    $.FroalaEditor.DefineIcon('klUnlinkAll', {NAME: 'unlink'});
    $.FroalaEditor.RegisterCommand('klUnlinkAll', {
        title: 'unlink all links',
        focus: true,
        undo: true,
        refreshAfterCallback: true,
        callback: function (e) {
            $('.fr-view').find("a").each(function () {
                var elem = $(this);
                elem.before(elem.html());
                elem.remove();
            });
        }
    });
})(jQuery);