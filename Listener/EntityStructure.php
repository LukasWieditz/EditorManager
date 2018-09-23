<?php

namespace KL\EditorManager\Listener;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Manager;
use XF\Mvc\Entity\Structure;

class EntityStructure
{
    public static function bbCode(Manager $em, Structure &$structure)
    {
        $structure->relations['KLEMBbCode'] = [
            'entity' => 'XF:BbCode',
            'type' => Entity::TO_ONE,
            'conditions' => 'bb_code_id',
            'primary' => true
        ];
    }

    public static function user(Manager $em, Structure &$structure)
    {
        $structure->columns['kl_em_wordcount_mode'] = [
            'type' => Entity::STR,
            'default' => 'letter',
            'allowedValues' => ['letter', 'word']
        ];
    }
}