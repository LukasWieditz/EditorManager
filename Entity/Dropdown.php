<?php

/*!
 * KL/EditorManager/Entity/Dropdown.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Entity;
    
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * Class Dropdown
 * @package KL\EditorManager\Entity
 *
 * @property integer dropdown_id
 * @property string title
 * @property string icon
 * @property array buttons
 */
class Dropdown extends Entity {
    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure) {
        $structure->table = 'xf_kl_em_dropdowns';
        $structure->shortName = 'KL\EditorManager:Dropdown';
        $structure->primaryKey = 'dropdown_id';
        $structure->columns = [
            'dropdown_id' =>  ['type' => self::UINT, 'unique' => true, 'autoIncrement' => true],
            'title' =>  ['type' => self::STR, 'required' => true, 'maxLength' => 50],
            'icon' => ['type' => self::STR, 'required' => true, 'maxLength' => 50],
            'buttons' => ['type' => self::JSON, 'default' => []]
        ];
        
        return $structure;
    }
}