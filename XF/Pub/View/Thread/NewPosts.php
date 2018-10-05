<?php

namespace KL\EditorManager\XF\Pub\View\Thread;

class NewPosts extends XFCP_NewPosts
{
    public function renderJson()
    {
        if(isset($this->params['klEMPosts'])) {
            $posts = $this->params['klEMPosts'];
            foreach ($posts as &$post) {
                $post = \XF::app()->bbCode()->render($post->message, 'html', 'post', $post);
            }
            $this->params['klEMPosts'] = $posts;
        }

        return parent::renderJson();
    }
}