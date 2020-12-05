<?php

/*!
 * KL/EditorManager/XF/Admin/Controller/Option.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Admin\Controller;

use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\Error;
use XF\Mvc\Reply\View;

/**
 * Class Option
 * @package KL\EditorManager\XF\Admin\Controller
 */
class Option extends XFCP_Option
{
    /**
     * @param ParameterBag $params
     * @return Error|View
     */
    public function actionGroup(ParameterBag $params): AbstractReply
    {
        if ($params['group_id'] === 'klEM') {
            $this->setSectionContext('emOptions');
        }

        return parent::actionGroup($params);
    }
}