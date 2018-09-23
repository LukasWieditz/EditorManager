<?php

/*!
 * KL/EditorManager/Entity/Option.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * Class Option
 * @package KL\EditorManager\Entity
 *
 * @property integer option_id
 * @property mixed value
 */
class Option extends Entity
{
    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_kl_em_options';
        $structure->shortName = 'KL\EditorManager:Option';
        $structure->primaryKey = 'option_id';
        $structure->columns = [
            'option_id' => ['type' => self::STR, 'unique' => 'true'],
            'value' => ['type' => self::JSON]
        ];

        return $structure;
    }
}