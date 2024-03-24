/*!
 * kl/editor-manager/plugins/hide.plugin.js
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020-2024 Lukas Wieditz
 */

(function () {
    XF.FE.DefineIcon('klHide', {NAME: 'eye-slash'});
    XF.FE.DefineIcon('klHidePosts', {NAME: 'minus-circle'});
    XF.FE.DefineIcon('klHideReply', {NAME: 'minus-octagon'});
    XF.FE.DefineIcon('klHideThanks', {NAME: 'minus-hexagon'});
    XF.FE.DefineIcon('klHideReplyThanks', {NAME: 'minus-square'});
    XF.FE.DefineIcon('klHideMembers', {NAME: 'user-minus'});
    XF.FE.DefineIcon('klHideDate', {NAME: 'stopwatch'});
    XF.FE.DefineIcon('klHideGroup', {NAME: 'folder-minus'});

    XF.FE.RegisterCommand('klEMHide', {
        title: 'Hide',
        icon: 'klHide',
        undo: true,
        focus: true,
        callback: function () {
            XF.EditorHelpers.wrapSelectionText(this, '[HIDE]', '[/HIDE]', true);
        }
    });
    XF.FE.RegisterCommand('klEMHidePosts', {
        title: 'Hide Posts',
        icon: 'klHidePosts',
        undo: true,
        focus: true,
        callback: function () {
            XF.EditorHelpers.wrapSelectionText(this, '[HIDEPOSTS]', '[/HIDEPOSTS]', true);
        }
    });
    XF.FE.RegisterCommand('klEMHideThanks', {
        title: 'Hide Thanks',
        icon: 'klHideThanks',
        undo: true,
        focus: true,
        callback: function () {
            XF.EditorHelpers.wrapSelectionText(this, '[HIDETHANKS]', '[/HIDETHANKS]', true);
        }
    });
    XF.FE.RegisterCommand('klEMHideReply', {
        title: 'Hide Reply',
        icon: 'klHideReply',
        undo: true,
        focus: true,
        callback: function () {
            XF.EditorHelpers.wrapSelectionText(this, '[HIDEREPLY]', '[/HIDEREPLY]', true);
        }
    });
    XF.FE.RegisterCommand('klEMHideReplyThanks', {
        title: 'Hide Reply Thanks',
        icon: 'klHideReplyThanks',
        undo: true,
        focus: true,
        callback: function () {
            XF.EditorHelpers.wrapSelectionText(this, '[HIDEREPLYTHANKS]', '[/HIDEREPLYTHANKS]', true);
        }
    });
    XF.FE.RegisterCommand('klEMHideMembers', {
        title: 'Hide Members',
        icon: 'klHideMembers',
        undo: true,
        focus: true,
        callback: function () {
            XF.EditorHelpers.wrapSelectionText(this, '[HIDEMEMBERS]', '[/HIDEMEMBERS]', true);
        }
    });
    XF.FE.RegisterCommand('klEMHideMembers', {
        title: 'Hide Group',
        icon: 'klHideGroup',
        undo: true,
        focus: true,
        callback: function () {
            XF.EditorHelpers.wrapSelectionText(this, '[HIDEGROUP=]', '[/HIDEGROUP]', true);
        }
    });
    XF.FE.RegisterCommand('klEMHideDate', {
        title: 'Hide Date',
        icon: 'klHideDate',
        undo: true,
        focus: true,
        callback: function () {
            XF.EditorHelpers.wrapSelectionText(this, '[HIDEDATE=]', '[/HIDEDATE]', true);
        }
    });
})();