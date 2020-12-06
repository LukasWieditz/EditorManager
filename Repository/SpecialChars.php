<?php

/*!
 * KL/EditorManager/Admin/Controller/Fonts.php
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2017 Lukas Wieditz
 */

namespace KL\EditorManager\Repository;

use KL\EditorManager\Entity\SpecialCharacter;
use XF;
use XF\Mvc\Entity\ArrayCollection;
use XF\Mvc\Entity\Repository;

/**
 * Class SpecialChars
 * @package KL\EditorManager\Repository
 */
class SpecialChars extends Repository
{
    /**
     * @param $string
     * @param array $options
     * @return array
     */
    public function getMatchingCharactersByString($string, array $options = [])
    {
        $options = array_replace([
            'includeEmoji' => true,
            'includeSmilies' => true,
            'limit' => 5
        ], $options);

        $characters = $this->getCharactersForList();

        $results = [];

        foreach ($characters AS $id => $character) {
            if (stripos($character->title, $string) !== false) {
                $results[$id] = $character;
            }
        }

        uasort($results, function ($a, $b) {
            return (strlen($a->title) > strlen($b->title));
        });

        return array_slice($results, 0, $options['limit'], true);
    }

    /**
     * @return XF\Mvc\Entity\AbstractCollection
     */
    public function getCategoriesForList()
    {
        $groups = $this->finder('KL\EditorManager:SpecialCharacterGroup')
            ->where('active', '=', 1)
            ->order('display_order')
            ->fetch();

        $visitor = XF::visitor();

        foreach ($groups as $key => $group) {
            $userCriteria = XF::app()->criteria('XF:User', $group->user_criteria);
            $userCriteria->setMatchOnEmpty(true);

            if (!$userCriteria->isMatched($visitor)) {
                $groups->offsetUnset($key);
            }
        }

        return $groups;
    }

    /**
     * @param array $groupIds
     * @return XF\Mvc\Entity\AbstractCollection
     */
    public function getCharactersForList($groupIds = [])
    {
        $finder = $this->finder('KL\EditorManager:SpecialCharacter');

        if (!empty($groupIds)) {
            $finder->where('group_id', '=', $groupIds);
        }

        return $finder->order('display_order')
            ->fetch();
    }

    /**
     * @return array
     */
    public function getCharacters()
    {
        $groups = $this->finder('KL\EditorManager:SpecialCharacterGroup')->order('display_order')->fetch();


        $visitor = XF::visitor();

        foreach ($groups as $key => $group) {
            $userCriteria = XF::app()->criteria('XF:User', $group->user_criteria);
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