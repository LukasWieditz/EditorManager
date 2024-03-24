/*!
 * kl/editor-manager/edit-font.js
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017-2024 Lukas Wieditz
 */

(function (window, document, _undefined) {
    "use strict";

    document.addEventListener('change', function (event) {
        const target = event.target.closest('input[name="type"]');
        if (target === null || !target.checked) {
            return;
        }

        const containerId = `container-${target.value}`;
        const container = document.getElementById(containerId);
        if (container === null || container.offsetParent !== null) {
            return;
        }

        const containers = [...document.querySelectorAll('#container-upload, #container-web, #container-client')];
        containers.forEach((container) => {
            XF.Animate[container.id !== containerId ? 'slideUp' : 'slideDown'](container);
        });

        setTimeout(function () {
            XF.layoutChange();
        }, 100);
    });

    document.addEventListener('change', function (event) {
        const target = event.target.closest('select[name="web_service"]');
        if (target === null || !target.checked) {
            return;
        }

        const exampleId = `example-${target.value}`;
        const example = document.getElementById(exampleId);
        if (example === null || example.offsetParent !== null) {
            return;
        }

        const examples = [...document.querySelectorAll('#example-gfonts, #example-typekit, #example-webtype, #example-fonts')];
        examples.forEach((example) => {
            XF.Animate[example.id !== exampleId ? 'slideUp' : 'slideDown'](example);
        });

        setTimeout(function () {
            XF.layoutChange();
        }, 100);
    });

}(window, document));