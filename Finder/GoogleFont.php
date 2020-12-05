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