<?php

/*!
 * KL/EditorManager/Entity/SpecialCharacterGroup.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class SpecialCharacterGroup extends Entity
{
    /**
     * @return \XF\Phrase
     */
    public function getTitle()
    {
        return \XF::phrase($this->getPhraseName());
    }

    public function getPhraseName()
    {
        return 'kl_em_sc_group_id.' . $this->group_id;
    }

    public function getMasterPhrase()
    {
        $phrase = $this->MasterTitle;
        if (!$phrase)
        {
            $phrase = $this->_em->create('XF:Phrase');
            $phrase->title = $this->_getDeferredValue(function() { return $this->getPhraseName(); }, 'save');
            $phrase->language_id = 0;
            $phrase->addon_id = '';
        }

        return $phrase;
    }

    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_kl_em_special_chars_groups';
        $structure->shortName = 'KL\EditorManager:SpecialCharacterGroup';
        $structure->primaryKey = 'group_id';
        $structure->columns = [
            'group_id' => ['type' => self::UINT, 'unique' => 'true', 'autoIncrement' => true],
            'display_order' => ['type' => self::UINT, 'default' => 10],
            'active' => ['type' => self::BOOL, 'default' => 1],
            'user_criteria' => ['type' => self::SERIALIZED_ARRAY, 'default' => []],
        ];

        $structure->getters = [
            'title' => true,
        ];
        $structure->relations = [
            'MasterTitle' => [
                'entity' => 'XF:Phrase',
                'type' => self::TO_ONE,
                'conditions' => [
                    ['language_id', '=', 0],
                    ['title', '=', 'kl_em_sc_group_id.', '$group_id']
                ]
            ]
        ];

        return $structure;
    }
}