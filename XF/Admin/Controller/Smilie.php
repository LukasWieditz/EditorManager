<?php

/*!
 * KL/EditorManager/XF/Admin/Controller/Smilie.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\XF\Admin\Controller;

use XF\ControllerPlugin\Toggle;
use XF\Entity\Smilie as SmilieEntity;
use XF\Mvc\FormAction;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\View;

/**
 * Class Smilie
 * @package KL\EditorManager\XF\Admin\Controller
 */
class Smilie extends XFCP_Smilie
{
    /**
     * @param SmilieEntity $smilie
     * @return View
     */
    public function smilieAddEdit(SmilieEntity $smilie): AbstractReply
    {
        $response = parent::smilieAddEdit($smilie);

        if ($response instanceof View) {
            /** @var \KL\EditorManager\XF\Entity\Smilie $smilie */
            $userCriteria = $this->app->criteria('XF:User', $smilie->kl_em_user_criteria ?: []);
            $response->setParam('klEMUserCriteria', $userCriteria);
        }

        return $response;
    }

    /**
     * @param SmilieEntity $smilie
     * @return FormAction
     */
    protected function smilieSaveProcess(SmilieEntity $smilie): FormAction
    {
        $form = parent::smilieSaveProcess($smilie);

        $userCriteria = $this->filter('user_criteria', 'array');
        $entityInput['kl_em_user_criteria'] = $userCriteria;
        $entityInput['kl_em_active'] = $this->filter('kl_em_active', 'bool');
        $form->basicEntitySave($smilie, $entityInput);

        return $form;
    }

    /**
     * @return AbstractReply
     */
    public function actionToggle(): AbstractReply
    {
        /** @var Toggle $plugin */
        $plugin = $this->plugin('XF:Toggle');
        $response = $plugin->actionToggle('XF:Smilie', 'kl_em_active');
        $this->getSmilieRepo()->rebuildSmilieCache();
        return $response;
    }
}
