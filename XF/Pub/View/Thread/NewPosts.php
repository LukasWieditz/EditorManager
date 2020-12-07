<?php

/*!
 * KL/EditorManager/XF/Pub/View/Thread/NewPosts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Pub\View\Thread;

use KL\EditorManager\XF\Mvc\Renderer\Json;
use XF;

/**
 * Class NewPosts
 * @package KL\EditorManager\XF\Pub\View\Thread
 *
 * @property array params
 * @property Json renderer
 */
class NewPosts extends XFCP_NewPosts
{
    /**
     *
     */
    public function renderJson(): void
    {
        $renderer = $this->renderer;

        if (isset($this->params['klEMPosts'])) {
            $posts = $this->params['klEMPosts']->toArray();
            foreach ($posts as &$post) {
                $post = XF::app()->bbCode()->render($post->message, 'html', 'post', $post);
            }
            $renderer->setKLEMPosts($posts);
        }

        if (method_exists(get_parent_class($this), 'renderJson')) {
            /** @noinspection PhpUndefinedMethodInspection */
            parent::renderJson();
        }
    }
}