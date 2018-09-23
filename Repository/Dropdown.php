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
 * Class Dropdown
 * @package KL\EditorManager\Repository
 */
class Dropdown extends Repository
{
    /**
     * Returns a finder for all fonts, ordered by display_order.
     * @return Finder
     */
    public function findDropdowns()
    {
        $finder = $this->finder('KL\EditorManager:Dropdown');

        return $finder;
    }

    /**
     * Returns the font caches content.
     * @return mixed|null
     */
    public function getDropdownsCached()
    {
        if ($cache = \XF::app()->simpleCache()) {
            $data = $cache->getValue('KL/EditorManager', 'kl_em_dropdowns');
            if ($data) {
                return $data;
            }
        }

        return $this->rebuildDropdownsCache();
    }

    /**
     * Rebuilds the font cache.
     * @return mixed
     */
    public function rebuildDropdownsCache()
    {
        $data = $this->stripData($this->findDropdowns()->fetch());


        if ($cache = \XF::app()->simpleCache()) {
            $cache->setValue('KL/EditorManager', 'kl_em_dropdowns', $data);
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
        $dropdowns = [];

        foreach ($data as $key => $value) {
            $dropdown_key = "dropdown-{$key}";

            $dropdowns[$dropdown_key] = new \stdClass;
            $dropdowns[$dropdown_key]->id = $value->dropdown_id;
            $dropdowns[$dropdown_key]->title = $value->title;
            $dropdowns[$dropdown_key]->icon = $value->icon;
            $dropdowns[$dropdown_key]->buttons = $value->buttons;
        }

        return $dropdowns;
    }
}