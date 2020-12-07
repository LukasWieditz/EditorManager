<?php

/*!
 * KL/EditorManager/XF/Pub/Controller/Thread.php
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
 * Class Thread
 * @package KL\EditorManager\XF\Pub\Controller
 */
class Thread extends XFCP_Thread
{
    /**
     * @param ParameterBag $params
     * @return Redirect|View
     * @throws Exception
     */
    public function actionAddReply(ParameterBag $params)
    {
        $response = parent::actionAddReply($params);

        if ($response instanceof View) {
            $messagesPerPage = XF::options()->messagesPerPage;
            $offset = max(0, ($this->filter('klPage', 'uint') ?: 0) - 1) * $messagesPerPage;

            $thread = $response->getParam('thread') ?: $this->assertViewableThread($params['thread_id']);

            /** @var \KL\EditorManager\XF\Finder\Post $postFinder */
            $postFinder = $this->getPostRepo()
                ->findPostsForThreadView($thread);

            $posts = $postFinder
                ->whereContainsKLHideBBCode()
                ->fetch($messagesPerPage, $offset);

            $response->setParam('klEMPosts', $posts);
        }

        return $response;
    }
}