<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Admin\Controller;

use KL\EditorManager\Entity\SpecialCharacter;
use KL\EditorManager\Entity\SpecialCharacterGroup;
use SimpleXMLElement;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Redirect;
use XF\Mvc\Reply\View;
use XF\PrintableException;
use XF\Util\Xml;

/**
 * Class SpecialChars
 * @package KL\EditorManager\Admin\Controller
 */
class SpecialChars extends AbstractController
{
    /**
     * @param ParameterBag $params
     * @return View
     */
    public function actionIndex(ParameterBag $params)
    {
        $options = $this->app->options();

        $viewParams = [
            'enabled' => $options->klEMGeneralOptions['special_chars'],
            'specialChars' => \XF::finder('KL\EditorManager:SpecialCharacterGroup')->order('display_order')->fetch()
        ];

        return $this->view('KL\EditorManager:SpecialChars\List', 'kl_em_special_chars_list', $viewParams);
    }

    /**
     * @return View
     */
    public function actionAdd()
    {
        /** @noinspection PhpParamsInspection */
        return $this->actionAddEdit($this->em()->create('KL\EditorManager:SpecialCharacterGroup'));
    }

    /**
     * @param ParameterBag $params
     * @return View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionEdit(ParameterBag $params)
    {
        /** @var SpecialCharacterGroup $group */
        $group = $this->assertGroupExists($params->group_id);

        return $this->actionAddEdit($group);
    }

    protected function actionAddEdit(SpecialCharacterGroup $group)
    {
        $userCriteria = $this->app->criteria('XF:User', $group->user_criteria);

        $viewParams = [
            'group' => $group,
            'userCriteria' => $userCriteria
        ];

        return $this->view('KL\EditorManager:SpecialChars\List', 'kl_em_special_chars_edit', $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return Redirect
     * @throws PrintableException
     */
    public function actionSave(ParameterBag $params)
    {
        if ($params->group_id) {
            /** @var SpecialCharacterGroup $group */
            $group = $this->em()->find('KL\EditorManager:SpecialCharacterGroup', $params->group_id);
        } else {
            /** @var SpecialCharacterGroup $group */
            $group = $this->em()->create('KL\EditorManager:SpecialCharacterGroup');
        }

        $this->groupSaveProcess($group)->run();

        return $this->redirect($this->buildLink('em/special-chars'));
    }

    /**
     * @param SpecialCharacterGroup $group
     * @return \XF\Mvc\FormAction
     */
    protected function groupSaveProcess(SpecialCharacterGroup $group)
    {
        $entityInput = $this->filter([
            'display_order' => 'uint',
            'active' => 'uint',
            'user_criteria' => 'array',
        ]);

        $form = $this->formAction();
        $form->basicEntitySave($group, $entityInput);

        $phraseInput = $this->filter([
            'title' => 'str'
        ]);

        $form->validate(function (FormAction $form) use ($phraseInput) {
            if ($phraseInput['title'] === '') {
                $form->logError(\XF::phrase('please_enter_valid_title'), 'title');
            }
        });

        $form->apply(function () use ($phraseInput, $group) {
            $masterTitle = $group->getMasterPhrase();
            $masterTitle->phrase_text = $phraseInput['title'];
            $masterTitle->save();
        });

        return $form;
    }

    /**
     * @return \XF\Mvc\Reply\Message
     */
    public function actionToggle()
    {
        /** @var \XF\ControllerPlugin\Toggle $plugin */
        $plugin = $this->plugin('XF:Toggle');
        $return = $plugin->actionToggle('KL\EditorManager:SpecialCharacterGroup');
        return $return;
    }

    /**
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     */
    public function actionSort()
    {
        $groups = $this->finder('KL\EditorManager:SpecialCharacterGroup')->order('display_order')->fetch();

        if ($this->isPost()) {
            $lastOrder = 0;
            foreach (json_decode($this->filter('groups', 'string')) as $groupValue) {
                $lastOrder += 10;

                /** @var \XF\Entity\Smilie $smilie */
                $group = $groups[$groupValue->id];
                $group->display_order = $lastOrder;
                $group->saveIfChanged();
            }

            return $this->redirect($this->buildLink('em/special-chars'));
        } else {
            $viewParams = [
                'groups' => $groups
            ];
            return $this->view('KL\EditorManager:SpecialCharacterGroup\Sort', 'kl_em_special_chars_sort', $viewParams);
        }
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws \XF\PrintableException
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionDelete(ParameterBag $params)
    {
        /** @var SpecialCharacterGroup $group */
        $group = $this->assertGroupExists($params->group_id);

        if ($this->isPost()) {
            $group->delete();
            $return = $this->redirect($this->buildLink('em/special-chars'));
        } else {
            $viewParams = [
                'group' => $group
            ];
            $return = $this->view('KL\EditorManager:Template\Delete', 'kl_em_special_chars_delete', $viewParams);
        }

        return $return;
    }

    /**
     * @param ParameterBag $params
     * @return View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionCharacter(ParameterBag $params)
    {
        /** @var SpecialCharacterGroup $group */
        $group = $this->assertGroupExists($params->group_id);

        $viewParams = [
            'group' => $group,
            'specialChars' => \XF::finder('KL\EditorManager:SpecialCharacter')->where('group_id', '=',
                $params->group_id)->order('display_order')->fetch()
        ];

        return $this->view('KL\EditorManager:SpecialChars\Character\List', 'kl_em_special_chars_character_list',
            $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return View
     */
    public function actionCharacterAdd(ParameterBag $params)
    {
        $character = $this->em()->create('KL\EditorManager:SpecialCharacter');
        $character->group_id = $params->group_id;

        /** @noinspection PhpParamsInspection */
        return $this->characterAddEdit($character);
    }

    /**
     * @param ParameterBag $params
     * @return View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionCharacterEdit(ParameterBag $params)
    {
        /** @var SpecialCharacter $character */
        $character = $this->assertCharacterExists($params->character_id);

        return $this->characterAddEdit($character);
    }

    protected function characterAddEdit(SpecialCharacter $character)
    {
        $viewParams = [
            'groups' => \XF::finder('KL\EditorManager:SpecialCharacterGroup')->order('display_order')->fetch(),
            'character' => $character
        ];

        return $this->view('KL\EditorManager:SpecialChars\Character\Edit', 'kl_em_special_chars_character_edit',
            $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return Redirect
     * @throws PrintableException
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionCharacterSave(ParameterBag $params)
    {
        if ($params->character_id) {
            /** @var SpecialCharacter $character */
            $character = $this->assertCharacterExists($params->character_id);
        } else {
            /** @var SpecialCharacterGroup $group */
            $character = $this->em()->create('KL\EditorManager:SpecialCharacter');
        }

        $this->characterSaveProcess($character)->run();

        return $this->redirect($this->buildLink('em/special-chars/characters', $character));
    }

    /**
     * @param SpecialCharacter $character
     * @return \XF\Mvc\FormAction
     */
    protected function characterSaveProcess(SpecialCharacter $character)
    {
        $entityInput = $this->filter([
            'display_order' => 'uint',
            'active' => 'uint',
            'code' => 'str',
            'group_id' => 'int'
        ]);

        $form = $this->formAction();
        $form->basicEntitySave($character, $entityInput);

        $phraseInput = $this->filter([
            'title' => 'str'
        ]);

        $form->validate(function (FormAction $form) use ($phraseInput) {
            if ($phraseInput['title'] === '') {
                $form->logError(\XF::phrase('please_enter_valid_title'), 'title');
            }
        });

        $form->apply(function () use ($phraseInput, $character) {
            $masterTitle = $character->getMasterPhrase();
            $masterTitle->phrase_text = $phraseInput['title'];
            $masterTitle->save();
        });

        return $form;
    }

    /**
     * @return \XF\Mvc\Reply\Message
     */
    public function actionCharacterToggle()
    {
        /** @var \XF\ControllerPlugin\Toggle $plugin */
        $plugin = $this->plugin('XF:Toggle');
        $return = $plugin->actionToggle('KL\EditorManager:SpecialCharacter');
        return $return;
    }

    /**
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionCharacterSort(ParameterBag $params)
    {
        $characters = $this->finder('KL\EditorManager:SpecialCharacter')->where('group_id', '=',
            $params->group_id)->order('display_order')->fetch();

        if ($this->isPost()) {
            $lastOrder = 0;
            foreach (json_decode($this->filter('characters', 'string')) as $characterValue) {
                $lastOrder += 10;

                /** @var \XF\Entity\Smilie $smilie */
                $character = $characters[$characterValue->id];
                $character->display_order = $lastOrder;
                $character->saveIfChanged();
            }

            return $this->redirect($this->buildLink('em/special-chars/characters', $params));
        } else {
            $viewParams = [
                'group' => $this->assertGroupExists($params->group_id),
                'characters' => $characters
            ];
            return $this->view('KL\EditorManager:SpecialChars\Character\Sort', 'kl_em_special_chars_character_sort',
                $viewParams);
        }
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws \XF\PrintableException
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionCharacterDelete(ParameterBag $params)
    {
        $character = $this->assertCharacterExists($params->character_id);

        if ($this->isPost()) {
            $character->delete();
            $return = $this->redirect($this->buildLink('em/special-chars/characters', $params));
        } else {
            $viewParams = [
                'character' => $character
            ];
            $return = $this->view('KL\EditorManager:SpecialChars\Character\Delete',
                'kl_em_special_chars_character_delete', $viewParams);
        }

        return $return;
    }

    /**
     * @param $groupId
     * @return \XF\Mvc\Entity\Entity
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertGroupExists($groupId)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->assertRecordExists('KL\EditorManager:SpecialCharacterGroup', $groupId);
    }

    /**
     * @param $groupId
     * @return \XF\Mvc\Entity\Entity
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertCharacterExists($charId)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->assertRecordExists('KL\EditorManager:SpecialCharacter', $charId);
    }

    public function actionImport()
    {
        $sourceDir = \XF::getSourceDirectory();
        $dirSep = DIRECTORY_SEPARATOR;
        $xmlDir = "{$sourceDir}{$dirSep}addons{$dirSep}KL{$dirSep}EditorManager{$dirSep}_specialChars";
        $libraries = [];
        array_map(function ($entry) use (&$libraries) {
            $libraries[substr($entry, 0, -4)] = [
                'file' => $entry,
                'name' => substr(str_replace('_', ' ', $entry), 0, -4)
            ];
        }, array_slice(scandir($xmlDir), 2));

        $viewParams = [
            'libraries' => $libraries
        ];

        return $this->view('KL\EditorManager:SpecialChars\Import', 'kl_em_special_chars_import', $viewParams);
    }

    public function actionImportForm()
    {
        $mode = $this->filter('mode', 'str');

        if ($mode == 'upload') {
            $upload = $this->request->getFile('upload', false);
            if (!$upload) {
                return $this->error(\XF::phrase('kl_em_please_upload_valid_xml_file'));
            }

            $file = $upload->getTempFile();
        } else {
            $libraryFile = $this->filter('library', 'str');
            $sourceDir = \XF::getSourceDirectory();
            $dirSep = DIRECTORY_SEPARATOR;
            $xmlDir = "{$sourceDir}{$dirSep}addons{$dirSep}KL{$dirSep}EditorManager{$dirSep}_specialChars";
            $file = "{$xmlDir}{$dirSep}{$libraryFile}";
        }

        try {
            $xml = Xml::openFile($file);
        } catch (\Exception $e) {
            $xml = null;
        }

        $viewParams = [
            'categoryName' => $xml->title,
            'characters' => (array)$xml->characters
        ];

        return $this->view('KL\EditorManager:SpecialChars\Import', 'kl_em_special_chars_import_form', $viewParams);
    }

    /**
     * @throws PrintableException
     */
    public function actionImportSave()
    {
        $enabled = $this->filter('enabled', 'array-str');
        $codes = $this->filter('codes', 'array-str');
        $names = $this->filter('names', 'array-str');

        $categoryName = $this->filter('categoryTitle', 'str');
        /** @var \KL\EditorManager\Entity\SpecialCharacterGroup $category */
        $category = $this->em()->create('KL\EditorManager:SpecialCharacterGroup');
        $category->save();

        $masterTitle = $category->getMasterPhrase();

        $masterTitle->phrase_text = $categoryName;
        $masterTitle->save();

        foreach ($enabled as $key) {
            /** @var \KL\EditorManager\Entity\SpecialCharacter $character */
            $character = $this->em()->create('KL\EditorManager:SpecialCharacter');
            $character->code = $codes[$key];
            $character->group_id = $category->group_id;
            $character->save();

            $masterTitle = $character->getMasterPhrase();
            $masterTitle->phrase_text = $names[$key];
            $masterTitle->save();
        }

        return $this->redirect($this->buildLink('em/special-chars'));
    }

    public function actionExport()
    {
        if ($this->isPost()) {
            $categoryId = $this->filter('category', 'uint');

            $category = $this->em()->find('KL\EditorManager:SpecialCharacterGroup', $categoryId);
            $icons = $this->finder('KL\EditorManager:SpecialCharacter')->where('group_id', '=',
                $categoryId)->order('display_order')->fetch();

            $xmlIcons = [];
            foreach ($icons as $icon) {
                $xmlIcons[] = [
                    'title' => $icon->title,
                    'code' => $icon->code
                ];
            }

            $category = [
                'title' => $category->title,
                'characters' => $xmlIcons
            ];

            $xml = new SimpleXMLElement('<category/>');

            $this->setResponseType('xml');
            $this->arrayToXML($category, $xml);

            $viewParams = [
                'xml' => $xml,
                'title' => $category->title
            ];
            return $this->view('KL\EditorManager:SpecialCharacters\XML', '', $viewParams);

        } else {
            $categories = \XF::finder('KL\EditorManager:SpecialCharacterGroup')->order('display_order')->fetch();
            $viewParams = [
                'categories' => $categories
            ];

            return $this->view('KL\EditorManager:SpecialCharacters\Export', 'kl_em_special_chars_export', $viewParams);
        }
    }

    protected function arrayToXML($data, &$xml_data)
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item' . $key; //dealing with <0/>..<n/> issues
            }
            if (is_array($value)) {
                $subnode = $xml_data->addChild($key);
                $this->arrayToXML($value, $subnode);
            } else {
                $xml_data->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }
}