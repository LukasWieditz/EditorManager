<?php

/*!
 * KL/EditorManager/Pub/Controller/Templates.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Pub\Controller;

use XF;
use XF\ControllerPlugin\Delete;
use XF\ControllerPlugin\Editor;
use XF\ControllerPlugin\Toggle;
use XF\Entity\Smilie;
use XF\Mvc\FormAction;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\Exception;
use XF\Mvc\Reply\Message;
use XF\Mvc\Reply\Redirect;
use XF\PrintableException;
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
     * @throws Exception
     */
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertRegistrationRequired();
        $this->assertCanHaveTemplates();
    }

    /**
     * Checks if a user is allowed to generally create private templates.
     * @throws Exception
     */
    protected function assertCanHaveTemplates(): void
    {
        if (!XF::visitor()->hasPermission('klEM', 'klEMPrivateTemplates')) {
            throw $this->exception($this->noPermission());
        }
    }

    /**
     * Checks if a user is allowed to create a new template or has hit his template limit.
     * @throws Exception
     */
    protected function assertCanCreateTemplate(): void
    {
        $visitor = XF::visitor();

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
     * @throws Exception
     */
    protected function assertUserTemplate(Template $template): void
    {
        if (XF::visitor()->user_id !== $template->user_id) {
            throw $this->exception($this->noPermission());
        }
    }

    /**
     * @param View $view
     * @param $selected
     * @return View
     */
    protected function addAccountWrapperParams(View $view, $selected): View
    {
        $view->setParam('pageSelected', $selected);
        return $view;
    }

    /**
     * Displays a list of all user templates.
     * @return View
     */
    public function actionIndex(): AbstractReply
    {
        /** @var \KL\EditorManager\Repository\Template $repo */
        $repo = $this->repository('KL\EditorManager:Template');
        $templates = $repo->getTemplatesForUser(XF::visitor()->user_id);
        $visitor = XF::visitor();

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

        $view = $this->view('KL\EditorManager:ListTemplates', 'kl_em_template_list', $viewParams);
        return $this->addAccountWrapperParams($view, 'editor_templates');
    }

    /**
     * Returns the template edit view.
     * @param Template $template
     * @return View
     */
    public function templateAddEdit(Template $template): AbstractReply
    {
        $viewParams = [
            'template' => $template
        ];
        return $this->view('KL\EditorManager:EditTemplate', 'kl_em_template_edit', $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return View
     * @throws Exception
     */
    public function actionEdit(ParameterBag $params): AbstractReply
    {
        $template = $this->assertTemplateExists($params['template_id']);

        $this->assertUserTemplate($template);

        return $this->templateAddEdit($template);
    }

    /**
     * @return View
     * @throws Exception
     */
    public function actionAdd(): AbstractReply
    {
        $this->assertCanCreateTemplate();

        /** @var Template $template */
        $template = $this->em()->create('KL\EditorManager:Template');
        return $this->templateAddEdit($template);
    }

    /**
     * Deletes a template or shows confirmation screen.
     * @param ParameterBag $params
     * @return Redirect|View
     * @throws Exception
     */
    public function actionDelete(ParameterBag $params): AbstractReply
    {
        $template = $this->assertTemplateExists($params['template_id']);
        /** @var Delete $plugin */
        $plugin = $this->plugin('XF:Delete');
        return $plugin->actionDelete(
            $template,
            $this->buildLink('account/kl-editor-templates/delete', $template),
            $this->buildLink('account/kl-editor-templates/edit', $template),
            $this->buildLink('account/kl-editor-templates'),
            $template->title
        );
    }

    /**
     * Saves template changes.
     * @param ParameterBag $params
     * @return FormAction|Redirect
     * @throws Exception
     * @throws PrintableException
     */
    public function actionSave(ParameterBag $params): AbstractReply
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

        return $this->redirect($this->buildLink('account/kl-editor-templates'));
    }

    /**
     * @param Template $template
     * @return FormAction
     */
    protected function templateSaveProcess(Template $template): FormAction
    {
        $entityInput = $this->filter([
            'title' => 'str',
            'active' => 'uint'
        ]);

        $entityInput['user_id'] = XF::visitor()->user_id;
        /** @var Editor $editor */
        $editor = $this->plugin('XF:Editor');
        $entityInput['content'] = $editor->fromInput('content');

        $form = $this->formAction();
        $form->basicEntitySave($template, $entityInput);

        return $form;
    }

    /**
     * Sorts templates as requested.
     * @return Redirect|View
     * @throws Exception
     */
    public function actionSort(): AbstractReply
    {
        /** @var \KL\EditorManager\Repository\Template $repo */
        $repo = $this->repository('KL\EditorManager:Template');
        $templates = $repo->getTemplatesForUser(XF::visitor()->user_id);

        if ($this->isPost()) {
            $lastOrder = 0;
            foreach (json_decode($this->filter('templates', 'string')) as $templateValue) {
                $lastOrder += 10;

                /** @var Smilie $smilie */
                $template = $templates[$templateValue->id];
                $this->assertUserTemplate($template);
                $template->display_order = $lastOrder;
                $template->saveIfChanged();
            }

            return $this->redirect($this->buildLink('account/kl-editor-templates'));
        } else {
            $viewParams = [
                'templates' => $templates
            ];
            return $this->view('KL\EditorManager:Templates\Sort', 'kl_em_template_sort', $viewParams);
        }
    }

    /**
     * Toggles templates on or off.
     * @return Message
     */
    public function actionToggle(): AbstractReply
    {
        /** @var Toggle $plugin */
        $plugin = $this->plugin('XF:Toggle');
        return $plugin->actionToggle('KL\EditorManager:Template');
    }

    /**
     * @param string $id
     * @param array|string|null $with
     * @param null|string $phraseKey
     *
     * @return Template
     * @throws Exception
     */
    protected function assertTemplateExists($id, $with = null, $phraseKey = null): Template
    {
        /** @var Template $template */
        $template = $this->assertRecordExists('KL\EditorManager:Template', $id, $with, $phraseKey);
        return $template;
    }
}