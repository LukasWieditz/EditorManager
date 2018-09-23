<?php

/*!
 * KL/EditorManager/Entity/Font.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * Class GoogleFont
 * @package KL\EditorManager\Entity
 *
 * @property integer font_id
 * @property string category
 * @property array variants
 * @property array subsets
 * @property boolean active
 */
class GoogleFont extends Entity
{
    public function getFontIdEscaped() {
        return strtr($this->font_id, [' ' => '+']);
    }

    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_kl_em_google_fonts';
        $structure->shortName = 'KL\EditorManager:GoogleFont';
        $structure->primaryKey = 'font_id';
        $structure->columns = [
            'font_id' => ['type' => self::STR, 'unique' => 'kl_em_specific_font_already_exists', 'required' => true],
            'category' => ['type' => self::STR, 'required' => true],
            'variants' => ['type' => self::JSON, 'default' => []],
            'subsets' => ['type' => self::JSON, 'default' => []],
            'active' => ['type' => self::BOOL, 'default' => 1]
        ];

        $structure->getters = [
            'font_id_escaped' => true
        ];

        return $structure;
    }
}