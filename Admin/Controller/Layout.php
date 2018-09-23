<?php

/*!
 * KL/EditorManager/Admin/Controller/Layout.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Admin\Controller;

use XF\Admin\Controller\AbstractController;

/**
 * Class Layout
 * @package KL\EditorManager\Admin\Controller
 */
class Layout extends AbstractController
{
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
        'table' => 0,
        'hide' => 0,
        'klEMHide' => 0,
        'klEMHideThanks' => 0,
        'klEMHideReplyThanks' => 0,
        'klEMHideReply' => 0,
        'klEMHidePosts' => 0
    ];

    /**
     * Returns a list of all available BB codes to manage layout.
     * @return \XF\Mvc\Reply\View
     */
    public function actionIndex()
    {
        /** @var \KL\EditorManager\Repository\BbCodes $repo */
        $repo = $this->em()->getRepository('KL\EditorManager:BbCodes');
        $bbCodes = $repo->getExtendedBbCodes();
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

            if($key == 'hide' && !$option) {
                unset($bbCodes['hide']);
                unset($bbCodes['hidethanks']);
                unset($bbCodes['hidereply']);
                unset($bbCodes['hidereplythanks']);
                unset($bbCodes['hideposts']);
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


        $dropdowns = $this->em()->getFinder('KL\EditorManager:Dropdown')->fetch();

        foreach($dropdowns as $dropdown) {
            $bbCodes["dropdown-{$dropdown->dropdown_id}"] = [
                'toolbarName' => "dropdown-{$dropdown->dropdown_id}",
                'name' => $dropdown->title,
                'iconType' => 'fa',
                'icon' => $dropdown->icon
            ];
        }

        $mockups = [];

        $layouts = json_decode(trim($options['klEMLayout'], "'"));

        foreach ($layouts as $size => $layout) {
            $mockup = [];

            foreach ($layout as $toolbarOption) {
                $key = $this->searchByToolbarName($toolbarOption, $bbCodes);

                if ($key === 'separator') {
                    $mockup[] = 'Separator';
                } else if ($key !== -1) {
                    $mockup[] = $bbCodes[$key];
                }
            }

            $mockups[$size] = [
                'name' => \XF::phrase('kl_em_' . $size),
                'layout' => $mockup
            ];
        }

        $viewParams = [
            'layouts' => $mockups,
            'bbCodes' => $bbCodes,
            'dropdowns' => $dropdowns,
            'layoutValue' => $layouts
        ];

        return $this->view('KL\EditorManager:Layout', 'kl_em_layout', $viewParams);
    }

    /**
     * Saves layout changes.
     * @return \XF\Mvc\Reply\Redirect
     * @throws \Exception
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionSave()
    {
        $this->assertPostOnly();

        /** @var \XF\Entity\Option $option */
        $option = $this->finder('XF:Option')->where('option_id', 'klEMLayout')->fetchOne();
        $option->option_value = $this->filter('toolbar_layout', 'str');
        $option->save();

        return $this->redirect($this->getDynamicRedirect());
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