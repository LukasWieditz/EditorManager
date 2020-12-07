<?php

/*!
 * KL/EditorManager/Pub/Controller/Post.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Pub\Controller;

use XF;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Exception;
use XF\Mvc\Reply\Redirect;
use XF\Mvc\Reply\View;

/**
 * Class Post
 * @package KL\EditorManager\XF\Pub\Controller
 */
class Post extends XFCP_Post
{
    /**
     * @param ParameterBag $params
     * @return Redirect|View
     * @throws Exception
     */
    public function actionReact(ParameterBag $params)
    {
        $return = parent::actionReact($params);

        if ($return instanceof View) {
            $post = $this->assertViewablePost($params['post_id']);

            if (stripos($post->message, '[hide') !== false) {
                $message = XF::app()->bbCode()->render($post->message, 'html', 'post', $post);
                $return->setJsonParam('klEMPosts', [$post->post_id => $message]);
            }
        }

        return $return;
    }
}