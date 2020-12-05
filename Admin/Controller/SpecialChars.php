<?php /** @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection */

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Admin\Controller;

use Exception;
use KL\EditorManager\Entity\SpecialCharacter;
use KL\EditorManager\Entity\SpecialCharacterGroup;
use SimpleXMLElement;
use XF;
use XF\Admin\Controller\AbstractController;
use XF\ControllerPlugin\Delete;
use XF\ControllerPlugin\Toggle;
use XF\Entity\Smilie;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\Error;
use XF\Mvc\Reply\Message;
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
    public function actionIndex(ParameterBag $params) : AbstractReply
    {
        $viewParams = [
            'specialChars' => XF::finder('KL\EditorManager:SpecialCharacterGroup')->order('display_order')->fetch()
        ];

        return $this->view('KL\EditorManager:SpecialChars\List', 'kl_em_special_chars_list', $viewParams);
    }

    /**
     * @return View
     */
    public function actionAdd() : AbstractReply
    {
        /** @noinspection PhpParamsInspection */
        return $this->actionAddEdit($this->em()->create('KL\EditorManager:SpecialCharacterGroup'));
    }

    /**
     * @param ParameterBag $params
     * @return View
     * @throws XF\Mvc\Reply\Exception
     */
    public function actionEdit(ParameterBag $params) : AbstractReply
    {
        $group = $this->assertGroupExists($params['group_id']);

        return $this->actionAddEdit($group);
    }

    /**
     * @param SpecialCharacterGroup $group
     * @return View
     */
    protected function actionAddEdit(SpecialCharacterGroup $group) : AbstractReply
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
    public function actionSave(ParameterBag $params) : AbstractReply
    {
        if ($params['group_id']) {
            /** @var SpecialCharacterGroup $group */
            $group = $this->em()->find('KL\EditorManager:SpecialCharacterGroup', $params['group_id']);
        } else {
            /** @var SpecialCharacterGroup $group */
            $group = $this->em()->create('KL\EditorManager:SpecialCharacterGroup');
        }

        $this->groupSaveProcess($group)->run();

        return $this->redirect($this->buildLink('em/special-chars'));
    }

    /**
     * @param SpecialCharacterGroup $group
     * @return FormAction
     */
    protected function groupSaveProcess(SpecialCharacterGroup $group) : FormAction
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
                $form->logError(XF::phrase('please_enter_valid_title'), 'title');
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
     * @return Message
     */
    public function actionToggle() : AbstractReply
    {
        /** @var Toggle $plugin */
        $plugin = $this->plugin('XF:Toggle');
        return $plugin->actionToggle('KL\EditorManager:SpecialCharacterGroup');
    }

    /**
     * @return Redirect|View
     */
    public function actionSort() : AbstractReply
    {
        $groups = $this->finder('KL\EditorManager:SpecialCharacterGroup')->order('display_order')->fetch();

        if ($this->isPost()) {
            $lastOrder = 0;
            foreach (json_decode($this->filter('groups', 'string')) as $groupValue) {
                $lastOrder += 10;

                /** @var Smilie $smilie */
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
     * @return Redirect|View
     * @throws XF\Mvc\Reply\Exception
     */
    public function actionDelete(ParameterBag $params) : AbstractReply
    {
        $group = $this->assertGroupExists($params['group_id']);

        /** @var Delete $plugin */
        $plugin = $this->plugin('XF:Delete');
        return $plugin->actionDelete(
            $group,
            $this->buildLink('em/special-chars/delete', $group),
            $this->buildLink('em/special-chars/edit', $group),
            $this->buildLink('em/special-chars'),
            $group->title
        );
    }


    /**
     * @param ParameterBag $params
     * @return View
     * @throws XF\Mvc\Reply\Exception
     */
    public function actionCharacter(ParameterBag $params) : AbstractReply
    {
        $group = $this->assertGroupExists($params['group_id']);

        $viewParams = [
            'group' => $group,
            'specialChars' => XF::finder('KL\EditorManager:SpecialCharacter')->where('group_id', '=',
                $params['group_id'])->order('display_order')->fetch()
        ];

        return $this->view('KL\EditorManager:SpecialChars\Character\List', 'kl_em_special_chars_character_list',
            $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return View
     */
    public function actionCharacterAdd(ParameterBag $params) : AbstractReply
    {
        /** @var SpecialCharacter $character */
        $character = $this->em()->create('KL\EditorManager:SpecialCharacter');
        $character->group_id = $params['group_id'];

        return $this->characterAddEdit($character);
    }

    /**
     * @param ParameterBag $params
     * @return View
     * @throws XF\Mvc\Reply\Exception
     */
    public function actionCharacterEdit(ParameterBag $params) : AbstractReply
    {
        $character = $this->assertCharacterExists($params['character_id']);

        return $this->characterAddEdit($character);
    }

    /**
     * @param SpecialCharacter $character
     * @return View
     */
    protected function characterAddEdit(SpecialCharacter $character) : AbstractReply
    {
        $viewParams = [
            'groups' => XF::finder('KL\EditorManager:SpecialCharacterGroup')->order('display_order')->fetch(),
            'character' => $character
        ];

        return $this->view('KL\EditorManager:SpecialChars\Character\Edit', 'kl_em_special_chars_character_edit',
            $viewParams);
    }

    /**
     * @param ParameterBag $params
     * @return Redirect
     * @throws PrintableException
     * @throws XF\Mvc\Reply\Exception
     */
    public function actionCharacterSave(ParameterBag $params) : AbstractReply
    {
        if ($params['character_id']) {
            $character = $this->assertCharacterExists($params['character_id']);
        } else {
            /** @var SpecialCharacterGroup $group */
            $character = $this->em()->create('KL\EditorManager:SpecialCharacter');
        }

        $this->characterSaveProcess($character)->run();

        return $this->redirect($this->buildLink('em/special-chars/characters', $character));
    }

    /**
     * @param SpecialCharacter $character
     * @return FormAction
     */
    protected function characterSaveProcess(SpecialCharacter $character) : FormAction
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
                $form->logError(XF::phrase('please_enter_valid_title'), 'title');
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
     * @return Message
     */
    public function actionCharacterToggle() : AbstractReply
    {
        /** @var Toggle $plugin */
        $plugin = $this->plugin('XF:Toggle');
        return $plugin->actionToggle('KL\EditorManager:SpecialCharacter');
    }

    /**
     * @param ParameterBag $params
     * @return Redirect|View
     * @throws XF\Mvc\Reply\Exception
     */
    public function actionCharacterSort(ParameterBag $params) : AbstractReply
    {
        $characters = $this->finder('KL\EditorManager:SpecialCharacter')->where('group_id', '=',
            $params['group_id'])->order('display_order')->fetch();

        if ($this->isPost()) {
            $lastOrder = 0;
            foreach (json_decode($this->filter('characters', 'string')) as $characterValue) {
                $lastOrder += 10;

                /** @var Smilie $smilie */
                $character = $characters[$characterValue->id];
                $character->display_order = $lastOrder;
                $character->saveIfChanged();
            }

            return $this->redirect($this->buildLink('em/special-chars/characters', $params));
        } else {
            $viewParams = [
                'group' => $this->assertGroupExists($params['group_id']),
                'characters' => $characters
            ];
            return $this->view('KL\EditorManager:SpecialChars\Character\Sort', 'kl_em_special_chars_character_sort',
                $viewParams);
        }
    }

    /**
     * @param ParameterBag $params
     * @return Redirect|View
     * @throws XF\Mvc\Reply\Exception
     */
    public function actionCharacterDelete(ParameterBag $params) : AbstractReply
    {
        $character = $this->assertCharacterExists($params['character_id']);
        /** @var Delete $plugin */
        $plugin = $this->plugin('XF:Delete');
        return $plugin->actionDelete(
            $character,
            $this->buildLink('em/special-chars/characters/delete', $character),
            $this->buildLink('em/special-chars/characters/edit', $character),
            $this->buildLink('em/special-chars/characters', $params),
            $character->title
        );
    }

    /**
     * @param $groupId
     * @return SpecialCharacterGroup
     * @throws XF\Mvc\Reply\Exception
     */
    protected function assertGroupExists($groupId) : SpecialCharacterGroup
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->assertRecordExists('KL\EditorManager:SpecialCharacterGroup', $groupId);
    }

    /**
     * @param $charId
     * @return SpecialCharacter
     * @throws XF\Mvc\Reply\Exception
     */
    protected function assertCharacterExists($charId) : SpecialCharacter
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->assertRecordExists('KL\EditorManager:SpecialCharacter', $charId);
    }

    /**
     * @return View
     */
    public function actionImport()  : AbstractReply
    {
        $sourceDir = XF::getSourceDirectory();
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

    /**
     * @return Error|View
     */
    public function actionImportForm() : AbstractReply
    {
        $mode = $this->filter('mode', 'str');

        if ($mode == 'upload') {
            $upload = $this->request->getFile('upload', false);
            if (!$upload) {
                return $this->error(XF::phrase('kl_em_please_upload_valid_xml_file'));
            }

            $file = $upload->getTempFile();
        } else {
            $libraryFile = $this->filter('library', 'str');
            $sourceDir = XF::getSourceDirectory();
            $dirSep = DIRECTORY_SEPARATOR;
            $xmlDir = "{$sourceDir}{$dirSep}addons{$dirSep}KL{$dirSep}EditorManager{$dirSep}_specialChars";
            $file = "{$xmlDir}{$dirSep}{$libraryFile}";
        }

        try {
            $xml = Xml::openFile($file);
        } catch (Exception $e) {
            $xml = null;
        }

        $viewParams = [
            'categoryName' => $xml ? $xml->title : '',
            'characters' => $xml ? (array)$xml->characters : []
        ];

        return $this->view('KL\EditorManager:SpecialChars\Import', 'kl_em_special_chars_import_form', $viewParams);
    }

    /**
     * @throws PrintableException
     */
    public function actionImportSave() : AbstractReply
    {
        $enabled = $this->filter('enabled', 'array-str');
        $codes = $this->filter('codes', 'array-str');
        $names = $this->filter('names', 'array-str');

        $categoryName = $this->filter('categoryTitle', 'str');
        /** @var SpecialCharacterGroup $category */
        $category = $this->em()->create('KL\EditorManager:SpecialCharacterGroup');
        $category->save();

        $masterTitle = $category->getMasterPhrase();

        $masterTitle->phrase_text = $categoryName;
        $masterTitle->save();

        foreach ($enabled as $key) {
            /** @var SpecialCharacter $character */
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

    /**
     * @return View
     */
    public function actionExport() : AbstractReply
    {
        if ($this->isPost()) {
            $categoryId = $this->filter('category', 'uint');

            /** @var SpecialCharacterGroup $category */
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
            $categories = XF::finder('KL\EditorManager:SpecialCharacterGroup')->order('display_order')->fetch();
            $viewParams = [
                'categories' => $categories
            ];

            return $this->view('KL\EditorManager:SpecialCharacters\Export', 'kl_em_special_chars_export', $viewParams);
        }
    }

    /**
     * @param array $data
     * @param SimpleXMLElement $xml_data
     * @return void
     * @return void
     */
    protected function arrayToXML(array $data, SimpleXMLElement &$xml_data) : void
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