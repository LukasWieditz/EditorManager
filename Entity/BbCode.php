<?php

namespace KL\EditorManager\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * Class BbCode
 * @package KL\EditorManager\Entity
 *
 * @property string bb_code_id
 * @property array user_criteria
 */
class BbCode extends Entity {

    protected function verifyUserCriteria(&$criteria)
    {
        $userCriteria = $this->app()->criteria('XF:User', $criteria);
        $criteria = $userCriteria->getCriteria();
        return true;
    }

    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure) {
        $structure->table = 'xf_kl_em_bb_codes';
        $structure->shortName = 'KL\EditorManager:BbCode';
        $structure->primaryKey = 'bb_code_id';

        $structure->columns = [
            'bb_code_id' => ['type' => self::STR, 'maxLength' => 25,
                'required' => 'please_enter_valid_bb_code_tag',
                'unique' => 'bb_code_tags_must_be_unique',
                'match' => 'alphanumeric'
            ],
            'user_criteria' => ['type' => self::SERIALIZED_ARRAY, 'default' => []],
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