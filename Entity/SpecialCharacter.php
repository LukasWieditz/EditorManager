<?php

/*!
 * KL/EditorManager/Entity/SpecialCharacter.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 */

namespace KL\EditorManager\Entity;

use XF;
use XF\Entity\Phrase as PhraseEntity;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\Phrase;

/**
 * Class SpecialCharacter
 * @package KL\EditorManager\Entity
 *
 * COLUMNS
 * @property integer character_id
 * @property integer group_id
 * @property integer display_order
 * @property boolean active
 * @property string code
 *
 * GETTERS
 * @property Phrase title
 *
 * RELATIONS
 * @property PhraseEntity MasterTitle
 */
class SpecialCharacter extends Entity
{
    /**
     * @return Phrase
     */
    public function getTitle(): Phrase
    {
        return XF::phrase($this->getPhraseName());
    }

    /**
     * @return string
     */
    public function getPhraseName(): string
    {
        return 'kl_em_sc_char_id.' . $this->character_id;
    }

    /**
     * @return PhraseEntity
     */
    public function getMasterPhrase(): PhraseEntity
    {
        $phrase = $this->MasterTitle;
        if (!$phrase) {
            /** @var PhraseEntity $phrase */
            $phrase = $this->_em->create('XF:Phrase');
            $phrase->title = $this->_getDeferredValue(function () {
                return $this->getPhraseName();
            }, 'save');
            $phrase->language_id = 0;
            $phrase->addon_id = '';
        }

        return $phrase;
    }

    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure): Structure
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