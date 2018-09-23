<?php

/*!
 * KL/EditorManager/Admin/Controller/Templates.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use KL\EditorManager\Entity\Template;
use XF\Mvc\ParameterBag;

/**
 * Class Templates
 * @package KL\EditorManager\Admin\Controller
 */
class Templates extends AbstractController
{
    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionIndex()
    {
        /** @var \KL\EditorManager\Repository\Template $repo */
        $repo = $this->repository('KL\EditorManager:Template');

        $viewParams = [
            'templates' => $repo->getTemplatesForUser(0)
        ];

        return $this->view('KL\EditorManager:ListTemplates', 'kl_em_templates_list', $viewParams);
    }

    /**
     * @param Template $template
     * @return \XF\Mvc\Reply\View
     */
    public function templateAddEdit(Template $template)
    {
        $userCriteria = $this->app->criteria('XF:User', $template->user_criteria);

        $viewParams = [
            'template' => $template,
            'userCriteria' => $userCriteria
        ];
        return $this->view('KL\EditorManager:EditTemplate', 'kl_em_template_edit', $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionEdit(ParameterBag $params)
    {
        $template = $this->assertTemplateExists($params['template_id']);
        return $this->templateAddEdit($template);
    }

    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionAdd()
    {
        /** @var \KL\EditorManager\Entity\Template $template */
        $template = $this->em()->create('KL\EditorManager:Template');
        return $this->templateAddEdit($template);
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws \XF\PrintableException
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionDelete(ParameterBag $params)
    {
        $template = $this->assertTemplateExists($params['template_id']);
        if ($this->isPost()) {
            $template->delete();
            $return = $this->redirect($this->buildLink('em/templates'));
        } else {
            $viewParams = [
                'template' => $template
            ];
            $return = $this->view('KL\EditorManager:Template\Delete', 'kl_em_template_delete', $viewParams);
        }

        return $return;
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\FormAction|\XF\Mvc\Reply\Redirect
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();

        if ($params['template_id']) {
            $template = $this->assertTemplateExists($params['template_id']);
        } else {
            $template = $this->em()->create('KL\EditorManager:Template');
        }

        $form = $this->templateSaveProcess($template);
        if (get_class($form) === 'XF\Mvc\Reply\Error') {
            return $form;
        } else {
            $form->run();
        }

        return $this->redirect($this->buildLink('em/templates'));
    }

    /**
     * @param Template $template
     * @return \XF\Mvc\FormAction
     */
    protected function templateSaveProcess(Template $template)
    {
        $entityInput = $this->filter([
            'title' => 'str',
            'display_order' => 'uint',
            'active' => 'uint',
            'user_criteria' => 'array',
        ]);

        /** @var \XF\ControllerPlugin\Editor $editor */
        $editor = $this->plugin('XF:Editor');
        $entityInput['content'] = $editor->fromInput('content');


        $form = $this->formAction();
        $form->basicEntitySave($template, $entityInput);

        return $form;
    }

    /**
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     */
    public function actionSort()
    {
        /** @var \Kl\EditorManager\Repository\Template $repo */
        $repo = $this->repository('KL\EditorManager:Template');
        $templates = $repo->getTemplatesForUser(0);

        if ($this->isPost()) {
            $lastOrder = 0;
            foreach (json_decode($this->filter('templates', 'string')) as $templateValue) {
                $lastOrder += 10;

                /** @var \XF\Entity\Smilie $smilie */
                $template = $templates[$templateValue->id];
                $template->display_order = $lastOrder;
                $template->saveIfChanged();
            }

            return $this->redirect($this->buildLink('em/templates'));
        } else {
            $viewParams = [
                'templates' => $templates
            ];
            return $this->view('KL\EditorManager:Templates\Sort', 'kl_em_templates_sort', $viewParams);
        }
    }

    /**
     * @return \XF\Mvc\Reply\Message
     */
    public function actionToggle()
    {
        /** @var \XF\ControllerPlugin\Toggle $plugin */
        $plugin = $this->plugin('XF:Toggle');
        $return = $plugin->actionToggle('KL\EditorManager:Template');
        return $return;
    }

    /**
     * @param string $id
     * @param array|string|null $with
     * @param null|string $phraseKey
     *
     * @return \KL\EditorManager\Entity\Template
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertTemplateExists($id, $with = null, $phraseKey = null)
    {
        /** @var \KL\EditorManager\Entity\Template $template */
        $template = $this->assertRecordExists('KL\EditorManager:Template', $id, $with, $phraseKey);
        return $template;
    }

}