<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Pub\View\Thread;

/**
 * Class NewPosts
 * @package KL\EditorManager\XF\Pub\View\Thread
 *
 * @property array params
 */
class NewPosts extends XFCP_NewPosts
{
    /**
     *
     */
    public function renderJson()
    {
        if (isset($this->params['klEMPosts'])) {
            $posts = $this->params['klEMPosts'];
            foreach ($posts as &$post) {
                $post = \XF::app()->bbCode()->render($post->message, 'html', 'post', $post);
            }
            $this->params['klEMPosts'] = $posts;
        }

        if (method_exists(get_parent_class($this), 'renderJson')) {
            parent::renderJson();
        }
    }
}