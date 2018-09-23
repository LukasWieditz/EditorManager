<?php

/*!
 * KL/EditorManager/Entity/SpecialCharacter.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\Phrase;

/**
 * Class SpecialCharacter
 * @package KL\EditorManager\Entity
 *
 * @property Phrase title
 */
class SpecialCharacter extends Entity
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
        return 'kl_em_sc_char_id.' . $this->character_id;
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
        $structure->table = 'xf_kl_em_special_chars';
        $structure->shortName = 'KL\EditorManager:SpecialCharacter';
        $structure->primaryKey = 'character_id';
        $structure->columns = [
            'character_id' => ['type' => self::UINT, 'unique' => 'true', 'autoIncrement' => true],
            'group_id' => ['type' => self::UINT, 'required' => true],
            'display_order' => ['type' => self::UINT, 'default' => 10],
            'active' => ['type' => self::BOOL, 'default' => 1],
            'code' => ['type' => self::STR, 'maxLength' => 25],
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
                    ['title', '=', 'kl_em_sc_char_id.', '$character_id']
                ]
            ]
        ];

        return $structure;
    }
}