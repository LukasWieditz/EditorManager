<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Admin\Controller;

use XF\Mvc\ParameterBag;

class Option extends XFCP_Option
{
    public function actionGroup(ParameterBag $params)
    {
        if ($params->group_id === 'klEM') {
            $this->setSectionContext('emOptions');
        }

        return parent::actionGroup($params);
    }
}