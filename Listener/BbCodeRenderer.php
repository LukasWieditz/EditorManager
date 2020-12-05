<?php /** @noinspection PhpUnusedParameterInspection */

/*!
 * KL/EditorManager/Listener/BbCodeRenderer.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Listener;

use KL\EditorManager\BbCode\EditorManagerInterface;
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