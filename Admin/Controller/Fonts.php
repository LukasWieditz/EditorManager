<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use KL\EditorManager\Entity\Font;
use \XF\Mvc\ParameterBag;

/**
 * Class Fonts
 * @package KL\EditorManager\Admin\Controller
 */
class Fonts extends AbstractController
{
    /**
     * Return font list.
     * @return \XF\Mvc\Reply\View
     */
    public function actionIndex()
    {

        /** @var \KL\EditorManager\Repository\Font $repo */
        $repo = $this->repository('KL\EditorManager:Font');
        $finder = $repo->findFonts();

        $fonts = $finder->fetch();
        $external = 0;
        foreach ($fonts as $font) {
            if ($font->type !== 'client') {
                $external++;
            }
        }

        $viewParams = [
            'fonts' => $fonts,
            'externalFontCount' => $external
        ];

        return $this->view('KL\EditorManager:ListFont', 'kl_em_fonts_list', $viewParams);
    }

    /**
     * Return font edit screen.
     * @param Font $font
     * @return \XF\Mvc\Reply\View
     */
    public function fontAddEdit(Font $font)
    {
        if ($font->get('type') === 'web') {
            $extra_data = $font->get('extra_data');

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
            $font->set('extra_data', $extra_data);
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
     * @return \XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionEdit(ParameterBag $params)
    {
        $font = $this->assertFontExists($params['font_id']);
        return $this->fontAddEdit($font);
    }

    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionAdd()
    {
        /** @var \KL\EditorManager\Entity\Font $font */
        $font = $this->em()->create('KL\EditorManager:Font');
        return $this->fontAddEdit($font);
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws \XF\PrintableException
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionDelete(ParameterBag $params)
    {
        $font = $this->assertFontExists($params['font_id']);

        if ($this->isPost()) {
            $font->delete();
            $return = $this->redirect($this->buildLink('em/fonts'));
        } else {
            $viewParams = [
                'font' => $font
            ];
            $return = $this->view('KL\EditorManager:Font\Delete', 'kl_em_font_delete', $viewParams);
        }

        /** @var \KL\EditorManager\Repository\Font $repo */
        $repo = $this->repository('KL\EditorManager:Font');
        $repo->rebuildFontCache();

        return $return;
    }

    /**
     * Saves font.
     * @param ParameterBag $params
     * @return \XF\Mvc\FormAction|\XF\Mvc\Reply\Redirect
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();

        if ($params['font_id']) {
            $font = $this->assertFontExists($params['font_id']);
        } else {
            $font = $this->em()->create('KL\EditorManager:Font');
        }

        $form = $this->fontSaveProcess($font);
        if (get_class($form) === 'XF\Mvc\Reply\Error') {
            return $form;
        } else {
            $form->run();
        }


        /** @var \KL\EditorManager\Repository\Font $repo */
        $repo = $this->repository('KL\EditorManager:Font');
        $repo->rebuildFontCache();

        return $this->redirect($this->buildLink('em/fonts'));
    }

    /**
     * @param Font $font
     * @return \XF\Mvc\FormAction|\XF\Mvc\Reply\Error
     */
    protected function fontSaveProcess(Font $font)
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
                return $this->error(\XF::phrase('kl_em_invalid_file'));
            }

            $entityInput['extra_data'] = [
                'filename' => $name,
                'filetypes' => $fileList[$name]['types']
            ];

        } else if ($entityInput['type'] === 'web') {
            $entityInput['extra_data'] = $this->filter([
                'web_service' => 'str',
                'web_url' => 'str'
            ]);

            /*
             * Processes service provider inputs to required format.
             */
            switch ($entityInput['extra_data']['web_service']) {
                case 'gfonts':
                    if (strpos($entityInput['extra_data']['web_url'], 'https://fonts.googleapis.com/css?family=') === 0) {
                        $entityInput['extra_data']['web_url'] = substr($entityInput['extra_data']['web_url'], 40);
                    } else {
                        return $this->throwInvalidServiceError();
                    }
                    break;
                case 'typekit':
                    if (strpos($entityInput['extra_data']['web_url'], 'https://use.typekit.net/') === 0 && substr($entityInput['extra_data']['web_url'], -3) === '.js') {
                        $entityInput['extra_data']['web_url'] = substr($entityInput['extra_data']['web_url'], 24, -3);
                    } else {
                        return $this->throwInvalidServiceError();
                    }
                    break;
                case 'webtype':
                    if (strpos($entityInput['extra_data']['web_url'], '//cloud.webtype.com/css/') === 0 && substr($entityInput['extra_data']['web_url'], -4) === '.css') {
                        $entityInput['extra_data']['web_url'] = substr($entityInput['extra_data']['web_url'], 24, -4);
                    } else {
                        return $this->throwInvalidServiceError();
                    }
                    break;
                case 'fonts':
                    if (strpos($entityInput['extra_data']['web_url'], '//fast.fonts.net/jsapi/') === 0 && substr($entityInput['extra_data']['web_url'], -3) === '.js') {
                        $entityInput['extra_data']['web_url'] = substr($entityInput['extra_data']['web_url'], 23, -3);
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

        $form = $this->formAction();
        $form->basicEntitySave($font, $entityInput);

        return $form;
    }

    /**
     * @return \XF\Mvc\Reply\Error
     */
    private function throwInvalidServiceError()
    {
        return $this->error(\XF::phrase('kl_em_invalid_service_url'));
    }

    /**
     * Sorts font list.
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     */
    public function actionSort()
    {
        /** @var \KL\EditorManager\Repository\Font $repo */
        $repo = $this->repository('KL\EditorManager:Font');
        $finder = $repo->findFonts();

        if ($this->isPost()) {
            $fonts = $finder->fetch();

            $lastOrder = 0;
            foreach (json_decode($this->filter('fonts', 'string')) as $fontValue) {
                $lastOrder += 10;

                /** @var \XF\Entity\Smilie $smilie */
                $font = $fonts[$fontValue->id];
                $font->display_order = $lastOrder;
                $font->saveIfChanged();
            }

            $repo->rebuildFontCache();

            return $this->redirect($this->buildLink('em/fonts'));
        } else {
            $viewParams = [
                'fonts' => $finder->fetch()
            ];
            return $this->view('KL\EditorManager:Fonts\Sort', 'kl_em_fonts_sort', $viewParams);
        }
    }

    /**
     * Toggles fonts.
     * @return \XF\Mvc\Reply\Message
     */
    public function actionToggle()
    {
        /** @var \XF\ControllerPlugin\Toggle $plugin */
        $plugin = $this->plugin('XF:Toggle');
        $return = $plugin->actionToggle('KL\EditorManager:Font');

        /** @var \KL\EditorManager\Repository\Font $repo */
        $repo = $this->repository('KL\EditorManager:Font');
        $repo->rebuildFontCache();
        return $return;
    }

    /**
     * @param string $id
     * @param array|string|null $with
     * @param null|string $phraseKey
     *
     * @return \KL\EditorManager\Entity\Font
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertFontExists($id, $with = null, $phraseKey = null)
    {
        /** @var \KL\EditorManager\Entity\Font $font */
        $font = $this->assertRecordExists('KL\EditorManager:Font', $id, $with, $phraseKey);
        return $font;
    }

}