<?php

/*!
 * KL/EditorManager/Pub/Controller/Account.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Pub\Controller;

use XF\Entity\User;
use XF\Mvc\FormAction;
use \XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;

/**
 * Class Account
 * @package KL\EditorManager\XF\Pub\Controller
 */
class Account extends XFCP_Account
{
    /**
     * Proxy wrong link to correct controller.
     * @param ParameterBag $params
     * @return AbstractReply
     */
    public function actionTemplates(ParameterBag $params): AbstractReply
    {
        return $this->redirectPermanently($this->buildLink('account/templates', $params));
    }

    /**
     * @param User $visitor
     * @return FormAction
     */
    protected function preferencesSaveProcess(User $visitor): FormAction
    {
        $form = parent::preferencesSaveProcess($visitor);

        $input = [
            'kl_em_wordcount_mode' => $this->filter('kl_em_wordcount_mode', 'str')
        ];

        $form->basicEntitySave($visitor, $input);

        return $form;
    }
}