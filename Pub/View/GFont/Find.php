<?php

namespace KL\EditorManager\Pub\View\GFont;

use XF\Mvc\View;

/**
 * Class Find
 * @package KL\EditorManager\Pub\View\GFont
 */
class Find extends View
{
    /**
     * @return array
     */
    public function renderJson()
    {
        $results = [];
        foreach ($this->params['fonts'] AS $font)
        {
            $results[] = [
                'id' => $font->font_id,
                'text' => $font->font_id,
                'q' => $this->params['q']
            ];
        }

        return [
            'results' => $results,
            'q' => $this->params['q']
        ];
    }
}