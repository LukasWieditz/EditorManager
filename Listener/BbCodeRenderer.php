<?php

namespace KL\EditorManager\Listener;

use XF\BbCode\Renderer\AbstractRenderer;

class BbCodeRenderer
{
    public static function extend(AbstractRenderer $renderer, $type)
    {
        if (method_exists($renderer, 'klFilterTags')) {
            $renderer->klFilterTags();
        }
    }
}