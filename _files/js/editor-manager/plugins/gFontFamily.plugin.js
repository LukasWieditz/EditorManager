/*!
 * kl/editor-manager/plugins/gFontFamily.plugin.js
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020-2024 Lukas Wieditz
 */

(function () {
    XF.on(document.body, 'editor:start', function () {
        XF.FE.DefineIcon('xfKLEMgFontFamily', {NAME: 'fa-google fab'});

        XF.FE.RegisterCommand('gFontFamily', {
            title: 'Google Font',
            icon: 'xfKLEMgFontFamily',
            undo: true,
            focus: true,
            callback: function () {
                XF.EditorHelpers.loadDialog(this, 'gfont');
            }
        });

        XF.EditorDialogGFont = XF.extend(XF.EditorDialog, {
            _beforeShow: function (overlay) {
                document.getElementById('editor_kl_em_gfont_title').value = '';
                document.getElementById('editor_kl_em_gfont_preview').style.fontFamily = '';
            },

            _init: function (overlay) {
                document.getElementById('editor_kl_em_gfont_title')
                    .closest('form')
                    ?.submit(XF.proxy(this, 'submit'));
            },

            submit: function (e) {
                e.preventDefault();

                const ed = this.ed,
                    overlay = this.overlay;

                ed.selection.restore();
                XF.EditorHelpers.insertKLEMgFontFamily(ed, document.getElementById('editor_kl_em_gfont_title').value);

                overlay.hide();
                return false;
            }
        });

        XF.EditorHelpers.dialogs.gfont = new XF.EditorDialogGFont('gfont');

        /* Additional Helpers */
        XF.EditorHelpers.insertKLEMgFontFamily = function (ed, title) {
            if (title) {
                const titleReplace = title.replace(/\s/g, '+');

                ed.format.applyStyle('font-family', "'" + title + "'");

                const stylesheet = XF.createElement('link', {
                    rel: 'stylesheet',
                    href: 'https://fonts.googleapis.com/css2?family=' + titleReplace
                });

                const parentNode = ed.selection.element.parentNode;
                parentNode.insertBefore(stylesheet, ed.selection.element);
            }
        };
    });
})();