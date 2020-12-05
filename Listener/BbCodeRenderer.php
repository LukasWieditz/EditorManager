<?php /** @noinspection PhpUnusedParameterInspection */

namespace KL\EditorManager\Listener;

use KL\EditorManager\BbCode\EditorManagerInterface;
use KL\EditorManager\XF\BbCode\Renderer\EditorManagerTrait;
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
    public static function extend(AbstractRenderer $renderer, $type) : void
    {
        if($renderer instanceof EditorManagerInterface) {
            $renderer->klFilterTags();
        }
    }
}