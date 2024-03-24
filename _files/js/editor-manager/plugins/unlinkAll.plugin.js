/*!
 * kl/editor-manager/plugins/unlinkAll.plugin.js
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020-2024 Lukas Wieditz
 */

(function () {
    XF.FE.DefineIcon('klUnlinkAll', {NAME: 'unlink'});
    XF.FE.RegisterCommand('klUnlinkAll', {
        title: 'unlink all links',
        focus: true,
        icon: 'klUnlinkAll',
        undo: true,
        refreshAfterCallback: true,
        callback: function (e) {
            const container = document.querySelector('.fr-view');
            [...container.querySelectorAll("a")].forEach(el =>  {
                el.outerHTML = el.innerHTML;
            });
        }
    });
})();