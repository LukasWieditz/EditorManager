<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Admin\Controller;

use KL\EditorManager\Entity\BbCode;
use XF;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\Exception;
use XF\Mvc\Reply\Message;
use XF\Mvc\Reply\Redirect;
use XF\Mvc\Reply\Reroute;
use XF\Mvc\Reply\View;
use XF\PrintableException;
use XF\Repository\BbCode as BbCodeRepo;
use XF\Repository\Option;

/**
 * Class BBCodes
 * @package KL\EditorManager\Admin\Controller
 */
class BBCodes extends AbstractController
{
    /**
     * @param ParameterBag $params
     * @return Reroute|View
     */
    public function actionIndex(ParameterBag $params) : AbstractReply
    {
        if ($params['bb_code_id']) {
            return $this->rerouteController('KL\EditorManager:BBCodes', 'edit', $params);
        }

        $bbCodes = $this->getBbCodeRepo()
            ->findBbCodesForList();

        $viewParams = [
            'permissions' => $this->finder('KL\EditorManager:BbCode')->fetch(),
            'customBbCodes' => $bbCodes->fetch()
        ];

        return $this->view('KL\EditorManager:BbCode', 'kl_em_bb_code_list', $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return View
     * @throws PrintableException
     */
    public function actionEdit(ParameterBag $params) : AbstractReply
    {
        /** @var BbCode $bbCode */
        $bbCode = XF::em()->find('KL\EditorManager:BbCode', $params['bb_code_id']);
        if (!$bbCode) {
            $bbCode = XF::em()->create('KL\EditorManager:BbCode');
            $bbCode->bb_code_id = $params['bb_code_id'];
            $bbCode->save();
        }
        $userCriteria = $this->app->criteria('XF:User', $bbCode->user_criteria);

        /** @var \KL\EditorManager\Repository\BbCodes $bbCodeRepo */
        $bbCodeRepo = $this->repository('KL\EditorManager:BbCodes');
        $bbCodeOptions = $bbCodeRepo->getRelatedBbCodeOptions();
        if (isset($bbCodeOptions[$params['bb_code_id']])) {
            $optionConfig = $bbCodeOptions[$params['bb_code_id']];

            $optionFinder = $this->finder('XF:Option')
                ->where('option_id', '=', $optionConfig['options']);

            foreach ($optionConfig['sort'] as $sort) {
                $optionFinder
                    ->with("Relations|{$sort}")
                    ->order("Relations|{$sort}.display_order");
            }

            $options = $optionFinder
                ->fetch();
        } else {
            $options = [];
        }

        $viewParams = [
            'bbCode' => $bbCode,
            'options' => $options,
            'userCriteria' => $userCriteria,
        ];

        return $this->view('KL\EditorManager:BbCode\Edit', 'kl_em_bb_code_edit', $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return Redirect
     * @throws Exception
     * @throws PrintableException
     */
    public function actionSave(ParameterBag $params) : AbstractReply
    {
        $this->assertPostOnly();
        /** @var BbCode $bbCode */
        $bbCode = XF::em()->find('KL\EditorManager:BbCode', $params['bb_code_id']);

        $this->bbCodeSaveProcess($bbCode)->run();


        $input = $this->filter([
            'options_listed' => 'array-str',
            'options' => 'array'
        ]);

        $options = [];
        foreach ($input['options_listed'] AS $optionId) {
            if (!isset($input['options'][$optionId])) {
                $options[$optionId] = false;
            } else {
                $options[$optionId] = $input['options'][$optionId];
            }
        }

        $this->getOptionRepo()->updateOptions($options);

        return $this->redirect($this->buildLink('em/bb-codes'));
    }

    /**
     * @param BbCode $bbCode
     * @return FormAction
     */
    protected function bbCodeSaveProcess(BbCode $bbCode) : FormAction
    {
        $form = $this->formAction();

        $bbCodeInput = $this->filter([
            'user_criteria' => 'array',
            'aliases' => 'array-str'
        ]);

        $bbCodeInput['aliases'] = array_filter($bbCodeInput['aliases']);

        $form->basicEntitySave($bbCode, $bbCodeInput);

        return $form;
    }

    /**
     * @return array
     */
    protected function getBBCodeLists() : array
    {
        return [
            'stock' => [
                'bold',
                'italic',
                'underline',
                'strike',
                'color',
                'font',
                'size',
                'url',
                'email',
                'img',
                'media',
                'quote',
                'spoiler',
                'code',
                'icode',
                'align',
                'list',
                'attach',
                'ispoiler',
                'table',
                'heading',
                'hr',
                'table'
            ],
            'klem' => [
                'bgcolor',
                'hide',
                'parsehtml',
                'sub',
                'sup',
                'video',
                'audio'
            ]
        ];
    }

    /**
     * @return Message
     * @throws Exception
     * @throws PrintableException
     */
    public function actionToggle() : AbstractReply
    {
        $this->assertPostOnly();

        /** @var XF\Entity\Option $option */
        $option = XF::em()->find('XF:Option', 'klEMEnabledBBCodes');

        $activeState = $this->request->filter('kl_active', 'array-bool');

        $activeCodes = [];
        $lists = $this->getBBCodeLists();
        $list = $lists[$this->filter('list', 'str')];
        foreach ($option->sub_options AS $bbCode) {
            if (in_array($bbCode, $list)) {
                if (isset($activeState[$bbCode]) && $activeState[$bbCode]) {
                    $activeCodes[$bbCode] = true;
                }
            } else {
                $activeState[$bbCode] = isset($option->option_value[$bbCode]) && $option->option_value[$bbCode];
            }
        }
        $option->option_value = $activeCodes;
        $option->save();

        return $this->message(XF::phrase('your_changes_have_been_saved'));
    }

    /**
     * @return BbCodeRepo
     */
    protected function getBbCodeRepo() : BbCodeRepo
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository('XF:BbCode');
    }

    /**
     * @return Option
     */
    protected function getOptionRepo() : Option
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository('XF:Option');
    }
}