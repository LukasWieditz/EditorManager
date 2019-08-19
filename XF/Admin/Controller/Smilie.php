<?php

namespace KL\EditorManager\XF\Admin\Controller;

use XF\Mvc\Reply\View;

class Smilie extends XFCP_Smilie
{
    /**
     * @param \XF\Entity\Smilie $smilie
     * @return View
     */
    public function smilieAddEdit(\XF\Entity\Smilie $smilie)
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
     * @param \XF\Entity\Smilie $smilie
     * @return \XF\Mvc\FormAction
     */
    protected function smilieSaveProcess(\XF\Entity\Smilie $smilie)
    {
        $form = parent::smilieSaveProcess($smilie);

        $userCriteria = $this->filter('user_criteria', 'array');
        $entityInput['kl_em_user_criteria'] = $userCriteria;
        $entityInput['kl_em_active'] = $this->filter('kl_em_active', 'bool');
        $form->basicEntitySave($smilie, $entityInput);

        return $form;
    }

    public function actionToggle()
    {
        /** @var \XF\ControllerPlugin\Toggle $plugin */
        $plugin = $this->plugin('XF:Toggle');
        $response = $plugin->actionToggle('XF:Smilie', 'kl_em_active');
        $this->getSmilieRepo()->rebuildSmilieCache();
        return $response;
    }
}
