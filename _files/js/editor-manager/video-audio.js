/*!
 * kl/editor-manager/hide-refresh.js
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017-2024 Lukas Wieditz
 */

(function (window, document, Plyr, _undefined) {
    "use strict";

    const videoOptions = {
        controls: ['play', 'progress', 'volume', 'mute', 'current-time', 'fullscreen'],
        keyboard: {focused: true, global: false},
        displayDuration: true,
    };
    Array.from(document.querySelectorAll('.js-PlyrVideo')).map(p => new Plyr(p, videoOptions));
    const audioOptions = {
        controls: ['play', 'progress', 'volume', 'mute', 'current-time'],
        keyboard: {focused: true, global: false},
        displayDuration: true,
    };
    Array.from(document.querySelectorAll('.js-PlyrAudio')).map(p => new Plyr(p, audioOptions));

    document.addEventListener('DOMNodeInserted', function () {
        Array.from(document.querySelectorAll('.js-PlyrVideo')).map(p => new Plyr(p, videoOptions));
        Array.from(document.querySelectorAll('.js-PlyrAudio')).map(p => new Plyr(p, audioOptions));
    });

}(window, document, Plyr));