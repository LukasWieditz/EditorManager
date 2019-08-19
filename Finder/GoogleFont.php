<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
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
     * @return $this
     */
    public function active()
    {
        $this->where('active', '=', 1);

        $this->setDefaultOrder('font_id', 'ASC');

        return $this;
    }
}