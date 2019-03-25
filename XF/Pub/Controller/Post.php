<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View;

class Post extends XFCP_Post
{
    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect|View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionReact(ParameterBag $params)
    {
        $return = parent::actionReact($params);

        if ($return instanceof View) {
            $post = $this->assertViewablePost($params->post_id);

            if (stripos($post->message, '[hide') !== false) {
                $message = \XF::app()->bbCode()->render($post->message, 'html', 'post', $post);
                $return->setJsonParam('klEMPosts', [$post->post_id => $message]);
            }
        }

        return $return;
    }
}