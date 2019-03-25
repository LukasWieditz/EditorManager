<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Listener;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Manager;
use XF\Mvc\Entity\Structure;

class EntityStructure
{
    /**
     * @param Manager $em
     * @param Structure $structure
     */
    public static function bbCode(Manager $em, Structure &$structure)
    {
        $structure->relations['KLEMBbCode'] = [
            'entity' => 'XF:BbCode',
            'type' => Entity::TO_ONE,
            'conditions' => 'bb_code_id',
            'primary' => true
        ];
    }

    /**
     * @param Manager $em
     * @param Structure $structure
     */
    public static function user(Manager $em, Structure &$structure)
    {
        $structure->columns['kl_em_wordcount_mode'] = [
            'type' => Entity::STR,
            'default' => 'letter',
            'allowedValues' => ['letter', 'word']
        ];
    }
}