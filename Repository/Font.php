<?php

/*!
 * KL/EditorManager/Repository/Font.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

/**
 * Class Font
 * @package KL\EditorManager\Repository
 */
class Font extends Repository
{
    /**
     * Returns a finder for all fonts, ordered by display_order.
     * @return Finder
     */
    public function findFonts()
    {
        $finder = $this->finder('KL\EditorManager:Font');
        $finder
            ->setDefaultOrder('display_order', 'ASC');

        return $finder;
    }

    /**
     * Returns the font caches content.
     * @return mixed|null
     */
    public function getFontsCached()
    {
        if ($cache = \XF::app()->simpleCache()) {
            $data = $cache->getValue('KL/EditorManager', 'kl_em_fonts');
            if ($data) {
                return $data;
            }
        }

        $data = $this->stripData($this->findFonts()->fetch());


        if ($cache) {
            $cache->setValue('KL/EditorManager', 'kl_em_fonts', $data);
        }
        return $data;
    }

    /**
     * Rebuilds the font cache.
     * @return mixed
     */
    public function rebuildFontCache()
    {
        $data = $this->stripData($this->findFonts()->fetch());


        if ($cache = \XF::app()->simpleCache()) {
            $cache->setValue('KL/EditorManager', 'kl_em_fonts', $data);
        }
        return $data;
    }

    /**
     * Removes all unnecessary data for the font cache and builds a new object for each font.
     * @param $data
     * @return mixed
     */
    private function stripData($data)
    {
        $fonts = [];

        foreach ($data as $key => $value) {
            if ($value->active) {
                $fonts[$key] = new \stdClass;

                $fonts[$key]->font_id = $value->font_id;
                $fonts[$key]->title = $value->title;
                $fonts[$key]->type = $value->type;
                $fonts[$key]->family = $value->family;
                $fonts[$key]->display_order = $value->display_order;
                $fonts[$key]->extra_data = $value->extra_data;
            }
        }

        return $fonts;
    }
}