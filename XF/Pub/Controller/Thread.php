<?php

namespace KL\EditorManager\XF\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View;

class Thread extends XFCP_Thread
{
    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionAddReply(ParameterBag $params)
    {
        $return = parent::actionAddReply($params);

        if ($return instanceof View) {
            $messagesPerPage = \XF::options()->messagesPerPage;
            $offset = max(0 , ($this->filter('klPage', 'uint') ?: 0) - 1) * $messagesPerPage;

            $thread = $this->assertViewableThread($params->thread_id);

            $finder = \XF::finder('XF:Post');

            $posts = $finder
                ->where('thread_id', '=', $thread->thread_id)
                ->whereSQL("LOWER(message) LIKE '%[hide%'")
                ->fetch($messagesPerPage, $offset);

            if ($posts->count()) {
                $renderedPosts = [];

                foreach ($posts as $post) {
                    $message = \XF::app()->bbCode()->render($post->message, 'html', 'post', $post);
                    $renderedPosts[$post->post_id] = $message;;
                }

                $return->setJsonParam('klEMPosts', $renderedPosts);
            }
        }

        return $return;
    }
}