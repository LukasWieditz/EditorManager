<?php

/*!
 * KL/EditorManager/Pub/Controller/Templates.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Pub\Controller;

use \XF\Pub\Controller\AbstractController;
use \XF\Mvc\Reply\View;
use \XF\Mvc\ParameterBag;
use KL\EditorManager\Entity\Template;

/**
 * Class Templates
 * @package KL\EditorManager\Pub\Controller
 */
class Templates extends AbstractController
{
    /**
     * @param $action
     * @param ParameterBag $params
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertRegistrationRequired();
        $this->assertCanHaveTemplates();
    }

    /**
     * Checks if a user is allowed to generally create private templates.
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertCanHaveTemplates()
    {
        if (!\XF::visitor()->hasPermission('klEM', 'klEMPrivateTemplates')) {
            throw $this->exception($this->noPermission());
        }
    }

    /**
     * Checks if a user is allowed to create a new template or has hit his template limit.
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertCanCreateTemplate()
    {
        $visitor = \XF::visitor();

        if ($visitor->hasPermission('klEM', 'klEMPrivateTemplatesMax') != -1) {
            /** @var \KL\EditorManager\Repository\Template $repo */
            $repo = $this->repository('KL\EditorManager:Template');
            $templateCount = count($repo->getTemplatesForUser($visitor->user_id));

            if ($templateCount >= $visitor->hasPermission('klEM', 'klEMPrivateTemplatesMax')) {
                throw $this->exception($this->noPermission());
            }
        }
    }

    /**
     * Checks if the given template belongs to a user.
     * @param Template $template
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertUserTemplate(Template $template)
    {
        if (\XF::visitor()->user_id !== $template->user_id) {
            throw $this->exception($this->noPermission());
        }
    }

    /**
     * @param View $view
     * @param $selected
     * @return View
     */
    protected function addAccountWrapperParams(View $view, $selected)
    {
        $view->setParam('pageSelected', $selected);
        return $view;
    }

    /**
     * Displays a list of all user templates.
     * @return View
     */
    public function actionIndex()
    {
        /** @var \KL\EditorManager\Repository\Template $repo */
        $repo = $this->repository('KL\EditorManager:Template');
        $templates = $repo->getTemplatesForUser(\XF::visitor()->user_id);
        $visitor = \XF::visitor();

        $canCreateTemplates = true;
        if ($visitor->hasPermission('klEM', 'klEMPrivateTemplatesMax') != -1) {
            /** @var \KL\EditorManager\Repository\Template $repo */
            $repo = $this->repository('KL\EditorManager:Template');
            $templateCount = count($repo->getTemplatesForUser($visitor->user_id));

            if ($templateCount >= $visitor->hasPermission('klEM', 'klEMPrivateTemplatesMax')) {
                $canCreateTemplates = false;
            }
        }

        $viewParams = [
            'templates' => $templates,
            'canCreateTemplates' => $canCreateTemplates
        ];

        $view = $this->view('KL\EditorManager:ListTemplates', 'kl_em_templates_list', $viewParams);
        return $this->addAccountWrapperParams($view, 'editor_templates');
    }

    /**
     * Returns the template edit view.
     * @param Template $template
     * @return View
     */
    public function templateAddEdit(Template $template)
    {
        $viewParams = [
            'template' => $template
        ];
        return $this->view('KL\EditorManager:EditTemplate', 'kl_em_template_edit', $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionEdit(ParameterBag $params)
    {
        $template = $this->assertTemplateExists($params['template_id']);

        $this->assertUserTemplate($template);

        return $this->templateAddEdit($template);
    }

    /**
     * @return View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionAdd()
    {
        $this->assertCanCreateTemplate();

        /** @var \KL\EditorManager\Entity\Template $template */
        $template = $this->em()->create('KL\EditorManager:Template');
        return $this->templateAddEdit($template);
    }

    /**
     * Deletes a template or shows confirmation screen.
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect|View
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionDelete(ParameterBag $params)
    {
        $template = $this->assertTemplateExists($params['template_id']);

        $this->assertUserTemplate($template);

        if ($this->isPost()) {
            $template->delete();
            $return = $this->redirect($this->buildLink('account/editor-templates'));
        } else {
            $viewParams = [
                'template' => $template
            ];
            $return = $this->view('KL\EditorManager:Template\Delete', 'kl_em_template_delete', $viewParams);
        }

        return $return;
    }

    /**
     * Saves template changes.
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

            $this->assertUserTemplate($template);
        } else {
            $template = $this->em()->create('KL\EditorManager:Template');
        }

        $form = $this->templateSaveProcess($template);
        if (get_class($form) === 'XF\Mvc\Reply\Error') {
            return $form;
        } else {
            $form->run();
        }

        return $this->redirect($this->buildLink('account/editor-templates'));
    }

    /**
     * @param Template $template
     * @return \XF\Mvc\FormAction
     */
    protected function templateSaveProcess(Template $template)
    {
        $entityInput = $this->filter([
            'title' => 'str',
            'active' => 'uint'
        ]);

        $entityInput['user_id'] = \XF::visitor()->user_id;
        /** @var \XF\ControllerPlugin\Editor $editor */
        $editor = $this->plugin('XF:Editor');
        $entityInput['content'] = $editor->fromInput('content');

        $form = $this->formAction();
        $form->basicEntitySave($template, $entityInput);

        return $form;
    }

    /**
     * Sorts templates as requested.
     * @return \XF\Mvc\Reply\Redirect|View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionSort()
    {
        /** @var \KL\EditorManager\Repository\Template $repo */
        $repo = $this->repository('KL\EditorManager:Template');
        $templates = $repo->getTemplatesForUser(\XF::visitor()->user_id);

        if ($this->isPost()) {
            $lastOrder = 0;
            foreach (json_decode($this->filter('templates', 'string')) as $templateValue) {
                $lastOrder += 10;

                /** @var \XF\Entity\Smilie $smilie */
                $template = $templates[$templateValue->id];
                $this->assertUserTemplate($template);
                $template->display_order = $lastOrder;
                $template->saveIfChanged();
            }

            return $this->redirect($this->buildLink('account/editor-templates'));
        } else {
            $viewParams = [
                'templates' => $templates
            ];
            return $this->view('KL\EditorManager:Templates\Sort', 'kl_em_templates_sort', $viewParams);
        }
    }

    /**
     * Toggles templates on or off.
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