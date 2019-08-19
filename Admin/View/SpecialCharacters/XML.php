<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Admin\View\SpecialCharacters;

use XF\Mvc\View;

/**
 * Class XML
 * @package KL\EditorManager\Admin\View\SpecialCharacters
 */
class XML extends View
{
    /**
     * @return string
     */
    public function renderXml()
    {
        /** @var \DOMDocument $document */
        $document = $this->params['xml'];
        $name = $this->params['title'];
        $this->response->setDownloadFileName("{$name}.xml");
        return $document->saveXML();
    }
}