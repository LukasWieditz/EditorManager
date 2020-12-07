<?php

namespace KL\EditorManager\XF\Entity;

use XF\Mvc\Entity\Structure;

/**
 * Class UserOption
 * @package KL\EditorManager\XF\Entity
 *
 * COLUMNS
 * @property string kl_em_wordcount_mode
 * @property array kl_em_template_cache
 */
class UserOption extends XFCP_UserOption
{
    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->columns += [
            'kl_em_wordcount_mode' => ['type' => self::STR, 'default' => 'letter', 'allowedValues' => ['letter', 'word']],
            'kl_em_template_cache' => ['type' => self::JSON_ARRAY, 'default' => [], 'nullable' => true]
        ];

        return $structure;
    }
}
