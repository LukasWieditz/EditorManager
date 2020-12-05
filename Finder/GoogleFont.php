<?php

/*!
 * KL/EditorManager/Finder/GoogleFont.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Finder;

use XF\Mvc\Entity\Finder;

/**
 * Class GoogleFont
 * @package KL\EditorManager\Finder
 */
class GoogleFont extends Finder
{
    /**
     * @param string $searchTerm
     * @return GoogleFont
     */
    public function whereIdLike(string $searchTerm): GoogleFont
    {
        return $this->where('font_id', 'like', $searchTerm);
    }

    /**
     * @return GoogleFont
     */
    public function active(): GoogleFont
    {
        return $this->where('active', '=', 1);
    }

    /**
     * @return GoogleFont
     */
    public function inactive(): GoogleFont
    {
        return $this->where('active', '<>', 1);
    }
}