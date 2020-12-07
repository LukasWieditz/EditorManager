<?php

namespace KL\EditorManager\XF\Mvc\Renderer;

/**
 * Class Json
 * @package KL\EditorManager\XF\Mvc\Renderer
 */
class Json extends XFCP_Json
{
    /**
     * @var array
     */
    protected $klEMPosts;

    /**
     * @param array $posts
     */
    public function setKLEMPosts(array $posts): void
    {
        $this->klEMPosts = $posts;
    }

    /**
     * @param array $content
     * @return array
     */
    protected function addDefaultJsonParams(array $content): array
    {
        if ($this->klEMPosts) {
            $content['klEMPosts'] = $this->klEMPosts;
        }

        return parent::addDefaultJsonParams($content);
    }
}
