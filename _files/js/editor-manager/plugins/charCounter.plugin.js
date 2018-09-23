/*!
 * $_editor v2.8.4 (https://www.$.com/wysiwyg-editor)
 * License https://$.com/wysiwyg-editor/terms/
 * Copyright 2014-2018 $ Labs
 */

!function (n) {
    "function" == typeof define && define.amd ? define(["jquery"], n) : "object" == typeof module && module.exports ? module.exports = function (e, t) {
        return t === undefined && (t = "undefined" != typeof window ? require("jquery") : require("jquery")(e)), n(t)
    } : n(window.jQuery)
}(function ($) {
    $.extend($.FE.DEFAULTS, {
        charCounterMax: -1,
        charCounterCount: !0,
        charCounterMode: 'letter'
    }), $.FE.PLUGINS.charCounter = function (editor) {
        var r, functions = {
            'letter': function () {
                return (editor.el.textContent || "").replace(/\u200B/g, "").length
            },
            'word': function () {
                return editor.el.textContent ? editor.el.textContent.trim().replace(/\u200B/g, "").split(/\s+/).length : 0;
            }
        }

        function e(event) {
            if (editor.opts.charCounterMax < 0) return !0;
            if (functions[editor.opts.charCounterMode]() < editor.opts.charCounterMax) return !0;
            var target = event.which;
            return !(!editor.keys.ctrlKey(event) && editor.keys.isCharacter(t) || target === $.FE.KEYCODE.IME) || (event.preventDefault(), event.stopPropagation(), editor.events.trigger("charCounter.exceeded"), !1)
        }

        function t(event) {
            return editor.opts.charCounterMax < 0 ? event : $("<div>").html(e).text().length + functions[editor.opts.charCounterMode]() <= editor.opts.charCounterMax ? event : (editor.events.trigger("charCounter.exceeded"), "")
        }

        function u() {
            if (editor.opts.charCounterCount) {
                var e = functions[editor.opts.charCounterMode]() + (0 < editor.opts.charCounterMax ? "/" + editor.opts.charCounterMax : "");

                r.text(e), editor.opts.toolbarBottom && r.css("margin-bottom", editor.$tb.outerHeight(!0));

                var t = editor.$wp.get(0).offsetWidth - editor.$wp.get(0).clientWidth;

                0 <= t && ("rtl" == editor.opts.direction ? r.css("margin-left", t) : r.css("margin-right", t))
            }
        }

        return {
            _init: function () {
                return !!editor.$wp && !!editor.opts.charCounterCount && ((r = $('<span class="fr-counter"></span>')).css("bottom", editor.$wp.css("border-bottom-width")), editor.$box.append(r), editor.events.on("keydown", e, !0), editor.events.on("paste.afterCleanup", t), editor.events.on("keyup contentChanged input", function () {
                    editor.events.trigger("charCounter.update")
                }), editor.events.on("charCounter.update", u), editor.events.trigger("charCounter.update"), void editor.events.on("destroy", function () {
                    $(editor.o_win).off("resize.char" + editor.id), r.removeData().remove(), r = null
                }))
            },
            count: functions[editor.opts.charCounterMode]
        }
    }
});