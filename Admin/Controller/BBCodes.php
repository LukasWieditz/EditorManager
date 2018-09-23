<?php

namespace KL\EditorManager\Admin\Controller;

use KL\EditorManager\Entity\BbCode;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class BBCodes extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        /** @noinspection PhpUndefinedFieldInspection */
        if ($params->bb_code_id) {
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
     * @return \XF\Mvc\Reply\View
     * @throws \XF\PrintableException
     */
    public function actionEdit(ParameterBag $params)
    {
        /** @var BbCode $bbCode */
        /** @noinspection PhpUndefinedFieldInspection */
        $bbCode = \XF::em()->find('KL\EditorManager:BbCode', $params->bb_code_id);
        if (!$bbCode) {
            $bbCode = \XF::em()->create('KL\EditorManager:BbCode');
            $bbCode->bb_code_id = $params->bb_code_id;
            $bbCode->save();
        }
        $userCriteria = $this->app->criteria('XF:User', $bbCode->user_criteria);

        /** @var \KL\EditorManager\Repository\BbCodes $bbCodeRepo */
        $bbCodeRepo = $this->repository('KL\EditorManager:BbCodes');
        $bbCodeOptions = $bbCodeRepo->getRelatedBbCodeOptions();
        if (isset($bbCodeOptions[$params->bb_code_id])) {
            $optionConfig = $bbCodeOptions[$params->bb_code_id];

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
     * @return \XF\Mvc\Reply\Redirect
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();
        /** @var BbCode $bbCode */
        /** @noinspection PhpUndefinedFieldInspection */
        $bbCode = \XF::em()->find('KL\EditorManager:BbCode', $params->bb_code_id);

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

    protected function bbCodeSaveProcess(BbCode $bbCode)
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

    protected function getBBCodeLists()
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
                'image',
                'media',
                'quote',
                'spoiler',
                'code',
                'icode',
                'align',
                'list',
                'attach'
            ],
            'klem' => [
                'bgcolor',
                'ispoiler',
                'table',
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
     * @return \XF\Mvc\Reply\Message
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionToggle()
    {
        $this->assertPostOnly();

        /** @var \XF\Entity\Option $option */
        $option = \XF::em()->find('XF:Option', 'klEMEnabledBBCodes');

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
        $option->option_value = $activeState;
        $option->save();

        return $this->message(\XF::phrase('your_changes_have_been_saved'));
    }

    /**
     * @return \XF\Repository\BbCode
     */
    protected function getBbCodeRepo()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository('XF:BbCode');
    }

    /**
     * @return \XF\Repository\Option
     */
    protected function getOptionRepo()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository('XF:Option');
    }
}