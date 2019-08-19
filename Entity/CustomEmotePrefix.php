<?php

namespace KL\EditorManager\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * Class CustomEmotePrefix
 * @package KL\EditorManager\Entity
 *
 * @property integer prefix_id
 * @property integer user_id
 * @property string prefix
 */
class CustomEmotePrefix extends Entity
{
    /**
     * @return bool
     */
    public function canChange()
    {
        if ($this->user_id != \XF::visitor()->user_id) {
            return false;
        }

        if (\XF::visitor()->hasPermission('klEM', 'changeEmotePrefix')) {
            return true;
        }

        return false;
    }

    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_kl_em_custom_emote_prefix';
        $structure->shortName = 'KL\EditorManager:CustomEmotePrefix';
        $structure->primaryKey = 'prefix_id';
        $structure->columns = [
            'prefix_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'prefix' => ['type' => self::STR, 'maxLength' => 65,
                'unique' => 'kl_em_emote_prefix_already_taken']
        ];

        $structure->relations = [
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => true
            ],
            'Emotes' => [
                'entity' => 'KL\EditorManager:CustomEmote',
                'type' => self::TO_MANY,
                'conditions' => 'prefix_id',
                'primary' => true
            ],
        ];

        return $structure;
    }
}