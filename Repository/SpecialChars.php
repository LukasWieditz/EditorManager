<?php

namespace KL\EditorManager\Repository;

use KL\EditorManager\Entity\SpecialCharacter;
use XF\Mvc\Entity\Repository;

class SpecialChars extends Repository
{
    public function getCharacters()
    {
        $groups = $this->finder('KL\EditorManager:SpecialCharacterGroup')->order('display_order')->fetch();

        $visitor = \XF::visitor();

        foreach ($groups as $key => $group) {
            $userCriteria = \XF::app()->criteria('XF:User', $group->user_criteria);
            $userCriteria->setMatchOnEmpty(true);

            if (!$userCriteria->isMatched($visitor)) {
                $groups->offsetUnset($key);
            }
        }

        $characters = $this->finder('KL\EditorManager:SpecialCharacter')
            ->where('group_id', '=', $groups->keys())
            ->order('display_order')
            ->fetch()
            ->groupBy('group_id');

        $characterMap = [];

        foreach ($groups as $groupId => $group) {
            if (empty($characters[$groupId])) {
                continue;
            }

            $groupMap = [];

            foreach ($characters[$groupId] as $character) {
                /** @var SpecialCharacter $character */
                $groupMap[] = [
                    'char' => $character->code,
                    'desc' => $character->title->render()
                ];
            }

            $characterMap[] = [
                'title' => $group->title->render(),
                'list' => $groupMap
            ];
        }

        return $characterMap;
    }
}