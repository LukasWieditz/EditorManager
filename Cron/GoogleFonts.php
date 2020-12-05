<?php

/*!
 * KL/EditorManager/Cron/GoogleFonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Cron;

use GuzzleHttp\Exception\GuzzleException;
use KL\EditorManager\Repository\GoogleFont;
use XF;
use XF\PrintableException;

/**
 * Class GoogleFonts
 * @package KL\EditorManager\Cron
 */
class GoogleFonts
{
    /**
     * @throws PrintableException
     * @throws GuzzleException
     */
    public static function run() : void
    {
        /** @var GoogleFont $repo */
        $repo = XF::repository('KL\EditorManager:GoogleFont');
        $repo->updateFontList();
    }
}