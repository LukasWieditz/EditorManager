<?php

/*!
 * KL/EditorManager/Entity/Font.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Entity;

use KL\EditorManager\EditorConfig;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * Class Font
 * @package KL\EditorManager\Entity
 *
 * @property integer font_id
 * @property string title
 * @property string type
 * @property string family
 * @property integer display_order
 * @property boolean active
 * @property array extra_data
 */
class Font extends Entity
{
    /**
     *
     */
    protected function _postSave(): void
    {
        $editorConfig = EditorConfig::getInstance();
        $editorConfig->cacheDelete('fonts');
    }

    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_kl_em_fonts';
        $structure->shortName = 'KL\EditorManager:Font';
        $structure->primaryKey = 'font_id';
        $structure->columns = [
            'font_id' => ['type' => self::STR, 'unique' => 'kl_em_specific_font_already_exists', 'required' => true],
            'title' => ['type' => self::STR, 'unique' => 'kl_em_specific_font_already_exists', 'required' => true],
            'type' => ['type' => self::STR, 'required' => true],
            'family' => ['type' => self::STR, 'required' => true],
            'display_order' => ['type' => self::UINT, 'default' => 10],
            'active' => ['type' => self::BOOL, 'default' => 1],
            'extra_data' => ['type' => self::JSON, 'default' => []]
        ];

        return $structure;
    }
}