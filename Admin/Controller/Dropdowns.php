<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use KL\EditorManager\Entity\Dropdown;
use \XF\Mvc\ParameterBag;

/**
 * Class Dropdowns
 * @package KL\EditorManager\Admin\Controller
 */
class Dropdowns extends AbstractController {
    protected $defaultBbCodes = [
        'bold' => 0,
        'italic' => 0,
        'underline' => 0,
        'strike' => 0,
        'color' => 0,
        'bgcolor' => 0,
        'font' => 0,
        'size' => 0,
        'url' => 0,
        'img' => 0,
        'media' => 0,
        'quote' => 0,
        'spoiler' => 0,
        'code' => 0,
        'icode' => 0,
        'align' => 0,
        'list' => 0,
        'table' => 0
    ];

    protected function getAvailableBbCodes() {

    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionEdit(ParameterBag $params) {
        /** @var \KL\EditorManager\Entity\Dropdown $dropdown */
        $dropdown = $this->assertDropdownExists($params->dropdown_id);
        return $this->dropdownAddEdit($dropdown);
	}


    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionDelete(ParameterBag $params)
    {
        $dropdown = $this->assertDropdownExists($params->dropdown_id);

        if ($this->isPost()) {
            $dropdown->delete();
            $return = $this->redirect($this->buildLink('em/layout'));
        } else {
            $viewParams = [
                'dropdown' => $dropdown
            ];
            $return = $this->view('KL\EditorManager:Dropdown\Delete', 'kl_em_dropdown_delete', $viewParams);
        }

        /** @var \KL\EditorManager\Repository\Font $repo */
        $repo = $this->repository('KL\EditorManager:Font');
        $repo->rebuildFontCache();

        return $return;
    }

    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionAdd() {
        /** @var \KL\EditorManager\Entity\Dropdown $dropdown */
        $dropdown = $this->em()->create('KL\EditorManager:Dropdown');
        return $this->dropdownAddEdit($dropdown);
	}

    /**
     * @param Dropdown $dropdown
     * @return \XF\Mvc\Reply\View
     */
    protected function dropdownAddEdit(Dropdown $dropdown) {
        /** @var \KL\EditorManager\Repository\BbCodes $repo */
        $repo = $this->em()->getRepository('KL\EditorManager:BbCodes');
        $bbCodes = $repo->getBasicBbCodes();

        $options = \XF::app()->options();

        if (isset($options['klEMGeneralOptions']['disable_smilies'])) {
            unset($bbCodes['smiley']);
        }

        $enabledBbCodes = array_merge($this->defaultBbCodes, $options['klEMEnabledBBCodes']);

        $enabledBbCodes['color'] = $enabledBbCodes['color'] || $enabledBbCodes['bgcolor'];


        foreach ($enabledBbCodes as $key => $option) {
            if (!$option) {
                unset($bbCodes[$key]);
            }
        }

        /** @var \XF\Repository\BbCode $finder */
        $finder = $this->repository('XF:BbCode');
        $customBbCodes = $finder->findBbCodesForList()->fetch();

        foreach ($customBbCodes as $key => $code) {
            if ($code['active'] && $code['editor_icon_type']) {
                $bbCodes['custom_' . $key] = [
                    'icon' => $code['editor_icon_value'],
                    'iconType' => $code['editor_icon_type'],
                    'toolbarName' => 'xfCustom_' . $key,
                    'name' => \XF::phrase('custom_bb_code_title.' . $key)
                ];
            }
        }

        $layout = [];
        foreach($dropdown->buttons as $button) {
            $key = $this->searchByToolbarName($button, $bbCodes);

            if($key == -1) {
                continue;
            }

            $layout[] = $bbCodes[$key];
        }

        $viewParams = [
            'dropdown' => $dropdown,
            'layout' => $layout,
            'input' => ['dropdown' => $dropdown->buttons],
            'bbCodes' => $bbCodes
        ];

        return $this->view('KL\EditorManager:Dropdown\Edit', 'kl_em_dropdown_edit', $viewParams);
	}

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect
     * @throws \Exception
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();

        if ($params->dropdown_id)
        {
            $dropdown = $this->assertDropdownExists($params->dropdown_id);
        }
        else
        {
            $dropdown = $this->em()->create('KL\EditorManager:Dropdown');
        }

        $input = $this->filter([
            'title'     => 'str',
            'icon'      => 'str'
        ]);

        $layout = json_decode($this->filter('toolbar_layout', 'str'));
        $input['buttons'] = $layout->dropdown;

        $dropdown->bulkSet($input);
        $dropdown->save();

        /** @var \KL\EditorManager\Repository\Dropdown $repo */
        $repo = $this->em()->getRepository('KL\EditorManager:Dropdown');
        $repo->rebuildDropdownsCache();

        return $this->redirect($this->buildLink('em/layout'));
    }


    /**
     * @param string $id
     * @param array|string|null $with
     * @param null|string $phraseKey
     *
     * @return \KL\EditorManager\Entity\Dropdown
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertDropdownExists($id, $with = null, $phraseKey = null)
    {
        /** @var \KL\EditorManager\Entity\Dropdown $dropdown */
        $dropdown = $this->assertRecordExists('KL\EditorManager:Dropdown', $id, $with, $phraseKey);
        return $dropdown;
    }

    /**
     * @param $needle
     * @param $haystack
     * @return int|string
     */
    private function searchByToolbarName($needle, $haystack)
    {
        foreach ($haystack as $key => $option) {
            if ($option['toolbarName'] === $needle) {
                return $key;
            }
        }
        return -1;
    }
}