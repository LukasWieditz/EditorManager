<?php

namespace KL\EditorManager\XF\Entity;

use XF\Mvc\Entity\Structure;

/**
 * Class Smilie
 * @package KL\EditorManager\XF\Entity
 *
 * @property array kl_em_user_criteria
 * @property bool kl_em_active
 */
class Smilie extends XFCP_Smilie
{
    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->columns += [
            'kl_em_user_criteria' => ['type' => self::JSON, 'default' => []],
            'kl_em_active' => ['type' => self::BOOL, 'default' => 1]
        ];

        return $structure;
    }
}
