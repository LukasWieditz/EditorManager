<?php

/*!
 * KL/EditorManager/XF/Admin/Controller/ButtonManager.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Admin\Controller;

use XF\Mvc\ParameterBag;

/**
 * Class ButtonManager
 * @package KL\EditorManager\XF\Admin\Controller
 */
class ButtonManager extends XFCP_ButtonManager
{
    /**
     * @param $action
     * @param ParameterBag $params
     */
    protected function preDispatchController($action, ParameterBag $params)
    {
        parent::preDispatchController($action, $params);
        $this->setSectionContext('emLayout');
    }
}