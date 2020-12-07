<?php

/*!
 * KL/EditorManager/XF/Admin/Controller/BbCodeMediaSite.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Admin\Controller;

use XF\Mvc\ParameterBag;

/**
 * Class BbCodeMediaSite
 * @package KL\EditorManager\XF\Admin\Controller
 */
class BbCodeMediaSite extends XFCP_BbCodeMediaSite
{
    /**
     * @param $action
     * @param ParameterBag $params
     */
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->setSectionContext('kl_em_bbMedia');
        parent::preDispatchController($action, $params);
    }
}