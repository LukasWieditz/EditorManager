<?php

/*!
 * KL/EditorManager/Repository/Font.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Repository;

use KL\EditorManager\Finder\Font as FontFinder;
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
     * @return FontFinder|Finder
     */
    public function findFonts(): FontFinder
    {
        return $this->finder('KL\EditorManager:Font')
            ->setDefaultOrder('display_order', 'ASC');
    }
}