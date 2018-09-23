<?php

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
    public function actionLike(ParameterBag $params)
    {
        $return = parent::actionLike($params);

        if($return instanceof View) {
            $post = $this->assertViewablePost($params->post_id);

            if(stripos($post->message, '[hide') !== false) {
                $message = \XF::app()->bbCode()->render($post->message, 'html', 'post', $post);
                $return->setJsonParam('klEMPosts', [$post->post_id => $message]);
            }
        }

        return $return;
    }
}