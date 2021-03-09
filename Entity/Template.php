<?php

/*!
 * KL/EditorManager/Entity/Template.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Entity;

use KL\EditorManager\EditorConfig;
use KL\EditorManager\Repository\Template as TemplateRepo;
use XF;
use XF\Entity\User;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\Mvc\Entity\Structure;

/**
 * Class Template
 * @package KL\EditorManager\Entity
 *
 * COLUMNS
 * @property integer template_id
 * @property string title
 * @property string content
 * @property integer user_id
 * @property integer display_order
 * @property boolean active
 * @property array extra_data
 * @property array user_criteria
 * @property array page_criteria
 *
 * GETTERS
 * @property array editor_values
 *
 * RELATIONS
 * @property User User
 */
class Template extends Entity
{
    /**
     * @return TemplateRepo|Repository
     */
    protected function getTemplateRepo(): TemplateRepo
    {
        return $this->repository('KL\EditorManager:Template');
    }

    /**
     * @throws XF\PrintableException
     */
    protected function _postSave(): void
    {
        if ($this->user_id) {
            $repo = $this->getTemplateRepo();
            $repo->rebuildUserTemplateCache($this->User);
        } else {
            $editorConfig = EditorConfig::getInstance();
            $editorConfig->cacheDelete('publicTemplates');
        }
    }

    /**
     * @return array
     */
    public function getEditorValues(): array
    {
        $bbCode = XF::app()->bbCode();
        return [
            'title' => $this->title,
            'content' => $bbCode->render($this->content, 'editorHtml', '', null)
        ];
    }

    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_kl_em_templates';
        $structure->shortName = 'KL\EditorManager:Template';
        $structure->primaryKey = 'template_id';
        $structure->columns = [
            'template_id' => ['type' => self::UINT, 'unique' => 'true', 'autoIncrement' => true],
            'title' => ['type' => self::STR, 'required' => true],
            'content' => ['type' => self::STR, 'required' => true],
            'user_id' => ['type' => self::UINT, 'default' => 0],
            'display_order' => ['type' => self::UINT, 'default' => 10],
            'active' => ['type' => self::BOOL, 'default' => 1],
            'extra_data' => ['type' => self::JSON, 'default' => '[]'],
            'user_criteria' => ['type' => self::JSON, 'default' => []],
            'page_criteria' => ['type' => self::JSON, 'default' => []],
        ];

        $structure->getters = [
            'editor_values' => true
        ];

        $structure->relations = [
            'User' => [
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'entity' => 'XF:User',
                'primary' => true
            ]
        ];

        return $structure;
    }
}