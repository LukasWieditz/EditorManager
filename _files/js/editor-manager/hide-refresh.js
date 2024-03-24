/*!
 * kl/editor-manager/hide-refresh.js
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017-2024 Lukas Wieditz
 */
(function (document) {
    "use strict";
    document.addEventListener('ajax:before-success', function (event) {
        if (event.data.klEMPosts) {
            for (const postId in event.data.klEMPosts) {
                const postContent = event.data.klEMPosts[postId];
                const postSelector = `article[data-content="post-${postId}"] article.message-body`;
                const post = document.querySelector(postSelector);
                if (post) {
                    post.innerHTML = postContent;
                }
            }
        }
    });
}(document));