<?php

/*!
 * KL/EditorManager/Entity/Template.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * Class Template
 * @package KL\EditorManager\Entity
 *
 * @property integer template_id
 * @property string title
 * @property string content
 * @property integer user_id
 * @property integer display_order
 * @property boolean active
 * @property mixed extra_data
 */
class Template extends Entity
{
    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
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
            'user_criteria' => ['type' => self::SERIALIZED_ARRAY, 'default' => []],
        ];

        return $structure;
    }
}