<?php

/*!
 * KL/EditorManager/Pub/Controller/Account.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */
	
namespace KL\EditorManager\XF\Pub\Controller;

use XF\Entity\User;
use \XF\Mvc\ParameterBag;

class Account extends XFCP_Account {
    /**
     * Proxy wrong link to correct controller.
     * @param ParameterBag $params
     * @return mixed
     */
    public function actionTemplates(ParameterBag $params) {
		return $this->redirectPermanently($this->buildLink('account/templates', $params));
	}

	protected function preferencesSaveProcess(User $visitor)
    {
        $form = parent::preferencesSaveProcess($visitor);

        $input = [
            'kl_em_wordcount_mode' => $this->filter('kl_em_wordcount_mode', 'str')
        ];

        $form->basicEntitySave($visitor, $input);

        return $form;
    }
}