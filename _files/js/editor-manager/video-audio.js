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

    // Convert to mutation observer
    new MutationObserver(function (mutations) {
        mutations.forEach(mutation => {
            const addedNodes = mutation.addedNodes;
            addedNodes.forEach(addedNode => {
                if (addedNode.nodeType !== 1) {
                    return;
                }
                [...addedNode.querySelectorAll('.js-PlyrVideo')].forEach(p => new Plyr(p, videoOptions));
                [...addedNode.querySelectorAll('.js-PlyrAudio')].forEach(p => new Plyr(p, audioOptions));
            })
        });
    }).observe(document.body, {childList: true, subtree: true});
}(window, document, Plyr));