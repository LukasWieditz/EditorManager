<?php

/*!
 * KL/EditorManager/Admin/View/SpecialCharacters/XML.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Admin\View\SpecialCharacters;

use DOMDocument;
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
    public function renderXml() : string
    {
        /** @var DOMDocument $document */
        $document = $this->params['xml'];
        $name = $this->params['title'];
        $this->response->setDownloadFileName("{$name}.xml");
        return $document->saveXML();
    }
}