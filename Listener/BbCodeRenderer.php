<?php

namespace KL\EditorManager\Listener;

use XF\BbCode\Renderer\AbstractRenderer;

/**
 * Class BbCodeRenderer
 * @package KL\EditorManager\Listener
 */
class BbCodeRenderer
{
    /**
     * @param AbstractRenderer $renderer
     * @param $type
     */
    public static function extend(AbstractRenderer $renderer, $type)
    {
        if (method_exists($renderer, 'klFilterTags')) {
            $renderer->klFilterTags();
        }
    }
}