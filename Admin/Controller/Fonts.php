<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Admin\Controller;

use KL\EditorManager\Entity\Font;
use KL\EditorManager\Repository\Font as FontRepo;
use XF;
use XF\Admin\Controller\AbstractController;
use XF\ControllerPlugin\Delete;
use XF\ControllerPlugin\Sort;
use XF\ControllerPlugin\Toggle;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\Error;
use XF\Mvc\Reply\Exception;
use XF\Mvc\Reply\Message;
use XF\Mvc\Reply\Redirect;
use XF\Mvc\Reply\View;
use XF\PrintableException;

/**
 * Class Fonts
 * @package KL\EditorManager\Admin\Controller
 */
class Fonts extends AbstractController
{
    /**
     * @return XF\Mvc\Entity\Repository|FontRepo
     */
    protected function getFontRepo(): FontRepo
    {
        return $this->repository('KL\EditorManager:Font');
    }

    /**
     * Return font list.
     * @return View
     */
    public function actionIndex(): AbstractReply
    {
        $fonts = $this->getFontRepo()
            ->findFonts()
            ->fetch();

        $externalFontCount = $fonts->filter(function ($font) {
            /** @var Font $font */
            return $font->type !== 'client';
        })->count();

        $viewParams = [
            'fonts' => $fonts,
            'externalFontCount' => $externalFontCount
        ];

        return $this->view('KL\EditorManager:ListFont', 'kl_em_font_list', $viewParams);
    }

    /**
     * Return font edit screen.
     * @param Font $font
     * @return View
     */
    public function fontAddEdit(Font $font): AbstractReply
    {
        if ($font->type === 'web') {
            $extra_data = $font->extra_data;

            switch ($extra_data['web_service']) {
                case 'gfonts':
                    $extra_data['web_url'] = 'https://fonts.googleapis.com/css?family=' . $extra_data['web_url'];
                    break;

                case 'typekit':
                    $extra_data['web_url'] = 'https://use.typekit.net/' . $extra_data['web_url'] . '.js';
                    break;

                case 'webtype':
                    $extra_data['web_url'] = '//cloud.webtype.com/css/' . $extra_data['web_url'] . '.css';
                    break;

                case 'fonts':
                    $extra_data['web_url'] = '//fast.fonts.net/jsapi/' . $extra_data['web_url'] . '.js';
                    break;

                default:
                    break;
            }
            $font->extra_data = $extra_data;
        }

        try {
            $fileScan = scandir($this->app()->get('config')['externalDataPath'] . '/fonts');
            $files = [];
            foreach ($fileScan as $file) {
                $elems = explode('.', $file);
                $type = array_pop($elems);
                if (in_array($type, ['woff', 'woff2', 'ttf', 'otf', 'eot', 'svg'])) {
                    $files[implode('.', $elems)][] = $type;
                }
            }

            foreach ($files as &$file) {
                $file = [
                    'types' => $file,
                    'typeString' => '[' . join(', ', $file) . ']',
                    'hasWoff2' => in_array('woff2', $file)
                ];
            }
        } catch (\Exception $e) {
            $files = [];
        }

        $viewParams = [
            'font' => $font,
            'files' => $files
        ];
        return $this->view('KL\EditorManager:EditFont', 'kl_em_font_edit', $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return View
     * @throws Exception
     */
    public function actionEdit(ParameterBag $params): AbstractReply
    {
        $font = $this->assertFontExists($params['font_id']);
        return $this->fontAddEdit($font);
    }

    /**
     * @return View
     */
    public function actionAdd(): AbstractReply
    {
        /** @var Font $font */
        $font = $this->em()->create('KL\EditorManager:Font');
        return $this->fontAddEdit($font);
    }

    /**
     * @param ParameterBag $params
     * @return Redirect|View
     * @throws Exception
     */
    public function actionDelete(ParameterBag $params): AbstractReply
    {
        $font = $this->assertFontExists($params['font_id']);
        /** @var Delete $plugin */
        $plugin = $this->plugin('XF:Delete');
        return $plugin->actionDelete(
            $font,
            $this->buildLink('em/fonts/delete', $font),
            $this->buildLink('em/fonts/edit', $font),
            $this->buildLink('em/fonts'),
            $font->title
        );
    }

    /**
     * Saves font.
     * @param ParameterBag $params
     * @return FormAction|Redirect
     * @throws Exception
     * @throws PrintableException
     */
    public function actionSave(ParameterBag $params): AbstractReply
    {
        $this->assertPostOnly();

        if ($params['font_id']) {
            $font = $this->assertFontExists($params['font_id']);
        } else {
            $font = $this->em()->create('KL\EditorManager:Font');
        }

        $this->fontSaveProcess($font)->run();

        return $this->redirect($this->buildLink('em/fonts'));
    }

    /**
     * @param Font $font
     * @return FormAction|Error
     */
    protected function fontSaveProcess(Font $font): FormAction
    {
        $entityInput = $this->filter([
            'font_id' => 'str',
            'title' => 'str',
            'type' => 'str',
            'family' => 'str',
            'display_order' => 'uint',
            'active' => 'uint'
        ]);

        /*
         * Fetch available font types for uploaded fonts.
         */

        if ($entityInput['type'] === 'upload') {

            try {
                $fileList = [];

                $fileScan = scandir($this->app()->get('config')['externalDataPath'] . '/fonts');
                foreach ($fileScan as $file) {
                    $elems = explode('.', $file);
                    $type = array_pop($elems);
                    if (in_array($type, ['woff', 'woff2', 'ttf', 'otf', 'eot', 'svg'])) {
                        $fileList[implode('.', $elems)][] = $type;
                    }
                }

                foreach ($fileList as &$file) {
                    $file = [
                        'types' => $file,
                        'typeString' => '[' . join(', ', $file) . ']',
                        'hasWoff2' => in_array('woff2', $file)
                    ];
                }
            } catch (\Exception $e) {
                $fileList = [];
            }


            $name = $this->filter('file', 'str');

            if (!$name) {
                return $this->error(XF::phrase('kl_em_invalid_file'));
            }

            $entityInput['extra_data'] = [
                'filename' => $name,
                'filetypes' => $fileList[$name]['types']
            ];

        } else {
            if ($entityInput['type'] === 'web') {
                $entityInput['extra_data'] = $this->filter([
                    'web_service' => 'str',
                    'web_url' => 'str'
                ]);

                /*
                 * Processes service provider inputs to required format.
                 */
                switch ($entityInput['extra_data']['web_service']) {
                    case 'gfonts':
                        if (strpos($entityInput['extra_data']['web_url'],
                                'https://fonts.googleapis.com/css?family=') === 0) {
                            $entityInput['extra_data']['web_url'] = substr($entityInput['extra_data']['web_url'], 40);
                        } else {
                            return $this->throwInvalidServiceError();
                        }
                        break;
                    case 'typekit':
                        if (strpos($entityInput['extra_data']['web_url'],
                                'https://use.typekit.net/') === 0 && substr($entityInput['extra_data']['web_url'],
                                -3) === '.js') {
                            $entityInput['extra_data']['web_url'] = substr($entityInput['extra_data']['web_url'], 24,
                                -3);
                        } else {
                            return $this->throwInvalidServiceError();
                        }
                        break;
                    case 'webtype':
                        if (strpos($entityInput['extra_data']['web_url'],
                                '//cloud.webtype.com/css/') === 0 && substr($entityInput['extra_data']['web_url'],
                                -4) === '.css') {
                            $entityInput['extra_data']['web_url'] = substr($entityInput['extra_data']['web_url'], 24,
                                -4);
                        } else {
                            return $this->throwInvalidServiceError();
                        }
                        break;
                    case 'fonts':
                        if (strpos($entityInput['extra_data']['web_url'],
                                '//fast.fonts.net/jsapi/') === 0 && substr($entityInput['extra_data']['web_url'],
                                -3) === '.js') {
                            $entityInput['extra_data']['web_url'] = substr($entityInput['extra_data']['web_url'], 23,
                                -3);
                        } else {
                            return $this->throwInvalidServiceError();
                        }
                        break;
                    default:
                        break;
                }
            } else {
                $entityInput['extra_data'] = [];
            }
        }

        $form = $this->formAction();
        $form->basicEntitySave($font, $entityInput);

        return $form;
    }

    /**
     * @return Error
     */
    private function throwInvalidServiceError(): Error
    {
        return $this->error(XF::phrase('kl_em_invalid_service_url'));
    }

    /**
     * Sorts font list.
     * @return Redirect|View
     */
    public function actionSort(): AbstractReply
    {
        $fonts = $this->getFontRepo()->findFonts()->fetch();

        if ($this->isPost()) {
            $sortData = $this->filter('fonts', 'json-array');

            /** @var Sort $sorter */
            $sorter = $this->plugin('XF:Sort');
            $sorter->sortFlat($sortData, $fonts);

            return $this->redirect($this->buildLink('em/fonts'));
        } else {
            $viewParams = [
                'fonts' => $fonts
            ];
            return $this->view('KL\EditorManager:Fonts\Sort', 'kl_em_font_sort', $viewParams);
        }
    }

    /**
     * Toggles fonts.
     * @return Message
     */
    public function actionToggle(): AbstractReply
    {
        /** @var Toggle $plugin */
        $plugin = $this->plugin('XF:Toggle');
        return $plugin->actionToggle('KL\EditorManager:Font');
    }

    /**
     * @param string $id
     * @param array|string|null $with
     * @param null|string $phraseKey
     *
     * @return Font|XF\Mvc\Entity\Entity
     * @throws Exception
     */
    protected function assertFontExists($id, $with = null, $phraseKey = null): Font
    {
        return $this->assertRecordExists('KL\EditorManager:Font', $id, $with, $phraseKey);
    }

}