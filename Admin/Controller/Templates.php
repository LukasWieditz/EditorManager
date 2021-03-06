<?php

/*!
 * KL/EditorManager/Admin/Controller/Templates.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Admin\Controller;

use KL\EditorManager\EditorConfig;
use KL\EditorManager\Entity\Template;
use KL\EditorManager\Repository\Template as TemplateRepo;
use XF\Admin\Controller\AbstractController;
use XF\ControllerPlugin\Delete;
use XF\ControllerPlugin\Editor;
use XF\ControllerPlugin\Toggle;
use XF\Entity\Smilie;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\Exception;
use XF\Mvc\Reply\Message;
use XF\Mvc\Reply\Redirect;
use XF\Mvc\Reply\View;
use XF\PrintableException;

/**
 * Class Templates
 * @package KL\EditorManager\Admin\Controller
 */
class Templates extends AbstractController
{
    /**
     * @return Repository|TemplateRepo
     */
    protected function getTemplateRepo(): TemplateRepo
    {
        return $this->repository('KL\EditorManager:Template');
    }

    /**
     * @return View
     */
    public function actionIndex(): AbstractReply
    {
        $viewParams = [
            'templates' => $this->getTemplateRepo()
                ->findTemplates()
                ->publicOnly()
                ->fetch()
        ];

        return $this->view('KL\EditorManager:ListTemplates', 'kl_em_template_list', $viewParams);
    }

    /**
     * @param Template $template
     * @return View
     */
    public function templateAddEdit(Template $template): AbstractReply
    {
        $userCriteria = $this->app->criteria('XF:User', $template->user_criteria ?: []);
        $pageCriteria = $this->app->criteria('XF:Page', $template->page_criteria ?: []);

        $viewParams = [
            'template' => $template,
            'userCriteria' => $userCriteria,
            'pageCriteria' => $pageCriteria
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
        return $this->templateAddEdit($template);
    }

    /**
     * @return View
     */
    public function actionAdd(): AbstractReply
    {
        /** @var Template $template */
        $template = $this->em()->create('KL\EditorManager:Template');
        return $this->templateAddEdit($template);
    }

    /**
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
            $this->buildLink('em/templates/delete', $template),
            $this->buildLink('em/templates/edit', $template),
            $this->buildLink('em/templates'),
            $template->title
        );
    }

    /**
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
        } else {
            $template = $this->em()->create('KL\EditorManager:Template');
        }

        $this->templateSaveProcess($template)->run();

        return $this->redirect($this->buildLink('em/templates'));
    }

    /**
     * @param Template $template
     * @return FormAction
     */
    protected function templateSaveProcess(Template $template): FormAction
    {
        $entityInput = $this->filter([
            'title' => 'str',
            'display_order' => 'uint',
            'active' => 'uint',
            'user_criteria' => 'array',
            'page_criteria' => 'array'
        ]);

        /** @var Editor $editor */
        $editor = $this->plugin('XF:Editor');
        $entityInput['content'] = $editor->fromInput('content');

        $form = $this->formAction();
        $form->basicEntitySave($template, $entityInput);

        return $form;
    }

    /**
     * @return Redirect|View
     */
    public function actionSort(): AbstractReply
    {
        $templates = $this->getTemplateRepo()
            ->findTemplates()
            ->publicOnly()
            ->fetch();

        if ($this->isPost()) {
            $lastOrder = 0;
            foreach (json_decode($this->filter('templates', 'string')) as $templateValue) {
                $lastOrder += 10;

                /** @var Smilie $smilie */
                $template = $templates[$templateValue->id];
                $template->display_order = $lastOrder;
                $template->saveIfChanged();
            }

            return $this->redirect($this->buildLink('em/templates'));
        } else {
            $viewParams = [
                'templates' => $templates
            ];
            return $this->view('KL\EditorManager:Templates\Sort', 'kl_em_template_sort', $viewParams);
        }
    }

    /**
     * @return Message
     */
    public function actionToggle(): AbstractReply
    {
        /** @var Toggle $plugin */
        $plugin = $this->plugin('XF:Toggle');
        $response = $plugin->actionToggle('KL\EditorManager:Template');
        $editorConfig = EditorConfig::getInstance();
        $editorConfig->cacheDelete('publicTemplates');
        return $response;
    }

    /**
     * @param string $id
     * @param array|string|null $with
     * @param null|string $phraseKey
     *
     * @return Template|Entity
     * @throws Exception
     */
    protected function assertTemplateExists($id, $with = null, $phraseKey = null): Template
    {
        return $this->assertRecordExists('KL\EditorManager:Template', $id, $with, $phraseKey);
    }

}