<?php

namespace KL\EditorManager\Admin\View\SpecialCharacters;

use XF\Mvc\View;

class XML extends View
{
    public function renderXml()
    {
        /** @var \DOMDocument $document */
        $document = $this->params['xml'];
        $name = $this->params['title'];
        $this->response->setDownloadFileName("{$name}.xml");
        return $document->saveXML();
    }
}