<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Admin\Controller;

use XF\Mvc\ParameterBag;

class BbCodeMediaSite extends XFCP_BbCodeMediaSite
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->setSectionContext('kl_em_bbMedia');
        parent::preDispatchController($action, $params);
    }
}