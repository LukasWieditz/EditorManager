/*!
 * kl/editor-manager/google-font.js
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017-2024 Lukas Wieditz
 */

(function (window, document, _undefined) {
    "use strict";

    XF.on(document.body, 'change pase auto-complete:insert', function (event) {
        const target = event.target.closest('#editor_kl_em_gfont_title');
        if (!target) {
            return;
        }

        const font = target.value;
        const fontStripped = font.replace(/[^A-Za-z0-9+ ]/g, '');
        const fontPreview = document.getElementById('editor_kl_em_gfont_preview');

        WebFont.load({
            google: {
                families: [fontStripped]
            },
            loading: () => {
                fontPreview.style.width = fontPreview.offsetWidth + 'px';
                fontPreview.style.fontFamily = fontStripped;
            },
            active: () => {
                textFit(fontPreview);
            }
        });
    });
}(window, document));