<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Entity;

use KL\EditorManager\EditorConfig;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * Class BbCode
 * @package KL\EditorManager\Entity
 *
 * @property string bb_code_id
 * @property array user_criteria
 */
class BbCode extends Entity
{
    protected function _postSave(): void
    {
        $editorConfig = EditorConfig::getInstance();
        $editorConfig->cacheDelete('bbCodesSettings');
    }

    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_kl_em_bb_codes';
        $structure->shortName = 'KL\EditorManager:BbCode';
        $structure->primaryKey = 'bb_code_id';

        $structure->columns = [
            'bb_code_id' => [
                'type' => self::STR,
                'maxLength' => 25,
                'required' => 'please_enter_valid_bb_code_tag',
                'unique' => 'bb_code_tags_must_be_unique',
                'match' => 'alphanumeric'
            ],
            'user_criteria' => ['type' => self::JSON, 'default' => []],
            'aliases' => ['type' => self::LIST_COMMA, 'default' => []]
        ];

        $structure->relations = [
            'BBCode' => [
                'entity' => 'XF:BbCode',
                'type' => self::TO_ONE,
                'conditions' => 'bb_code_id',
                'primary' => true
            ]
        ];

        return $structure;
    }
}